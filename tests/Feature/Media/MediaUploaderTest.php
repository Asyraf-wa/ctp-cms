<?php

use App\Jobs\GenerateMediaVariants;
use App\Media\MediaUploader;
use App\Media\VariantGenerator;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Series;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\ResponseCache\Facades\ResponseCache;

beforeEach(function () {
    Storage::fake('media');
    $this->uploader = app(MediaUploader::class);
});

// A JPEG with an EXIF segment carrying a fake GPS string, like a phone photo.
function jpegWithExif(int $width = 1000, int $height = 600): UploadedFile
{
    $fake = UploadedFile::fake()->image('photo.jpg', $width, $height); // keep a reference: the temp file dies with it
    $jpeg = (string) file_get_contents($fake->getRealPath());
    $payload = "Exif\0\0GPSLatitude 3.1390N GPSLongitude 101.6869E";
    $app1 = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

    $path = tempnam(sys_get_temp_dir(), 'exif').'.jpg';
    file_put_contents($path, substr($jpeg, 0, 2).$app1.substr($jpeg, 2));

    return new UploadedFile($path, 'IMG_2041 Phone Photo.jpg', 'image/jpeg', null, true);
}

it('stores an image under a readable, dated path and strips EXIF', function () {
    $media = $this->uploader->store(jpegWithExif());

    expect($media->path)->toMatch('#^\d{4}/\d{2}/img-2041-phone-photo-[a-z0-9]{4}\.jpg$#')
        ->and($media->name)->toBe('IMG 2041 Phone Photo')
        ->and([$media->width, $media->height])->toBe([1000, 600])
        ->and($media->mime)->toBe('image/jpeg');

    $stored = Storage::disk('media')->get($media->path);
    expect($stored)->not->toContain('GPSLatitude')->not->toContain('Exif');
});

it('scales huge images down to the maximum dimension', function () {
    $media = $this->uploader->store(UploadedFile::fake()->image('wide.png', 4000, 1000));

    expect([$media->width, $media->height])->toBe([2560, 640]);
});

it('returns the existing item when the same file is uploaded again', function () {
    $file = UploadedFile::fake()->image('same.png', 400, 300);

    $first = $this->uploader->store($file);
    $second = $this->uploader->store($file);

    expect($second->id)->toBe($first->id)->and(Media::count())->toBe(1);
});

it('generates WebP variants and a social crop without upscaling', function () {
    $media = $this->uploader->store(UploadedFile::fake()->image('screen.png', 1000, 800))->fresh();

    expect(array_keys($media->variants))->toEqualCanonicalizing(['320', '768', 'og'])
        ->and($media->variants['og'])->toEndWith('-og.webp');

    foreach ($media->variants as $path) {
        expect(Storage::disk('media')->exists($path))->toBeTrue();
    }

    [$w, $h] = getimagesizefromstring(Storage::disk('media')->get($media->variants['og']));
    expect([$w, $h])->toBe([1200, 630])
        ->and($media->srcset())->toContain('320w')->toContain('768w')->toContain('1000w');
});

it('rejects files whose content is not an allowed type, whatever the extension', function () {
    // Built in pieces: host malware scanners blank out test files that contain a literal PHP shell.
    $fake = UploadedFile::fake()->createWithContent('innocent.jpg', '<?'.'php sys'.'tem($_GET["c"]); ?>');

    expect(fn () => $this->uploader->store($fake))->toThrow(ValidationException::class);
    expect(Media::count())->toBe(0);
});

it('rejects files over the size limit', function () {
    config(['ctp.media.max_upload_kb' => 1]);

    expect(fn () => $this->uploader->store(UploadedFile::fake()->image('big.png', 800, 800)))->toThrow(ValidationException::class);
});

it('sanitises SVG uploads', function () {
    $svg = UploadedFile::fake()->createWithContent('logo.svg', '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" onload="alert(1)"><script>alert(1)</script><rect width="10" height="10"/></svg>');

    $media = $this->uploader->store($svg);
    $stored = Storage::disk('media')->get($media->path);

    expect($media->mime)->toBe('image/svg+xml')
        ->and($stored)->toContain('<rect')->not->toContain('<script')->not->toContain('onload');
});

it('replaces a file but keeps its id and URL', function () {
    $media = $this->uploader->store(UploadedFile::fake()->image('diagram.png', 900, 600))->fresh();
    $oldVariant = $media->variants['320'];

    $this->uploader->replace($media, UploadedFile::fake()->image('diagram-v2.png', 1200, 700));
    $media->refresh();

    expect($media->path)->toEndWith('.png')
        ->and([$media->width, $media->height])->toBe([1200, 700])
        ->and(Storage::disk('media')->exists($media->path))->toBeTrue()
        ->and($media->variants)->toHaveKey('768')
        ->and($media->variants)->not->toHaveKey('1280'); // 1200 wide: no upscaled 1280 variant
});

it('deletes the file and its variants with the record', function () {
    $media = $this->uploader->store(UploadedFile::fake()->image('gone.png', 900, 600))->fresh();
    $paths = [$media->path, ...array_values($media->variants)];

    $media->delete();

    foreach ($paths as $path) {
        expect(Storage::disk('media')->exists($path))->toBeFalse();
    }
});

it('records where media is used', function () {
    $cover = Media::factory()->create();
    $series = Series::factory()->create(['cover_media_id' => $cover->id]);

    expect($cover->isInUse())->toBeTrue()
        ->and(MediaUsage::sole()->only('usable_type', 'field'))->toBe(['usable_type' => 'series', 'field' => 'cover']);

    $series->update(['cover_media_id' => null]);
    expect($cover->isInUse())->toBeFalse();

    $series->update(['cover_media_id' => $cover->id]);
    $series->delete();
    expect($cover->isInUse())->toBeFalse();
});

it('writes the file back when the same image is uploaded again after its file went missing', function () {
    $first = $this->uploader->store(jpegWithExif());
    Storage::disk('media')->delete([$first->path, ...array_values($first->fresh()->variants ?? [])]);
    expect(Storage::disk('media')->exists($first->path))->toBeFalse();

    $again = $this->uploader->store(jpegWithExif());

    expect($again->id)->toBe($first->id)
        ->and(Storage::disk('media')->exists($first->path))->toBeTrue()
        ->and(Media::count())->toBe(1);

    // An intact file is left alone.
    Storage::disk('media')->put($first->path, 'marker');
    $this->uploader->store(jpegWithExif());
    expect(Storage::disk('media')->get($first->path))->toBe('marker');
});

it('empties the page cache once the variants exist, so cached pages switch to the WebP files', function () {
    $media = $this->uploader->store(jpegWithExif(1600, 900));

    ResponseCache::shouldReceive('clear')->once();
    (new GenerateMediaVariants($media->id))->handle(app(VariantGenerator::class));

    expect($media->fresh()->variants)->toHaveKeys(['320', '768', '1280', 'og']);
});
