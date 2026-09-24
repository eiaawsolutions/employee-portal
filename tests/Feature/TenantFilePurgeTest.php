<?php

namespace Tests\Feature;

use App\Models\SignupInvite;
use App\Models\Tenant;
use App\Services\TenantFilePurger;
use App\Support\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The privacy notice, terms and DPA promise a cancelled workspace's files
 * are deleted. The purge must reach every upload the tenant owns — and
 * nothing that belongs to another tenant.
 */
class TenantFilePurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_deletes_only_the_cancelled_tenants_files_on_both_disks(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $a = $this->provision('tenanta');
        $b = $this->provision('tenantb');

        DB::table('users')->where('tenant_id', $a->id)->update(['profile_picture' => 'profile-pictures/a.png']);
        DB::table('users')->where('tenant_id', $b->id)->update(['profile_picture' => 'profile-pictures/b.png']);
        Storage::disk('public')->put('profile-pictures/a.png', 'A');
        Storage::disk('public')->put('profile-pictures/b.png', 'B');
        Storage::disk('local')->put('profile-pictures/a.png', 'A-private-copy');

        $this->assertSame(['profile-pictures/a.png'], app(TenantFilePurger::class)->pathsFor($a->id));

        $count = app(TenantFilePurger::class)->purge($a->id);

        $this->assertSame(1, $count);
        Storage::disk('public')->assertMissing('profile-pictures/a.png');
        Storage::disk('local')->assertMissing('profile-pictures/a.png');
        Storage::disk('public')->assertExists('profile-pictures/b.png');
    }

    public function test_every_listed_column_exists_in_the_schema(): void
    {
        foreach (TenantFilePurger::COLUMNS as $table => $columns) {
            $this->assertTrue(\Schema::hasTable($table), "missing table $table");
            $this->assertTrue(\Schema::hasColumn($table, 'tenant_id'), "$table has no tenant_id");
            foreach (array_keys($columns) as $column) {
                $this->assertTrue(\Schema::hasColumn($table, $column), "missing column $table.$column");
            }
        }
    }

    public function test_two_factor_qr_is_rendered_locally_as_svg(): void
    {
        $uri = QrCode::svgDataUri('otpauth://totp/EIAAW:a@example.com?secret=JBSWY3DPEHPK3PXP');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);
        $this->assertStringContainsString('<svg', base64_decode(substr($uri, strlen('data:image/svg+xml;base64,'))));
        $this->assertStringNotContainsString('qrserver', file_get_contents(resource_path('views/auth/two-factor-setup.blade.php')));
    }

    public function test_csp_no_longer_allows_the_third_party_qr_service(): void
    {
        $csp = $this->get(route('marketing.landing'))->headers->get('Content-Security-Policy');
        $this->assertStringNotContainsString('qrserver', $csp);
    }

    private function provision(string $slug): Tenant
    {
        $invite = SignupInvite::create([
            'work_email' => "$slug@example.com", 'full_name' => 'Owner', 'company_name' => ucfirst($slug),
            'desired_slug' => $slug, 'plan' => 'growth',
            'confirmation_token' => Str::random(48), 'expires_at' => now()->addDay(),
        ]);
        return app(\App\Services\TenantProvisioner::class)->provisionFromInvite($invite, 'a-strong-password-123');
    }
}
