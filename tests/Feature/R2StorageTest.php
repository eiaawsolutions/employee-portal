<?php

namespace Tests\Feature;

use App\Services\AttachmentProcessor;
use App\Support\LocalFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * File storage must work when the 'local' and 'public' disks live on R2
 * (R2_ENABLED=true), not just on the container disk.
 */
class R2StorageTest extends TestCase
{
    public function test_r2_switch_points_local_public_and_backups_at_folders_in_the_bucket(): void
    {
        $cfg = $this->filesystemsConfigWith([
            'R2_ENABLED' => 'true', 'R2_BUCKET' => 'eiaaw-workforce-prod',
            'R2_ENDPOINT' => 'https://example.r2.cloudflarestorage.com', 'R2_ROOT' => 'workforce',
        ]);

        foreach (['local' => 'workforce/private', 'public' => 'workforce/public', 'backups' => 'workforce/backups'] as $disk => $root) {
            $this->assertSame('s3', $cfg['disks'][$disk]['driver'], $disk);
            $this->assertSame($root, $cfg['disks'][$disk]['root'], $disk);
            $this->assertSame('eiaaw-workforce-prod', $cfg['disks'][$disk]['bucket'], $disk);
            $this->assertSame('private', $cfg['disks'][$disk]['visibility'], $disk);
        }
    }

    public function test_without_the_switch_disks_stay_on_the_container(): void
    {
        $cfg = $this->filesystemsConfigWith(['R2_ENABLED' => 'false']);

        $this->assertSame('local', $cfg['disks']['local']['driver']);
        $this->assertSame('local', $cfg['disks']['public']['driver']);
        $this->assertSame('local', $cfg['disks']['backups']['driver']);
    }

    public function test_public_disk_files_are_served_at_storage_urls(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('company-logos/acme.png', 'PNGDATA');

        $res = $this->get('/storage/company-logos/acme.png');

        $res->assertOk();
        $this->assertSame('PNGDATA', $res->streamedContent());
        $this->assertStringContainsString('max-age=86400', $res->headers->get('Cache-Control'));
    }

    public function test_storage_route_never_reaches_outside_the_public_disk(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('local')->put('nric_documents/secret.pdf', 'PRIVATE');

        $this->get('/storage/../private/nric_documents/secret.pdf')->assertNotFound();
        $this->get('/storage/nric_documents/secret.pdf')->assertNotFound();
        $this->get('/storage/missing.png')->assertNotFound();
    }

    public function test_attachments_are_written_through_the_disk_not_a_local_path(): void
    {
        Storage::fake('local');

        $meta = AttachmentProcessor::store(UploadedFile::fake()->createWithContent('notes.txt', 'hello'), 'tickets/1');

        Storage::disk('local')->assertExists($meta['file_path']);
        $this->assertSame('hello', Storage::disk('local')->get($meta['file_path']));
    }

    public function test_local_file_returns_a_readable_path(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('accounting/invoice-scans/a.pdf', '%PDF-1.4 test');

        $this->assertSame('%PDF-1.4 test', file_get_contents(LocalFile::for('local', 'accounting/invoice-scans/a.pdf')));
    }

    /** @param array<string,string> $vars */
    private function filesystemsConfigWith(array $vars): array
    {
        $saved = [];
        foreach ($vars as $k => $v) {
            $saved[$k] = getenv($k);
            putenv("{$k}={$v}");
            $_ENV[$k] = $_SERVER[$k] = $v;
        }
        try {
            return require config_path('filesystems.php');
        } finally {
            foreach ($saved as $k => $old) {
                $old === false ? putenv($k) : putenv("{$k}={$old}");
                unset($_ENV[$k], $_SERVER[$k]);
            }
        }
    }
}
