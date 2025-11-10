<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListEventsApiRequest extends FormRequest
{
    // Authorization is already handled by middleware in the controller's __construct, 
    // but you could add a final check here if needed.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Ensure 'since' is present, is an integer, and is non-negative
            'since' => 'nullable|integer|min:0',

            // Ensure 'limit' is an integer between 1 and 200 (as per your original logic)
            'limit' => 'nullable|integer|min:1|max:200',
        ];
    }

    /**
     * Prepare the data for validation, setting defaults and normalizing types.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            // Apply default limit if none is provided
            'limit' => $this->limit ?? 50,

            // Normalize 'since' to an integer, defaulting to 0
            'since' => (int)($this->since ?? 0),
        ]);
    }
}
