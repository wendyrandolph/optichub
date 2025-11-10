<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   * This defines the exact structure returned to API consumers.
   *
   * @param Request $request
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'email' => $this->email,
      'phone' => $this->phone,
      'source' => $this->source,
      'status' => $this->status,
      'notes' => $this->notes,

      // Format dates consistently for API clients
      'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
      'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,

      // If you had relationships, you'd include them here:
      // 'activities' => LeadActivityResource::collection($this->whenLoaded('activities')),
    ];
  }
}
