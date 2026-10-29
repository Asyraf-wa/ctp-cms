<?php

return [

    /*
    | Google accounts allowed into /admin (PRD AUTH-02). Comma-separated in ADMIN_EMAILS.
    */
    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

    /*
    | ctp:release-sync (DEPLOY §10) migrates and caches after a new release is pulled. Production only: the dev
    | containers set CTP_RELEASE_SYNC=false, because a cached config in bootstrap/cache would also be read by tests.
    */
    'release_sync' => (bool) env('CTP_RELEASE_SYNC', true),

    /*
    | Times are stored in UTC (config app.timezone); the admin shows and accepts them in this zone.
    */
    /*
    | Media library (PRD MED-02, MED-03).
    */
    'media' => [
        'disk' => env('MEDIA_DISK', 'media'),
        'max_upload_kb' => (int) env('MEDIA_MAX_UPLOAD_KB', 20480),
        // Originals larger than this (px, longest side) are scaled down on upload.
        'max_dimension' => 2560,
        'widths' => [320, 768, 1280],
        'og' => [1200, 630],
        'quality' => 82,
        // AVIF with GD is slow (seconds per image); off unless the host is fast enough.
        'avif' => (bool) env('MEDIA_AVIF', false),
        'mimes' => [
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
            'image/avif' => 'avif', 'image/svg+xml' => 'svg', 'application/pdf' => 'pdf', 'application/zip' => 'zip',
        ],
    ],

    /*
    | ART-08: a tutorial not updated for this many months shows "may be outdated".
    */
    'freshness_months' => (int) env('CTP_FRESHNESS_MONTHS', 24),

    'display_timezone' => env('CTP_DISPLAY_TIMEZONE', 'Asia/Kuala_Lumpur'),

    'seo' => [
        // robots.txt blocks everything unless this is the production site (or forced for testing).
        'allow_indexing' => (bool) env('CTP_ALLOW_INDEXING', env('APP_ENV') === 'production'),
        // SEO-08: IndexNow pings go out only from production with a key set in Site settings.
        'indexnow' => (bool) env('CTP_INDEXNOW', env('APP_ENV') === 'production'),
        'indexnow_endpoint' => 'https://api.indexnow.org/indexnow',
    ],

    // OPS-05: extra hosts for scripts pasted in Site settings → Scripts (e.g. "https://www.googletagmanager.com https://*.google-analytics.com").
    'csp' => [
        'extra_hosts' => env('CSP_EXTRA_HOSTS', ''),
    ],

    // OPS-03: empty database that ctp:backup-verify restores into (never the live one).
    'backup' => [
        'verify_database' => env('BACKUP_VERIFY_DATABASE'),
    ],

    // MIG-01: the database ./dev legacy-import loads the old CakePHP dump into.
    'legacy_database' => env('LEGACY_DB_DATABASE', 'ctp_legacy'),
];
