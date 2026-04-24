{{--
    AI Assistant drawer (read-only v1).
    Floating button bottom-right. Click opens a slide-in drawer with a
    prompt textarea + chat transcript. Plan-gated at the route level.

    Conditions under which it renders:
      - User is authenticated
      - Current tenant is bound
      - Tenant plan includes 'ai.basic'
      - User is not on the upgrade-required page (avoid recursion)
--}}
@php
    $aiTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
    $aiEnabled = auth()->check()
        && $aiTenant
        && $aiTenant->hasFeature('ai.basic')
        && !request()->routeIs('upgrade-required');
@endphp

@if($aiEnabled)

<style nonce="{{ $cspNonce ?? '' }}">
    .ai-fab {
        position: fixed; right: 22px; bottom: 22px;
        width: 56px; height: 56px; border-radius: 50%;
        background: var(--ink, #0F1A1D); color: var(--bg, #FAF7F2);
        border: 0; cursor: pointer;
        box-shadow: 0 10px 30px rgba(15,26,29,0.25);
        display: inline-flex; align-items: center; justify-content: center;
        z-index: 3000;
        transition: transform 0.25s var(--ease, cubic-bezier(.2,.7,.2,1)), background 0.25s var(--ease, cubic-bezier(.2,.7,.2,1));
    }
    .ai-fab:hover { transform: scale(1.05); background: var(--primary-dark, #11766A); }
    .ai-fab svg { width: 24px; height: 24px; }

    .ai-drawer-overlay {
        position: fixed; inset: 0;
        background: rgba(15,26,29,0.28);
        backdrop-filter: blur(2px);
        z-index: 3001;
        opacity: 0; pointer-events: none;
        transition: opacity 0.2s var(--ease, cubic-bezier(.2,.7,.2,1));
    }
    .ai-drawer-overlay.open { opacity: 1; pointer-events: auto; }

    .ai-drawer {
        position: fixed; top: 0; right: 0; bottom: 0;
        width: min(480px, 100vw);
        background: var(--bg, #FAF7F2);
        border-left: 1px solid var(--line-soft, #E8DFCC);
        box-shadow: -20px 0 60px -20px rgba(15,26,29,0.2);
        z-index: 3002;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(.2,.7,.2,1);
        display: flex; flex-direction: column;
    }
    .ai-drawer.open { transform: translateX(0); }

    .ai-drawer-head {
        padding: 18px 22px;
        border-bottom: 1px solid var(--line-soft, #E8DFCC);
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        background: var(--surface, #FFFFFF);
    }
    .ai-drawer-title {
        font-family: var(--sans, sans-serif); font-weight: 500; font-size: 15px;
        color: var(--ink, #0F1A1D); letter-spacing: -0.01em;
        display: flex; align-items: center; gap: 10px;
    }
    .ai-drawer-title::before {
        content: ''; width: 8px; height: 8px; border-radius: 50%;
        background: var(--primary, #1FA896); box-shadow: 0 0 8px var(--primary, #1FA896);
    }
    .ai-drawer-sub {
        font-family: var(--mono, monospace); font-size: 10.5px;
        text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--mute, #6B7A7F); margin-top: 4px;
    }
    .ai-drawer-close {
        background: transparent; border: 0; cursor: pointer;
        font-size: 22px; color: var(--mute, #6B7A7F); padding: 4px 10px;
    }
    .ai-drawer-close:hover { color: var(--ink, #0F1A1D); }

    .ai-messages {
        flex: 1; overflow-y: auto;
        padding: 20px 22px; background: var(--bg, #FAF7F2);
        display: flex; flex-direction: column; gap: 14px;
    }
    .ai-empty {
        margin: auto; text-align: center;
        font-family: var(--sans, sans-serif); color: var(--mute, #6B7A7F);
        padding: 40px 16px; line-height: 1.6; font-size: 13.5px;
    }
    .ai-empty strong { color: var(--ink, #0F1A1D); font-weight: 500; }

    .ai-msg {
        max-width: 86%;
        padding: 11px 15px; border-radius: 14px;
        font-family: var(--sans, sans-serif); font-size: 14px; line-height: 1.55;
        white-space: pre-wrap; word-wrap: break-word;
    }
    .ai-msg-user {
        align-self: flex-end;
        background: var(--ink, #0F1A1D); color: var(--bg, #FAF7F2);
    }
    .ai-msg-assistant {
        align-self: flex-start;
        background: var(--surface, #FFFFFF);
        border: 1px solid var(--line-soft, #E8DFCC);
        color: var(--ink, #0F1A1D);
    }
    .ai-msg-assistant-meta {
        font-family: var(--mono, monospace); font-size: 10.5px;
        color: var(--mute, #6B7A7F); margin-top: 8px;
        letter-spacing: 0.06em;
    }
    .ai-msg-error {
        align-self: flex-start; max-width: 92%;
        padding: 10px 14px; border-radius: 12px;
        background: rgba(180,65,43,0.08); color: var(--danger, #B4412B);
        border: 1px solid rgba(180,65,43,0.18);
        font-size: 13px; line-height: 1.5;
    }

    .ai-thinking {
        align-self: flex-start;
        padding: 10px 14px; border-radius: 12px;
        background: var(--primary-tint, #E5F4F1); color: var(--primary-dark, #11766A);
        font-family: var(--mono, monospace); font-size: 11.5px; letter-spacing: 0.08em;
    }
    .ai-thinking::after {
        content: ''; display: inline-block; width: 1px;
        animation: ai-dots 1.2s infinite;
    }
    @keyframes ai-dots { 0%{content:''}33%{content:'.'}66%{content:'..'}100%{content:'...'} }

    .ai-form {
        padding: 14px 18px 18px;
        border-top: 1px solid var(--line-soft, #E8DFCC);
        background: var(--surface, #FFFFFF);
        display: flex; gap: 10px; align-items: flex-end;
    }
    .ai-form textarea {
        flex: 1; resize: none;
        min-height: 40px; max-height: 120px;
        padding: 10px 14px;
        font-family: var(--sans, sans-serif); font-size: 14px; line-height: 1.4;
        border: 1px solid var(--line, #D9CFBC); border-radius: 12px;
        background: var(--bg, #FAF7F2); color: var(--ink, #0F1A1D);
        outline: none; transition: border-color 0.18s var(--ease, cubic-bezier(.2,.7,.2,1)), box-shadow 0.18s var(--ease, cubic-bezier(.2,.7,.2,1));
    }
    .ai-form textarea:focus {
        border-color: var(--primary, #1FA896);
        box-shadow: 0 0 0 3px rgba(31,168,150,0.12);
    }
    .ai-form button {
        padding: 10px 14px;
        background: var(--ink, #0F1A1D); color: var(--bg, #FAF7F2);
        border: 0; border-radius: 999px; cursor: pointer;
        font-family: var(--sans, sans-serif); font-size: 13px; font-weight: 500;
        transition: background 0.25s var(--ease, cubic-bezier(.2,.7,.2,1));
    }
    .ai-form button:hover:not(:disabled) { background: var(--primary-dark, #11766A); }
    .ai-form button:disabled { opacity: 0.5; cursor: not-allowed; }

    .ai-footer {
        padding: 8px 18px 14px;
        font-family: var(--mono, monospace); font-size: 10px;
        text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--mute, #6B7A7F);
        display: flex; justify-content: space-between; gap: 10px;
        background: var(--surface, #FFFFFF);
        border-top: 1px solid var(--line-soft, #E8DFCC);
    }
</style>

<button class="ai-fab" id="ai-fab" aria-label="Open AI assistant">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 3a9 9 0 0 1 9 9v4.5a3 3 0 0 1-3 3h-2.5L12 23l-3.5-3.5H6a3 3 0 0 1-3-3V12a9 9 0 0 1 9-9z"></path>
        <circle cx="8.5" cy="12" r="1"></circle>
        <circle cx="12" cy="12" r="1"></circle>
        <circle cx="15.5" cy="12" r="1"></circle>
    </svg>
</button>

<div class="ai-drawer-overlay" id="ai-drawer-overlay"></div>

<aside class="ai-drawer" id="ai-drawer" aria-hidden="true" aria-label="AI assistant">
    <div class="ai-drawer-head">
        <div>
            <div class="ai-drawer-title">Workforce Assistant</div>
            <div class="ai-drawer-sub">Read-only · Grounded on your data</div>
        </div>
        <button class="ai-drawer-close" id="ai-drawer-close" aria-label="Close">×</button>
    </div>

    <div class="ai-messages" id="ai-messages" role="log" aria-live="polite">
        <div class="ai-empty" id="ai-empty">
            <strong>Ask anything about your workspace.</strong><br>
            "Who's OOO next week?", "Summarise pending claims", "Explain this month's payslip delta."
        </div>
    </div>

    <form class="ai-form" id="ai-form" autocomplete="off">
        <textarea id="ai-prompt" placeholder="Ask the assistant…" rows="1" required></textarea>
        <button type="submit" id="ai-submit">Ask</button>
    </form>

    <div class="ai-footer">
        <span id="ai-budget">Budget · —</span>
        <span id="ai-model">Model · —</span>
    </div>
</aside>

<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var fab = document.getElementById('ai-fab');
    var drawer = document.getElementById('ai-drawer');
    var overlay = document.getElementById('ai-drawer-overlay');
    var closeBtn = document.getElementById('ai-drawer-close');
    var form = document.getElementById('ai-form');
    var prompt = document.getElementById('ai-prompt');
    var submit = document.getElementById('ai-submit');
    var messages = document.getElementById('ai-messages');
    var empty = document.getElementById('ai-empty');
    var budgetEl = document.getElementById('ai-budget');
    var modelEl = document.getElementById('ai-model');

    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var askUrl = '{{ route('ai.ask') }}';

    function openDrawer() {
        drawer.classList.add('open');
        overlay.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        setTimeout(function () { prompt.focus(); }, 150);
    }
    function closeDrawer() {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
    }

    fab.addEventListener('click', openDrawer);
    overlay.addEventListener('click', closeDrawer);
    closeBtn.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer.classList.contains('open')) closeDrawer();
    });

    // Textarea auto-grow + submit-on-Enter
    prompt.addEventListener('input', function () {
        prompt.style.height = 'auto';
        prompt.style.height = Math.min(prompt.scrollHeight, 120) + 'px';
    });
    prompt.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    function appendUser(text) {
        if (empty) { empty.remove(); }
        var div = document.createElement('div');
        div.className = 'ai-msg ai-msg-user';
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }
    function appendAssistant(text, meta, sources, refused, refusalReason) {
        var div = document.createElement('div');
        div.className = 'ai-msg ai-msg-assistant';

        // Primary answer text — ALWAYS rendered as textContent, never innerHTML.
        var body = document.createElement('div');
        if (refused) {
            body.textContent = text || refusalReason || 'I can\'t help with that.';
            body.style.color = 'rgba(180,65,43,0.9)';
        } else {
            body.textContent = text;
        }
        div.appendChild(body);

        // Sources — rendered as a list of pills below the answer, text-only.
        if (sources && sources.length) {
            var src = document.createElement('div');
            src.className = 'ai-msg-sources';
            src.style.cssText = 'margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px;';
            var label = document.createElement('span');
            label.textContent = 'Sources:';
            label.style.cssText = 'font-family: var(--mono, monospace); font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--mute, #6B7A7F); margin-right: 4px;';
            src.appendChild(label);
            sources.forEach(function (s) {
                var pill = document.createElement('span');
                pill.textContent = s;
                pill.style.cssText = 'padding: 2px 8px; border-radius: 999px; background: var(--primary-tint, #E5F4F1); color: var(--primary-dark, #11766A); font-family: var(--mono, monospace); font-size: 10.5px; letter-spacing: 0.04em;';
                src.appendChild(pill);
            });
            div.appendChild(src);
        }

        if (meta) {
            var m = document.createElement('div');
            m.className = 'ai-msg-assistant-meta';
            m.textContent = meta.model + ' · ' + meta.latency_ms + 'ms · $' + (meta.cost_usd || 0).toFixed(4);
            div.appendChild(m);
        }
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }
    function appendError(text) {
        var div = document.createElement('div');
        div.className = 'ai-msg-error';
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }
    function appendThinking() {
        var div = document.createElement('div');
        div.className = 'ai-thinking';
        div.id = 'ai-thinking';
        div.textContent = 'Thinking';
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }
    function removeThinking() {
        var t = document.getElementById('ai-thinking');
        if (t) t.remove();
    }
    function updateMeta(meta) {
        if (!meta) return;
        budgetEl.textContent = 'Budget · $' + (meta.monthly_spend_usd || 0).toFixed(2) + ' / $' + (meta.monthly_budget_usd || 0).toFixed(0);
        modelEl.textContent = 'Model · ' + (meta.model || '—').replace(/claude-|-20\d{6}/g, '');
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = prompt.value.trim();
        if (!text) return;

        appendUser(text);
        prompt.value = '';
        prompt.style.height = 'auto';
        submit.disabled = true;
        appendThinking();

        fetch(askUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ prompt: text }),
            credentials: 'same-origin',
        })
        .then(function (r) { return r.json().then(function (body) { return { status: r.status, body: body }; }); })
        .then(function (res) {
            removeThinking();
            if (res.status >= 400) {
                appendError(res.body.message || 'AI request failed.');
                return;
            }
            appendAssistant(
                res.body.answer,
                res.body.meta,
                res.body.sources || [],
                !!res.body.refused,
                res.body.refusal_reason || ''
            );
            updateMeta(res.body.meta);
        })
        .catch(function () {
            removeThinking();
            appendError('Network error — please retry.');
        })
        .finally(function () {
            submit.disabled = false;
            prompt.focus();
        });
    });
})();
</script>

@endif
