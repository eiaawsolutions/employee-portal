<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\SystemMetadataService;

class KnowledgeBaseController extends Controller
{
    /**
     * All KB topics with metadata for the index page.
     */
    private function getTopics(): array
    {
        return [
            'onboarding' => [
                'title'       => 'Onboarding Flow',
                'icon'        => 'bi-person-plus-fill',
                'color'       => '#198754',
                'description' => 'Invite → Register → Activate lifecycle with staging JSON, consent, and auto-activation.',
            ],
            'offboarding' => [
                'title'       => 'Offboarding Flow',
                'icon'        => 'bi-box-arrow-right',
                'color'       => '#dc3545',
                'description' => 'Exit initiation → notifications → asset return → account deactivation pipeline.',
            ],
            'leave' => [
                'title'       => 'Leave Management',
                'icon'        => 'bi-calendar2-week',
                'color'       => '#0d6efd',
                'description' => 'Apply → Manager/HR approval → balance deduction with tenure-based entitlements.',
            ],
            'payroll' => [
                'title'       => 'Payroll & Statutory',
                'icon'        => 'bi-wallet2',
                'color'       => '#6f42c1',
                'description' => 'Pay run generation → statutory calculations (EPF/SOCSO/EIS/PCB) → payslip issuance.',
            ],
            'claims' => [
                'title'       => 'Expense Claims',
                'icon'        => 'bi-receipt-cutoff',
                'color'       => '#fd7e14',
                'description' => 'Draft → Submit → Manager → HR → Payroll integration with duplicate detection.',
            ],
            'attendance' => [
                'title'       => 'Attendance & Overtime',
                'icon'        => 'bi-stopwatch',
                'color'       => '#20c997',
                'description' => 'Clock in/out → late detection → overtime requests → HR approval flow.',
            ],
            'assets' => [
                'title'       => 'IT Asset Lifecycle',
                'icon'        => 'bi-laptop',
                'color'       => '#0dcaf0',
                'description' => 'Procurement → Assignment → AARF acknowledgement → Return → Decommission pipeline.',
            ],
            'authentication' => [
                'title'       => 'Authentication & Security',
                'icon'        => 'bi-shield-lock-fill',
                'color'       => '#6610f2',
                'description' => 'Work-email login → single-session → lockout → password reset → session timeout.',
            ],
            'consent' => [
                'title'       => 'Edit & Consent Flows',
                'icon'        => 'bi-pencil-square',
                'color'       => '#d63384',
                'description' => 'Onboarding notification-only vs employee consent-required edit log pipelines.',
            ],
            'roles' => [
                'title'       => 'Roles & Permissions',
                'icon'        => 'bi-people-fill',
                'color'       => '#adb5bd',
                'description' => 'Role hierarchy, capability methods, custom per-resource permission overrides.',
            ],
            'scheduled' => [
                'title'       => 'Scheduled Jobs & Automation',
                'icon'        => 'bi-clock-history',
                'color'       => '#495057',
                'description' => 'Cron commands: activation, offboarding notifications, reminders, backups, audit.',
            ],
            'emails' => [
                'title'       => 'Email & Notification Map',
                'icon'        => 'bi-envelope-fill',
                'color'       => '#e35d6a',
                'description' => 'All 20+ mail classes organized by module with triggers and recipients.',
            ],
        ];
    }

    /**
     * System Logic / Knowledge Base is EIAAW HQ only. The routes are already
     * gated by EnsurePlatformAdmin middleware; this is defense-in-depth that
     * survives route refactors and returns 404 to prevent tenant superadmins
     * from enumerating the page's existence. Method name preserved for the
     * existing call sites throughout this controller.
     */
    private function authorizeSuperadmin(): void
    {
        if (!Auth::user() || !Auth::user()->isPlatformAdmin()) {
            abort(404);
        }
    }

    /**
     * Check if user has set a KB password.
     */
    private function hasKbPassword(): bool
    {
        return !empty(Auth::user()->kb_password_hash);
    }

    /**
     * Check if current session has unlocked KB.
     */
    private function isUnlocked(): bool
    {
        return session('kb_unlocked') === true;
    }

    // ─── Password Setup ───────────────────────────────────────────

    /**
     * Show the "set KB password" form (first time).
     */
    public function setupPassword()
    {
        $this->authorizeSuperadmin();

        if ($this->hasKbPassword()) {
            return redirect()->route('superadmin.kb.gate');
        }

        return view('superadmin.knowledge-base.setup-password');
    }

    /**
     * Store the KB password.
     */
    public function storePassword(Request $request)
    {
        $this->authorizeSuperadmin();

        $request->validate([
            'password'              => 'required|string|min:8|max:128|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = Auth::user();
        $user->kb_password_hash = Hash::make($request->password);
        $user->save();

        session(['kb_unlocked' => true]);

        return redirect()->route('superadmin.kb.index')
            ->with('success', 'Knowledge Base password set successfully.');
    }

    // ─── Password Gate ────────────────────────────────────────────

    /**
     * Show the password entry form.
     */
    public function gate()
    {
        $this->authorizeSuperadmin();

        if (!$this->hasKbPassword()) {
            return redirect()->route('superadmin.kb.setup');
        }

        if ($this->isUnlocked()) {
            return redirect()->route('superadmin.kb.index');
        }

        return view('superadmin.knowledge-base.gate');
    }

    /**
     * Verify the KB password.
     */
    public function unlock(Request $request)
    {
        $this->authorizeSuperadmin();

        $request->validate([
            'password' => 'required|string|max:128',
        ]);

        if (!Hash::check($request->password, Auth::user()->kb_password_hash)) {
            return back()->withErrors(['password' => 'Incorrect Knowledge Base password.']);
        }

        session(['kb_unlocked' => true]);

        return redirect()->route('superadmin.kb.index');
    }

    // ─── Change Password ──────────────────────────────────────────

    /**
     * Show the change-password form.
     */
    public function changePassword()
    {
        $this->authorizeSuperadmin();
        $this->ensureAccess();

        return view('superadmin.knowledge-base.change-password');
    }

    /**
     * Process password change.
     */
    public function updatePassword(Request $request)
    {
        $this->authorizeSuperadmin();
        $this->ensureAccess();

        $request->validate([
            'current_password'      => 'required|string|max:128',
            'password'              => 'required|string|min:8|max:128|confirmed',
            'password_confirmation' => 'required',
        ]);

        if (!Hash::check($request->current_password, Auth::user()->kb_password_hash)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user = Auth::user();
        $user->kb_password_hash = Hash::make($request->password);
        $user->save();

        return redirect()->route('superadmin.kb.index')
            ->with('success', 'Knowledge Base password changed successfully.');
    }

    // ─── Index (Topic List) ───────────────────────────────────────

    /**
     * Show the knowledge base index with all topics.
     */
    public function index(SystemMetadataService $meta)
    {
        $this->authorizeSuperadmin();
        $this->ensureAccess();

        return view('superadmin.knowledge-base.index', [
            'topics' => $this->getTopics(),
            'meta'   => $meta->get(),
        ]);
    }

    // ─── Topic Detail ─────────────────────────────────────────────

    /**
     * Show a single topic.
     */
    public function topic(string $slug, SystemMetadataService $meta)
    {
        $this->authorizeSuperadmin();
        $this->ensureAccess();

        $topics = $this->getTopics();

        if (!isset($topics[$slug])) {
            abort(404);
        }

        $viewName = 'superadmin.knowledge-base.topics.' . $slug;

        if (!view()->exists($viewName)) {
            abort(404);
        }

        return view($viewName, [
            'topic'  => $topics[$slug],
            'topics' => $topics,
            'slug'   => $slug,
            'meta'   => $meta->get(),
        ]);
    }

    // ─── Lock ─────────────────────────────────────────────────────

    /**
     * Lock the KB (forget session).
     */
    public function lock()
    {
        $this->authorizeSuperadmin();
        session()->forget('kb_unlocked');

        return redirect()->route('superadmin.kb.gate')
            ->with('info', 'Knowledge Base locked.');
    }

    // ─── Access helper ────────────────────────────────────────────

    private function ensureAccess(): void
    {
        if (!$this->hasKbPassword()) {
            abort(302, '', ['Location' => route('superadmin.kb.setup')]);
        }
        if (!$this->isUnlocked()) {
            abort(302, '', ['Location' => route('superadmin.kb.gate')]);
        }
    }
}
