<?php
// database/seeders/EmailSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Email;

class EmailSeeder extends Seeder
{
  public function run(): void
  {
    $tenantId = 1;

    Email::create([
      'tenant_id' => $tenantId,
      'subject' => 'Kickoff Call Follow-Up',
      'recipient_email' => 'client@example.com',
      'related_type' => 'project',
      'related_id' => 101,
      'body' => 'Great meeting today—recapping next steps…',
      'date_sent' => now()->subDays(2),
    ]);

    Email::create([
      'tenant_id' => $tenantId,
      'subject' => 'Invoice #2025-001 Sent',
      'recipient_email' => 'billing@example.com',
      'related_type' => 'invoice',
      'related_id' => 2025001,
      'body' => 'Here’s your invoice. Let me know if any questions.',
      'date_sent' => now()->subDay(),
    ]);
  }
}
