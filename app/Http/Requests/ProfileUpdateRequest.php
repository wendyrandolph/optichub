<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // you already gate access with middleware
    }
    /**
     * Validation rules for the tenant/org branding + profile form.
     */
    public function rules(): array
    {
        return [
            // Basic org info
            'name'            => ['required', 'string', 'max:255'],
            'website'         => ['nullable', 'string', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:50'],
            'support_email'   => ['nullable', 'string', 'email', 'max:255'],
            'primary_color'   => ['nullable', 'string', 'max:7'],
            'secondary_color' => ['nullable', 'string', 'max:7'],
            'accent_color'    => ['nullable', 'string', 'max:7'],
            'brand_tagline'   => ['nullable', 'string', 'max:255'],
            'invoice_footer'  => ['nullable', 'string'],
            'logo'            => ['nullable', 'file', 'mimes:png,svg', 'max:1024'],
            'default_uses_phases' => ['nullable', 'boolean'],
            'registered_users_enabled' => ['nullable', 'boolean'],
            'phases'          => ['nullable', 'array', 'max:7'],
            'phases.*'        => ['nullable', 'string', 'max:100'],
            'team_member_colors' => ['nullable', 'array', 'max:20'],
            'team_member_colors.*' => ['nullable', 'string', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            if (! $this->hasFile('logo')) {
                return;
            }

            $file = $this->file('logo');
            if (! $file) {
                return;
            }

            $mime = $file->getMimeType();
            // Only enforce dimensions for raster (png) uploads.
            if ($mime === 'image/png') {
                try {
                    [$width, $height] = getimagesize($file->getRealPath());
                    if ($width < 200 || $height < 200) {
                        $v->errors()->add('logo', 'Logo must be at least 200×200px.');
                    }
                } catch (\Exception $e) {
                    $v->errors()->add('logo', 'Unable to read image dimensions. Please upload a PNG that is at least 200×200px.');
                }
            }
        });
    }
}
