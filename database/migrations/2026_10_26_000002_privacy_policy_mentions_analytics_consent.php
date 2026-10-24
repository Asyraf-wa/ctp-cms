<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

// The Privacy Policy said "no tracking cookies". With Google Analytics behind a consent notice, it must say what
// happens instead. Only the exact sentences written by LaunchPagesSeeder are replaced; a page edited by hand is left alone.
return new class extends Migration
{
    private const READING_OLD = 'You can read everything without an account. The site does not use advertising or tracking cookies, and it does not build a profile of you. Article views and kudos are counted with a salted, one-way hash of your IP address and browser, which cannot be turned back into either. Your light or dark theme choice is stored in your own browser.';

    private const READING_NEW = 'You can read everything without an account. The site does not use advertising cookies and does not build a profile of you. Article views and kudos are counted with a salted, one-way hash of your IP address and browser, which cannot be turned back into either. Your light or dark theme choice and your cookie choice are stored in your own browser.';

    private const COOKIES_OLD = 'Only technical cookies needed for forms and security are used. If I add analytics in future, this page will say so and a notice will ask for your consent where required.';

    private const COOKIES_NEW = 'Technical cookies needed for forms and security are always used. Analytics cookies (_ga and _ga_…) are set only if you click Accept on the cookie notice. You can change your choice at any time with Cookie settings in the footer.';

    private const ANALYTICS = 'If you accept, Google Analytics (Google LLC) counts visits and shows me which tutorials are read. It sets cookies in your browser and receives your IP address, browser details and the pages you view, and Google may process this outside Malaysia. Nothing is loaded and no analytics cookie is set unless you accept. If you decline, or withdraw later, the analytics cookies are removed.';

    public function up(): void
    {
        $page = Page::where('slug', 'privacy')->first();
        $doc = $page?->body;
        if (! is_array($doc) || ! isset($doc['content'])) {
            return;
        }

        /** @param array<string, mixed> $node */
        $text = fn (array $node): string => implode('', array_column($node['content'] ?? [], 'text'));
        $changed = false;
        $out = [];

        foreach ($doc['content'] as $node) {
            $t = $text($node);

            if ($node['type'] === 'heading' && ($node['attrs']['level'] ?? 0) === 2 && $t === 'Cookies') {
                $out[] = ['type' => 'heading', 'attrs' => ['level' => 3], 'content' => [['type' => 'text', 'text' => 'Analytics (only with your consent)']]];
                $out[] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => self::ANALYTICS]]];
                $changed = true;
            }

            foreach ([self::READING_OLD => self::READING_NEW, self::COOKIES_OLD => self::COOKIES_NEW] as $old => $new) {
                if ($node['type'] === 'paragraph' && $t === $old) {
                    $node['content'] = [['type' => 'text', 'text' => $new]];
                    $changed = true;
                }
            }

            $out[] = $node;
        }

        if ($changed) {
            $page->body = ['type' => 'doc', 'content' => $out];
            $page->save(); // renders body_rendered and the TOC
        }
    }
};
