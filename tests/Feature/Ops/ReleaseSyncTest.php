<?php

use App\Jobs\GenerateMediaVariants;
use App\Models\Media;
use App\Support\Release;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

it('reads the commit from a loose ref and from packed-refs', function () {
    $root = sys_get_temp_dir().'/rel'.uniqid();
    mkdir("{$root}/.git/refs/heads", 0777, true);
    file_put_contents("{$root}/.git/HEAD", "ref: refs/heads/production\n");

    expect(Release::id($root))->toBeNull();

    file_put_contents("{$root}/.git/packed-refs", "# pack-refs\nabc123 refs/heads/production\n");
    expect(Release::id($root))->toBe('abc123');

    file_put_contents("{$root}/.git/refs/heads/production", "def456\n");
    expect(Release::id($root))->toBe('def456')
        ->and(Release::id($root.'/missing'))->toBeNull();
});

it('does nothing at all where release sync is switched off', function () {
    Storage::fake('local');
    config(['ctp.release_sync' => false]);

    $this->artisan('ctp:release-sync')->doesntExpectOutputToContain('post-deploy')->assertSuccessful();

    expect(Storage::disk('local')->exists('release.txt'))->toBeFalse();
});

it('does nothing when the release is unchanged and records it after the steps run', function () {
    Storage::fake('local');
    config(['ctp.release_sync' => true]);
    $current = Release::id();

    if ($current === null) {
        $this->markTestSkipped('No .git folder here.');
    }

    Storage::disk('local')->put('release.txt', $current);
    $this->artisan('ctp:release-sync')->doesntExpectOutputToContain('post-deploy')->assertSuccessful();

    // The command really runs `optimize`; leave no cached config or routes behind, even if this fails.
    try {
        Storage::disk('local')->put('release.txt', 'old');
        $this->artisan('ctp:release-sync')->expectsOutputToContain('post-deploy')->assertSuccessful();
        expect(Storage::disk('local')->get('release.txt'))->toBe($current);
    } finally {
        $this->artisan('optimize:clear');
    }
});

it('queues variants only for images that lack them', function () {
    Bus::fake();
    $done = Media::factory()->create(['mime' => 'image/jpeg', 'extension' => 'jpg', 'width' => 600, 'variants' => ['320' => 'a-320.webp', '768' => 'a-768.webp', 'og' => 'a-og.webp']]);
    $missing = Media::factory()->create(['mime' => 'image/jpeg', 'extension' => 'jpg', 'width' => 3000, 'variants' => ['320' => 'b-320.webp']]);
    Media::factory()->create(['mime' => 'application/pdf', 'extension' => 'pdf', 'width' => null, 'variants' => null]);

    $this->artisan('ctp:media-variants')->assertSuccessful();

    Bus::assertDispatchedTimes(GenerateMediaVariants::class, 1);
    Bus::assertDispatched(GenerateMediaVariants::class, fn ($job) => $job->mediaId === $missing->id);

    $this->artisan('ctp:media-variants', ['--force' => true])->assertSuccessful();
    Bus::assertDispatchedTimes(GenerateMediaVariants::class, 3);
});
