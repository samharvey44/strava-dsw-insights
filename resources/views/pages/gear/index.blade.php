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
    </div>
</x-app>
