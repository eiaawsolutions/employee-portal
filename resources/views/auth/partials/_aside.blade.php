{{--
    Shared auth-page left aside (lockup + serif quote + meta).
    Pages can override the quote via the $quote variable.
--}}
<aside class="auth-aside">
    <a href="{{ url('/') }}" class="eiaaw-lockup">
        <img src="{{ asset('brand/shield.png') }}" alt="EIAAW Workforce">
        <span class="eiaaw-lockup-text">
            <strong>EIAAW Workforce</strong>
            <small>AI &middot; Human Partnerships</small>
        </span>
    </a>

    <div class="auth-aside-quote">
        {!! $quote ?? 'The HR platform built for people who replaced spreadsheets with <em>real systems</em> — and want to do the same with AI.' !!}
    </div>

    <div class="auth-aside-meta">
        EIAAW Solutions &middot; Made in Malaysia
    </div>
</aside>
