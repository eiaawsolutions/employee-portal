@extends('layouts.app')

@section('title', 'Platform integrations')
@section('page-title', 'Platform integrations')

@section('content')
<style>
    .int-wrap { max-width: 880px; margin: 32px auto; padding: clamp(28px, 4vw, 48px); background: var(--surface, #FFFFFF); border: 1px solid var(--line-soft, #E8DFCC); border-radius: 20px; }
    .int-wrap h1 { font-family: var(--sans, 'Inter', sans-serif); font-weight: 500; font-size: clamp(24px, 2.6vw, 32px); letter-spacing: -0.02em; margin: 14px 0 6px; }
    .int-wrap h1 em { font-family: var(--serif, 'Instrument Serif', serif); font-style: italic; font-weight: 400; color: var(--primary-dark, #11766A); }
    .int-lede { color: var(--ink-2, #4A5358); font-size: 15px; line-height: 1.55; max-width: 640px; margin: 0 0 32px; }
    .int-pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: rgba(17,118,106,0.08); color: var(--primary-dark, #11766A); font-size: 11px; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 14px; }
    .int-section { border: 1px solid var(--line-soft, #E8DFCC); border-radius: 16px; padding: 24px 28px; margin-bottom: 22px; background: var(--bg, #FAF7F2); }
    .int-section h2 { font-size: 17px; font-weight: 600; margin: 0 0 4px; color: var(--ink, #0F1A1D); }
    .int-section .desc { font-size: 13px; color: var(--ink-2, #4A5358); margin: 0 0 18px; line-height: 1.5; }
    .int-field { margin-bottom: 16px; }
    .int-field label { display: block; font-size: 13px; font-weight: 500; color: var(--ink, #0F1A1D); margin-bottom: 6px; }
    .int-field .help { display: block; font-size: 12px; color: var(--ink-2, #4A5358); margin-top: 4px; line-height: 1.45; }
    .int-field input { width: 100%; padding: 10px 12px; border: 1px solid var(--line, #D9CFBC); border-radius: 10px; font-family: var(--mono, ui-monospace, 'JetBrains Mono', monospace); font-size: 13px; background: var(--surface, #FFFFFF); color: var(--ink, #0F1A1D); }
    .int-field input:focus { outline: none; border-color: var(--primary-dark, #11766A); box-shadow: 0 0 0 3px rgba(17,118,106,0.12); }
    .int-existing { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; color: var(--ink-2, #4A5358); margin-top: 4px; }
    .int-existing code { background: var(--surface, #FFFFFF); border: 1px solid var(--line, #D9CFBC); padding: 2px 8px; border-radius: 6px; font-family: var(--mono, monospace); }
    .int-clear { background: none; border: none; color: var(--danger, #B4412B); font-size: 12px; cursor: pointer; text-decoration: underline; padding: 0; }
    .int-actions { display: flex; gap: 12px; align-items: center; padding: 18px 0 0; border-top: 1px solid var(--line-soft, #E8DFCC); margin-top: 28px; }
    .int-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border-radius: 999px; border: none; cursor: pointer; font-family: var(--sans, sans-serif); font-size: 14px; font-weight: 500; }
    .int-btn--primary { background: var(--ink, #0F1A1D); color: var(--bg, #FAF7F2); }
    .int-btn--primary:hover { background: var(--primary-dark, #11766A); }
    .int-status { padding: 12px 16px; border-radius: 12px; background: rgba(17,118,106,0.08); color: var(--primary-dark, #11766A); font-size: 13px; margin-bottom: 24px; }
</style>

<div class="int-wrap">
    <span class="int-pill">Platform · EIAAW staff only</span>
    <h1>API keys &amp; <em>integrations</em></h1>
    <p class="int-lede">
        Operational credentials for EIAAW Workforce. Values are AES-256 encrypted at
        rest; the form never echoes a stored secret. Submit blank to leave a value
        unchanged. Use the clear link to remove a key entirely.
    </p>

    @if (session('status'))
        <div class="int-status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="int-status" style="background: rgba(180,65,43,0.08); color: var(--danger, #B4412B);">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('superadmin.integrations.update') }}">
        @csrf

        @foreach ($catalog as $section)
            <section class="int-section">
                <h2>{{ $section['section'] }}</h2>
                @if (! empty($section['description']))
                    <p class="desc">{{ $section['description'] }}</p>
                @endif

                @foreach ($section['fields'] as $field)
                    @php $row = $rows[$field['key']] ?? null; @endphp
                    <div class="int-field">
                        <label for="f-{{ $field['key'] }}">{{ $field['label'] }}</label>
                        <input
                            type="{{ ($field['is_secret'] ?? true) ? 'password' : 'text' }}"
                            id="f-{{ $field['key'] }}"
                            name="{{ $field['key'] }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            autocomplete="off"
                            spellcheck="false"
                        >
                        @if ($row && $row->value)
                            <span class="int-existing">
                                Current: <code>{{ $row->maskedValue() }}</code>
                                <span aria-hidden="true">·</span>
                                <button type="submit"
                                    form="clear-{{ $field['key'] }}"
                                    class="int-clear">Clear</button>
                            </span>
                        @endif
                        @if (! empty($field['help']))
                            <span class="help">{{ $field['help'] }}</span>
                        @endif
                    </div>
                @endforeach
            </section>
        @endforeach

        <div class="int-actions">
            <button type="submit" class="int-btn int-btn--primary">Save changes</button>
            <span style="font-size:12px; color:var(--ink-2);">Audit-logged on save.</span>
        </div>
    </form>

    @foreach ($catalog as $section)
        @foreach ($section['fields'] as $field)
            @if (($rows[$field['key']] ?? null) && $rows[$field['key']]->value)
                <form id="clear-{{ $field['key'] }}"
                      method="POST"
                      action="{{ route('superadmin.integrations.delete', $field['key']) }}"
                      style="display:none">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        @endforeach
    @endforeach
</div>
@endsection
