<?php
$apiKeyContext = 'onboarding';
$generateAction = '/onboarding/api-key';
$createAnotherAction = '/onboarding/api-key';
$showEndpoint = '/settings/api/show';
?>

<section class="section section--white">
  <div class="container">
    <p class="eyebrow">Onboarding</p>
    <h2 class="h3">Your API Key</h2>

    <?php include __DIR__ . '/../partials/api_keys_panel.php'; ?>

    <p style="margin-top:16px">
      <a class="btn btn--ghost" href="/dashboard">Continue</a>
    </p>
  </div>
</section>
