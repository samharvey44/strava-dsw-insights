<x-app page-title="Gear">
    <div class="pt-3 px-1 px-md-3">
        <form method="POST"
              action="{{ route('gear.store') }}"
              enctype="multipart/form-data"
              x-data="{ imageFileUrl: '' }"
        >
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           placeholder="Enter name..."
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                    >
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                    @if(! $errors->has('name'))
                        <div class="form-text">
                            The name of the gear item you want to add.
                        </div>
                    @endif
                </div>

                <div class="col-md-6 mt-3 mt-md-0">
                    <label class="form-label" for="description">Description</label>
                    <input type="text"
                           class="form-control @error('description') is-invalid @enderror"
                           placeholder="Enter description..."
                           id="description"
                           name="description"
                           value="{{ old('description') }}"
                    >
                    @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                    @if(! $errors->has('description'))
                        <div class="form-text">
                            A brief description of the gear item.
                        </div>
                    @endif
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label" for="first_used">First Used</label>
                    <input
                        type="text"
                        class="form-control @error('first_used') is-invalid @enderror"
                        placeholder="Enter first used..."
                        id="first_used"
                        name="first_used"
                        x-init="new Pikaday({ field: $el })"
                        value="{{ old('first_used') }}"
                    >
                    @error('first_used')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                    @if(! $errors->has('first_used'))
                        <div class="form-text">
                            The date you first used the gear item.
                        </div>
                    @endif
                </div>

                <div class="col-md-6 mt-3 mt-md-0">
                    <label class="form-label" for="decommissioned">Decommissioned</label>
                    <input
                        type="text"
                        class="form-control @error('decommissioned') is-invalid @enderror"
                        placeholder="Enter decommissioned..."
                        id="decommissioned"
                        name="decommissioned"
                        x-init="new Pikaday({ field: $el })"
                        value="{{ old('decommissioned') }}"
                    >
                    @error('decommissioned')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                    @if(! $errors->has('decommissioned'))
                        <div class="form-text">
                            When the gear item was decommissioned, if applicable.
                        </div>
                    @endif
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label" for="image">Image</label>
                    <input
                        type="file"
                        class="form-control @error('image') is-invalid @enderror"
                        id="image"
                        name="image"
                        @change="imageFileUrl = URL.createObjectURL($event.target.files[0])"
                        x-ref="image"
                        accept="image/*"
                    >
                    @error('image')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                    @if(! $errors->has('image'))
                        <div class="form-text">
                            A picture to represent the gear item.
                        </div>
                    @endif
                </div>
            </div>

            <div class="row mt-3" x-show="imageFileUrl" style="display: none;">
                <div class="col-md-6">
                    <img
                        class="img-fluid"
                        :src="imageFileUrl"
                        alt="Gear item picture"
                        style="max-height: 130px; max-width: 130px; object-fit: contain"
                    >

                    <button
                        type="button"
                        class="btn btn-sm btn-danger ms-3"
                        @click="imageFileUrl = ''; $refs.image.value = null"
                    >
                        Remove Image
                    </button>
                </div>
            </div>

            <div class="row mt-3 pb-3">
                <div class="col-md-12">
                    <div class="d-flex">
                        <button class="btn btn-primary ms-auto">
                            <i class="bi bi-floppy"></i>
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app>
