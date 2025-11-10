<?php

namespace App\Services;

use App\Models\Email;
use App\Models\Lead;
use App\Models\Client;

class EmailAssociation
{
  public static function attachToLeadOrClient(Email $email, int $tenantId): void
  {
    $from = strtolower((string) $email->from_email);
    if (!$from) return;

    $lead = Lead::where('tenant_id', $tenantId)->whereRaw('LOWER(email) = ?', [$from])->first();
    if ($lead) {
      $email->update(['related_type' => 'lead', 'related_id' => $lead->id]);
      return;
    }

    $client = Client::where('tenant_id', $tenantId)->whereRaw('LOWER(email) = ?', [$from])->first();
    if ($client) {
      $email->update(['related_type' => 'client', 'related_id' => $client->id]);
    }
  }
}
