<x-app page-title="Gear">
    <div class="pt-3">
        <div class="d-flex">
            <a class="btn btn-primary ms-auto" href="{{ route('gear.create') }}">
                <i class="bi bi-plus"></i>
                Create New
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-center">
            <div style="max-width: 800px" class="d-flex flex-column flex-grow-1 pt-3">
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

                                                <strong>Decommissioned:</strong>
                                                {{ $gearItem->decommissioned?->format('d/m/Y') ?? 'N/A' }}
                                            </p>
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
            </div>
        </div>
    </div>
</x-app>
