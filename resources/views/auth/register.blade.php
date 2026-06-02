@extends('layouts.mantis-guest')

@section('title', 'Sign up | Mantis Admin')

@section('content')
    <div class="card my-5">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <h3 class="mb-0"><b>Sign up</b></h3>
                <a href="{{ route('login') }}" class="link-primary">Already have an account?</a>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Nama Bisnis*</label>
                            <input type="text" name="business_name"
                                class="form-control @error('business_name') is-invalid @enderror"
                                placeholder="Contoh: Salon Cantik" value="{{ old('business_name') }}" required autofocus>
                            @error('business_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Nama Pemilik*</label>
                            <input type="text" name="owner_name"
                                class="form-control @error('owner_name') is-invalid @enderror"
                                placeholder="Nama lengkap kamu" value="{{ old('owner_name') }}" required>
                            @error('owner_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Email Address*</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        placeholder="Email Address" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Password*</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        placeholder="Password" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <p class="mt-4 text-sm text-muted">By Signing up, you agree to our <a href="#" class="text-primary">
                        Terms
                        of Service </a> and <a href="#" class="text-primary"> Privacy Policy</a></p>
                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>


        </div>
    </div>
@endsection
