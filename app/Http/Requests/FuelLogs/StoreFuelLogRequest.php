<?php

namespace App\Http\Requests\FuelLogs;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('fuel.manage') ?? false;
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
            'quantity_liters' => ['required', 'regex:/\A\d+(?:\.\d{1,3})?\z/', 'numeric', 'gt:0', 'max:9999999.999'],
            'total_cost' => ['required', 'regex:/\A\d+(?:\.\d{1,2})?\z/', 'numeric', 'min:0', 'max:9999999999.99'],
            'odometer' => ['required', 'regex:/\A\d+\z/', 'integer', 'min:0'],
            'refueled_at' => ['required', 'regex:/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}\z/', 'date_format:Y-m-d\TH:i', 'before_or_equal:now'],
            'station' => ['nullable', 'string', 'max:255', 'regex:/\A[^\x00-\x1F\x7F\r\n]+\z/u'],
            'receipt_reference' => ['nullable', 'string', 'max:255', 'regex:/\A[^\x00-\x1F\x7F\r\n]+\z/u'],
            'notes' => ['nullable', 'string', 'max:5000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ];
    }
}
