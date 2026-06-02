@extends('layouts.mantis-guest')

@section('title', 'Login')

@section('content')
    <div class="card my-5">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <h3 class="mb-0"><b>Login</b></h3>
                <a href="{{ route('register') }}" class="link-primary">Don't have an account?</a>
            </div>

            @if (session('status'))
                <div class="alert alert-success mb-3" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        placeholder="Email Address" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        placeholder="Password" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="d-flex mt-1 justify-content-between">
                    <div class="form-check">
                        <input class="form-check-input input-primary" type="checkbox" name="remember" id="customCheckc1" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label text-muted" for="customCheckc1">Keep me sign in</label>
                    </div>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">
                            <h5 class="text-secondary f-w-400">Forgot Password?</h5>
                        </a>
                    @endif
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>


        </div>
    </div>
@endsection