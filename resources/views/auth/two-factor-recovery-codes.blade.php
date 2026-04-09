<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recovery Codes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #E8F0FE; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .auth-card { background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); width: 100%; max-width: 480px; overflow: hidden; }
        .auth-header { background: linear-gradient(135deg, #198754, #20c997); padding: 30px; text-align: center; color: #fff; }
        .auth-header h4 { font-weight: 700; margin: 0; }
        .auth-body { padding: 30px; }
        .recovery-code { font-family: monospace; font-size: 1em; background: #f8f9fa; padding: 6px 12px; border-radius: 6px; display: inline-block; margin: 4px; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-header">
            <i class="bi bi-shield-check" style="font-size:40px;"></i>
            <h4 class="mt-2">Two-Factor Authentication Enabled</h4>
            <p class="mb-0" style="opacity:0.8; font-size:14px;">Save these recovery codes in a safe place</p>
        </div>
        <div class="auth-body">
            <div class="alert alert-warning py-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Important:</strong> Store these recovery codes in a secure location. Each code can only be used once. If you lose your authenticator device, these codes are the only way to access your account.
            </div>

            <div class="text-center mb-4">
                @foreach($recoveryCodes as $code)
                    <span class="recovery-code">{{ $code }}</span>
                @endforeach
            </div>

            <a href="{{ route('profile') }}" class="btn btn-primary w-100">
                <i class="bi bi-arrow-left me-2"></i>Return to Profile
            </a>
        </div>
    </div>
</body>
</html>
