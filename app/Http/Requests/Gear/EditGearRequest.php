<?php

namespace App\Http\Requests\Gear;

use Illuminate\Foundation\Http\FormRequest;

class EditGearRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('gear'));
    }
}
