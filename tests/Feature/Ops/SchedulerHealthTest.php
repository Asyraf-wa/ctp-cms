<?php

use App\Filament\Widgets\SiteOverview;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\SchedulerHealth;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Cache::forget('ctp.scheduler.heartbeat');
    $this->actingAs(User::factory()->create());
});

it('knows whether the cron entry is alive from the scheduler heartbeat', function () {
    expect(SchedulerHealth::isRunning())->toBeFalse()->and(SchedulerHealth::lastRun())->toBeNull();

    SchedulerHealth::beat();
    expect(SchedulerHealth::isRunning())->toBeTrue();

    $this->travel(4)->minutes();
    expect(SchedulerHealth::isRunning())->toBeFalse();
});

it('shows the background jobs state on the admin dashboard', function () {
    Livewire::test(SiteOverview::class)->assertSee('Not running')->assertSee('Never ran');

    SchedulerHealth::beat();
    DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'available_at' => time(), 'created_at' => time()]);

    Livewire::test(SiteOverview::class)->assertSee('Running')->assertSee('mail queue: 1 waiting, 0 failed');
});

it('registers the heartbeat with the scheduler', function () {
    $events = collect(app(Schedule::class)->events())->map(fn ($e) => $e->description);

    expect($events)->toContain('scheduler-heartbeat');
});

it('sends a test email straight away and says where it went', function () {
    Mail::fake();
    app(GeneralSettings::class)->fill(['notification_email' => 'owner@example.com'])->save();

    $this->artisan('ctp:mail-test')
        ->expectsOutputToContain('To:        owner@example.com')
        ->expectsOutputToContain('Sent.')
        ->assertSuccessful();

});

it('reports a mail failure instead of crashing', function () {
    Mail::shouldReceive('raw')->andThrow(new RuntimeException('Connection refused'));

    $this->artisan('ctp:mail-test', ['to' => 'you@example.com'])
        ->expectsOutputToContain('NOT SENT: Connection refused')
        ->assertFailed();
});
