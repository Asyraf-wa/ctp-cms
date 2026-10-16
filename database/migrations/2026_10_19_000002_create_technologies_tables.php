<?php

use App\Support\SimpleIcons;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Technologies with a logo, picked per tutorial (tech stack) and per project; categories fall back to one.
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 80)->unique();
            $table->string('icon_type', 10)->default('simple'); // simple | media | lucide
            $table->string('icon', 80)->nullable();              // Simple Icons slug or Lucide name
            $table->foreignId('icon_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('color', 7)->nullable();              // brand colour, used on hover
            $table->string('website_url')->nullable();
            $table->timestamps();
        });

        // Ordered: the first technology is the tutorial's main one (shown in lists). Version replaces "Tested with".
        Schema::create('article_technology', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->cascadeOnDelete();
            $table->string('version', 30)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->unique(['article_id', 'technology_id']);
        });

        Schema::create('project_technology', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->primary(['project_id', 'technology_id']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('technology_id')->nullable()->after('icon')->constrained()->nullOnDelete();
        });

        $this->moveExistingData();

        Schema::table('articles', fn (Blueprint $table) => $table->dropColumn('tested_with'));
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('stack'));
    }

    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $table) => $table->json('tested_with')->nullable());
        Schema::table('projects', fn (Blueprint $table) => $table->json('stack')->nullable());
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('technology_id');
        });
        Schema::dropIfExists('project_technology');
        Schema::dropIfExists('article_technology');
        Schema::dropIfExists('technologies');
    }

    // "Tested with" rows and project stack strings become technologies; categories named like a brand get its logo.
    private function moveExistingData(): void
    {
        $ids = [];
        $technology = function (string $name) use (&$ids): int {
            $name = trim($name);
            $slug = Str::slug($name) ?: Str::lower(Str::random(6));
            if (isset($ids[$slug])) {
                return $ids[$slug];
            }
            $icon = SimpleIcons::find(SimpleIcons::match($name));
            $id = DB::table('technologies')->where('slug', $slug)->value('id') ?? DB::table('technologies')->insertGetId([
                'name' => $name, 'slug' => $slug,
                'icon_type' => $icon ? 'simple' : 'lucide', 'icon' => $icon['slug'] ?? 'code-xml', 'color' => $icon['hex'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return $ids[$slug] = (int) $id;
        };

        foreach (DB::table('articles')->whereNotNull('tested_with')->get(['id', 'tested_with']) as $article) {
            foreach (array_values(json_decode((string) $article->tested_with, true) ?: []) as $i => $item) {
                if (filled($item['name'] ?? null)) {
                    DB::table('article_technology')->insertOrIgnore([
                        'article_id' => $article->id, 'technology_id' => $technology($item['name']),
                        'version' => $item['version'] ?? null, 'sort' => $i,
                    ]);
                }
            }
        }

        foreach (DB::table('projects')->whereNotNull('stack')->get(['id', 'stack']) as $project) {
            foreach (array_values(json_decode((string) $project->stack, true) ?: []) as $i => $name) {
                if (filled($name)) {
                    DB::table('project_technology')->insertOrIgnore(['project_id' => $project->id, 'technology_id' => $technology($name), 'sort' => $i]);
                }
            }
        }

        foreach (DB::table('categories')->get(['id', 'name']) as $category) {
            if (SimpleIcons::match($category->name)) {
                DB::table('categories')->where('id', $category->id)->update(['technology_id' => $technology($category->name)]);
            }
        }
    }
};
