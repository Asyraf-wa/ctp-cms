<?php

namespace App\Console\Commands;

use App\Settings\GeneralSettings;
use App\Support\SchedulerHealth;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

// For hosts without a Terminal: run it once from a cron job and read the log. It sends straight away (not through the
// queue), so the SMTP settings are tested on their own, and it prints what the mail setup and the scheduler look like.
#[Signature('ctp:mail-test {to? : Recipient (default: the notification email in Settings → General)}')]
#[Description('Send a test email now and report the mail and scheduler setup')]
class MailTest extends Command
{
    public function handle(GeneralSettings $general): int
    {
        $to = $this->argument('to') ?: $general->notification_email ?: $general->contact_email;

        $this->line('Mailer:    '.config('mail.default').' ('.config('mail.mailers.'.config('mail.default').'.host').':'.config('mail.mailers.'.config('mail.default').'.port').')');
        $this->line('From:      '.config('mail.from.address'));
        $this->line('To:        '.$to);
        $this->line('Owner notifications go to: '.($general->notification_email ?: $general->contact_email).' (Settings → General)');

        $last = SchedulerHealth::lastRun();
        $queue = SchedulerHealth::queue();
        $this->line('Scheduler: '.($last ? 'last ran '.$last->diffForHumans() : 'never ran (the cron entry is missing)'));
        $this->line('Queue:     '.$queue['waiting'].' waiting, '.$queue['failed'].' failed');

        try {
            Mail::raw("This is a test from {$general->site_name}.\n\nIf you can read it, outgoing email works.", function ($message) use ($to, $general) {
                $message->to($to)->subject("[{$general->site_name}] mail test");
            });
        } catch (Throwable $e) {
            $this->error('NOT SENT: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Sent. Check the inbox (and the spam folder) of '.$to.'.');

        return self::SUCCESS;
    }
}
