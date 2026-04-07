@extends('layouts.app')
@section('title', 'Accounting Settings')
@section('page-title', 'Accounting Settings')

@section('content')
@include('accounting.partials.nav')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
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

<div class="row g-4">
    {{-- General Settings --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-gear me-1"></i>General Settings</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('accounting.settings.update') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="company" value="{{ $company ?? '' }}">
                    <div class="mb-3"><label class="form-label">Default Currency</label><input type="text" name="base_currency" class="form-control" value="{{ $settings->base_currency ?? 'MYR' }}"></div>
                    <h6 class="mt-3 mb-2" style="font-size:13px;">Document Number Prefixes</h6>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label small">Invoice</label><input type="text" name="invoice_prefix" class="form-control form-control-sm" value="{{ $settings->invoice_prefix ?? 'INV-' }}"></div>
                        <div class="col-md-6"><label class="form-label small">Bill</label><input type="text" name="bill_prefix" class="form-control form-control-sm" value="{{ $settings->bill_prefix ?? 'BILL-' }}"></div>
                        <div class="col-md-6"><label class="form-label small">Journal Entry</label><input type="text" name="journal_prefix" class="form-control form-control-sm" value="{{ $settings->journal_prefix ?? 'JE-' }}"></div>
                        <div class="col-md-6"><label class="form-label small">Payment</label><input type="text" name="payment_prefix" class="form-control form-control-sm" value="{{ $settings->payment_prefix ?? 'PAY-' }}"></div>
                        <div class="col-md-6"><label class="form-label small">Credit Note</label><input type="text" name="credit_note_prefix" class="form-control form-control-sm" value="{{ $settings->credit_note_prefix ?? 'CN-' }}"></div>
                        <div class="col-md-6"><label class="form-label small">Purchase Order</label><input type="text" name="po_prefix" class="form-control form-control-sm" value="{{ $settings->po_prefix ?? 'PO-' }}"></div>
                    </div>
                    <h6 class="mt-3 mb-2" style="font-size:13px;">AI Configuration</h6>
                    @php
                        $aiProvider = $settings->ai_provider ?? 'openai';
                        $aiModel    = $settings->ai_model    ?? 'gpt-4o';
                        $hasKey     = $settings->exists && ($settings->getAttributes()['ai_api_key'] ?? null);
                        $providers  = [
                            'openai'    => ['label' => 'OpenAI (ChatGPT)',    'placeholder' => 'sk-…',        'models' => ['gpt-4o','gpt-4o-mini','gpt-4-turbo','gpt-3.5-turbo']],
                            'anthropic' => ['label' => 'Anthropic (Claude)', 'placeholder' => 'sk-ant-…',    'models' => ['claude-opus-4-6','claude-sonnet-4-6','claude-haiku-4-5-20251001']],
                            'gemini'    => ['label' => 'Google Gemini',      'placeholder' => 'AIza…',       'models' => ['gemini-2.0-flash','gemini-1.5-pro','gemini-1.5-flash']],
                            'deepseek'  => ['label' => 'DeepSeek',           'placeholder' => 'sk-…',        'models' => ['deepseek-chat','deepseek-reasoner']],
                            'groq'      => ['label' => 'Groq',               'placeholder' => 'gsk_…',       'models' => ['llama-3.3-70b-versatile','mixtral-8x7b-32768','gemma2-9b-it']],
                            'local'     => ['label' => 'Local / Ollama',     'placeholder' => '(no key needed)', 'models' => ['llama3','mistral','phi3','gemma3']],
                        ];
                    @endphp

                    <div class="mb-2">
                        <label class="form-label small">AI Provider</label>
                        <select name="ai_provider" id="aiProvider" class="form-select form-select-sm" onchange="onProviderChange()">
                            @foreach($providers as $value => $meta)
                            <option value="{{ $value }}" {{ $aiProvider === $value ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2" id="apiKeyRow">
                        <label class="form-label small">AI API Key</label>
                        <input type="password" name="ai_api_key" id="aiApiKey" class="form-control form-control-sm" value=""
                               placeholder="{{ $hasKey ? '••••••••••••••••' : ($providers[$aiProvider]['placeholder'] ?? 'API key…') }}">
                        @if($hasKey)
                        <div class="form-text text-success" id="keyConfigured"><i class="bi bi-check-circle me-1"></i>API key is configured. Leave blank to keep current key.</div>
                        @endif
                    </div>

                    <div class="mb-2">
                        <label class="form-label small">AI Model</label>
                        <div class="input-group input-group-sm">
                            <select name="ai_model" id="aiModelSelect" class="form-select form-select-sm" onchange="document.getElementById('aiModelCustom').value=this.value">
                                @foreach($providers[$aiProvider]['models'] ?? [] as $m)
                                <option value="{{ $m }}" {{ $aiModel === $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                                <option value="_custom" {{ !in_array($aiModel, $providers[$aiProvider]['models'] ?? []) ? 'selected' : '' }}>Custom…</option>
                            </select>
                            <input type="text" id="aiModelCustom" name="ai_model" class="form-control form-control-sm"
                                   placeholder="e.g. gpt-4o-mini"
                                   value="{{ $aiModel }}"
                                   style="{{ in_array($aiModel, $providers[$aiProvider]['models'] ?? []) ? 'display:none' : '' }}">
                        </div>
                        <div class="form-text text-muted" id="aiModelHint" style="font-size:11px;"></div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm mt-3">Save Settings</button>

                    @push('scripts')
                    <script>
                    const AI_PROVIDERS = @json($providers);
                    const SAVED_PROVIDER = @json($aiProvider);
                    const SAVED_MODEL    = @json($aiModel);

                    const hints = {
                        openai:    'Requires an OpenAI API key from platform.openai.com',
                        anthropic: 'Requires an Anthropic API key from console.anthropic.com',
                        gemini:    'Requires a Google AI Studio key from aistudio.google.com',
                        deepseek:  'Requires a DeepSeek API key from platform.deepseek.com',
                        groq:      'Requires a Groq API key from console.groq.com',
                        local:     'No API key needed. Ensure Ollama is running on your server.',
                    };

                    function onProviderChange() {
                        const prov     = document.getElementById('aiProvider').value;
                        const meta     = AI_PROVIDERS[prov] || {};
                        const keyRow   = document.getElementById('apiKeyRow');
                        const keyInput = document.getElementById('aiApiKey');
                        const selModel = document.getElementById('aiModelSelect');
                        const custModel= document.getElementById('aiModelCustom');
                        const hint     = document.getElementById('aiModelHint');

                        // Show/hide key field for local provider
                        keyRow.style.display = (prov === 'local') ? 'none' : '';
                        if (prov !== 'local') {
                            keyInput.placeholder = meta.placeholder || 'API key…';
                        }

                        // Rebuild model dropdown
                        selModel.innerHTML = '';
                        (meta.models || []).forEach(m => {
                            const opt = new Option(m, m);
                            selModel.add(opt);
                        });
                        const customOpt = new Option('Custom…', '_custom');
                        selModel.add(customOpt);

                        // Select first model by default when provider changes
                        if (meta.models && meta.models.length) {
                            selModel.value = meta.models[0];
                            custModel.value = meta.models[0];
                            custModel.style.display = 'none';
                        }

                        hint.textContent = hints[prov] || '';
                    }

                    document.getElementById('aiModelSelect').addEventListener('change', function() {
                        const custModel = document.getElementById('aiModelCustom');
                        if (this.value === '_custom') {
                            custModel.style.display = '';
                            custModel.focus();
                        } else {
                            custModel.value = this.value;
                            custModel.style.display = 'none';
                        }
                    });

                    // Set initial hint
                    document.getElementById('aiModelHint').textContent = hints[SAVED_PROVIDER] || '';

                    // Show local provider key row correctly on load
                    if (SAVED_PROVIDER === 'local') {
                        document.getElementById('apiKeyRow').style.display = 'none';
                    }
                    </script>
                    @endpush
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        {{-- Fiscal Years --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-calendar3 me-1"></i>Fiscal Years</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('accounting.settings.store-fiscal-year') }}" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-4"><label class="form-label small">Name *</label><input type="text" name="name" class="form-control form-control-sm" placeholder="FY 2026" required></div>
                    <div class="col-md-3"><label class="form-label small">Start *</label><input type="date" name="start_date" class="form-control form-control-sm" required></div>
                    <div class="col-md-3"><label class="form-label small">End *</label><input type="date" name="end_date" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Add</button></div>
                </form>
                <table class="table table-sm mb-0" style="font-size:13px;">
                    <thead><tr><th>Name</th><th>Period</th><th>Status</th><th>Periods</th></tr></thead>
                    <tbody>
                    @foreach($fiscalYears ?? [] as $fy)
                        <tr>
                            <td>{{ $fy->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($fy->start_date)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($fy->end_date)->format('d/m/Y') }}</td>
                            <td><span class="badge bg-{{ $fy->status === 'open' ? 'success' : 'secondary' }}">{{ ucfirst($fy->status) }}</span></td>
                            <td>{{ $fy->periods_count ?? $fy->periods->count() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Currencies --}}
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-currency-exchange me-1"></i>Currencies</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('accounting.settings.store-currency') }}" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-2"><label class="form-label small">Code *</label><input type="text" name="code" class="form-control form-control-sm" maxlength="3" required></div>
                    <div class="col-md-4"><label class="form-label small">Name *</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><label class="form-label small">Symbol</label><input type="text" name="symbol" class="form-control form-control-sm"></div>
                    <div class="col-md-2"><label class="form-label small">Rate</label><input type="number" name="exchange_rate" class="form-control form-control-sm" step="0.000001" value="1"></div>
                    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Add</button></div>
                </form>
                <table class="table table-sm mb-0" style="font-size:13px;">
                    <thead><tr><th>Code</th><th>Name</th><th>Symbol</th><th>Rate</th></tr></thead>
                    <tbody>
                    @foreach($currencies ?? [] as $cur)
                        <tr><td>{{ $cur->code }}</td><td>{{ $cur->name }}</td><td>{{ $cur->symbol }}</td><td>{{ $cur->exchange_rate }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
