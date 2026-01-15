<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    /**
     * Scope activities for a given contact_id.
     */
    public function scopeForContact($query, $contactId)
    {
        if (! $contactId) {
            return $query->whereRaw('1 = 0'); // no results if no contact
        }

        // Adjust this to however you're tagging contact on activities.
        // Common patterns:
        // - in properties JSON (properties->contact_id)
        // - or as causer (causer_type/contact)
        return $query->where(function ($q) use ($contactId) {
            $q->where('properties->contact_id', $contactId)
                ->orWhere(function ($q2) use ($contactId) {
                    $q2->where('causer_type', \App\Models\Contact::class ?? \App\Models\Client::class)
                        ->where('causer_id', $contactId);
                });
        });
    }
}
