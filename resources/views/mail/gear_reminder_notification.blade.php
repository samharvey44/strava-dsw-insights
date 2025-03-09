<x-mail::message>
Your reminder '{{ $reminder->name }}' for Gear item '{{ $reminder->gear->name }}'
has been triggered by your Strava activity '{{ $stravaActivity->name }}',
as it was the {{ new NumberFormatter('en-GB', NumberFormatter::ORDINAL)->format($reminder->trigger_after_number_of_activities) }}
activity since it was last triggered.

<x-mail::button url="{{ route('home') }}">
View Activities
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
