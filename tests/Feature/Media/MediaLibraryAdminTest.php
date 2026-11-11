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

it('filters by usage across all files', function () {
    $folder = MediaFolder::create(['name' => 'Covers']);
    $used = Media::factory()->create(['folder_id' => $folder->id]);
    $unused = Media::factory()->create();
    Series::factory()->create(['cover_media_id' => $used->id]);

    Livewire::test(ListMedia::class)
        ->call('openFolder', 'all')
        ->filterTable('used', false)
        ->assertCanSeeTableRecords([$unused])->assertCanNotSeeTableRecords([$used])
        ->filterTable('used', true)
        ->assertCanSeeTableRecords([$used])->assertCanNotSeeTableRecords([$unused]);
});

it('browses the library folder by folder', function () {
    $covers = MediaFolder::create(['name' => 'Covers']);
    $old = MediaFolder::create(['name' => 'Old covers', 'parent_id' => $covers->id]);
    $loose = Media::factory()->create(['name' => 'Loose file']);
    $cover = Media::factory()->create(['folder_id' => $covers->id, 'name' => 'Laravel cover']);
    $archived = Media::factory()->create(['folder_id' => $old->id, 'name' => 'Archived cover']);

    $page = Livewire::test(ListMedia::class);

    // Top level: its folders, and only the unfiled files.
    $page->assertSee('Covers')->assertSee('Unfiled files')->assertDontSee('Old covers')
        ->assertCanSeeTableRecords([$loose])->assertCanNotSeeTableRecords([$cover, $archived]);

    // Into a folder: its files and its subfolders, with a breadcrumb back.
    $page->call('openFolder', (string) $covers->id)
        ->assertSet('folder', (string) $covers->id)
        ->assertSee('Old covers')->assertSee('Files in this folder')
        ->assertCanSeeTableRecords([$cover])->assertCanNotSeeTableRecords([$loose, $archived]);

    $page->call('openFolder', (string) $old->id)
        ->assertCanSeeTableRecords([$archived])->assertCanNotSeeTableRecords([$cover]);

    // A search looks in every folder, wherever you are.
    $page->searchTable('Laravel')->assertSee('Results from every folder')
        ->assertCanSeeTableRecords([$cover])->assertCanNotSeeTableRecords([$loose, $archived]);

    $page->searchTable('')->call('openFolder', 'all')
        ->assertCanSeeTableRecords([$loose, $cover, $archived]);

    // Back to the top.
    $page->call('openFolder', null)->assertSet('folder', null)
        ->assertCanSeeTableRecords([$loose])->assertCanNotSeeTableRecords([$cover]);
});

it('uploads into the folder being browsed', function () {
    $folder = MediaFolder::create(['name' => 'Screenshots']);

    Livewire::withQueryParams(['folder' => (string) $folder->id])
        ->test(ListMedia::class)
        ->mountAction('upload')
        ->assertSchemaStateSet(['folder_id' => $folder->id]);
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
