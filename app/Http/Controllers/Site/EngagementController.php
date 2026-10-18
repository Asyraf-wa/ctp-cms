<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * ART-10 view beacon and ART-12 kudos. Both are POSTs from our own pages without a CSRF token
 * (pages are response-cached), so they check the origin, filter bots and are rate limited.
 */
class EngagementController extends Controller
{
    public function view(Request $request, Article $article): Response
    {
        if ($article->isPubliclyVisible() && Visitor::isSameOrigin($request) && ! Visitor::isBot($request)) {
            $inserted = DB::table('article_views')->insertOrIgnore([
                'article_id' => $article->id,
                'viewed_on' => now()->toDateString(),
                'visitor_hash' => Visitor::hash($request),
            ]);

            // Query builder, not Eloquent: a view must not touch updated_at or fire model events.
            if ($inserted) {
                DB::table('articles')->where('id', $article->id)->increment('views');
            }
        }

        return response()->noContent();
    }

    public function kudos(Request $request, Article $article): JsonResponse
    {
        abort_unless($article->isPubliclyVisible(), 404);
        abort_unless(Visitor::isSameOrigin($request) && ! Visitor::isBot($request), 403);

        $inserted = DB::table('article_kudos')->insertOrIgnore([
            'article_id' => $article->id,
            'visitor_hash' => Visitor::hash($request),
            'created_at' => now(),
        ]);

        if ($inserted) {
            DB::table('articles')->where('id', $article->id)->increment('kudos');
        }

        return response()->json([
            'count' => (int) DB::table('articles')->where('id', $article->id)->value('kudos'),
            'given' => true,
        ]);
    }
}
