<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRD MENU-01..06.
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('location', 30)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->string('label');
            $table->string('type', 20); // url | route | page | … (MenuItem::TYPES)
            $table->nullableMorphs('linkable');
            $table->string('url', 500)->nullable();
            $table->string('route', 50)->nullable();
            $table->string('target', 10)->default('_self');
            $table->string('icon', 60)->nullable();
            $table->string('badge', 20)->nullable();
            $table->string('css_class', 100)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'sort']);
        });

        // Default menus (PRD MENU-01, T1.08). Data, not a seeder, so production gets them on the first migrate.
        $now = now();
        $menus = [
            'header' => ['Header', [['Home', 'home'], ['All Tutorials', 'tutorials'], ['Blog', 'blog'], ['Projects', 'projects']]],
            'footer_1' => ['Platform', [['All Tutorials', 'tutorials'], ['Series', 'series'], ['Blog', 'blog'], ['Projects', 'projects']]],
            'footer_2' => ['Company', [['About', null, '/about'], ['Contact', 'contact'], ['RSS Feed', 'feed']]],
            'footer_3' => ['More', []],
            'legal' => ['Legal', [['Privacy Policy', null, '/privacy'], ['Terms of Service', null, '/terms']]],
        ];

        foreach ($menus as $location => [$name, $items]) {
            $menuId = DB::table('menus')->insertGetId(['location' => $location, 'name' => $name, 'created_at' => $now, 'updated_at' => $now]);

            foreach ($items as $i => $item) {
                DB::table('menu_items')->insert([
                    'menu_id' => $menuId,
                    'sort' => $i + 1,
                    'label' => $item[0],
                    'type' => $item[1] ? 'route' : 'url',
                    'route' => $item[1],
                    'url' => $item[2] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
