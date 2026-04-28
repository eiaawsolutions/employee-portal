{{-- ============================================================
     EIAAW Workforce — voice agent launcher

     Reuses the existing Sales-marketing-agent Retell web-call
     infrastructure at sa.eiaawsolutions.com/api/voice/public-session.
     One Retell agent serves all three EIAAW marketing surfaces;
     the prompt branches on site_scope. With source =
     "ep.eiaawsolutions.com", the agent locks to Workforce.

     Cost: web call only (browser WebRTC). No Twilio. No phone
     number. Same Retell minutes allocation Sales Agent already
     uses — zero new cost.

     Include from layouts/marketing.blade.php right before the
     existing chat partial; both can coexist. CSP-nonced.
     ============================================================ --}}

<style nonce="{{ $cspNonce ?? '' }}">
    /* The voice launcher sits just to the LEFT of the chat launcher
       so both can live in the bottom-right corner without overlap.
       On small screens it stacks above. */
    .ep-voice-launcher {
        position: fixed;
        right: clamp(176px, calc(3vw + 168px), 220px); /* leaves room for the chat pill */
        bottom: clamp(16px, 3vw, 28px);
        z-index: 80;
        display: inline-flex; align-items: center; gap: 10px;
        padding: 12px 18px 12px 14px;
        background: #fff;
        color: var(--primary-dark, #11766A);
        border: 1px solid var(--primary-dark, #11766A);
        border-radius: 999px;
        font-family: var(--sans, Inter, system-ui, sans-serif);
        font-size: 14px; font-weight: 500;
        letter-spacing: -0.005em;
        box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        cursor: pointer;
        transition: transform 0.18s var(--ease, ease-out), background 0.18s, color 0.18s;
    }
    .ep-voice-launcher:hover {
        transform: translateY(-2px);
        background: var(--primary-dark, #11766A);
        color: #fff;
    }
    .ep-voice-launcher svg { width: 16px; height: 16px; }
    .ep-voice-launcher[hidden] { display: none !important; }

    @media (max-width: 720px) {
        /* Stack: voice button sits ABOVE the chat pill */
        .ep-voice-launcher {
            right: clamp(16px, 3vw, 28px);
            bottom: calc(clamp(16px, 3vw, 28px) + 56px);
        }
    }

    .ep-voice-status {
        position: fixed;
        right: clamp(16px, 3vw, 28px);
        bottom: calc(clamp(16px, 3vw, 28px) + 56px);
        z-index: 81;
        max-width: 320px;
        padding: 12px 16px;
        background: var(--bg, #faf7f2);
        border: 1px solid var(--line-soft, #e8e3da);
        border-radius: 14px;
        box-shadow: 0 12px 32px rgba(0,0,0,0.12);
        font-family: var(--sans, Inter, system-ui, sans-serif);
        font-size: 13px; line-height: 1.45;
        color: var(--ink-2, #4a4a52);
        display: none;
    }
    .ep-voice-status.is-visible { display: block; }
    .ep-voice-status strong { color: var(--ink, #1d1d1f); display: block; margin-bottom: 4px; }
    .ep-voice-status a { color: var(--primary-dark, #11766A); text-decoration: underline; }
</style>

<button type="button" class="ep-voice-launcher" id="ep-voice-launcher" aria-label="Talk to the assistant by voice">
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M12 2a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"
              stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M19 10v1a7 7 0 0 1-14 0v-1M12 18v4M8 22h8"
              stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    Talk by voice
</button>

<div class="ep-voice-status" id="ep-voice-status" role="status" aria-live="polite"></div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    'use strict';

    const VOICE_API = 'https://sa.eiaawsolutions.com/api/voice/public-session';
    const SOURCE   = 'ep.eiaawsolutions.com';

    const launcher = document.getElementById('ep-voice-launcher');
    const status   = document.getElementById('ep-voice-status');
    if (!launcher || !status) return;

    function setStatus(html, persist) {
        status.innerHTML = html;
        status.classList.add('is-visible');
        if (!persist) {
            setTimeout(function () { status.classList.remove('is-visible'); }, 6000);
        }
    }

    async function startCall() {
        launcher.disabled = true;
        const orig = launcher.innerHTML;
        launcher.innerHTML = 'Connecting…';

        try {
            const res = await fetch(VOICE_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                // No credentials needed; the public-session endpoint is
                // unauthenticated by design and returns a one-shot tokenised callUrl.
                body: JSON.stringify({ source: SOURCE }),
            });

            const data = await res.json().catch(function () { return {}; });

            if (data && data.callUrl) {
                // Open the Retell-hosted call.html in a new tab. The page
                // handles mic permissions and the WebRTC handshake.
                window.open(data.callUrl, '_blank', 'noopener');
                setStatus(
                    '<strong>Opening voice call in a new tab</strong>'
                    + 'Allow microphone access when prompted. If the tab didn\'t open, '
                    + '<a href="#" data-ep-action="talk">use the Talk-to-us form instead</a>.',
                    false
                );
            } else {
                // Surface the server error verbatim so the visitor can see why
                // (rate limited, voice not configured, etc.) — but always offer
                // the Talk-to-us fallback so we never lose a lead.
                const msg = (data && (data.error || data.message)) || 'Voice agent is unavailable right now.';
                setStatus(
                    '<strong>' + msg + '</strong>'
                    + 'No worries — <a href="#" data-ep-action="talk">click here for the Talk-to-us form</a> '
                    + 'and our team will reply within one working day.',
                    true
                );
            }
        } catch (e) {
            setStatus(
                '<strong>Couldn\'t reach the voice agent.</strong>'
                + 'Please <a href="#" data-ep-action="talk">use the Talk-to-us form</a> — '
                + 'our team will reply within one working day.',
                true
            );
        } finally {
            launcher.disabled = false;
            launcher.innerHTML = orig;
        }
    }

    launcher.addEventListener('click', startCall);
})();
</script>
@endpush
