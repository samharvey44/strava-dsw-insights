<?php

namespace App\Http\Requests\Gear\Reminders;

use Illuminate\Foundation\Http\FormRequest;

class DestroyGearReminderRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return $this->user()->can('destroy', $this->route('gearReminder'));
    }
}
