<?php

namespace App\Http\Requests;

use App\Support\ContactOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['name', 'email', 'company', 'country', 'phone', 'description'] as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = trim(strip_tags($this->input($field)));
            }
        }
        if (($clean['country'] ?? null) === 'Bosnia & Herzegovina') {
            $clean['country'] = 'Bosnia and Herzegovina';
        }
        $this->merge($clean);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email:rfc', 'max:255'], 'company' => ['nullable', 'string', 'max:180'], 'country' => ['nullable', 'string', Rule::in(array_keys(config('countries')))],
            'intent' => ['nullable', 'string', Rule::in(array_keys(ContactOptions::Intents))], 'phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9][0-9\s().-]{5,38}[0-9]$/'], 'project_type' => [Rule::requiredIf($this->input('intent') === 'quote'), 'nullable', 'string', Rule::exists('services', 'slug')->where(fn ($query) => $query->where('status', 'published')->whereNull('deleted_at')->where('published_at', '<=', now()))],
            'budget' => ['nullable', Rule::in(array_keys(ContactOptions::budgets(is_string($this->input('country')) ? $this->input('country') : null)))],
            'timeline' => ['nullable', Rule::in(['As soon as possible', '1-3 months', '3-6 months', 'Exploring options'])],
            'description' => ['required', 'string', 'min:15', 'max:10000'], 'privacy_consent' => ['accepted'], 'website' => ['nullable', 'prohibited'],
        ];
    }
}
