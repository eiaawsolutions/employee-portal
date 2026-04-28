{{-- ============================================================
     EIAAW Workforce — marketing chatbot + Talk-to-us form

     Hard-locked to ep.eiaawsolutions.com (apex marketing surface). Posts
     to /api/chatbot for assistant turns and /api/contact for form
     submissions. CSRF token comes from the marketing layout's
     <meta name="csrf-token"> header — no token, no submit.

     CSP-friendly: every <script> + <style> block carries the per-request
     nonce shared by SecurityHeaders middleware as $cspNonce.

     Include from layouts/marketing.blade.php right before @stack('scripts').
     ============================================================ --}}

<style nonce="{{ $cspNonce ?? '' }}">
    /* ── Floating launcher ─────────────────────────────────── */
    .ep-chat-launcher {
        position: fixed; right: clamp(16px, 3vw, 28px); bottom: clamp(16px, 3vw, 28px);
        z-index: 80;
        display: inline-flex; align-items: center; gap: 10px;
        padding: 12px 20px 12px 16px;
        background: var(--primary-dark, #11766A);
        color: #fff;
        border: 0; border-radius: 999px;
        font-family: var(--sans, Inter, system-ui, sans-serif);
        font-size: 14px; font-weight: 500;
        letter-spacing: -0.005em;
        box-shadow: 0 8px 28px rgba(17, 118, 106, 0.28), 0 1px 2px rgba(0,0,0,0.06);
        cursor: pointer;
        transition: transform 0.2s var(--ease, ease-out), box-shadow 0.2s var(--ease, ease-out);
    }
    .ep-chat-launcher:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(17, 118, 106, 0.34), 0 1px 2px rgba(0,0,0,0.08);
    }
    .ep-chat-launcher svg { width: 18px; height: 18px; }
    .ep-chat-launcher[hidden] { display: none !important; }

    /* ── Chat panel ────────────────────────────────────────── */
    .ep-chat-panel {
        position: fixed; right: clamp(16px, 3vw, 28px); bottom: clamp(16px, 3vw, 28px);
        z-index: 81;
        width: min(380px, calc(100vw - 32px));
        height: min(560px, calc(100vh - 80px));
        background: var(--bg, #faf7f2);
        border: 1px solid var(--line-soft, #e8e3da);
        border-radius: 18px;
        box-shadow: 0 18px 48px rgba(0,0,0,0.16), 0 2px 6px rgba(0,0,0,0.06);
        display: none;
        flex-direction: column;
        overflow: hidden;
        font-family: var(--sans, Inter, system-ui, sans-serif);
    }
    .ep-chat-panel.is-open { display: flex; }
    .ep-chat-head {
        background: var(--primary-dark, #11766A);
        color: #fff;
        padding: 16px 18px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .ep-chat-head strong {
        display: block; font-size: 14.5px; font-weight: 600; letter-spacing: -0.01em;
    }
    .ep-chat-head small {
        display: block; font-size: 11px; font-family: var(--mono, JetBrains Mono, ui-monospace, monospace);
        text-transform: uppercase; letter-spacing: 0.14em; opacity: 0.78;
        margin-top: 2px;
    }
    .ep-chat-close {
        background: transparent; border: 0; color: #fff;
        cursor: pointer; padding: 6px; line-height: 1;
        font-size: 22px; opacity: 0.85;
    }
    .ep-chat-close:hover { opacity: 1; }

    .ep-chat-msgs {
        flex: 1; overflow-y: auto;
        padding: 18px; display: flex; flex-direction: column; gap: 10px;
    }
    .ep-chat-bubble {
        max-width: 88%; padding: 10px 14px; border-radius: 14px;
        font-size: 14px; line-height: 1.5;
        white-space: pre-wrap; word-wrap: break-word;
    }
    .ep-chat-bubble.bot {
        align-self: flex-start;
        background: #fff;
        border: 1px solid var(--line-soft, #e8e3da);
        color: var(--ink, #1d1d1f);
    }
    .ep-chat-bubble.user {
        align-self: flex-end;
        background: var(--primary-dark, #11766A);
        color: #fff;
    }
    .ep-chat-bubble.typing {
        display: inline-flex; gap: 4px; padding: 14px;
    }
    .ep-chat-bubble.typing span {
        width: 6px; height: 6px; border-radius: 50%;
        background: var(--mute, #9b958a);
        animation: ep-chat-blink 1.2s infinite ease-in-out;
    }
    .ep-chat-bubble.typing span:nth-child(2) { animation-delay: 0.2s; }
    .ep-chat-bubble.typing span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes ep-chat-blink {
        0%, 80%, 100% { opacity: 0.3; }
        40% { opacity: 1; }
    }

    .ep-chat-quick {
        display: flex; flex-wrap: wrap; gap: 6px;
        padding: 0 18px 10px;
    }
    .ep-chat-chip {
        background: #fff;
        border: 1px solid var(--line-soft, #e8e3da);
        color: var(--ink-2, #4a4a52);
        padding: 6px 12px; border-radius: 999px;
        font-size: 12.5px; font-family: inherit; cursor: pointer;
        transition: border-color 0.15s, color 0.15s;
    }
    .ep-chat-chip:hover {
        border-color: var(--primary, #1d9085);
        color: var(--primary-dark, #11766A);
    }

    .ep-chat-form {
        display: flex; gap: 8px;
        padding: 14px 16px;
        border-top: 1px solid var(--line-soft, #e8e3da);
        background: #fff;
    }
    .ep-chat-form input[type="text"] {
        flex: 1; padding: 10px 14px;
        border: 1px solid var(--line-soft, #e8e3da);
        border-radius: 10px;
        font-family: inherit; font-size: 14px;
        background: var(--bg, #faf7f2);
        color: var(--ink, #1d1d1f);
    }
    .ep-chat-form input[type="text"]:focus {
        outline: none;
        border-color: var(--primary, #1d9085);
        background: #fff;
    }
    .ep-chat-form button[type="submit"] {
        background: var(--primary-dark, #11766A);
        color: #fff;
        border: 0; border-radius: 10px;
        padding: 0 14px; cursor: pointer;
        font-size: 16px;
    }
    .ep-chat-form button[type="submit"]:disabled {
        opacity: 0.5; cursor: not-allowed;
    }

    /* ── Talk-to-us modal ─────────────────────────────────── */
    .ep-modal {
        position: fixed; inset: 0; z-index: 90;
        background: rgba(20, 22, 24, 0.45);
        display: none; align-items: center; justify-content: center;
        padding: 20px;
        backdrop-filter: blur(4px);
    }
    .ep-modal.is-open { display: flex; }
    .ep-modal-panel {
        background: var(--bg, #faf7f2);
        border-radius: 18px;
        max-width: 520px; width: 100%;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
        padding: clamp(28px, 4vw, 40px);
        box-shadow: 0 24px 64px rgba(0,0,0,0.24);
        position: relative;
    }
    .ep-modal-close {
        position: absolute; top: 16px; right: 16px;
        background: transparent; border: 0; cursor: pointer;
        font-size: 24px; line-height: 1; color: var(--mute, #9b958a);
        padding: 4px 8px;
    }
    .ep-modal-eyebrow {
        font-family: var(--mono, JetBrains Mono, ui-monospace, monospace);
        font-size: 11px; text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--primary-dark, #11766A);
    }
    .ep-modal h3 {
        font-family: var(--sans, Inter, system-ui, sans-serif);
        font-weight: 500;
        font-size: clamp(22px, 2.6vw, 28px); line-height: 1.15;
        letter-spacing: -0.02em;
        margin: 8px 0 6px;
        color: var(--ink, #1d1d1f);
    }
    .ep-modal h3 em {
        font-family: var(--serif, "Instrument Serif", Georgia, serif);
        font-style: italic; font-weight: 400;
        color: var(--primary-dark, #11766A);
    }
    .ep-modal-lede {
        color: var(--ink-2, #4a4a52); font-size: 14.5px;
        margin: 0 0 22px;
    }
    .ep-modal-lede strong { color: var(--ink, #1d1d1f); font-weight: 600; }
    .ep-field {
        margin-bottom: 14px;
    }
    .ep-field label {
        display: block;
        font-size: 12.5px; font-weight: 500; color: var(--ink-2, #4a4a52);
        margin-bottom: 6px;
    }
    .ep-field label small { color: var(--mute, #9b958a); font-weight: 400; }
    .ep-field input,
    .ep-field textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--line-soft, #e8e3da);
        border-radius: 10px;
        font-family: inherit; font-size: 14px;
        background: #fff;
        color: var(--ink, #1d1d1f);
        resize: vertical;
    }
    .ep-field input:focus,
    .ep-field textarea:focus {
        outline: none;
        border-color: var(--primary, #1d9085);
    }
    .ep-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 480px) { .ep-row { grid-template-columns: 1fr; } }
    .ep-modal-err {
        color: #b14242;
        font-size: 13px; margin: 8px 0 12px;
        padding: 10px 12px;
        background: #fbe9e9; border-radius: 8px;
    }
    .ep-modal-actions {
        display: flex; gap: 10px; flex-wrap: wrap;
        margin-top: 8px;
    }
    .ep-modal .ep-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 11px 20px; border-radius: 999px;
        font-family: inherit; font-size: 14px; font-weight: 500;
        cursor: pointer; border: 1px solid transparent;
        transition: background 0.15s, color 0.15s, border-color 0.15s;
    }
    .ep-modal .ep-btn--primary {
        background: var(--primary-dark, #11766A);
        color: #fff;
    }
    .ep-modal .ep-btn--primary:hover { background: var(--primary, #1d9085); }
    .ep-modal .ep-btn--primary:disabled { opacity: 0.55; cursor: not-allowed; }
    .ep-modal .ep-btn--ghost {
        background: transparent;
        color: var(--ink-2, #4a4a52);
        border-color: var(--line-soft, #e8e3da);
    }
    .ep-modal .ep-btn--ghost:hover { color: var(--ink, #1d1d1f); }
</style>

{{-- Floating launcher --}}
<button type="button" class="ep-chat-launcher" id="ep-chat-launcher" aria-label="Open chat">
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"
              stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    Ask the assistant
</button>

{{-- Chat panel --}}
<aside class="ep-chat-panel" id="ep-chat-panel" role="dialog" aria-label="EIAAW Workforce assistant">
    <header class="ep-chat-head">
        <div>
            <strong>EIAAW Workforce</strong>
            <small>Locked to this site · always honest</small>
        </div>
        <button type="button" class="ep-chat-close" id="ep-chat-close" aria-label="Close chat">×</button>
    </header>
    <div class="ep-chat-msgs" id="ep-chat-msgs" role="log" aria-live="polite"></div>
    <div class="ep-chat-quick" id="ep-chat-quick"></div>
    <form class="ep-chat-form" id="ep-chat-form" autocomplete="off">
        <input type="text" id="ep-chat-input" maxlength="500"
               placeholder="Ask about features, pricing, security, or signup…"
               aria-label="Type your question">
        <button type="submit" aria-label="Send">→</button>
    </form>
</aside>

{{-- Talk-to-us modal --}}
<div class="ep-modal" id="ep-contact-modal" role="dialog" aria-modal="true" aria-labelledby="ep-contact-title">
    <div class="ep-modal-panel">
        <button type="button" class="ep-modal-close" data-ep-close aria-label="Close">×</button>

        <div data-view="form">
            <span class="ep-modal-eyebrow">Talk to us</span>
            <h3 id="ep-contact-title">Tell us what you&rsquo;re <em>working on.</em></h3>
            <p class="ep-modal-lede">We read every message. Expect a reply within one working day from <strong>{{ config('eiaaw.sales_email', 'sales@eiaawsolutions.com') }}</strong>.</p>

            <div class="ep-field">
                <label for="ep-c-name">Name</label>
                <input id="ep-c-name" type="text" autocomplete="name" maxlength="80" required>
            </div>
            <div class="ep-row">
                <div class="ep-field">
                    <label for="ep-c-email">Work email</label>
                    <input id="ep-c-email" type="email" autocomplete="email" maxlength="120" required>
                </div>
                <div class="ep-field">
                    <label for="ep-c-phone">Phone <small>(optional)</small></label>
                    <input id="ep-c-phone" type="tel" autocomplete="tel" maxlength="32">
                </div>
            </div>
            <div class="ep-field">
                <label for="ep-c-company">Company <small>(optional)</small></label>
                <input id="ep-c-company" type="text" autocomplete="organization" maxlength="120">
            </div>
            <div class="ep-field">
                <label for="ep-c-message">What would you like to explore?</label>
                <textarea id="ep-c-message" rows="4" maxlength="2000" required
                          placeholder="A few lines about your team, your goals, and the outcome you&rsquo;re after."></textarea>
            </div>

            <div class="ep-modal-err" id="ep-c-error" hidden></div>

            <div class="ep-modal-actions">
                <button type="button" class="ep-btn ep-btn--primary" id="ep-c-submit">Send enquiry →</button>
                <a href="{{ route('marketing.pricing') }}" class="ep-btn ep-btn--ghost">Or just start the trial</a>
            </div>
        </div>

        <div data-view="success" hidden>
            <span class="ep-modal-eyebrow">Message sent</span>
            <h3>Thanks — we&rsquo;ll <em>be in touch.</em></h3>
            <p class="ep-modal-lede">Your enquiry just landed at <strong>{{ config('eiaaw.sales_email', 'sales@eiaawsolutions.com') }}</strong>. While you wait, you&rsquo;re welcome to keep exploring or start the 14-day Growth trial — no credit card.</p>
            <div class="ep-modal-actions">
                <a href="{{ route('marketing.pricing') }}" class="ep-btn ep-btn--primary">Start 14-day trial →</a>
                <button type="button" class="ep-btn ep-btn--ghost" data-ep-close>Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    'use strict';

    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrf = tokenMeta ? tokenMeta.getAttribute('content') : '';

    const launcher = document.getElementById('ep-chat-launcher');
    const panel    = document.getElementById('ep-chat-panel');
    const closeBtn = document.getElementById('ep-chat-close');
    const msgsEl   = document.getElementById('ep-chat-msgs');
    const quickEl  = document.getElementById('ep-chat-quick');
    const form     = document.getElementById('ep-chat-form');
    const input    = document.getElementById('ep-chat-input');
    const modal    = document.getElementById('ep-contact-modal');

    if (!launcher || !panel || !modal) return;

    let sessionId = null;
    let isOpen = false;

    function openPanel() {
        panel.classList.add('is-open');
        launcher.hidden = true;
        isOpen = true;
        if (msgsEl.children.length === 0) seedGreeting();
        setTimeout(() => input.focus(), 60);
    }

    function closePanel() {
        panel.classList.remove('is-open');
        launcher.hidden = false;
        isOpen = false;
    }

    launcher.addEventListener('click', openPanel);
    closeBtn.addEventListener('click', closePanel);

    function addBubble(text, who) {
        const el = document.createElement('div');
        el.className = 'ep-chat-bubble ' + who;
        el.textContent = text;
        msgsEl.appendChild(el);
        msgsEl.scrollTop = msgsEl.scrollHeight;
        return el;
    }

    function addTyping() {
        const el = document.createElement('div');
        el.className = 'ep-chat-bubble bot typing';
        el.id = 'ep-chat-typing';
        el.innerHTML = '<span></span><span></span><span></span>';
        msgsEl.appendChild(el);
        msgsEl.scrollTop = msgsEl.scrollHeight;
    }
    function removeTyping() {
        const el = document.getElementById('ep-chat-typing');
        if (el) el.remove();
    }

    function setQuickReplies(items) {
        quickEl.innerHTML = '';
        items.forEach(it => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'ep-chat-chip';
            b.textContent = it.label;
            b.addEventListener('click', () => {
                quickEl.innerHTML = '';
                if (it.action === 'talk') openContactModal();
                else if (it.action === 'trial') window.location.href = '{{ route('marketing.pricing') }}';
                else if (it.send) sendMessage(it.send);
            });
            quickEl.appendChild(b);
        });
    }

    function seedGreeting() {
        addBubble("Hi — I'm the EIAAW Workforce assistant. I can answer questions about features, pricing, security, and how to start the 14-day trial. For anything else, the Talk-to-us form is the fastest path. What brings you here?", 'bot');
        setQuickReplies([
            { label: 'How much does it cost?', send: 'How much does EIAAW Workforce cost?' },
            { label: 'Start the trial',         action: 'trial' },
            { label: 'Talk to us',               action: 'talk' },
        ]);
    }

    async function sendMessage(text) {
        if (!text || !text.trim()) return;
        addBubble(text, 'user');
        addTyping();

        try {
            const res = await fetch('{{ route('marketing.chatbot') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ message: text, session_id: sessionId }),
            });
            const data = await res.json().catch(() => ({}));
            removeTyping();

            if (!res.ok && data.error) {
                addBubble(data.error, 'bot');
            } else {
                if (data.session_id) sessionId = data.session_id;
                addBubble(data.response || "I'm having trouble answering that one — please use the Talk-to-us form.", 'bot');
            }

            setQuickReplies([
                { label: 'Talk to us',       action: 'talk' },
                { label: 'Start the trial',  action: 'trial' },
            ]);
        } catch (e) {
            removeTyping();
            addBubble("I can't reach our server right now. Please use the Talk-to-us form — our team replies within one working day.", 'bot');
            setQuickReplies([{ label: 'Talk to us', action: 'talk' }]);
        }
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        const v = input.value.trim();
        if (!v) return;
        input.value = '';
        sendMessage(v);
    });

    // ── Contact modal ──────────────────────────────────────
    function openContactModal(prefill) {
        modal.querySelector('[data-view="form"]').hidden = false;
        modal.querySelector('[data-view="success"]').hidden = true;
        modal.querySelector('#ep-c-error').hidden = true;
        if (prefill && prefill.message) {
            modal.querySelector('#ep-c-message').value = prefill.message;
        }
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => modal.querySelector('#ep-c-name').focus(), 40);
    }
    function closeContactModal() {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }
    window.epOpenContactModal = openContactModal;
    window.epCloseContactModal = closeContactModal;

    modal.addEventListener('click', function (ev) {
        if (ev.target === modal) closeContactModal();
        if (ev.target.closest('[data-ep-close]')) closeContactModal();
    });

    // Any element marked with data-ep-action="talk" opens the modal
    document.addEventListener('click', function (ev) {
        const t = ev.target.closest('[data-ep-action="talk"]');
        if (t) { ev.preventDefault(); openContactModal(); }
    });

    document.getElementById('ep-c-submit').addEventListener('click', async function () {
        const get = (id) => modal.querySelector('#' + id).value.trim();
        const name = get('ep-c-name');
        const email = get('ep-c-email');
        const phone = get('ep-c-phone');
        const company = get('ep-c-company');
        const message = get('ep-c-message');
        const errEl = modal.querySelector('#ep-c-error');
        const btn = this;

        if (!name || !email || !message) {
            errEl.textContent = 'Please fill in your name, email, and message.';
            errEl.hidden = false;
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errEl.textContent = 'Please enter a valid email address.';
            errEl.hidden = false;
            return;
        }

        errEl.hidden = true;
        btn.disabled = true;
        const origText = btn.textContent;
        btn.textContent = 'Sending…';

        try {
            const res = await fetch('{{ route('marketing.contact') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    name: name, email: email, phone: phone, company: company,
                    message: message, source: isOpen ? 'chatbot' : 'landing-form',
                }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.error) {
                errEl.textContent = data.error || 'Something went wrong. Please try again or email us directly.';
                errEl.hidden = false;
                btn.disabled = false;
                btn.textContent = origText;
                return;
            }
            modal.querySelector('[data-view="form"]').hidden = true;
            modal.querySelector('[data-view="success"]').hidden = false;
            btn.disabled = false;
            btn.textContent = origText;
        } catch (e) {
            errEl.textContent = 'Connection issue. You can also email {{ config('eiaaw.sales_email', 'sales@eiaawsolutions.com') }} directly.';
            errEl.hidden = false;
            btn.disabled = false;
            btn.textContent = origText;
        }
    });
})();
</script>
@endpush
