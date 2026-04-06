@extends('layouts.app')
@section('title', 'Set Knowledge Base Password')
@section('page-title', 'Knowledge Base — Set Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                         style="width:64px;height:64px;background:linear-gradient(135deg,#6610f2,#0d6efd);">
                        <i class="bi bi-shield-lock-fill text-white" style="font-size:28px;"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Create Knowledge Base Password</h5>
                    <p class="text-muted small mb-0">
                        This password protects the system knowledge base. It is separate from your login password.
                    </p>
                </div>

                <form method="POST" action="{{ route('superadmin.kb.setup.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">New Password</label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required minlength="8" maxlength="128" autocomplete="new-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control @error('password_confirmation') is-invalid @enderror"
                               required minlength="8" maxlength="128" autocomplete="new-password">
                        @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-lock-fill me-1"></i> Set Password & Enter
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
