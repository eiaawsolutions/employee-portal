@extends('layouts.app')
@section('title', 'Change KB Password')
@section('page-title', 'Knowledge Base — Change Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                         style="width:64px;height:64px;background:linear-gradient(135deg,#fd7e14,#dc3545);">
                        <i class="bi bi-key-fill text-white" style="font-size:28px;"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Change KB Password</h5>
                </div>

                <form method="POST" action="{{ route('superadmin.kb.password.update') }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold">Current Password</label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               required maxlength="128" autocomplete="current-password">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

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
                        <label for="password_confirmation" class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control" required minlength="8" maxlength="128" autocomplete="new-password">
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('superadmin.kb.index') }}" class="btn btn-outline-secondary flex-fill">Cancel</a>
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-check-lg me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
