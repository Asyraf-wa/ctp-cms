<?php

use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Resources\Series\Pages\ManageSeries;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\Series;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('media');
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));
});

it('uploads several files into a folder and reports rejected ones', function () {
    $folder = MediaFolder::create(['name' => 'Screenshots']);

    Livewire::test(ListMedia::class)
        ->callAction('upload', [
            'folder_id' => $folder->id,
            'files' => [
                UploadedFile::fake()->image('one.png', 800, 600),
                UploadedFile::fake()->image('two.jpg', 640, 480),
            ],
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('2 files uploaded');

    expect(Media::where('folder_id', $folder->id)->count())->toBe(2);
});

it('shows the grid and switches to the list view', function () {
    Media::factory()->count(3)->create();

    Livewire::test(ListMedia::class)
        ->assertCanSeeTableRecords(Media::all())
        ->assertSet('mediaView', 'grid')
        ->callAction('layout')
        ->assertSet('mediaView', 'list')
        ->assertTableColumnExists('usages_count');

    expect(session('media.layout'))->toBe('list');
});

it('filters by usage and folder', function () {
    $folder = MediaFolder::create(['name' => 'Covers']);
    $used = Media::factory()->create(['folder_id' => $folder->id]);
    $unused = Media::factory()->create();
    Series::factory()->create(['cover_media_id' => $used->id]);

    Livewire::test(ListMedia::class)
        ->filterTable('used', false)
        ->assertCanSeeTableRecords([$unused])->assertCanNotSeeTableRecords([$used])
        ->resetTableFilters()
        ->filterTable('folder', $folder->id)
        ->assertCanSeeTableRecords([$used])->assertCanNotSeeTableRecords([$unused])
        ->resetTableFilters()
        ->filterTable('folder', 'none')
        ->assertCanSeeTableRecords([$unused])->assertCanNotSeeTableRecords([$used]);
});

it('edits the metadata in the slide-over', function () {
    $media = Media::factory()->create();

    Livewire::test(ListMedia::class)
        ->callAction(TestAction::make('edit')->table($media), ['alt' => 'CakePHP bake output in a terminal', 'caption' => 'Bake in action'])
        ->assertHasNoActionErrors();

    expect($media->fresh()->only('alt', 'caption'))->toBe(['alt' => 'CakePHP bake output in a terminal', 'caption' => 'Bake in action']);
});

it('only bulk-deletes files that are not in use', function () {
    $used = Media::factory()->create();
    $unused = Media::factory()->create();
    Series::factory()->create(['cover_media_id' => $used->id]);

    Livewire::test(ListMedia::class)
        ->callTableBulkAction('delete', [$used, $unused])
        ->assertNotified('1 deleted, 1 skipped because they are in use');

    expect(Media::pluck('id')->all())->toBe([$used->id]);
});

it('moves files to a folder in bulk', function () {
    $folder = MediaFolder::create(['name' => 'Legacy']);
    $files = Media::factory()->count(2)->create();

    Livewire::test(ListMedia::class)->callTableBulkAction('move', $files, ['folder_id' => $folder->id]);

    expect(Media::where('folder_id', $folder->id)->count())->toBe(2);
});

it('picks a series cover from the library', function () {
    $cover = Media::factory()->create();

    Livewire::test(ManageSeries::class)
        ->mountAction('create')
        ->fillForm(['title' => 'Laravel Basics', 'slug' => 'laravel-basics'])
        ->callAction(TestAction::make('chooseMedia')->schemaComponent('cover_media_id'), ['selected' => $cover->id])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Series::sole()->cover_media_id)->toBe($cover->id)
        ->and($cover->isInUse())->toBeTrue();
});
