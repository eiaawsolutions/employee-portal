@extends('superadmin.knowledge-base.topic-layout')

@section('topic-content')
{{-- ── Pipeline ── --}}
<div class="kb-section card" id="pipeline">
    <div class="card-body">
        <h4><i class="bi bi-signpost-split me-2"></i>Asset Lifecycle Pipeline</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart TD
    A["IT Procures Asset\nAssetInventory created\nstatus: available\ncondition: new"] --> B{"Onboarding?"}
    B -->|"Yes"| C["autoAssignAssets\nbased on provisioning checklist\nstatus: assigned"]
    B -->|"Manual"| D["IT assigns to employee\nstatus: assigned"]

    C & D --> E["AARF Created\nLinked to onboarding/employee"]
    E --> F["IT Manager Acknowledges\nit_manager_acknowledged = true"]
    F --> G["AarfAcknowledgementMail\nToken link sent to employee"]
    G --> H["Employee Acknowledges\n/aarf/token (no auth)\nacknowledged = true"]

    H --> I{"Employee exits?"}
    I -->|"Yes"| J["Asset Return Required\nOffboarding checks"]
    J --> K["All Assets Returned\nstatus: available\nasset_cleaning_status: done"]
    K --> L["OffboardingSendoffMail\nunblocked"]

    I -->|"Damaged"| M["condition: not_good\nMoved to DisposedAsset"]

    style A fill:#d1e7dd,stroke:#198754,color:#000
    style C fill:#cfe2ff,stroke:#0d6efd,color:#000
    style F fill:#fef3cd,stroke:#ffc107,color:#000
    style H fill:#d1e7dd,stroke:#198754,color:#000
    style K fill:#d1e7dd,stroke:#198754,color:#000
    style M fill:#f8d7da,stroke:#dc3545,color:#000
            </div>
        </div>
    </div>
</div>

{{-- ── Status & Condition ── --}}
<div class="kb-section card" id="status">
    <div class="card-body">
        <h4><i class="bi bi-tag me-2"></i>Asset Status & Condition</h4>
        <div class="row g-3">
            <div class="col-md-6">
                <h6>Asset Status</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="status-badge" style="background:#d1e7dd;color:#0f5132;">available</span>
                    <span class="status-badge" style="background:#cfe2ff;color:#084298;">assigned</span>
                    <span class="status-badge" style="background:#e2e3e5;color:#41464b;">unavailable</span>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Asset Condition</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="status-badge" style="background:#d1e7dd;color:#0f5132;">new</span>
                    <span class="status-badge" style="background:#d1e7dd;color:#0f5132;">good</span>
                    <span class="status-badge" style="background:#fef3cd;color:#856404;">fair</span>
                    <span class="status-badge" style="background:#f8d7da;color:#842029;">damaged</span>
                    <span class="status-badge" style="background:#f8d7da;color:#842029;">not_good</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── AARF Flow ── --}}
<div class="kb-section card" id="aarf">
    <div class="card-body">
        <h4><i class="bi bi-file-earmark-check me-2"></i>AARF (Annual Asset Record Form)</h4>
        <div class="diagram-container">
            <div class="mermaid">
sequenceDiagram
    participant HR as HR Manager
    participant IT as IT Manager
    participant SYS as System
    participant EMP as Employee

    HR->>SYS: Create Onboarding (Section C assets)
    SYS->>SYS: autoAssignAssets() + create AARF
    IT->>SYS: Review & edit asset list
    IT->>SYS: IT Manager Acknowledge
    SYS->>EMP: AarfAcknowledgementMail (token link)
    EMP->>SYS: Open /aarf/{token} (no auth)
    EMP->>SYS: Review assets & Acknowledge
    SYS->>SYS: acknowledged=true, acknowledged_at=now
            </div>
        </div>
        <div class="alert alert-warning small mt-3 mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Lock Rule:</strong> AARF is locked when the onboarding transitions to an Employee record. After that, asset changes require direct edit on the asset inventory.
        </div>
    </div>
</div>

{{-- ── Decommissioning ── --}}
<div class="kb-section card" id="decommission">
    <div class="card-body">
        <h4><i class="bi bi-trash me-2"></i>Decommissioning Flow</h4>
        <div class="diagram-container">
            <div class="mermaid">
flowchart LR
    A["Asset condition: not_good\nor damaged beyond repair"] --> B["Create DisposedAsset record\nReason + remarks"]
    B --> C["Remove from main inventory\nNo longer in active listing"]
            </div>
        </div>
    </div>
</div>

{{-- ── Database ── --}}
<div class="kb-section card" id="database">
    <div class="card-body">
        <h4><i class="bi bi-database me-2"></i>Database Relations</h4>
        <div class="diagram-container">
            <div class="mermaid">
erDiagram
    ASSET_INVENTORIES ||--o{ ASSET_ASSIGNMENTS : "assigned via"
    ONBOARDINGS ||--o{ ASSET_ASSIGNMENTS : provisions
    ONBOARDINGS ||--o| AARFS : generates
    EMPLOYEES ||--o| AARFS : acknowledges
    ASSET_INVENTORIES ||--o| DISPOSED_ASSETS : decommissions
    ONBOARDINGS ||--|| ASSET_PROVISIONINGS : "Section C"
    ASSET_INVENTORIES {
        string asset_tag
        string brand
        string model
        string serial_number
        string status
        string condition
    }
    AARFS {
        boolean acknowledged
        boolean it_manager_acknowledged
        string token
    }
            </div>
        </div>
    </div>
</div>
@endsection
