{{-- Plain transactional email — no design treatment yet (Session 6 visual pass) --}}
<!DOCTYPE html>
<html>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 560px; margin: 24px auto; color: #0F1A1D;">
    <h2 style="font-weight: 500; letter-spacing: -0.02em;">Confirm your EIAAW Workforce signup</h2>

    <p>Hi {{ $invite->full_name }},</p>

    <p>Click the link below to finish creating <strong>{{ $invite->company_name }}</strong>'s workspace
    at <code>{{ $invite->desired_slug }}.{{ config('eiaaw.tenant_domain') }}</code>:</p>

    <p>
        <a href="{{ $confirmUrl }}"
           style="display: inline-block; padding: 12px 22px; background: #0F1A1D; color: #FAF7F2; border-radius: 999px; text-decoration: none; font-weight: 500;">
            Confirm and set password
        </a>
    </p>

    <p style="color: #6B7A7F; font-size: 13px;">This link expires {{ $invite->expires_at->diffForHumans() }}.
    If you didn't request this, you can safely ignore this email.</p>

    <hr style="border: none; border-top: 1px solid #E8DFCC; margin: 32px 0;">
    <p style="font-family: monospace; font-size: 11px; color: #6B7A7F; text-transform: uppercase; letter-spacing: 0.12em;">
        EIAAW Solutions &middot; AI &middot; Human Partnerships
    </p>
</body>
</html>
