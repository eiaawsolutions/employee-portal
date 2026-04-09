@extends('layouts.app')
@section('title', 'Account Settings')
@section('page-title', 'Account Settings')

@section('content')
<div class="row g-4">

    {{-- ── LEFT COL ─────────────────────────────────────────────────────── --}}
    <div class="col-md-6">

        {{-- Profile Picture --}}
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-person-circle me-2 text-primary"></i>Profile Picture</h6>
            </div>
            <div class="card-body text-center">
                @if(session('avatar_success'))
                    <div class="alert alert-success py-2"><i class="bi bi-check-circle me-2"></i>{{ session('avatar_success') }}</div>
                @endif
                <div class="mb-3">
                    <img src="{{ Auth::user()->profile_picture_url }}" alt="Profile" class="rounded-circle border shadow-sm" style="width:100px;height:100px;object-fit:cover;">
                </div>
                <form action="{{ route('account.avatar') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold small">Upload New Photo</label>
                        <input type="file" name="profile_picture" class="form-control @error('profile_picture') is-invalid @enderror" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" onchange="previewAvatar(this)">
                        @error('profile_picture')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">JPG, PNG, GIF or WebP. Max 2MB.</div>
                    </div>
                    <div id="avatarPreviewWrap" class="mb-3 d-none">
                        <img id="avatarPreview" class="rounded-circle border" style="width:70px;height:70px;object-fit:cover;">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-2"></i>Upload Profile Picture</button>
                </form>
            </div>
        </div>

        {{-- Change Password — single button, no card wrapper --}}
        <form action="{{ route('account.change-password') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-warning w-100 fw-bold py-2">
                <i class="bi bi-key me-2"></i>Change Password
            </button>
        </form>

    </div>

    {{-- ── RIGHT COL ────────────────────────────────────────────────────── --}}
    <div class="col-md-6">

        {{-- Theme --}}
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-palette me-2 text-primary"></i>Change Theme</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Choose your preferred interface theme.</p>
                <div class="d-flex gap-3">
                    <div class="theme-option p-3 rounded border text-center flex-fill" data-theme="light" style="cursor:pointer;" onclick="setTheme('light')">
                        <i class="bi bi-sun-fill" style="font-size:28px;color:#f59e0b;"></i>
                        <div class="fw-semibold mt-2 small">Light</div>
                    </div>
                    <div class="theme-option p-3 rounded border text-center flex-fill" data-theme="dark" style="cursor:pointer;" onclick="setTheme('dark')">
                        <i class="bi bi-moon-fill" style="font-size:28px;color:#2684FE;"></i>
                        <div class="fw-semibold mt-2 small">Dark</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Two-Factor Authentication --}}
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-primary"></i>Two-Factor Authentication</h6>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success py-2"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger py-2">{{ session('error') }}</div>
                @endif

                @if(Auth::user()->hasTwoFactorEnabled())
                    <div class="alert alert-success py-2 mb-3">
                        <i class="bi bi-shield-fill-check me-1"></i>
                        Two-factor authentication is <strong>enabled</strong>.
                    </div>
                    <form action="{{ route('two-factor.disable') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Confirm Password to Disable</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Enter your current password" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-shield-x me-2"></i>Disable Two-Factor Authentication
                        </button>
                    </form>
                @else
                    <p class="text-muted small mb-3">Add an extra layer of security to your account using a TOTP authenticator app (e.g. Google Authenticator, Authy).</p>
                    <a href="{{ route('two-factor.setup') }}" class="btn btn-primary w-100">
                        <i class="bi bi-shield-plus me-2"></i>Enable Two-Factor Authentication
                    </a>
                @endif
            </div>
        </div>

        {{-- Language --}}
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-translate me-2 text-primary"></i>Language Preference</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Select your preferred interface language.</p>
                <div class="d-grid gap-2">
                    @foreach(['en' => ['label'=>'English','flag'=>'🇬🇧'], 'ms' => ['label'=>'Bahasa Melayu','flag'=>'🇲🇾']] as $code => $lang)
                    <button type="button"
                        class="btn btn-outline-secondary text-start d-flex align-items-center gap-2 lang-btn {{ session('locale','en')===$code?'active btn-primary text-white border-primary':'' }}"
                        onclick="setLanguage('{{ $code }}')">
                        <span style="font-size:20px;">{{ $lang['flag'] }}</span>
                        <span class="fw-semibold">{{ $lang['label'] }}</span>
                        @if(session('locale','en')===$code)<i class="bi bi-check-circle-fill ms-auto text-white"></i>@endif
                    </button>
                    @endforeach
                </div>
                <div class="mt-3 alert alert-info small mb-0"><i class="bi bi-info-circle me-1"></i>Full multilingual support coming soon.</div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('avatarPreview').src = e.target.result;
            document.getElementById('avatarPreviewWrap').classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function setTheme(theme) {
    localStorage.setItem('theme', theme);
    document.documentElement.setAttribute('data-bs-theme', theme);
    document.documentElement.setAttribute('data-theme', theme);
    // Update active state on the cards
    document.querySelectorAll('.theme-option').forEach(el => {
        el.classList.remove('border-primary', 'bg-primary', 'text-white');
        el.style.borderWidth = '';
    });
    const active = document.querySelector('.theme-option[data-theme="' + theme + '"]');
    if (active) {
        active.style.borderWidth = '2px';
        active.classList.add('border-primary');
    }
}

// Highlight the currently active theme on page load
document.addEventListener('DOMContentLoaded', function () {
    const current = localStorage.getItem('theme') || 'light';
    const active = document.querySelector('.theme-option[data-theme="' + current + '"]');
    if (active) {
        active.style.borderWidth = '2px';
        active.classList.add('border-primary');
    }
});
</script>
@endpush