{{-- Shared layout for all KB topic pages --}}
@extends('layouts.app')
@section('title', $topic['title'] . ' — Knowledge Base')
@section('page-title', 'Knowledge Base')

@push('styles')
<style>
    .kb-topic-header {
        border-radius: 12px; padding: 1.5rem 2rem; margin-bottom: 1.5rem; color: #fff;
        display: flex; align-items: center; gap: 1rem;
    }
    .kb-topic-header i { font-size: 32px; }
    .kb-topic-header h2 { font-weight: 800; margin: 0; font-size: 1.5rem; }
    .kb-topic-header p { margin: 0; opacity: 0.85; font-size: 0.9rem; }
    .kb-nav { position: sticky; top: 12px; }
    .kb-nav a {
        display: block; padding: 6px 14px; font-size: 13px; color: #64748b;
        text-decoration: none; border-left: 3px solid transparent; margin-bottom: 2px;
        border-radius: 0 6px 6px 0; transition: all 0.15s;
    }
    .kb-nav a:hover, .kb-nav a.active { color: #2684FE; border-left-color: #2684FE; background: rgba(38,132,254,0.06); }
    .kb-section { margin-bottom: 2rem; scroll-margin-top: 80px; }
    .kb-section h4 { font-weight: 700; font-size: 1.1rem; margin-bottom: 1rem;
        padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; }
    .kb-section h5 { font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; }
    .pipeline-step {
        display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; position: relative;
        padding-left: 28px;
    }
    .pipeline-step::before {
        content: ''; position: absolute; left: 10px; top: 24px; bottom: -12px;
        width: 2px; background: #e2e8f0;
    }
    .pipeline-step:last-child::before { display: none; }
    .pipeline-num {
        width: 22px; height: 22px; border-radius: 50%; font-size: 11px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        position: absolute; left: 0; top: 2px; z-index: 1;
    }
    .status-badge {
        display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 11px;
        font-weight: 600; font-family: monospace;
    }
    .relation-table { font-size: 13px; }
    .relation-table th { background: #f8fafc; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
    .diagram-container { overflow-x: auto; }
    [data-theme="dark"] .kb-nav a { color: #94a3b8; }
    [data-theme="dark"] .kb-nav a:hover, [data-theme="dark"] .kb-nav a.active { color: #60a5fa; border-left-color: #60a5fa; }
    [data-theme="dark"] .kb-section h4 { border-bottom-color: #334155; }
    [data-theme="dark"] .relation-table th { background: #0f172a; }
    [data-theme="dark"] .pipeline-step::before { background: #334155; }
</style>
@endpush

@section('content')
{{-- Topic Header --}}
<div class="kb-topic-header" style="background: linear-gradient(135deg, {{ $topic['color'] }}, {{ $topic['color'] }}cc);">
    <i class="bi {{ $topic['icon'] }}"></i>
    <div>
        <h2>{{ $topic['title'] }}</h2>
        <p>{{ $topic['description'] }}</p>
    </div>
</div>

<div class="row">
    {{-- Sidebar Nav (topic list) --}}
    <div class="col-lg-3 d-none d-lg-block">
        <div class="kb-nav">
            <div class="small fw-bold text-muted mb-2" style="font-size:10px;text-transform:uppercase;letter-spacing:1px;padding-left:14px;">
                All Topics
            </div>
            @foreach($topics as $s => $t)
                <a href="{{ route('superadmin.kb.topic', $s) }}" class="{{ $s === $slug ? 'active' : '' }}">
                    <i class="bi {{ $t['icon'] }} me-1" style="font-size:13px;"></i> {{ $t['title'] }}
                </a>
            @endforeach
            <hr class="my-2">
            <a href="{{ route('superadmin.kb.index') }}"><i class="bi bi-arrow-left me-1"></i> Back to Index</a>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="col-lg-9">
        {{-- Mobile back link --}}
        <div class="d-lg-none mb-3">
            <a href="{{ route('superadmin.kb.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> All Topics
            </a>
        </div>

        @yield('topic-content')
    </div>
</div>

{{-- Mermaid JS --}}
<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    mermaid.initialize({
        startOnLoad: true,
        theme: isDark ? 'dark' : 'default',
        flowchart: { useMaxWidth: true, htmlLabels: true, curve: 'basis' },
        sequence: { useMaxWidth: true },
        er: { useMaxWidth: true },
        securityLevel: 'strict'
    });
});
</script>
@endsection
