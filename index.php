<?php

/**
 * Legacy front controller shim.
 *
 * The CRM previously bootstrapped from this file. Now that the codebase
 * runs on Laravel, we simply forward to Laravel's public/index.php so
 * anyone still pointing their web root here will automatically hit the
 * framework bootstrap.
 */

require __DIR__ . '/public/index.php';
