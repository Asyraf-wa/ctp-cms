<?php

namespace App\Support;

class AdminAccess
{
    public static function allows(?string $email): bool
    {
        if (blank($email)) {
            return false;
        }

        return in_array(strtolower(trim($email)), config('ctp.admin_emails'), true);
    }
}
