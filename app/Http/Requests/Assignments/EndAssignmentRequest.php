<?php

namespace App\Http\Requests\Assignments;

use Illuminate\Foundation\Http\FormRequest;

class EndAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assignments.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['end_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today']];
    }
}
