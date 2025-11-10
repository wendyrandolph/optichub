<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreLeadActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is simple: the user must be logged in. Policy checks (e.g., if they can access the lead's tenant)
     * should happen in the controller.
     */
    public function authorize(): bool
    {
        // 1. CSRF Verification (Handled automatically by Laravel's FormRequest)
        // 2. Authorization check
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Corresponds to $_POST['activity_type']
            'activity_type' => ['required', 'string', 'max:50'],

            // Corresponds to $_POST['description']
            'description' => ['required', 'string', 'max:1000'],

            // Note: The 'leadId' from the route URI is not validated here 
            // as Route Model Binding handles its existence and fetching in the controller.
        ];
    }

    /**
     * Prepare the data for validation.
     * This replaces the old manual trim() calls.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'activity_type' => trim($this->activity_type),
            'description' => trim($this->description),
        ]);
    }

    /**
     * Custom error messages for a better user experience.
     */
    public function messages(): array
    {
        return [
            'activity_type.required' => 'Activity type is required.',
            'description.required' => 'Activity description is required.',
        ];
    }
}
