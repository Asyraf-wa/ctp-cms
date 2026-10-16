<?php

return [

    /*
    | Google accounts allowed into /admin (PRD AUTH-02). Comma-separated in ADMIN_EMAILS.
    */
    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

];
