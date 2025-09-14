<div class="modal-dialog" x-data="{
    replaceModalContent: () => {
        axios.get('{{ route('activities.gear.modal-contents', $activity) }}')
            .then(({ data }) => {
                document.getElementById('activity_gear_modal_{{ $activity->id }}').innerHTML = data;

                document.getElementById('gear_modal_popup_gear_count_{{ $activity->id }}').innerText = document.querySelectorAll('.gear_list_item_{{ $activity->id }}').length;
            });
    }
}">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Gear for '{{ $activity->name }}'</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body" x-data="{
                                        attachGear: () => {
                                            axios.post(
                                                '{{ route(
                                                    'activities.gear.attach',
                                                    ['stravaActivity' => ':stravaActivity', 'gear' => ':gear']
                                                ) }}'.replace(':stravaActivity', '{{ $activity->id }}').replace(':gear', $refs.attach_gear_{{ $activity->id }}.value),
                                                {}
                                            ).then(replaceModalContent);
                                        },
                                        detachGear: (gearId) => {
                                            axios.delete(
                                                '{{ route(
                                                    'activities.gear.attach',
                                                    ['stravaActivity' => ':stravaActivity', 'gear' => ':gear']
                                                ) }}'.replace(':stravaActivity', '{{ $activity->id }}').replace(':gear', gearId),
                                                {}
                                            ).then(replaceModalContent);
                                        }
                                    }"
        >
            @session('success')
            <div class="alert alert-success text-center"
                 role="alert"
                 x-init="setTimeout(() => $el.remove(), 2000)"
            >
                {{ session('success') }}
            </div>
            @endsession

            <ul class="list-group">
                @foreach($activity->gears as $gear)
                    <li class="list-group-item gear_list_item_{{ $activity->id }}">
                        <div class="d-flex align-items-center">
                            <span class="fs-6 fw-bolder">{{ $gear->name }}</span>

                            <a @click="detachGear('{{ $gear->id }}')" class="btn btn-danger btn-sm ms-auto">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div @class(["mt-3" => $activity->gears->isNotEmpty()])>
                <label for="email" class="form-label">Attach Gear</label>
                <select class="form-select"
                        x-ref="attach_gear_{{ $activity->id }}"
                        @change="$refs.attach_gear_{{ $activity->id }}_button.disabled = !$el.value"
                    @disabled($gears->filter(fn ($gear) => $activity->gears->doesntContain($gear))->isEmpty())
                >
                    <option value="">--Select Gear--</option>
                    @foreach($gears->filter(fn ($gear) => $activity->gears->doesntContain($gear)) as $gear)
                        <option value="{{ $gear->id }}">{{ $gear->name }}</option>
                    @endforeach
                </select>

                <div class="d-flex">
                    <button type="button"
                            class="btn btn-sm btn-primary mt-3 ms-auto"
                            disabled
                            x-ref="attach_gear_{{ $activity->id }}_button"
                            @click="attachGear"
                    >
                        <i class="bi bi-plus"></i>
                        Attach
                    </button>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>
