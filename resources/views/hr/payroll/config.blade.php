@extends('layouts.app')
@section('title', 'Payroll Configuration')
@section('page-title', 'Payroll Configuration')

@section('content')
@include('hr.payroll.partials.nav-tabs')

{{-- ═══ Regulatory Alerts Banner ═══ --}}
@if($pendingAlertCount > 0)
<div class="alert alert-warning d-flex align-items-center mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
    <div>
        <strong>{{ $pendingAlertCount }} regulatory update{{ $pendingAlertCount > 1 ? 's' : '' }} require{{ $pendingAlertCount === 1 ? 's' : '' }} attention.</strong>
        Review the <a href="#regulatoryAlerts" class="alert-link">Regulatory Alerts</a> section below to ensure compliance.
    </div>
</div>
@endif

<form method="POST" action="{{ route('hr.payroll.config.update') }}">
    @csrf
    @method('PUT')

    {{-- ═══ EPF — Malaysian Citizens ═══ --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-bank me-2"></i>EPF — Malaysian Citizens <small class="text-muted">(KWSP Act 1991)</small></h5></div>
        <div class="card-body">
            <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Category 1: Employees below 60 years. Third Schedule, KWSP Act 1991.</p>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Employee Rate (%)</label>
                    <input type="number" step="0.01" name="epf_employee_rate" class="form-control" value="{{ old('epf_employee_rate', $config->epf_employee_rate ?? 11.00) }}">
                    <small class="text-muted">Default: 11%</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Employer Rate — ≤ Threshold (%)</label>
                    <input type="number" step="0.01" name="epf_employer_rate" class="form-control" value="{{ old('epf_employer_rate', $config->epf_employer_rate ?? 13.00) }}">
                    <small class="text-muted">Default: 13%</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Employer Rate — > Threshold (%)</label>
                    <input type="number" step="0.01" name="epf_employer_rate_high" class="form-control" value="{{ old('epf_employer_rate_high', $config->epf_employer_rate_high ?? 12.00) }}">
                    <small class="text-muted">Default: 12%</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Salary Threshold (RM)</label>
                    <input type="number" step="0.01" name="epf_employer_salary_threshold" class="form-control" value="{{ old('epf_employer_salary_threshold', $config->epf_employer_salary_threshold ?? 5000.00) }}">
                    <small class="text-muted">Default: RM5,000</small>
                </div>
            </div>
            <hr class="my-3">
            <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Category 2: Employees aged 60+ / pensionable / disabled.</p>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Senior Employee Rate (%)</label>
                    <input type="number" step="0.01" name="epf_employee_rate_senior" class="form-control" value="{{ old('epf_employee_rate_senior', $config->epf_employee_rate_senior ?? 5.50) }}">
                    <small class="text-muted">Default: 5.5%</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Senior Employer Rate (%)</label>
                    <input type="number" step="0.01" name="epf_employer_rate_senior" class="form-control" value="{{ old('epf_employer_rate_senior', $config->epf_employer_rate_senior ?? 6.50) }}">
                    <small class="text-muted">Default: 6.5%</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ EPF — Foreign Workers ═══ --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-globe me-2"></i>EPF — Foreign Workers <small class="text-muted">(Category 3)</small></h5></div>
        <div class="card-body">
            <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Foreign workers are not mandated to contribute to EPF. Employers may voluntarily contribute a flat amount per employee. KWSP Act 1991, s.2.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Foreign Worker Employee Rate (%)</label>
                    <input type="number" step="0.01" name="epf_foreign_employee_rate" class="form-control" value="{{ old('epf_foreign_employee_rate', $config->epf_foreign_employee_rate ?? 0.00) }}">
                    <small class="text-muted">Usually 0% (voluntary)</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Employer Flat Amount per Foreign Worker (RM)</label>
                    <input type="number" step="0.01" name="epf_foreign_employer_flat" class="form-control" value="{{ old('epf_foreign_employer_flat', $config->epf_foreign_employer_flat ?? 5.00) }}">
                    <small class="text-muted">Default: RM5.00/month</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ SOCSO ═══ --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>SOCSO / PERKESO <small class="text-muted">(Employees' Social Security Act 1969)</small></h5></div>
        <div class="card-body">
            <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Employment Injury & Invalidity Scheme. Wage ceiling increased from RM5,000 to RM6,000 effective 1 Oct 2024.</p>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Employee Rate (%)</label>
                    <input type="number" step="0.0001" name="socso_employee_rate" class="form-control" value="{{ old('socso_employee_rate', $config->socso_employee_rate ?? 0.50) }}">
                    <small class="text-muted">Default: 0.5%</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Employer Rate (%)</label>
                    <input type="number" step="0.0001" name="socso_employer_rate" class="form-control" value="{{ old('socso_employer_rate', $config->socso_employer_rate ?? 1.75) }}">
                    <small class="text-muted">Default: 1.75%</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Wage Ceiling (RM)</label>
                    <input type="number" step="0.01" name="socso_wage_ceiling" class="form-control" value="{{ old('socso_wage_ceiling', $config->socso_wage_ceiling ?? 6000.00) }}">
                    <small class="text-muted">Default: RM6,000 (from Oct 2024)</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Foreign Worker Employer Rate (%)</label>
                    <input type="number" step="0.0001" name="socso_foreign_employer_rate" class="form-control" value="{{ old('socso_foreign_employer_rate', $config->socso_foreign_employer_rate ?? 1.25) }}">
                    <small class="text-muted">FWCS — employer only, no EE portion</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ EIS ═══ --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-umbrella me-2"></i>EIS / SIP <small class="text-muted">(Employment Insurance System Act 2017)</small></h5></div>
        <div class="card-body">
            <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Applies to Malaysian citizens/PRs only. Foreign workers are exempt under s.5, EIS Act 2017.</p>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">EIS Rate — Each (%)</label>
                    <input type="number" step="0.0001" name="eis_rate" class="form-control" value="{{ old('eis_rate', $config->eis_rate ?? 0.20) }}">
                    <small class="text-muted">Employee & Employer each 0.2%</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">EIS Wage Ceiling (RM)</label>
                    <input type="number" step="0.01" name="eis_wage_ceiling" class="form-control" value="{{ old('eis_wage_ceiling', $config->eis_wage_ceiling ?? 6000.00) }}">
                    <small class="text-muted">Default: RM6,000 (from Oct 2024)</small>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="hidden" name="eis_foreign_exempt" value="0">
                        <input type="checkbox" name="eis_foreign_exempt" value="1" class="form-check-input" id="eisForeignExempt" {{ old('eis_foreign_exempt', $config->eis_foreign_exempt ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="eisForeignExempt">Foreign Workers Exempt</label>
                    </div>
                    <small class="text-muted">s.5 EIS Act 2017</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ PCB / MTD ═══ --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>PCB / MTD <small class="text-muted">(Income Tax Act 1967)</small></h5></div>
        <div class="card-body">
            <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Monthly Tax Deduction. Non-residents (including most foreign workers) taxed at flat rate under s.109B.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Non-Resident Flat Rate (%)</label>
                    <input type="number" step="0.01" name="pcb_nonresident_rate" class="form-control" value="{{ old('pcb_nonresident_rate', $config->pcb_nonresident_rate ?? 30.00) }}">
                    <small class="text-muted">Default: 30% (s.109B ITA 1967)</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ HRDF & Working Days ═══ --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-mortarboard me-2"></i>HRDF & Working Days</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">HRDF Rate (%)</label>
                    <input type="number" step="0.01" name="hrdf_rate" class="form-control" value="{{ old('hrdf_rate', $config->hrdf_rate ?? 1.00) }}">
                    <small class="text-muted">Employer only, ≥10 employees</small>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="hidden" name="hrdf_enabled" value="0">
                        <input type="checkbox" name="hrdf_enabled" value="1" class="form-check-input" id="hrdfEnabled" {{ old('hrdf_enabled', $config->hrdf_enabled ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hrdfEnabled">HRDF Enabled</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Default Working Days/Month</label>
                    <input type="number" name="default_working_days" class="form-control" value="{{ old('default_working_days', $config->default_working_days ?? 26) }}">
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Minimum Wage ═══ --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-currency-exchange me-2"></i>Minimum Wage <small class="text-muted">(Minimum Wages Order 2025)</small></h5></div>
        <div class="card-body">
            <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>National Wages Consultative Council Act 2011. Applies to all employers regardless of employee count.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Minimum Wage (RM/month)</label>
                    <input type="number" step="0.01" name="minimum_wage" class="form-control" value="{{ old('minimum_wage', $config->minimum_wage ?? 1700.00) }}">
                    <small class="text-muted">Current: RM1,700 (from Feb 2025)</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Effective Date</label>
                    <input type="date" name="minimum_wage_effective_date" class="form-control" value="{{ old('minimum_wage_effective_date', $config->minimum_wage_effective_date?->format('Y-m-d') ?? '2025-02-01') }}">
                </div>
            </div>
        </div>
    </div>

    <!-- Employer Registration Numbers (pulled from Company Registration) -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="bi bi-building me-2"></i>Employer Registration Numbers</h5>
            <span class="badge bg-info"><i class="bi bi-link-45deg me-1"></i>Auto-synced from Company Registration</span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>These fields are managed under <strong>Company Registration</strong>. To update, go to <a href="{{ route('superadmin.companies.index') }}">Company Registration</a>.</p>
            @if($companies->isNotEmpty())
                @foreach($companies as $regCompany)
                <div class="mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                    <h6 class="fw-bold mb-2"><i class="bi bi-building me-1"></i>{{ $regCompany->name }}</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">LHDN Employer No. (E Number)</label>
                            <input type="text" class="form-control bg-light" value="{{ $config->lhdn_employer_no }}" readonly placeholder="—">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">KWSP (EPF) Number</label>
                            <input type="text" class="form-control bg-light" value="{{ $regCompany->kwsp_number ?? '—' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TIN Number</label>
                            <input type="text" class="form-control bg-light" value="{{ $regCompany->tin_number ?? '—' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SOCSO Number</label>
                            <input type="text" class="form-control bg-light" value="{{ $regCompany->socso_number ?? '—' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employment Insurance Scheme (EIS/SIP) Number</label>
                            <input type="text" class="form-control bg-light" value="{{ $regCompany->eis_number ?? '—' }}" readonly>
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <div class="text-muted text-center py-3">No companies registered. <a href="{{ route('superadmin.companies.index') }}">Register a company</a> to populate these fields.</div>
            @endif
            {{-- Keep hidden inputs for LHDN which is still managed here --}}
            <input type="hidden" name="lhdn_employer_no" value="{{ $config->lhdn_employer_no }}">
            <input type="hidden" name="epf_employer_no" value="{{ $config->epf_employer_no }}">
            <input type="hidden" name="socso_employer_no" value="{{ $config->socso_employer_no }}">
            <input type="hidden" name="eis_employer_no" value="{{ $config->eis_employer_no }}">
        </div>
    </div>

    <!-- Company Bank Details -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-bank me-2"></i>Company Bank Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $config->bank_name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bank Account Number</label>
                    <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number', $config->bank_account_number) }}">
                </div>
            </div>
        </div>
    </div>

    <!-- External Links -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>External Portals</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <a href="https://ez.hasil.gov.my" target="_blank" rel="noopener" class="btn btn-outline-primary w-100">
                        <i class="bi bi-box-arrow-up-right me-1"></i>LHDN e-Filing
                    </a>
                    <small class="text-muted d-block mt-1">PCB/MTD submission, EA forms</small>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="https://www.kwsp.gov.my/employer/contribution" target="_blank" rel="noopener" class="btn btn-outline-success w-100">
                        <i class="bi bi-box-arrow-up-right me-1"></i>EPF i-Akaun
                    </a>
                    <small class="text-muted d-block mt-1">EPF contribution remittance</small>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="https://www.perkeso.gov.my" target="_blank" rel="noopener" class="btn btn-outline-info w-100">
                        <i class="bi bi-box-arrow-up-right me-1"></i>SOCSO Portal
                    </a>
                    <small class="text-muted d-block mt-1">SOCSO/PERKESO contributions</small>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="https://eis.perkeso.gov.my" target="_blank" rel="noopener" class="btn btn-outline-warning w-100">
                        <i class="bi bi-box-arrow-up-right me-1"></i>EIS Portal
                    </a>
                    <small class="text-muted d-block mt-1">Employment Insurance System</small>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Configuration</button>
    <a href="{{ route('hr.payroll.pay-runs.index') }}" class="btn btn-secondary ms-2">Back to Payroll</a>
</form>

{{-- ═══ Regulatory Alerts ═══ --}}
<div id="regulatoryAlerts" class="card mt-4 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Regulatory Alerts & Compliance Updates</h5>
        @if($pendingAlertCount > 0)
        <span class="badge bg-danger">{{ $pendingAlertCount }} pending</span>
        @endif
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            This section lists Malaysian employment law changes affecting payroll statutory contributions.
            Review each alert, verify your configuration is up to date, and mark as acknowledged or implemented.
            <strong>Authorities monitored:</strong> KWSP (EPF), PERKESO (SOCSO/EIS), LHDN (PCB/MTD), JTK (Labour/Minimum Wage).
        </p>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Alert</th>
                        <th>Authority</th>
                        <th>Effective Date</th>
                        <th class="text-center">Severity</th>
                        <th class="text-center">Status</th>
                        <th>Reference</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                    <tr class="{{ $alert->status === 'pending' && $alert->severity === 'critical' ? 'table-danger' : ($alert->status === 'pending' ? 'table-warning' : '') }}">
                        <td>
                            <strong>{{ $alert->title }}</strong>
                            <br><small class="text-muted">{{ Str::limit($alert->description, 120) }}</small>
                            @if($alert->config_fields_affected)
                            <br><small class="text-info"><i class="bi bi-gear me-1"></i>Affects: {{ implode(', ', $alert->config_fields_affected) }}</small>
                            @endif
                        </td>
                        <td><span class="badge bg-dark">{{ $alert->authority }}</span></td>
                        <td>{{ $alert->effective_date->format('d M Y') }}</td>
                        <td class="text-center">{!! $alert->severityBadge() !!}</td>
                        <td class="text-center">{!! $alert->statusBadge() !!}</td>
                        <td>
                            @if($alert->reference_url)
                            <a href="{{ $alert->reference_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            @endif
                            @if($alert->reference_law)
                            <br><small class="text-muted">{{ Str::limit($alert->reference_law, 60) }}</small>
                            @endif
                        </td>
                        <td>
                            @if($alert->status !== 'implemented')
                            <form method="POST" action="{{ route('hr.payroll.alerts.acknowledge', $alert) }}" class="d-inline">
                                @csrf
                                @if($alert->status === 'pending')
                                <button type="submit" name="status" value="acknowledged" class="btn btn-sm btn-outline-primary" title="Mark as Acknowledged">
                                    <i class="bi bi-check"></i>
                                </button>
                                @endif
                                <button type="submit" name="status" value="implemented" class="btn btn-sm btn-outline-success" title="Mark as Implemented">
                                    <i class="bi bi-check-all"></i>
                                </button>
                            </form>
                            @else
                            <small class="text-muted">
                                {{ $alert->acknowledgedByUser?->name ?? '—' }}<br>
                                {{ $alert->acknowledged_at?->format('d M Y') }}
                            </small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No regulatory alerts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
