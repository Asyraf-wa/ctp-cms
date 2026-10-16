<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // A visible link to the sitemap in the footer's bottom bar (legal menu). Editable in Admin → Menus.
    public function up(): void
    {
        $menuId = DB::table('menus')->where('location', 'legal')->value('id');
        if (! $menuId || DB::table('menu_items')->where('menu_id', $menuId)->where('url', '/sitemap.xml')->exists()) {
            return;
        }

        DB::table('menu_items')->insert([
            'menu_id' => $menuId,
            'label' => 'Sitemap',
            'type' => 'url',
            'url' => '/sitemap.xml',
            'target' => '_self',
            'is_enabled' => true,
            'sort' => (int) DB::table('menu_items')->where('menu_id', $menuId)->max('sort') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Cache::forget('menus.v1');
    }

    public function down(): void
    {
        DB::table('menu_items')->where('url', '/sitemap.xml')->where('type', 'url')->delete();
        Cache::forget('menus.v1');
    }
};
