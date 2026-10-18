<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // CMT-04: email the owner about new comments.
        $this->migrator->add('integrations.comments_notify_admin', true);

        // CONTACT-01, CONTACT-05.
        $this->migrator->add('contact.reply_time', '1–3 working days');
        $this->migrator->add('contact.auto_reply', true);
        $this->migrator->add('contact.intro', 'Questions about a tutorial, a collaboration idea or a workshop? Send a message and I will get back to you.');
    }
};
