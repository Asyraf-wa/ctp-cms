<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The footer's Sitemap link was /sitemap.xml, which is for crawlers (robots.txt and Search Console already point to it).
// People get the page with every tutorial on it; it links to the XML sitemap at the bottom.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_items')->where('type', 'url')->where('url', '/sitemap.xml')->update(['url' => '/sitemap']);
    }

    public function down(): void
    {
        DB::table('menu_items')->where('type', 'url')->where('url', '/sitemap')->update(['url' => '/sitemap.xml']);
    }
};
