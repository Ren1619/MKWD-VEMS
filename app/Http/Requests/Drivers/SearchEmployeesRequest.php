<?php

namespace App\Http\Requests\Drivers;

use Illuminate\Foundation\Http\FormRequest;

class SearchEmployeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('drivers.manage') ?? false;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return ['search' => ['required', 'string', 'min:2', 'max:100']];
    }
}
