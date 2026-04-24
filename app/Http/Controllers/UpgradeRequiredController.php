<?php

namespace App\Http\Controllers;

/**
 * UpgradeRequiredController — renders the page shown when EnsurePlan
 * middleware blocks access to a feature not included in the tenant's
 * current plan.
 *
 * Lives on the tenant subdomain (the user is signed in when they hit it).
 * The blocking middleware flashes 'required_feature' and 'current_plan'
 * into the session; we read those for contextual messaging.
 */
class UpgradeRequiredController extends Controller
{
    public function show()
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        return view('upgrade-required', [
            'tenant' => $tenant,
            'currentPlan' => session('current_plan', $tenant?->plan ?? 'starter'),
            'requiredFeature' => session('required_feature'),
            'featureLabel' => $this->featureLabel(session('required_feature')),
            'recommendedPlan' => $this->recommendedPlan(session('required_feature')),
        ]);
    }

    /**
     * Map the config/plans.php feature key to a human label.
     * Keep in sync with the features listed in config/plans.php.
     */
    private function featureLabel(?string $feature): string
    {
        return match ($feature) {
            'it.assets', 'it.aarf', 'it.offboarding' => 'IT asset inventory',
            'finance.accounting' => 'Full accounting module',
            'admin.knowledge_base' => 'Knowledge base',
            'auth.sso_saml', 'auth.sso_oidc' => 'SSO (SAML / OIDC)',
            'audit.export' => 'Audit log export',
            'infra.dedicated_db' => 'Dedicated database',
            'hr.payroll', 'hr.payslips', 'hr.ea_forms' => 'Payroll & EA forms',
            'hr.eclaim' => 'Expense claims',
            default => 'This feature',
        };
    }

    /**
     * The minimum plan tier that includes the given feature.
     * Returns 'growth' / 'scale' / 'enterprise' — whichever tier first
     * introduces the feature in config/plans.php.
     */
    private function recommendedPlan(?string $feature): string
    {
        if (!$feature) {
            return 'scale';
        }

        foreach (['starter', 'growth', 'scale', 'enterprise'] as $plan) {
            $features = config("plans.{$plan}.features", []);
            if (in_array($feature, $features, true)) {
                return $plan;
            }
        }

        return 'enterprise';
    }
}
