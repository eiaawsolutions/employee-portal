@extends('layouts.app')
@section('title', 'Knowledge Base — Unlock')
@section('page-title', 'Knowledge Base — Unlock')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                         style="width:64px;height:64px;background:linear-gradient(135deg,#6610f2,#0d6efd);">
                        <i class="bi bi-lock-fill text-white" style="font-size:28px;"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Knowledge Base Locked</h5>
                    <p class="text-muted small mb-0">
                        Enter your Knowledge Base password to access system documentation.
                    </p>
                </div>

                <form method="POST" action="{{ route('superadmin.kb.unlock') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">KB Password</label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required maxlength="128" autocomplete="current-password" autofocus>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-unlock-fill me-1"></i> Unlock
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
