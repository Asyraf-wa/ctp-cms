<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Contracts\HasPublicPath;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// PRD TAX-02.
#[Fillable(['name', 'slug', 'description', 'legacy_id'])]
class Tag extends Model implements HasPublicPath
{
    /** @use HasFactory<TagFactory> */
    use HasFactory, HasSlug;

    protected function slugSourceColumn(): string
    {
        return 'name';
    }

    public function pathForSlug(string $slug): ?string
    {
        return '/tag/'.$slug;
    }

    public function usageCount(): int
    {
        return DB::table('article_tag')->where('tag_id', $this->id)->count();
    }

    /**
     * Merge this tag into $target (TAX-02): articles move over without duplicates,
     * the old tag URL redirects to the target, and this tag is deleted.
     */
    public function mergeInto(Tag $target): void
    {
        if ($target->is($this)) {
            return;
        }

        DB::transaction(function () use ($target) {
            $already = DB::table('article_tag')->where('tag_id', $target->id)->pluck('article_id');

            DB::table('article_tag')->where('tag_id', $this->id)->whereIn('article_id', $already)->delete();
            DB::table('article_tag')->where('tag_id', $this->id)->update(['tag_id' => $target->id]);

            Redirect::record($this->publicPath(), $target->publicPath(), Redirect::SOURCE_SLUG);

            $this->delete();
        });
    }
}
