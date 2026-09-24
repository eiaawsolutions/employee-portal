<?php

/*
 * R2_ENABLED=true moves the app's file storage off the container disk (which
 * Railway wipes on every redeploy) into the private Cloudflare R2 bucket
 * R2_BUCKET, under one folder per purpose: {R2_ROOT}/private (the 'local'
 * disk: HR documents, receipts, invoices), {R2_ROOT}/public (the 'public'
 * disk: logos, profile photos, served through /storage/* by the app) and
 * {R2_ROOT}/backups. Credentials are secret:// handles resolved by
 * SecretsServiceProvider (see config/secrets.php).
 */
$r2Enabled = filter_var(env('R2_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
$r2 = static fn (string $folder): array => [
    'driver' => 's3',
    'key' => env('R2_ACCESS_KEY_ID'),
    'secret' => env('R2_SECRET_ACCESS_KEY'),
    'region' => 'auto',
    'bucket' => env('R2_BUCKET'),
    'endpoint' => env('R2_ENDPOINT'),
    'use_path_style_endpoint' => true,
    'root' => trim(env('R2_ROOT', 'workforce'), '/').'/'.$folder,
    'visibility' => 'private',
    'throw' => false,
    'report' => true,
];

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => $r2Enabled ? $r2('private') : [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            // No 'serve': /storage/* belongs to the public disk (PublicStorageController);
            // private files are only served through SecureFileController.
            'throw' => false,
            'report' => false,
        ],

        'public' => $r2Enabled ? $r2('public') + [
            'url' => rtrim((string) env('APP_URL'), '/').'/storage',
        ] : [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim((string) env('APP_URL'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Encrypted database backups (backup:run). Off the container disk when R2 is on.
        'backups' => $r2Enabled ? $r2('backups') : [
            'driver' => 'local',
            'root' => storage_path('app/backups'),
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        // Cloudflare R2 — S3-compatible, used as the default disk for tenant
        // file storage in production. Each tenant's files are namespaced by
        // {tenant_id}/ prefix; SecureFileController enforces access control.
        'r2' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'auto'),
            'bucket' => env('AWS_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
