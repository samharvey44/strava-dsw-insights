<div class="modal-dialog" x-data="{
    replaceModalContent: () => {
        axios.get('{{ route('gear.reminders.modal-contents', $gearItem) }}')
            .then(({ data }) => {
                document.getElementById('gear_reminder_modal_{{ $gearItem->id }}').innerHTML = data;
            });
    }
}">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Reminders for '{{ $gearItem->name }}'</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" x-data="{ currentView: 'list' }">
            @session('success')
            <div class="alert alert-success text-center"
                 role="alert"
                 x-init="setTimeout(() => $el.remove(), 2000)"
            >
                {{ session('success') }}
            </div>
            @endsession

            <div x-show="currentView === 'list'">
                <ul class="list-group">
                    @foreach($gearItem->reminders as $reminder)
                        <li class="list-group-item">
                            <div class="d-flex flex-column">
                                <span>
                                    <span class="fw-bolder">
                                        {{ $reminder->name }}
                                    </span>
                                    <i class="bi bi-trash text-danger float-end"
                                       role="button"
                                       @click="() => {
                                            if (confirm('Are you sure you want to delete this reminder?')) {
                                                axios.delete('{{ route('gear.reminders.destroy', ['gear' => $gearItem->id, 'gearReminder' => $reminder->id]) }}')
                                                    .then(replaceModalContent);
                                            }
                                        }"
                                    ></i>
                                    <i class="bi bi-pencil text-primary float-end me-2"
                                       role="button"
                                       @click="() => {
                                           currentView = 'form';

                                           $refs.reminder_form.dataset.gearReminderId = '{{ $reminder->id }}';
                                           $refs.reminder_form_name.value = '{{ $reminder->name }}';
                                           $refs.reminder_form_trigger_after_number_of_activities.value = '{{ $reminder->trigger_after_number_of_activities }}';
                                           $refs.reminder_form_current_number_of_activities.value = '{{ $reminder->current_number_of_activities }}';
                                       }"
                                    ></i>
                                </span>
                                <div class="d-flex align-items-center flex-wrap">
                                    @foreach(range(1, $reminder->trigger_after_number_of_activities) as $triggerNumber)
                                        <span data-bs-toggle="tooltip"
                                              title="{{ $triggerNumber > $reminder->current_number_of_activities ? 'Not triggered yet' : 'Triggered' }}"
                                                            @class([
                                                                'badge rounded-pill m-1',
                                                                'bg-primary' => $triggerNumber <= $reminder->current_number_of_activities,
                                                                'bg-primary-subtle' => $triggerNumber > $reminder->current_number_of_activities,
                                                            ])
                                                        >{{ $triggerNumber }}</span>
                                    @endforeach
                                </div>
                                <small>
                                    Last triggered:
                                    <span @class(['opacity-50' => is_null($reminder->last_triggered)])>
                                                        {{ $reminder->last_triggered?->format('d/m/Y \a\t H:i') ?? 'N/A' }}
                                                    </span>
                                </small>
                            </div>
                        </li>
                    @endforeach

                    <div @class(['d-flex', 'mt-3' => $gearItem->reminders->isNotEmpty()])>
                        <button class="btn btn-primary btn-sm ms-auto"
                                @click="() => {
                                                currentView = 'form';

                                                $refs.reminder_form.dataset.gearReminderId = '';
                                                $refs.reminder_form_name.value = '';
                                                $refs.reminder_form_trigger_after_number_of_activities.value = '';
                                                $refs.reminder_form_current_number_of_activities.value = '0';
                                            }"
                        >
                            <i class="bi bi-plus"></i>
                            Create Reminder
                        </button>
                    </div>
                </ul>
            </div>

            <div x-show="currentView === 'form'">
                <form x-ref="reminder_form" @submit="(e) => {
                        e.preventDefault();

                        const formRoute = $refs.reminder_form.dataset.gearReminderId !== ''
                            ? '{{ route('gear.reminders.update', ['gear' => $gearItem->id, 'gearReminder' =>  ':gearReminder']) }}'.replace(':gearReminder', $refs.reminder_form.dataset.gearReminderId)
                            : '{{ route('gear.reminders.store', $gearItem) }}';

                        const formData = new FormData($refs.reminder_form);

                        if ($refs.reminder_form.dataset.gearReminderId !== '') {
                            formData.append('_method', 'PATCH');
                        }

                        $refs.reminder_form.querySelectorAll('.form-text').forEach((element) => element.classList.remove('d-none'));
                        $refs.reminder_form.querySelectorAll('.is-invalid').forEach((element) => element.classList.remove('is-invalid'));

                        axios({
                            method: 'POST',
                            url: formRoute,
                            data: formData,
                        })
                            .then(replaceModalContent)
                            .catch(({ response: { data } }) => {
                                const errors = data.errors;

                                if (!errors) {
                                    return;
                                }

                                Object.keys(errors).forEach((errorKey) => {
                                    const formInput = $refs[`reminder_form_${errorKey}`];
                                    const errorElement = $refs[`reminder_form_${errorKey}_error`];
                                    const helpElement = $refs[`reminder_form_${errorKey}_help`];

                                    formInput.classList.add('is-invalid');
                                    errorElement.textContent = errors[errorKey][0];
                                    helpElement.classList.add('d-none');
                                });
                            });
                    }"
                >
                    @csrf

                    <div class="mb-3">
                        <label for="reminder_form_name" class="form-label">Reminder Name</label>
                        <input type="text" class="form-control" id="reminder_form_name" name="name" required x-ref="reminder_form_name">
                        <div class="form-text" x-ref="reminder_form_name_help">
                            The name of the reminder.
                        </div>
                        <div class="invalid-feedback" x-ref="reminder_form_name_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="reminder_form_trigger_after_number_of_activities" class="form-label">Trigger After Number of Activities</label>
                        <input type="number" class="form-control" id="reminder_form_trigger_after_number_of_activities" name="trigger_after_number_of_activities" required max="100" x-ref="reminder_form_trigger_after_number_of_activities">
                        <div class="form-text" x-ref="reminder_form_trigger_after_number_of_activities_help">
                            The number of activities that need to be completed with this gear before this reminder is triggered.
                        </div>
                        <div class="invalid-feedback" x-ref="reminder_form_trigger_after_number_of_activities_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="reminder_form_current_number_of_activities" class="form-label">Current Number of Activities</label>
                        <input type="number" class="form-control" id="reminder_form_current_number_of_activities" name="current_number_of_activities" required max="100" x-ref="reminder_form_current_number_of_activities">
                        <div class="form-text" x-ref="reminder_form_current_number_of_activities_help">
                            The number of activities that have triggered toward this reminder so far.
                        </div>
                        <div class="invalid-feedback" x-ref="reminder_form_current_number_of_activities_error"></div>
                    </div>

                    <div class="d-flex">
                        <button type="button" class="btn btn-sm btn-secondary" @click="currentView = 'list'">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary ms-auto">Create</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>
