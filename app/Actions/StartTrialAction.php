<?php

namespace App\Actions\Trial;

use App\Models\Tenant;
use App\Models\User;
use App\Models\OnboardingToken;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail; // Use Laravel's Mail Facade
use InvalidArgumentException;
use Throwable;

class StartTrialAction
{
  /**
   * Handles the complex, multi-step process of starting a new organization trial.
   * This replaces the static method in the procedural TrialModel.
   */
  public function execute(array $opts): array
  {
    $plan          = $opts['plan'] ?? 'starter';
    $email         = trim((string)($opts['email'] ?? ''));
    $companyName   = trim((string)($opts['companyName'] ?? ''));
    $trialDays     = (int)($opts['trialDays'] ?? 14);
    $tokenHours    = (int)($opts['tokenHours'] ?? 48);

    // Input Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException('A valid email address is required.');
    }
    if ($companyName === '') {
      $companyName = 'Trial — ' . now()->toDateTimeString();
    }

    Log::info('[trial] begin StartTrialAction', ['email' => $email]);

    try {
      // Use Laravel's DB::transaction for automatic commit and rollback
      return DB::transaction(function () use ($plan, $email, $companyName, $trialDays, $tokenHours) {

        // 1) Create Tenant (Organization)
        $tenant = Tenant::create([
          'name'                => $companyName,
          'type'                => 'saas_tenant',
          'trial_status'        => 'trialing',
          'trial_started_at'    => now(),
          'trial_ends_at'       => now()->addDays($trialDays),
          'subscription_status' => 'trialing',
        ]);

        // 2) Create Owner User
        $baseUsername = explode('@', $email)[0] ?: 'owner';
        // Note: In a real app, User::ensureUniqueUsername would be a method on the User model
        $username = User::ensureUniqueUsername($baseUsername);

        $tempPassword = bin2hex(random_bytes(12));

        $user = User::create([
          'tenant_id'  => $tenant->id,
          'username'   => $username,
          'email'      => $email,
          'password'   => bcrypt($tempPassword),
          'is_admin'   => true,
          'user_role'  => 'owner',
        ]);

        // 3) Start Trial Subscription
        // Delegate complex subscription logic to a dedicated service
        (new SubscriptionService())->startTrial($tenant, $plan, $trialDays);

        // 4) Create Onboarding Token
        $token = OnboardingToken::createTokenForUser($user->id, $tokenHours);

        // 5) Email magic link
        $link = "https://portal.causeywebsolutions.com/onboarding/set-password?token={$token}";
        $trialEnds = now()->addDays($trialDays)->format('M j, Y');

        // In a real app, you would use a Mailable class here:
        // Mail::to($user)->send(new TrialWelcomeMail($link, $trialEnds, $tokenHours));

        // Log the action instead of sending a real email
        Log::info('[trial] Magic link generated and email mocked.', [
          'to' => $email,
          'link' => $link,
          'trial_ends' => $trialEnds,
        ]);

        return [
          'tenantId' => $tenant->id,
          'userId'   => $user->id,
          'username' => $username,
          'token'    => $token,
        ];
      });
    } catch (Throwable $e) {
      Log::error('[trial] FAILED: ' . $e->getMessage());
      throw $e;
    }
  }
}
