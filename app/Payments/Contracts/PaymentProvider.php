<?php

namespace App\Payments\Contracts;

interface PaymentProvider
{
  public static function id(): string;                // 'stripe', 'authorizenet', etc.
  public static function displayName(): string;       // 'Stripe', 'Authorize.Net'
  public static function credentialRules(): array;    // for validating tenant settings

  public function __construct(array $credentials, array $options = []);

  // Core payment surface
  public function createPaymentIntent(array $payload): array;  // returns ['id','status','clientSecret'|null,'redirectUrl'|null]
  public function capture(string $paymentIntentId, ?int $amount = null): array;
  public function refund(string $paymentIntentId, ?int $amount = null): array;

  // Optional helpers
  public function createCustomer(array $data): array; // ['customerId'=>...]
  public function webhookHandlers(): array;           // ['event.name' => fn($event) => void]
}
