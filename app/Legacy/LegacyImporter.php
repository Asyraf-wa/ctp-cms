<?php

namespace App\Legacy;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Media\MediaUploader;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\Redirect;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\SimpleIcons;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * PRD MIG-01..07 (ARCH §10): the old CakePHP site (database ctp_legacy) → CTP.
 * Tutorials only; the 4 Blog-category posts get redirects instead (D10). Idempotent by legacy_id:
 * articles already imported are left alone unless $force, so manual QA edits survive a re-run.
 */
class LegacyImporter
{
    public const BLOG_CATEGORY = 5;

    public const LIVE = 'https://codethepixel.com';

    // Sidebar order: the big categories first, Misc last.
    public const CATEGORY_SORT = [1 => 1, 2 => 2, 3 => 3, 6 => 4, 4 => 9];

    /** legacy category id => [name, lucide icon, technology slug] */
    public const CATEGORIES = [
        1 => ['CakePHP', 'code-xml', 'cakephp'],
        2 => ['Joomla!', 'globe', 'joomla'],
        3 => ['Tools & Software', 'wrench', null],
        4 => ['Misc', 'braces', null],
        6 => ['User Interface', 'layout-template', null],
    ];

    // The Emmet blog post has a tutorial twin; the other legacy blog posts are gone (410).
    public const BLOG_REDIRECTS = ['Using-Emmet-in-VS-Code' => 'Boost-Your-Productivity-with-Emmet-in-VS-Code'];

    private const BRAND_TAGS = ['codethepixel', 'ctp', 'blog'];

    private ImportReport $report;

    private bool $dryRun = false;

    private bool $force = false;

    private bool $downloads = true;

    /** @var array<int, int> legacy category id => new id */
    private array $categoryIds = [];

    /** @var array<int, int> legacy tag id => new id */
    private array $tagIds = [];

    /** @var array<string, bool> image URL => reachable, remembered between runs */
    private array $probeCache = [];

    public function __construct(private HtmlCleaner $cleaner, private MediaUploader $uploader) {}

    /** @param  list<int>|null  $only  legacy article ids */
    public function run(bool $dryRun = false, bool $force = false, bool $downloads = true, ?array $only = null): ImportReport
    {
        $this->report = new ImportReport($dryRun);
        $this->dryRun = $dryRun;
        $this->force = $force;
        $this->downloads = $downloads;
        $this->probeCache = $this->loadProbeCache();

        $articles = $this->legacy()->table('articles')->orderBy('id')
            ->when($only, fn ($q) => $q->whereIn('id', $only))->get();

        $this->categories();
        $this->tags($articles);

        foreach ($articles as $row) {
            if ((int) $row->category_id === self::BLOG_CATEGORY) {
                $this->report->skipped[] = $row->title.' (blog post, D10)';

                continue;
            }
            try {
                $this->article($row);
            } catch (Throwable $e) {
                $this->report->errors[] = "#{$row->id} {$row->title}: ".$e->getMessage();
            }
        }

        $this->redirects($articles);
        $this->saveProbeCache();

        return $this->report;
    }

    // --- Categories and tags --------------------------------------------------------------------

    private function categories(): void
    {
        foreach (self::CATEGORIES as $legacyId => [$name, $icon, $tech]) {
            $category = Category::where('legacy_id', $legacyId)->first() ?? Category::where('name', $name)->first();
            $this->report->count('categories', $category ? 'kept' : 'created');
            if ($this->dryRun) {
                $this->categoryIds[$legacyId] = $category ? $category->id : 0;

                continue;
            }
            $category ??= new Category(['name' => $name]);
            $category->fill(['legacy_id' => $legacyId, 'icon' => $category->icon ?: $icon, 'sort' => $category->sort ?: self::CATEGORY_SORT[$legacyId]]);
            if ($tech && ! $category->technology_id) {
                $category->technology_id = $this->technology($tech)?->id;
            }
            $category->save();
            $this->categoryIds[$legacyId] = $category->id;
        }
    }

    /**
     * Tags used by the imported tutorials. Near-duplicates (CakePHP4 / cakephp-4) are merged under the
     * most used label; brand tags and tags nothing uses are dropped.
     *
     * @param  Collection<int, \stdClass>  $articles
     */
    private function tags(Collection $articles): void
    {
        $tutorialIds = $articles->where('category_id', '!=', self::BLOG_CATEGORY)->pluck('id')->all();
        $links = $this->legacy()->table('tags_tagged')->where('fk_model', 'Articles')->whereIn('fk_id', $tutorialIds)
            ->selectRaw('tag_id, count(*) as uses')->groupBy('tag_id')->pluck('uses', 'tag_id');

        $groups = $this->legacy()->table('tags_tags')->whereIn('id', $links->keys())->get()
            ->groupBy(fn ($t) => preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($t->label))));

        foreach ($groups as $key => $variants) {
            if (in_array($key, self::BRAND_TAGS, true) || $key === '') {
                $this->report->count('tags', 'dropped');

                continue;
            }
            $main = $variants->sortByDesc(fn ($t) => $links[$t->id] ?? 0)->first();
            if ($variants->count() > 1) {
                $this->report->notes[] = 'Merged tags '.$variants->pluck('label')->join(', ').' → '.$main->label;
            }
            $tag = Tag::where('legacy_id', $main->id)->first() ?? Tag::where('slug', Str::slug($main->label))->first();
            $this->report->count('tags', $tag ? 'kept' : 'created');
            if (! $this->dryRun) {
                $tag ??= Tag::create(['name' => $main->label, 'legacy_id' => $main->id]);
                if (! $tag->legacy_id) {
                    $tag->update(['legacy_id' => $main->id]);
                }
            }
            foreach ($variants as $variant) {
                $this->tagIds[$variant->id] = $tag->id ?? 0;
            }
        }
    }

    // --- Articles -------------------------------------------------------------------------------

    private function article(object $row): void
    {
        $existing = Article::withTrashed()->where('legacy_id', $row->id)->first();
        if ($existing && ! $this->force) {
            $this->report->count('articles', 'already imported');
            $this->report->articles[] = ['title' => $existing->title, 'url' => url($existing->publicPath() ?? '/'), 'status' => 'kept', 'notes' => []];

            return;
        }

        [$title, $deprecated] = $this->title((string) $row->title);
        $slug = Str::slug(Str::lower((string) $row->slug));
        $notes = [];
        $missingImages = [];

        $result = $this->cleaner->clean((string) $row->body, function (string $src, string $alt) use ($slug, $title, &$missingImages) {
            return $this->inlineImage($src, $alt, $slug, $title, $missingImages);
        });
        $notes = [...$notes, ...$result->warnings];
        if ($result->headings === 0) {
            $notes[] = 'No headings found: check the structure';
        }
        if ($missingImages) {
            $notes[] = count($missingImages).' screenshot(s) missing: '.implode(', ', array_map('basename', $missingImages));
        }

        $doc = RichContentRenderer::make($result->html)->customBlocks(Article::BLOCKS)->toArray();
        $excerpt = $this->excerpt($row->meta_description, $result->html);

        $this->report->count('articles', $existing ? 'updated' : 'created');
        $this->report->totals['headings'] = ($this->report->totals['headings'] ?? 0) + $result->headings;
        $this->report->totals['code blocks'] = ($this->report->totals['code blocks'] ?? 0) + $result->codeBlocks + $result->terminals;
        $this->report->totals['missing images'] = ($this->report->totals['missing images'] ?? 0) + count($missingImages);

        if ($this->dryRun) {
            $this->report->articles[] = ['title' => $title, 'url' => url('/'.$slug), 'status' => $existing ? 'update' : 'create', 'notes' => $notes, 'review' => $deprecated || $missingImages];

            return;
        }

        $article = $existing ?? new Article;
        $article->fill([
            'type' => ArticleType::Tutorial,
            'author_id' => $this->authorId(),
            'title' => $title,
            'slug' => $existing ? $existing->slug : $slug,
            'excerpt' => $excerpt,
            'body' => $doc,
            'category_id' => $this->categoryIds[(int) $row->category_id] ?? null,
            'status' => ArticleStatus::Published,
            'published_at' => $this->publishedAt($row),
            'content_updated_at' => $this->updatedAt($row),
            'views' => (int) $row->hits,
            'kudos' => (int) $row->kudos,
            'is_featured' => (bool) $row->featured,
            'needs_review' => $deprecated || (bool) $missingImages,
            'comments_enabled' => true,
            'legacy_id' => (int) $row->id,
        ]);
        if (! $article->featured_media_id && ($poster = $this->poster($row, $title))) {
            $article->featured_media_id = $poster->id;
        } elseif (! $article->featured_media_id) {
            $notes[] = 'Poster could not be downloaded';
        }
        $article->save();

        $article->tags()->sync($this->legacy()->table('tags_tagged')->where('fk_model', 'Articles')->where('fk_id', $row->id)->pluck('tag_id')
            ->map(fn ($id) => $this->tagIds[$id] ?? null)->filter()->unique()->values()->all());

        $keywords = collect(explode(',', (string) $row->meta_key))->map(fn ($k) => trim($k))
            ->reject(fn ($k) => $k === '' || preg_match('/^code\s*the\s*pixel$/i', $k))->join(', ');
        $article->seo()->updateOrCreate([], ['keywords' => $keywords ?: null, 'description' => filled($row->meta_description) ? Str::limit(trim((string) $row->meta_description), 300, '') : null]);

        $this->techStack($article, $title, (int) $row->category_id);

        $this->report->articles[] = ['title' => $title, 'url' => url($article->publicPath() ?? '/'), 'status' => $existing ? 'updated' : 'created',
            'notes' => $notes, 'review' => $article->needs_review, 'headings' => $result->headings, 'code' => $result->codeBlocks + $result->terminals];
    }

    /** @return array{0: string, 1: bool} title without the deprecated marker, and whether it had one */
    private function title(string $title): array
    {
        $clean = trim((string) preg_replace('/\s*[\[(]\s*deprecated\s*[\])]\s*/i', ' ', $title));

        return [$clean, $clean !== trim($title) || stripos($title, 'deprecated') !== false];
    }

    private function excerpt(?string $meta, string $html): ?string
    {
        $text = filled($meta) ? (string) $meta : strip_tags((string) Str::of($html)->before('</p>'));
        $text = Str::squish(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));

        return $text !== '' ? Str::limit($text, 300) : null;
    }

    // publish_on is a date: publish at 09:00 Malaysia time.
    private function publishedAt(object $row): Carbon
    {
        $date = $row->publish_on ?: $row->created;

        return Carbon::parse(substr((string) $date, 0, 10).' 09:00:00', config('ctp.display_timezone'))->utc();
    }

    // "Updated" is only shown when the legacy row changed well after it was published.
    private function updatedAt(object $row): ?Carbon
    {
        if (! $row->modified) {
            return null;
        }
        $modified = Carbon::parse((string) $row->modified, config('ctp.display_timezone'))->utc();

        return $modified->gt($this->publishedAt($row)->addDays(2)) ? $modified : null;
    }

    // "CakePHP 4 …" → CakePHP 4.x, "Joomla 5" → Joomla 5.x, "Bootstrap 5.3.3" → Bootstrap 5.3.3, "Laragon 6" → Laragon 6.
    private function techStack(Article $article, string $title, int $legacyCategory): void
    {
        /** @var array<string, ?string> $found technology slug => version */
        $found = [];
        foreach (['cakephp' => '/CakePHP[\s-]*(\d)(?:\.(\d))?/i', 'joomla' => '/Joomla!?\s*(\d)/i', 'bootstrap' => '/Bootstrap\s*(\d(?:\.\d+)*)/i', 'laragon' => '/Laragon\s*(\d+)/i'] as $slug => $pattern) {
            if (preg_match($pattern, $title, $m)) {
                $version = $slug === 'cakephp' || $slug === 'joomla' ? $m[1].'.x' : $m[1];
                $found[$slug] = $version;
            }
        }
        if (! $found && isset(self::CATEGORIES[$legacyCategory][2])) {
            $found[self::CATEGORIES[$legacyCategory][2]] = null;
        }
        if (str_contains(strtolower($title), 'php') && ! isset($found['cakephp']) && ! isset($found['php'])) {
            $found['php'] = null;
        }

        $rows = [];
        foreach (array_keys($found) as $i => $slug) {
            if ($technology = $this->technology($slug)) {
                $rows[$technology->id] = ['version' => $found[$slug], 'sort' => $i];
            }
        }
        if ($rows && $article->techStack()->count() === 0) {
            foreach ($rows as $id => $pivot) {
                $article->techStack()->create(['technology_id' => $id, ...$pivot]);
            }
        }
    }

    private function technology(string $slug): ?Technology
    {
        if ($this->dryRun) {
            return Technology::where('slug', $slug)->first();
        }
        $icon = SimpleIcons::find($slug);

        return Technology::firstOrCreate(['slug' => $slug], [
            'name' => $icon['title'] ?? Str::headline($slug),
            'icon_type' => $icon ? 'simple' : 'lucide',
            'icon' => $icon ? $slug : 'code-xml',
        ]);
    }

    private function authorId(): int
    {
        return app(GeneralSettings::class)->default_author_id ?? (int) User::query()->oldest('id')->value('id');
    }

    // --- Images ---------------------------------------------------------------------------------

    private function poster(object $row, string $title): ?Media
    {
        if (blank($row->poster) || ! $this->downloads) {
            return null;
        }
        $dir = basename(str_replace('\\', '/', (string) $row->poster_dir));
        // Some rows store a wrong folder (e.g. "CakePHP"); the article slug is the usual folder.
        $urls = array_values(array_unique(array_map(
            fn (string $folder) => self::LIVE.'/files/Articles/poster/'.rawurlencode($folder).'/'.rawurlencode((string) $row->poster),
            [$dir, (string) $row->slug],
        )));

        $media = $this->download($urls, (string) $row->poster, 'legacy/posters', $title);
        if ($media) {
            $this->report->count('images', 'posters downloaded');
        }

        return $media;
    }

    /** @param  list<string>  $missing */
    private function inlineImage(string $src, string $alt, string $slug, string $title, array &$missing): string
    {
        $path = '/'.ltrim((string) preg_replace('~^(\.\./)+~', '', (string) parse_url($src, PHP_URL_PATH)), '/');
        $relative = (string) preg_replace('~^/(ctp/)?(webroot/)?~', '', $path);
        $candidates = [
            'file://'.base_path('legacy/webroot/'.$relative),
            self::LIVE.'/'.$relative,
            self::LIVE.$path,
            'https://web.archive.org/web/2024id_/'.self::LIVE.$path,
        ];

        $media = $this->dryRun ? null : $this->download($candidates, basename($relative), 'legacy/'.$slug, $alt ?: $title);
        if ($media) {
            $this->report->count('images', 'inline imported');
            $config = ['media_id' => $media->id, 'alt' => $alt ?: $title, 'caption' => null, 'size' => 'normal', 'link' => null];

            return '<div data-type="customBlock" data-id="image" data-config="'.e((string) json_encode($config, JSON_UNESCAPED_SLASHES)).'"></div>';
        }

        // D11: the screenshot is gone everywhere. Say so instead of leaving a hole, and flag the article.
        $missing[] = $relative;
        $this->report->count('images', 'inline missing');
        $config = ['type' => 'warning', 'title' => 'Screenshot unavailable', 'body' => 'The original screenshot ('.basename($relative).') was lost when the old site moved. The text describes what it showed.'];

        return '<div data-type="customBlock" data-id="callout" data-config="'.e((string) json_encode($config, JSON_UNESCAPED_SLASHES)).'"></div>';
    }

    /** @param  list<string>  $candidates */
    private function download(array $candidates, string $filename, string $folder, string $alt): ?Media
    {
        foreach ($candidates as $url) {
            $contents = null;
            if (str_starts_with($url, 'file://')) {
                $file = substr($url, 7);
                $contents = is_file($file) ? (string) file_get_contents($file) : null;
            } elseif ($this->downloads && ($this->probeCache[$url] ?? true)) {
                try {
                    $response = Http::timeout(15)->connectTimeout(5)->withUserAgent('CTP legacy import')->get($url);
                    $ok = $response->successful() && str_starts_with((string) $response->header('Content-Type'), 'image/');
                    $this->probeCache[$url] = $ok;
                    $contents = $ok ? $response->body() : null;
                } catch (Throwable) {
                    $this->probeCache[$url] = false;
                }
            }
            if ($contents === null || $contents === '') {
                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'ctp');
            file_put_contents($tmp, $contents);
            try {
                $media = $this->uploader->store(new UploadedFile($tmp, $filename, null, null, true), $this->folder($folder), ['alt' => Str::limit($alt, 290, '')]);
            } finally {
                @unlink($tmp);
            }

            return $media;
        }

        return null;
    }

    private function folder(string $path): int
    {
        $parentId = null;
        foreach (explode('/', $path) as $name) {
            $parentId = (int) MediaFolder::firstOrCreate(['name' => $name, 'parent_id' => $parentId])->id;
        }

        return (int) $parentId;
    }

    // --- Redirects (MIG-05) ----------------------------------------------------------------------

    /** @param  Collection<int, \stdClass>  $articles */
    private function redirects(Collection $articles): void
    {
        $bySlug = Article::whereNotNull('legacy_id')->get(['id', 'slug', 'type', 'legacy_id'])->keyBy('legacy_id');
        $posterMedia = Article::whereNotNull('legacy_id')->with('featuredMedia')->get()->keyBy('legacy_id');

        foreach ($articles as $row) {
            $legacySlug = (string) $row->slug;
            $target = match (true) {
                (int) $row->category_id === self::BLOG_CATEGORY && isset(self::BLOG_REDIRECTS[$legacySlug]) => $bySlug->first(fn ($a) => strcasecmp($a->slug, Str::lower(self::BLOG_REDIRECTS[$legacySlug])) === 0)?->publicPath(),
                (int) $row->category_id === self::BLOG_CATEGORY => null, // 410 Gone
                default => $bySlug->get($row->id)?->publicPath(),
            };
            $redirectsToTutorial = (int) $row->category_id !== self::BLOG_CATEGORY || isset(self::BLOG_REDIRECTS[$legacySlug]);
            if ($redirectsToTutorial && $target === null) {
                continue; // the target was not imported (dry run, --id or an error): no redirect rather than a wrong 410
            }

            foreach (['/codethepixel/articles/', '/articles/', '/codethepixel/articles/view/', '/articles/view/'] as $prefix) {
                $this->redirect($prefix.$legacySlug, $target);
            }

            // Poster URLs keep their image-search traffic.
            $media = $posterMedia->get($row->id)?->featuredMedia;
            if ($media && filled($row->poster)) {
                $dir = basename(str_replace('\\', '/', (string) $row->poster_dir));
                $this->redirect('/files/Articles/poster/'.$dir.'/'.$row->poster, (string) parse_url($media->url(), PHP_URL_PATH));
                $this->redirect('/files/Articles/poster/'.rawurlencode($dir).'/'.rawurlencode((string) $row->poster), (string) parse_url($media->url(), PHP_URL_PATH));
            }
        }

        foreach (['/codethepixel' => '/', '/codethepixel/articles' => '/tutorials', '/articles' => '/tutorials', '/codethepixel/pages/about' => '/about', '/codethepixel/contacts/add' => '/contact'] as $from => $to) {
            $this->redirect($from, $to);
        }
    }

    private function redirect(string $from, ?string $to): void
    {
        $this->report->count('redirects', $to === null ? '410 gone' : '301');
        if (! $this->dryRun) {
            Redirect::record($from, $to, Redirect::SOURCE_LEGACY);
        }
    }

    // --- Helpers --------------------------------------------------------------------------------

    private function legacy(): ConnectionInterface
    {
        config(['database.connections.legacy' => ['database' => config('ctp.legacy_database', 'ctp_legacy')] + config('database.connections.mysql')]);

        return DB::connection('legacy');
    }

    /** @return array<string, bool> */
    private function loadProbeCache(): array
    {
        $file = storage_path('app/legacy-image-probes.json');

        return is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
    }

    private function saveProbeCache(): void
    {
        if (! $this->dryRun) {
            File::put(storage_path('app/legacy-image-probes.json'), (string) json_encode($this->probeCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}
