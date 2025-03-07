<x-app page-title="Gear">
    <div class="d-flex align-items-center justify-content-center">
        <div style="max-width: 800px" class="d-flex flex-column flex-grow-1 pt-3">
            <div>
                <a class="btn btn-primary mb-3" href="{{ route('gear.create') }}">
                    <i class="bi bi-plus"></i>
                    Create New
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success text-center"
                     role="alert"
                     x-init="setTimeout(() => $el.remove(), 2000)"
                >
                    {{ session('success') }}
                </div>
            @endif

            @if(count($gear))
                @foreach($gear as $gearItem)
                    <div class="card mb-3">
                        <p class="card-header">
                            <span class="fs-5 fw-bolder">
                                {{ $gearItem->name }}
                            </span>
                        </p>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="d-flex flex-column h-100">
                                        <div>
                                            <p class="card-text">{{ $gearItem->description ?? '[No description provided]' }}</p>
                                        </div>

                                        <div class="mt-2">
                                            <p class="card-text">
                                                <strong>First Used:</strong>
                                                {{ $gearItem->first_used?->format('d/m/Y') ?? '[Unknown]' }}

                                                <br />

                                                <strong >Decommissioned:</strong>
                                                <span @class(['text-danger' => !is_null($gearItem->decommissioned)])>
                                                    {{ $gearItem->decommissioned?->format('d/m/Y') ?? 'N/A' }}
                                                </span>
                                            </p>

                                            <div class="d-flex align-items-center">
                                                <a class="btn btn-primary btn-sm" href="{{ route('gear.edit', $gearItem) }}">
                                                    <i class="bi bi-pencil"></i>
                                                    Edit
                                                </a>

                                                <form action="{{ route('gear.destroy', $gearItem) }}" method="POST" x-data x-ref="deleteForm">
                                                    @csrf

                                                    @method('DELETE')

                                                    <button class="btn btn-danger btn-sm ms-2"
                                                            type="button"
                                                            @click="() => {
                                                                if (confirm('Are you sure you want to delete this gear item?')) {
                                                                    $refs.deleteForm.submit();
                                                                }
                                                            }"
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                        Delete
                                                    </button>

                                                    <input type="hidden"
                                                           name="redirect_page"
                                                           value="{{ min(1, count($gear) > 1 ? request()->query('page', 1) : (request()->query('page', 1) - 1)) }}"
                                                    >
                                                </form>

                                                <button class="btn btn-warning btn-sm ms-2"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#gear_reminder_modal_{{ $gearItem->id }}"
                                                        type="button"
                                                >
                                                    <i class="bi bi-alarm"></i>
                                                    Reminders
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    @if($gearItem->image_path)
                                        <div class="d-flex mt-3 mt-md-0">
                                            <img
                                                class="img-fluid ms-md-auto"
                                                src="{{ $gearItem->image_path }}"
                                                alt="{{ $gearItem->name }}"
                                                style="width: 130px; height: 130px; object-fit: contain"
                                            >
                                        </div>
                                    @else
                                        <div class="alert alert-light text-center d-flex align-items-center justify-content-center ms-md-auto mt-3 mb-3 mt-md-0" style="width: 130px; height: 130px;" role="alert">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="d-flex align-items-center justify-content-center">
                    {{ $gear->links() }}
                </div>
            @else
                <div class="alert alert-info text-center" role="alert">
                    <span class="fs-6 fw-bolder">No gear found</span><br />
                    <small>
                        Creating gear allows you to track your equipment you use for your runs
                        and set custom reminders.
                    </small>
                </div>
            @endif
        </div>

        @foreach($gear as $gearItem)
            <div class="modal fade" id="gear_reminder_modal_{{ $gearItem->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reminders for {{ $gearItem->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" x-data="{ currentView: 'list' }">
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
                                                       data-bs-toggle="tooltip"
                                                       title="Delete Reminder"
                                                       @click="() => {
                                                            if (confirm('Are you sure you want to delete this reminder?')) {
                                                                deleteReminder('{{ $reminder->id }}');
                                                            }
                                                        }"
                                                    ></i>
                                                    <i class="bi bi-pencil text-primary float-end me-2"
                                                       role="button"
                                                       data-bs-toggle="tooltip"
                                                       title="Edit Reminder"
                                                       @click="() => {
                                                           currentView = 'form';

                                                           $refs.reminder_form.dataset.gearId = '{{ $gearItem->id }}';
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

                                                $refs.reminder_form.dataset.gearId = '';
                                                $refs.reminder_form_name.value = '';
                                                $refs.reminder_form_trigger_after_number_of_activities.value = '';
                                                $refs.reminder_form_current_number_of_activities.value = '';
                                            }"
                                        >
                                            <i class="bi bi-plus"></i>
                                            Create Reminder
                                        </button>
                                    </div>
                                </ul>
                            </div>

                            <div x-show="currentView === 'form'">
                                <form x-ref="reminder_form">
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
            </div>
        @endforeach
    </div>
</x-app>
