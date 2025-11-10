<?php

$ROOT = dirname(__DIR__); // /public_html
$APP  = $ROOT . '/app';

require_once base_path('app/Http/Controllers/MarketingController.php');
require_once base_path('app/Http/Controllers/TrialController.php');

/* ------------------- STRIPE WEBHOOK ------------------- */
$router->post('/stripe/webhook', function () use ($pdo) {
  require_once base_path('app/helpers/StripeHelper.php');
  $payload = @file_get_contents('php://input') ?: '';
  $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

  try {
    $event = StripeHelper::verifyWebhook($payload, $sig);
  } catch (Throwable $e) {
    error_log('[stripe] invalid signature: ' . $e->getMessage());
    http_response_code(400);
    echo 'Invalid signature';
    exit;
  }

  error_log('[stripe] event: ' . $event->type);

  switch ($event->type) {
    case 'checkout.session.completed': {
        $s = $event->data->object;
        $customerEmail = isset($s->customer_details->email) ? (string)$s->customer_details->email : null;

        // 1) Tenant + owner
        $tenantId    = createTenant($pdo, ['name' => $s->customer_details->name ?? 'New Customer', 'email' => $customerEmail]);
        $ownerUserId = createOwnerUser($pdo, $tenantId, $customerEmail);

        // 2) Onboarding token
        $token = bin2hex(random_bytes(32));
        $pdo->prepare("INSERT INTO onboarding_tokens (user_id, token, expires_at, created_at)
                     VALUES (:u,:t, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 48 HOUR), UTC_TIMESTAMP())")
          ->execute([':u' => $ownerUserId, ':t' => $token]);

        $magicUrl = "https://portal.causeywebsolutions.com/onboarding/set-password?token={$token}";

        // 3) Subscription row
        $now = gmdate('Y-m-d H:i:s');
        $planCode = $s->metadata->plan_code ?? DEFAULT_PLAN_CODE;
        $stmt = $pdo->prepare("INSERT INTO subscriptions (tenant_id, stripe_customer_id, stripe_subscription_id, plan_code, status, created_at, updated_at)
                             VALUES (:t,:c,:s,:p,'active',:now,:now)
                             ON DUPLICATE KEY UPDATE plan_code=:p, status='active', updated_at=:now");
        $stmt->execute([':t' => $tenantId, ':c' => $s->customer, ':s' => $s->subscription, ':p' => $planCode, ':now' => $now]);

        // 4) First API key
        require_once base_path('app/helpers/ApiKeyService.php');
        $plain = ApiKeyService::issue($tenantId, 'Website Integration', ['leads:write', 'events:read']);

        // 5) Notify
        error_log("[stripe] onboarding link for {$customerEmail}: {$magicUrl}");
        if ($customerEmail) {
          @mail($customerEmail, "Welcome to Optic Hub", "Thanks for subscribing!\n\nSet your password here: {$magicUrl}\n\nLink expires in 48 hours.");
        }

        http_response_code(200);
        echo 'ok';
        exit;
      }

    case 'customer.subscription.updated':
    case 'customer.subscription.deleted': {
        $sub  = $event->data->object;
        $info = StripeHelper::subscriptionStatus($sub->id);
        $stmt = $pdo->prepare("UPDATE subscriptions
                             SET status=:st, current_period_end=:end, updated_at=:now
                             WHERE stripe_subscription_id=:sid");
        $stmt->execute([
          ':st'  => $info['status'] ?? ($event->type === 'customer.subscription.deleted' ? 'canceled' : $sub->status),
          ':end' => $info['current_period_end'] ?? null,
          ':now' => gmdate('Y-m-d H:i:s'),
          ':sid' => $sub->id
        ]);
        http_response_code(200);
        echo 'ok';
        exit;
      }
  }

  http_response_code(200);
  echo 'ignored';
});

/* ------------------- MARKETING PAGES ------------------- */

// HOME — use your existing BaseController->view() via `page()`
$router->get('/', fn() => (new MarketingController($pdo))->page('marketing/home'));

// Features / Pricing / FAQ / About / Contact
$router->get('/features', fn() => require base_path('resources/views/marketing/features.php'));
$router->get('/pricing',  fn() => require base_path('resources/views/marketing/pricing.php'));
$router->get('/faq',      fn() => require base_path('resources/views/marketing/faq.php'));
$router->get('/about',    fn() => require base_path('resources/views/marketing/about.php'));
$router->get('/contact',  fn() => require base_path('resources/views/marketing/contact.php'));

/* ------------------- TRIAL FLOW ------------------- */

// Remove any duplicate /trial routes first.
$router->get('/trial', fn() => (new MarketingController($pdo))->trial());

// POST /trial/start → no-card trial creation
$router->post('/trial/start', fn() => (new TrialController($pdo))->starttrial());

// Normalize trailing slash
$router->get('/trial/start/', fn() => (header('Location: /trial', true, 301) or exit));

/* ------------------- CHECKOUT & WELCOME ------------------- */

$router->post('/checkout', function () use ($pdo) {
  require_once base_path('app/helpers/StripeHelper.php');
  $planCode = $_POST['plan'] ?? DEFAULT_PLAN_CODE;

  $stmt = $pdo->prepare("SELECT stripe_price_id FROM plans WHERE code = :c LIMIT 1");
  $stmt->execute([':c' => $planCode]);
  $priceId = $stmt->fetchColumn();
  if (!$priceId) {
    http_response_code(400);
    echo "Unknown plan code";
    exit;
  }

  $url = StripeHelper::createSubscriptionCheckout(
    $priceId,
    $planCode,
    'https://portal.causeywebsolutions.com/welcome?session_id={CHECKOUT_SESSION_ID}',
    'https://portal.causeywebsolutions.com/pricing'
  );
  header('Location: ' . $url);
  exit;
});

$router->get('/welcome', function () {
  $sessionId = $_GET['session_id'] ?? null;
  if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');
  echo "<h2>You're in!</h2>
        <p>Thanks for subscribing. We’re finishing your account setup now.</p>"
    . ($sessionId ? "<p><small>Session: " . htmlspecialchars($sessionId) . "</small></p>" : "")
    . "<p><a href='/login'>Log in</a> or go to <a href='/settings/api'>API Keys</a> once you’re signed in.</p>";
  exit;
});
