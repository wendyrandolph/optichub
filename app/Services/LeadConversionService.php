<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class LeadConversionService
{
  /**
   * Converts a Lead model instance into a new Client instance within a transaction.
   *
   * @param \App\Models\Lead $lead
   * @return int The ID of the newly created Client.
   */
  public function convert(Lead $lead): int
  {
    return DB::transaction(function () use ($lead) {

      // 1. Create Client from Lead data
      $client = Client::create([
        'tenant_id' => $lead->tenant_id,
        'organization_name' => $lead->lead_name, // Map lead_name to organization_name
        'email' => $lead->email,
        'phone' => $lead->phone,
        'source' => $lead->source ?? 'Converted Lead',
        'notes' => "Converted from Lead ID {$lead->id}. \n\n" . ($lead->notes ?? ''),
        // Add any other required client fields
      ]);

      // 2. Update Lead status
      $lead->update(['status' => 'client']);

      // 3. Optional: Move/Copy/Relate activities if required (e.g., set client_id on activities)
      // LeadActivity::where('lead_id', $lead->id)->update(['client_id' => $client->id]);

      return $client->id;
    });
  }
}
