<?php

namespace App\Console\Commands;

use App\Models\RootSlug;
use App\Support\Slugs\RootSlugs;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

// ARCH §6 rule 4: run before adding a new top-level section (e.g. /courses).
#[Signature('ctp:check-slug-conflicts {--with=* : Extra first segments you plan to add}')]
#[Description('List tutorials/pages whose domain/{slug} URL collides with a route or reserved word')]
class CheckSlugConflicts extends Command
{
    public function handle(): int
    {
        $reserved = array_unique([...RootSlugs::reserved(), ...array_map('strtolower', (array) $this->option('with'))]);

        $conflicts = RootSlug::whereIn('slug', $reserved)->get();

        if ($conflicts->isEmpty()) {
            $this->components->info('No conflicts: '.RootSlug::count().' root slugs checked against '.count($reserved).' reserved words.');

            return self::SUCCESS;
        }

        $this->components->error('These URLs would be shadowed by a route:');
        $this->table(['Slug', 'Type', 'ID'], $conflicts->map(fn (RootSlug $r) => ["/{$r->slug}", $r->sluggable_type, $r->sluggable_id]));

        return self::FAILURE;
    }
}
