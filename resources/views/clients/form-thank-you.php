<!-- views/clients/form-thank-you.php -->
<?php require_once base_path('app/helpers/CSRF.php'); ?>
<div class="form-thank-you">
  <h2>🎉 Thank you!</h2>
  <p>Your submission has been received.</p>
  <p>Redirecting you back to your dashboard...</p>
</div>

<script>
  setTimeout(function() {
    window.location.href = "/client/portal";
  }, 3000); // 3000 ms = 3 seconds
</script>