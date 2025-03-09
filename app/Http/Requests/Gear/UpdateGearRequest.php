<?php

namespace App\Http\Requests\Gear;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGearRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'first_used' => ['nullable', 'date'],
            'decommissioned' => ['nullable', 'date'],
            'image' => ['nullable', 'image:allow_svg', 'max:5000'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('gear'));
    }

    public function messages(): array
    {
        return [
            'image.max' => 'The image must not be greater than 5MB.',
        ];
    }
}
