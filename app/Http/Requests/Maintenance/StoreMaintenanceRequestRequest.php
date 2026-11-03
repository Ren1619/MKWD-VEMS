<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('maintenance.request') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'regex:/\A[1-9]\d*\z/', 'integer', 'exists:vehicles,id'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'critical'])],
            'subject' => ['required', 'string', 'max:255', 'regex:/\A[^\x00-\x1F\x7F\r\n]+\z/u'],
            'description' => ['required', 'string', 'max:5000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ];
    }
}
