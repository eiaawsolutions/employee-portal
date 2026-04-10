@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Welcome Banner --}}
@php $p = $employee?->onboarding?->personalDetail; $w = $employee?->onboarding?->workDetail; @endphp
<div class="card mb-4" style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 50%,#3b82f6 100%);border:none;border-radius:16px;overflow:hidden;position:relative;">
    <div style="position:absolute;top:-40px;right:-20px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.07);"></div>
    <div style="position:absolute;bottom:-30px;right:80px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.05);"></div>
    <div class="card-body d-flex align-items-center gap-3 py-3" style="position:relative;z-index:1;">
        <div style="width:52px;height:52px;background:rgba(255,255,255,0.2);border-radius:14px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
            <i class="bi bi-person-fill" style="font-size:26px;color:#fff;"></i>
        </div>
        <div>
            <h5 class="text-white mb-0 fw-bold">Welcome, {{ $p?->full_name ?? $user->name }}</h5>
            <small style="color:rgba(255,255,255,0.8);">{{ $w?->designation ?? $employee?->designation ?? 'Employee' }}{{ ($w?->company ?? $employee?->company) ? ' · '.($w?->company ?? $employee?->company) : '' }}</small>
        </div>
        <div class="ms-auto text-end d-none d-md-block">
            <small style="color:rgba(255,255,255,.7);font-size:12px;">{{ now()->format('l, d/m/Y') }}</small>
        </div>
    </div>
</div>

@include('partials.announcements-widget')

@include('partials.birthday-babies-widget')

@include('partials.on-leave-widget')

@endsection
