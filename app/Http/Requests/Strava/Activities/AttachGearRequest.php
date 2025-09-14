<?php

namespace App\Http\Requests\Strava\Activities;

use Illuminate\Foundation\Http\FormRequest;

class AttachGearRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return $this->user()->can('gear', [
            $this->route('stravaActivity'),
            $this->route('gear'),
        ]);
    }
}
