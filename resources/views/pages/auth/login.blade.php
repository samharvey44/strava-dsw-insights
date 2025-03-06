<x-app page-title="Login">
    <div class="d-flex justify-content-center pt-5">
        <div style="max-width: 600px">
            <div class="card">
                <h1 class="card-header fs-5">Login</h1>

                <div class="card-body">
                    @if($errors->has('login'))
                        <div class="alert alert-danger" role="alert">
                            {{ $errors->first('login') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.action') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email') }}" required autofocus>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password"
                                   class="form-control @error('password') is-invalid @enderror" name="password"
                                   required>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="remember"
                                   id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>

                        <div class="d-lg-flex d-block">
                            <button type="submit" class="btn btn-primary">Login</button>

                            <div class="d-md-flex align-items-center mt-lg-0 mt-2">
                                <a class="btn btn-link" href="#">
                                    Forgot your password?
                                </a>

                                <a class="btn btn-link" href="#">
                                    Don't have an account?
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app>
