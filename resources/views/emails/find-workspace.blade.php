<!DOCTYPE html>
<html>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 560px; margin: 24px auto; color: #0F1A1D;">
    <h2 style="font-weight: 500; letter-spacing: -0.02em;">Your EIAAW Workforce workspaces</h2>

    <p>You asked which workspaces this email address can sign into. Here {{ $workspaces->count() === 1 ? 'it is' : 'they are' }}:</p>

    <ul style="padding-left: 18px;">
        @foreach($workspaces as $ws)
            <li style="margin-bottom: 10px;">
                <strong>{{ $ws->name }}</strong><br>
                <a href="{{ $ws->workspaceUrl('/login') }}" style="color: #11766A;">{{ $ws->workspaceUrl('/login') }}</a>
            </li>
        @endforeach
    </ul>

    <p style="color: #6B7A7F; font-size: 13px;">If you didn't ask for this, you can ignore this email — nobody else received it.</p>

    <hr style="border: none; border-top: 1px solid #E8DFCC; margin: 32px 0;">
    <p style="font-family: monospace; font-size: 11px; color: #6B7A7F; text-transform: uppercase; letter-spacing: 0.12em;">
        EIAAW Solutions &middot; AI &middot; Human Partnerships
    </p>
</body>
</html>
