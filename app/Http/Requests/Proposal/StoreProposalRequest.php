<?php:Store Proposal Validation:app/Http/Requests/Proposal/StoreProposalRequest.php
namespace App\Http\Requests\Proposal;

use Illuminate\Foundation\Http\FormRequest;

class StoreProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Rely on the controller's policy check for 'create' permission
        return true; 
    }

    /**
     * Get the validation rules that apply to the POST request.
     */
    public function rules(): array
    {
        return [
            // Matches the required checks from the original controller
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'exists:projects,id'],
            'client_id' => ['required', 'exists:clients,id'],
            
            // Optional proposal section fields
            'goals' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'investment' => ['nullable', 'string'], // Keep as string for complex price/payment notes
            'timeline' => ['nullable', 'string'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'project_id.required' => 'The Project field is required.',
            'client_id.required' => 'The Client field is required.',
        ];
    }
}