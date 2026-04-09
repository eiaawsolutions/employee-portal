@extends('layouts.app')
@section('title', 'AI Invoice Scanner')
@section('page-title', 'AI Invoice Scanner')

@section('content')
@include('accounting.partials.nav')

@if(!($hasApiKey ?? false) && !session('error'))
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i>No AI API key configured. <a href="{{ route('accounting.settings') }}" class="alert-link">Go to Accounting Settings</a> to add your OpenAI API key.
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

<div class="row g-4">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-robot me-1"></i>Upload Invoice</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('accounting.ai.upload-invoice') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Invoice Image / PDF *</label>
                        <input type="file" name="invoice" class="form-control" accept="image/*,.pdf" required>
                        <div class="form-text">Supported: JPG, PNG, PDF (max 10MB). AI will extract vendor, items, amounts.</div>
                    </div>
                    <div class="mb-3"><label class="form-label">Company</label>
                        <select name="company" class="form-select"><option value="">— Select —</option>
                            @foreach($companies ?? [] as $key => $name)<option value="{{ $key }}" {{ ($company ?? '') === $key ? 'selected' : '' }}>{{ $name }}</option>@endforeach
                        </select></div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-cloud-upload me-1"></i>Scan Invoice</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Scan History</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:13px;">
                    <thead><tr><th>Date</th><th>Vendor</th><th>Amount</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse($scans ?? [] as $scan)
                        <tr>
                            <td>{{ $scan->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $scan->extracted_data['vendor_name'] ?? 'Unknown' }}</td>
                            <td>RM {{ number_format($scan->extracted_data['total'] ?? 0, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $scan->status === 'reviewed' ? 'success' : ($scan->status === 'completed' ? 'warning' : ($scan->status === 'failed' ? 'danger' : ($scan->status === 'processing' ? 'info' : 'secondary'))) }}">
                                    {{ ucwords(str_replace('_', ' ', $scan->status)) }}
                                </span>
                            </td>
                            <td class="text-end" style="white-space:nowrap;">
                                @if($scan->status === 'completed' || $scan->status === 'failed')
                                <a href="{{ route('accounting.ai.review-scan', $scan) }}" class="btn btn-sm btn-outline-primary" title="Review / Edit"><i class="bi bi-pencil"></i></a>
                                @elseif($scan->bill_id)
                                <a href="{{ route('accounting.bills.show', $scan->bill_id) }}" class="btn btn-sm btn-outline-success" title="View Bill"><i class="bi bi-receipt"></i></a>
                                <a href="{{ route('accounting.ai.review-scan', $scan) }}" class="btn btn-sm btn-outline-primary" title="Edit Scan"><i class="bi bi-pencil"></i></a>
                                @endif
                                <form method="POST" action="{{ route('accounting.ai.destroy-scan', $scan) }}" class="d-inline" onsubmit="return confirm('Delete this scan record?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No scans yet. Upload an invoice to get started.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $scans->links() }}
        </div>
    </div>
</div>
@endsection
