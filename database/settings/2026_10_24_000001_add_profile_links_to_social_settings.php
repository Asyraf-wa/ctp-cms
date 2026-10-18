<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

// SET-05: developer and research profiles next to the social networks; filled with the owner's public profiles.
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('social.codepen', 'https://codepen.io/Asyraf');
        $this->migrator->add('social.freecodecamp', 'https://www.freecodecamp.org/Asyraf-wa');
        $this->migrator->add('social.packagist', 'https://packagist.org/users/Asyraf_wa/packages/');
        $this->migrator->add('social.google_developers', 'https://developers.google.com/profile/u/112771972522382922429');
        $this->migrator->add('social.google_scholar', 'https://scholar.google.com/citations?user=NpHcKO0AAAAJ');
        $this->migrator->add('social.orcid', 'https://orcid.org/0000-0002-7236-3367');
        $this->migrator->add('social.scopus', 'https://www.scopus.com/authid/detail.uri?authorId=57720318600');

        // Fill the existing networks only where they are still empty.
        $this->migrator->update('social.github', fn (?string $v) => $v ?: 'https://github.com/Asyraf-wa');
        $this->migrator->update('social.youtube', fn (?string $v) => $v ?: 'https://www.youtube.com/@codethepixel');
        $this->migrator->update('social.linkedin', fn (?string $v) => $v ?: 'https://www.linkedin.com/in/asyraf-wahi-anuar-057643328/');
        $this->migrator->update('social.instagram', fn (?string $v) => $v ?: 'https://www.instagram.com/asyraf_wa/');
    }
};
