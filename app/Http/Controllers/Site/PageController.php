<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Contracts\View\View;

// PRD PUB-10. Pages are reached through RootSlugController (domain/{slug}).
class PageController extends Controller
{
    public function show(Page $page, bool $preview = false): View
    {
        abort_unless($preview || $page->is_published, 404);

        return view('site.page', ['page' => $page->loadMissing('seo'), 'preview' => $preview]);
    }

    public function preview(Page $page): View
    {
        return $this->show($page, preview: true);
    }
}
