<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

// PRD PUB-09: showcase grid filtered by tech, and the project detail page.
class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $tech = $request->string('tech')->trim()->value() ?: null;

        $projects = Project::published()->with('cover')
            ->when($tech, fn ($q, string $t) => $q->whereJsonContains('stack', $t))
            ->orderByDesc('is_featured')->orderBy('sort')->latest('started_at')
            ->get();

        return view('site.projects', [
            'projects' => $projects,
            'tech' => $tech,
            'stack' => Project::stackOptions(),
        ]);
    }

    public function show(string $slug): View
    {
        $project = Project::published()->where('slug', $slug)->with(['cover', 'seo'])->firstOrFail();

        $gallery = collect($project->gallery ?? [])
            ->map(fn (array $item) => ['media' => Media::find($item['media_id'] ?? null), 'caption' => $item['caption'] ?? null])
            ->filter(fn (array $item) => $item['media'] !== null)
            ->values();

        return view('site.project', [
            'project' => $project,
            'gallery' => $gallery,
            'articles' => $project->articles()->published()->forCards()->get(),
        ]);
    }
}
