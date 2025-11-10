<?php

namespace App\Models;

use App\Models\Organization;
use App\Models\User;
use App\Models\OnboardingToken;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;


class TrialModel extends Model
{
  public static function startTrial(array $opts): array
  {
    global $pdo;
    if (!$pdo instanceof PDO) throw new RuntimeException('PDO not initialized');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $plan        = $opts['plan']        ?? 'starter';
    $email       = trim((string)($opts['email'] ?? ''));
    $companyName = trim((string)($opts['companyName'] ?? ''));
    $trialDays   = (int)($opts['trialDays']  ?? 14);
    $tokenHours  = (int)($opts['tokenHours'] ?? 48);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException('Valid email required');
    }
    if ($companyName === '') $companyName = 'Trial — ' . gmdate('Y-m-d H:i');

    error_log('[trial] begin startTrial');

    try {
      $pdo->beginTransaction();

      $org  = new Organization($pdo);
      $user = new User($pdo);
      $sub  = new Subscription($pdo);
      $tok  = new OnboardingToken($pdo);

      $now = gmdate('Y-m-d H:i:s');
      $ends = gmdate('Y-m-d H:i:s', strtotime('+14 days'));

      $org = new Organization($pdo);
      $tenantId = $org->create(['name' => $companyName, 'type' => 'saas_tenant']);
      error_log('[trial] step 1: create organization');


      error_log('[trial] org id=' . $tenantId);

      $tenantId = (int) lastInsertId();


      // 2) Owner user
      error_log('[trial] step 2: create owner user');
      $base     = explode('@', $email)[0] ?: 'owner';
      $base     = preg_replace('/[^a-z0-9_.-]+/i', '-', $base) ?: 'owner';
      $username = method_exists($user, 'ensureUniqueUsername') ? $user->ensureUniqueUsername($base) : $base;

      $tempHash = password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT);
      $userId   = (int)$user->createAdminUser($username, $email, $tempHash, $tenantId);

      // 3) Trial subscription
      error_log('[trial] step 3: start trial subscription');
      $sub->startTrial($tenantId, $plan, $trialDays); // sets status=trialing + current_period_end



      // 4) Onboarding token
      error_log('[trial] step 4: create onboarding token');
      $token = $tok->create($userId, $tokenHours);

      // 5) Email magic link (best-effort)
      @mail(
        $email,
        'Your Optic Hub Trial',
        "Welcome to Optic Hub!\n\nSet your password here (expires in {$tokenHours}h):\n" .
          "https://portal.causeywebsolutions.com/onboarding/set-password?token={$token}\n\n" .
          "You’ll get a reminder before your trial ends on " .
          gmdate('M j, Y', strtotime('+' . $trialDays . ' days')) . "."
      );

      $pdo->commit();
      error_log('[trial] success tenant=' . $tenantId . ' user=' . $userId);

      return [
        'tenantId' => $tenantId,
        'userId'   => $userId,
        'username' => $username,
        'token'    => $token,
      ];
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      error_log('[trial] FAILED: ' . $e->getMessage());
      throw $e;
    }
  }
}
