<?php

namespace App\Media;

use App\Jobs\GenerateMediaVariants;
use App\Models\Media;
use enshrined\svgSanitize\Sanitizer;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

/**
 * PRD MED-02: validate by content, strip metadata, cap the size, dedupe by hash, store, queue variants.
 */
class MediaUploader
{
    public function __construct(
        private ImageManager $images,
        private VariantGenerator $variants,
    ) {}

    /** @param array<string, mixed> $attributes extra columns (alt, caption…) */
    public function store(UploadedFile $file, ?int $folderId = null, array $attributes = []): Media
    {
        $prepared = $this->prepare($file);

        // MED-02: the same file uploaded twice is the same media item.
        if ($existing = Media::where('hash', $prepared['hash'])->first()) {
            return $existing;
        }

        $disk = config('ctp.media.disk');
        $path = $this->pathFor($file->getClientOriginalName(), $prepared['extension']);
        Storage::disk($disk)->put($path, $prepared['contents']);

        $media = Media::create([
            'folder_id' => $folderId,
            'disk' => $disk,
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'name' => Str::of(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))->replace(['-', '_'], ' ')->squish()->ucfirst()->toString(),
            'mime' => $prepared['mime'],
            'extension' => $prepared['extension'],
            'size' => strlen($prepared['contents']),
            'width' => $prepared['width'],
            'height' => $prepared['height'],
            'hash' => $prepared['hash'],
            ...$attributes,
        ]);

        $this->queueVariants($media);

        return $media;
    }

    /**
     * MED-07: swap the file, keep the id (and the URL when the type is the same).
     */
    public function replace(Media $media, UploadedFile $file): Media
    {
        $prepared = $this->prepare($file);
        $disk = Storage::disk($media->disk);

        $this->variants->deleteAll($media);

        $path = $prepared['extension'] === $media->extension
            ? $media->path
            : preg_replace('/\.[^.\/]+$/', '.'.$prepared['extension'], $media->path);

        if ($path !== $media->path) {
            $disk->delete($media->path);
        }
        $disk->put($path, $prepared['contents']);

        $media->update([
            'path' => $path,
            'mime' => $prepared['mime'],
            'extension' => $prepared['extension'],
            'size' => strlen($prepared['contents']),
            'width' => $prepared['width'],
            'height' => $prepared['height'],
            'hash' => $prepared['hash'],
        ]);

        $this->queueVariants($media);

        return $media;
    }

    /** @return array{mime: string, extension: string, contents: string, hash: string, width: ?int, height: ?int} */
    private function prepare(UploadedFile $file): array
    {
        $maxKb = (int) config('ctp.media.max_upload_kb');
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages(['file' => "“{$file->getClientOriginalName()}” is larger than {$maxKb} KB."]);
        }

        $mime = $this->detectMime($file);
        $extension = config('ctp.media.mimes')[$mime] ?? null;

        if ($extension === null) {
            throw ValidationException::withMessages(['file' => "“{$file->getClientOriginalName()}” is not an allowed file type."]);
        }

        $contents = (string) file_get_contents($file->getRealPath());
        $width = $height = null;

        if (in_array($mime, Media::RASTER, true)) {
            // Reading applies the EXIF orientation; re-encoding drops EXIF/GPS metadata.
            $max = (int) config('ctp.media.max_dimension');
            $image = $this->images->read($contents)->scaleDown(width: $max, height: $max);
            $quality = (int) config('ctp.media.quality') + 8;

            $contents = (string) match ($mime) {
                'image/jpeg' => $image->toJpeg($quality),
                'image/png' => $image->toPng(),
                'image/webp' => $image->toWebp($quality),
                'image/avif' => $image->toAvif($quality),
            };
            [$width, $height] = [$image->width(), $image->height()];
        } elseif ($mime === 'image/gif') {
            [$width, $height] = getimagesizefromstring($contents) ?: [null, null];
        } elseif ($mime === 'image/svg+xml') {
            $contents = $this->sanitizeSvg($contents, $file->getClientOriginalName());
        }

        return compact('mime', 'extension', 'contents', 'width', 'height') + ['hash' => sha1($contents)];
    }

    private function detectMime(UploadedFile $file): string
    {
        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());

        // finfo reports SVGs as XML or text; the sanitizer decides if it really is one.
        if (strtolower($file->getClientOriginalExtension()) === 'svg' && in_array($mime, ['image/svg+xml', 'image/svg', 'text/xml', 'application/xml', 'text/plain', 'text/html'], true)) {
            return 'image/svg+xml';
        }

        return $mime;
    }

    private function sanitizeSvg(string $svg, string $name): string
    {
        $sanitizer = new Sanitizer;
        $sanitizer->removeRemoteReferences(true);
        $clean = $sanitizer->sanitize($svg);

        if (! is_string($clean) || ! str_contains($clean, '<svg')) {
            throw ValidationException::withMessages(['file' => "“{$name}” is not a valid SVG."]);
        }

        return $clean;
    }

    // e.g. 2026/10/cakephp-crud-a1b2.png — readable names help image search.
    private function pathFor(string $originalName, string $extension): string
    {
        $slug = Str::of(pathinfo($originalName, PATHINFO_FILENAME))->slug()->limit(60, '')->trim('-')->toString() ?: 'file';

        return now()->format('Y/m').'/'.$slug.'-'.Str::lower(Str::random(4)).'.'.$extension;
    }

    private function queueVariants(Media $media): void
    {
        if ($media->isRaster()) {
            $this->variants->thumbnail($media);
            GenerateMediaVariants::dispatch($media->id);
        }
    }
}
