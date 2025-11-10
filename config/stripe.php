<?php
// Stripe configuration file. Secrets must come from the environment.

// Secret key (server-side only, NEVER expose to JS or commit to VCS)
if (!defined('STRIPE_SECRET_KEY')) {
  define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
}

// Webhook signing secret (from Stripe dashboard → Developers → Webhooks)
if (!defined('STRIPE_WEBHOOK_SECRET')) {
  define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');
}

// Publishable key (safe for client/browser if you ever need Stripe.js)
if (!defined('STRIPE_PUBLISHABLE_KEY')) {
  define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: '');
}

// Default plan code fallback
if (!defined('DEFAULT_PLAN_CODE')) {
  define('DEFAULT_PLAN_CODE', 'starter');
}
