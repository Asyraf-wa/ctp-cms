<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Seo\OgImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

// SEO-03: generate the card on first request, then serve the stored file (cached for a year: the URL is versioned).
class OgImageController extends Controller
{
    public function __invoke(string $key, OgImage $og): BinaryFileResponse
    {
        abort_unless(preg_match('/^([a-z]+)-(\d+)-([a-f0-9]{8})$/', $key, $m), 404);
        [, $type, $id] = $m;

        $model = null;
        if ($type !== 'site') {
            $class = OgImage::TYPES[$type] ?? abort(404);
            $model = $class::query()->findOrFail($id);
            abort_if($model instanceof Article && ! $model->isPubliclyVisible() && ! request()->hasValidSignature(false), 404);
        }

        // Only the current text's hash is served: old or guessed keys 404 instead of filling the disk.
        abort_unless(OgImage::key($type, $model) === $key, 404);

        $disk = Storage::disk('public');
        $path = "og/{$key}.png";
        if (! $disk->exists($path)) {
            $disk->put($path, $og->render($type, $model));
        }

        return response()->file($disk->path($path), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
