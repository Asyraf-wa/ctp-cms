<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Support\AdminAccess;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name', 'email', 'google_id', 'google_avatar', 'last_login_at',
    // Author profile (PRD AUTHOR-02)
    'display_name', 'slug', 'avatar_path', 'job_title', 'bio_short', 'bio_long', 'website_url', 'socials',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasSlug, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'socials' => 'array',
        ];
    }

    // Checked on every admin request, so removing an email from ADMIN_EMAILS locks the account out at once.
    public function canAccessPanel(Panel $panel): bool
    {
        return AdminAccess::allows($this->email);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatarUrl();
    }

    // The name readers see on articles and the author page.
    public function authorName(): string
    {
        return $this->display_name ?: $this->name;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : $this->google_avatar;
    }

    protected function slugSourceColumn(): string
    {
        return filled($this->display_name) ? 'display_name' : 'name';
    }

    public function pathForSlug(string $slug): ?string
    {
        return '/author/'.$slug;
    }
}
