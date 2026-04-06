@extends('layouts.app')
@section('title', 'System Knowledge Base')
@section('page-title', 'System Knowledge Base')

@push('styles')
<style>
    .kb-hero {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 40%, #0f3460 100%);
        border-radius: 1rem; color: #fff; padding: 2.5rem 2rem; margin-bottom: 2rem;
        position: relative; overflow: hidden;
    }
    .kb-hero::before {
        content: ''; position: absolute; top: -40%; right: -15%;
        width: 400px; height: 400px; background: rgba(255,255,255,0.03); border-radius: 50%;
    }
    .kb-hero h1 { font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; }
    .kb-hero .lead { font-size: 1rem; opacity: 0.8; }
    .topic-card {
        border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem;
        transition: all 0.2s; cursor: pointer; text-decoration: none; color: inherit;
        display: flex; gap: 1rem; align-items: flex-start; height: 100%;
    }
    .topic-card:hover {
        border-color: #2684FE; box-shadow: 0 4px 16px rgba(38,132,254,0.12);
        transform: translateY(-2px); color: inherit; text-decoration: none;
    }
    .topic-icon {
        width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .topic-icon i { font-size: 22px; color: #fff; }
    .topic-card h6 { font-weight: 700; margin: 0 0 4px; font-size: 15px; }
    .topic-card p { margin: 0; font-size: 13px; color: #64748b; line-height: 1.5; }
    [data-theme="dark"] .topic-card { border-color: #334155; background: #1e293b; }
    [data-theme="dark"] .topic-card:hover { border-color: #2684FE; }
    [data-theme="dark"] .topic-card p { color: #94a3b8; }
    [data-theme="dark"] .topic-card h6 { color: #f1f5f9; }
</style>
@endpush

@section('content')
{{-- Hero --}}
<div class="kb-hero">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1><i class="bi bi-book me-2"></i>System Knowledge Base</h1>
            <p class="lead mb-2">
                Complete process documentation with logic flows, data pipelines, and architecture wireframes for every module.
            </p>
            <div class="d-flex flex-wrap gap-2 mt-2" style="font-size:0.78rem;">
                <span class="badge bg-light text-dark"><i class="bi bi-database me-1"></i>{{ $meta['tables'] }} Tables</span>
                <span class="badge bg-light text-dark"><i class="bi bi-signpost-split me-1"></i>{{ $meta['endpoints'] }} Endpoints</span>
                <span class="badge bg-light text-dark"><i class="bi bi-envelope me-1"></i>{{ $meta['mail_classes'] }} Emails</span>
                <span class="badge bg-light text-dark"><i class="bi bi-box me-1"></i>{{ $meta['models'] }} Models</span>
                <span class="badge bg-light text-dark"><i class="bi bi-file-code me-1"></i>{{ $meta['views'] }} Views</span>
                <span class="badge bg-light text-dark"><i class="bi bi-clock-history me-1"></i>{{ $meta['scheduled_jobs'] }} Jobs</span>
                <span class="badge bg-light text-dark"><i class="bi bi-git me-1"></i>{{ $meta['git']['hash'] }}</span>
            </div>
            <div class="mt-2" style="font-size:0.72rem;opacity:0.65;">
                <i class="bi bi-arrow-repeat me-1"></i>Auto-updated {{ $meta['collected_at'] }}
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('superadmin.kb.password.change') }}" class="btn btn-sm btn-outline-light">
                <i class="bi bi-key me-1"></i> Change Password
            </a>
            <a href="{{ route('superadmin.kb.lock') }}" class="btn btn-sm btn-outline-light">
                <i class="bi bi-lock me-1"></i> Lock
            </a>
        </div>
    </div>
</div>

{{-- Topic Grid --}}
<div class="row g-3">
    @foreach($topics as $slug => $topic)
    <div class="col-md-6 col-xl-4">
        <a href="{{ route('superadmin.kb.topic', $slug) }}" class="topic-card bg-white">
            <div class="topic-icon" style="background: {{ $topic['color'] }};">
                <i class="bi {{ $topic['icon'] }}"></i>
            </div>
            <div>
                <h6>{{ $topic['title'] }}</h6>
                <p>{{ $topic['description'] }}</p>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- System Architecture Overview --}}
<div class="card mt-4">
    <div class="card-header bg-white fw-bold">
        <i class="bi bi-diagram-3 me-2"></i>System Architecture Overview
    </div>
    <div class="card-body">
        <div class="mermaid">
graph LR
    subgraph AUTH["Authentication"]
        Login["Work Email Login"]
        Session["Single Session"]
        Lockout["5-Fail Lockout"]
    end

    subgraph HR["HR Module"]
        OB["Onboarding"]
        EMP["Employee Records"]
        OFF["Offboarding"]
    end

    subgraph HRM["HRM Modules"]
        LV["Leave"]
        PAY["Payroll"]
        ATT["Attendance"]
        CLM["Claims"]
    end

    subgraph IT["IT Module"]
        AST["Asset Inventory"]
        AARF["AARF Forms"]
        TSK["IT Tasks"]
    end

    subgraph FIN["Finance"]
        ACC["Accounting"]
        AR["Receivables"]
        AP["Payables"]
    end

    Login --> Session
    Session --> HR & HRM & IT & FIN
    OB -->|"start_date"| EMP
    EMP -->|"exit_date"| OFF
    EMP --> LV & PAY & ATT & CLM
    OB --> AARF
    AST --> AARF
    CLM -->|"hr_approved"| PAY
    LV -->|"unpaid days"| PAY
    ATT -->|"late/absent"| PAY
    OFF --> AST
        </div>
        <p class="text-muted small mt-3 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Arrows show data flow direction. Click any topic card above for detailed logic, pipelines, and wireframes.
            <span class="ms-2 text-secondary"><i class="bi bi-git me-1"></i>Commit <code>{{ $meta['git']['hash'] }}</code> — {{ $meta['git']['message'] }}</span>
        </p>
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
        securityLevel: 'strict'
    });
});
</script>
@endsection
