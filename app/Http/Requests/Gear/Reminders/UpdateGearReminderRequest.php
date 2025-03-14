<?php

namespace App\Http\Requests\Gear\Reminders;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGearReminderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'trigger_after_number_of_activities' => ['required', 'integer', 'min:1', 'max:100'],
            'current_number_of_activities' => ['required', 'integer', 'min:0', 'max:100', 'lt:trigger_after_number_of_activities'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('gearReminder'));
    }

    public function messages(): array
    {
        return [
            'current_number_of_activities.lt' => 'The current number of activities must be less than the trigger after number of activities.',
        ];
    }
}
