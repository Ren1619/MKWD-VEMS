<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteMaintenanceWorkOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('maintenance.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'completed_at' => ['required', 'date', 'before_or_equal:now'],
            'completion_mileage' => ['required', 'integer', 'min:0'],
            'work_performed' => ['required', 'string', 'max:10000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }
}
