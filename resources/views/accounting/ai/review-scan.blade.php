@extends('layouts.app')
@section('title', 'Review Scanned Invoice')
@section('page-title', 'Review & Confirm Scanned Invoice')

@section('content')
@include('accounting.partials.nav')

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i>
    @foreach($errors->all() as $err){{ $err }} @endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php $data = $scan->extracted_data ?? []; @endphp
<div class="row g-4">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Original Document</h6></div>
            <div class="card-body text-center">
                @if($scan->file_path)
                    @if(str_contains(strtolower($scan->file_type ?? $scan->file_path), 'pdf'))
                        <embed src="{{ route('accounting.ai.scan-file', $scan) }}" type="application/pdf" width="100%" height="500px">
                    @else
                        <img src="{{ route('accounting.ai.scan-file', $scan) }}" class="img-fluid rounded" alt="Invoice scan" style="max-height:500px;">
                    @endif
                @else
                    <div class="text-muted py-5">No file available</div>
                @endif
            </div>
        </div>
        @if($scan->confidence_score)
        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">AI Confidence</h6></div>
            <div class="card-body" style="font-size:13px;">
                <div class="mb-2"><strong>Confidence:</strong>
                    <span class="badge bg-{{ $scan->confidence_score >= 80 ? 'success' : ($scan->confidence_score >= 50 ? 'warning' : 'danger') }}">{{ number_format($scan->confidence_score, 0) }}%</span>
                </div>
                @if($data['vendor_name'] ?? null)
                <div class="mb-1"><strong>Detected Vendor:</strong> {{ $data['vendor_name'] }}</div>
                @endif
                @if($data['invoice_number'] ?? null)
                <div class="mb-1"><strong>Invoice #:</strong> {{ $data['invoice_number'] }}</div>
                @endif
            </div>
        </div>
        @endif
    </div>
    <div class="col-md-7">
        <form method="POST" action="{{ route('accounting.ai.confirm-scan', $scan) }}">
            @csrf
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-robot me-1"></i>Extracted Data — Review & Edit</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vendor *</label>
                            <select name="vendor_id" class="form-select" required>
                                <option value="">— Select Vendor —</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                            @if($data['vendor_name'] ?? null)
                            <div class="form-text">AI detected: <strong>{{ $data['vendor_name'] }}</strong></div>
                            @endif
                        </div>
                        <div class="col-md-6"><label class="form-label">Vendor Bill Number</label><input type="text" name="vendor_bill_number" class="form-control" value="{{ old('vendor_bill_number', $data['invoice_number'] ?? '') }}"></div>
                        <div class="col-md-4"><label class="form-label">Date *</label><input type="date" name="date" class="form-control" value="{{ old('date', $data['date'] ?? now()->toDateString()) }}" required></div>
                        <div class="col-md-4"><label class="form-label">Due Date *</label><input type="date" name="due_date" class="form-control" value="{{ old('due_date', $data['due_date'] ?? '') }}" required></div>
                        <div class="col-md-4"><label class="form-label">Currency</label><input type="text" class="form-control bg-light" value="{{ $data['currency'] ?? 'MYR' }}" readonly></div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Line Items</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" style="font-size:13px;">
                        <thead><tr><th>Description</th><th style="width:80px;">Qty</th><th style="width:120px;">Unit Price</th><th style="width:120px;">Tax Code</th></tr></thead>
                        <tbody>
                        @foreach($data['items'] ?? [] as $i => $item)
                            <tr>
                                <td><input type="text" name="items[{{ $i }}][description]" class="form-control form-control-sm" value="{{ old("items.$i.description", $item['description'] ?? '') }}" required></td>
                                <td><input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm" step="0.0001" min="0.0001" value="{{ old("items.$i.quantity", $item['quantity'] ?? 1) }}" required></td>
                                <td><input type="number" name="items[{{ $i }}][unit_price]" class="form-control form-control-sm" step="0.01" min="0" value="{{ old("items.$i.unit_price", $item['unit_price'] ?? 0) }}" required></td>
                                <td>
                                    <select name="items[{{ $i }}][tax_code_id]" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        @foreach($taxCodes as $tc)<option value="{{ $tc->id }}">{{ $tc->code }} ({{ $tc->rate }}%)</option>@endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                        @if(empty($data['items']))
                            <tr>
                                <td><input type="text" name="items[0][description]" class="form-control form-control-sm" value="" required></td>
                                <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm" step="0.0001" min="0.0001" value="1" required></td>
                                <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm" step="0.01" min="0" value="0" required></td>
                                <td>
                                    <select name="items[0][tax_code_id]" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        @foreach($taxCodes as $tc)<option value="{{ $tc->id }}">{{ $tc->code }} ({{ $tc->rate }}%)</option>@endforeach
                                    </select>
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(($data['subtotal'] ?? null) || ($data['total'] ?? null))
            <div class="card mb-3">
                <div class="card-body" style="font-size:13px;">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>AI Subtotal:</strong> RM {{ number_format($data['subtotal'] ?? 0, 2) }}</div>
                        <div class="col-md-4"><strong>AI Tax:</strong> RM {{ number_format($data['tax_total'] ?? 0, 2) }}</div>
                        <div class="col-md-4"><strong>AI Total:</strong> RM {{ number_format($data['total'] ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            @endif

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Confirm & Create Bill</button>
                <a href="{{ route('accounting.ai.invoice-scanner') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
