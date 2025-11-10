<?php

/** @var string $token */ ?>
<div class="login-main">
  <div class="login-card">
    <h2 class="login-heading">Set your password</h2>
    <p class="muted">Create a password to finish activating your account.</p>

    <form method="post" action="/onboarding/set-password">

      <input class="input-field" type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="input">
        <label class="input-label">New password</label>
        <input class="input-field" type="password" name="password" required minlength="8" />
      </div>
      <div class="input">
        <label class="input-label">Confirm password</label>
        <input class="input-field" type="password" name="password2" required minlength="8" />
      </div>
      <div class="action">
        <button class="action-button" type="submit">Save & Continue</button>
      </div>
    </form>