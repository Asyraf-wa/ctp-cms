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

    'display_timezone' => env('CTP_DISPLAY_TIMEZONE', 'Asia/Kuala_Lumpur'),

];
