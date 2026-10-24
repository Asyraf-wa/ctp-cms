<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

// Launch copy for About, Privacy Policy and Terms (the footer links /terms). Run once, then edit in the admin:
//   php artisan db:seed --class=LaunchPagesSeeder --force
// It overwrites those three pages, so do not run it again after editing them.
class LaunchPagesSeeder extends Seeder
{
    public function run(): void
    {
        $this->page('about', 'About', 1, 'Who writes Code The Pixel, what it covers and how to get in touch.', [
            ['h2', 'Who writes here'],
            ['p', 'Hi, I am Asyraf Wahi Anuar, a web developer from Malaysia. Code The Pixel is where I write down how I solve everyday problems with PHP, CakePHP, Laravel and Joomla, plus the tools and front-end bits that go with them.'],
            ['h2', 'What you will find'],
            ['ul', [
                'Tutorials that solve one problem each, with the steps and code you need to follow along.',
                'Series that take a bigger topic one part at a time.',
                'Projects, and occasional notes on the blog.',
            ]],
            ['h2', 'How the tutorials are written'],
            ['p', 'I try every step before it is published, and I keep older tutorials up to date when a framework version changes. Each tutorial shows its last update date, so you can tell how current it is.'],
            ['h2', 'A short history'],
            ['p', 'The site started on CakePHP and moved to Laravel in 2026. The tutorials were carried over, and old links keep working.'],
            ['h2', 'Get in touch'],
            ['p', 'Found a mistake, or have a question about a tutorial? Use the contact page, or leave a comment under the article. I read everything, though replies can take a few days.'],
        ]);

        $this->page('privacy', 'Privacy Policy', 2, 'What Code The Pixel collects, why, how long it is kept, and your rights under the PDPA.', [
            ['p', 'Last updated: 17 October 2026. This policy explains what personal data Code The Pixel (codethepixel.com) collects and what happens to it. It follows the Personal Data Protection Act 2010 of Malaysia (PDPA).'],
            ['h2', 'Who is responsible'],
            ['p', 'The site is run by Asyraf Wahi Anuar, who is the data controller. You can reach me through the contact page.'],
            ['h2', 'What is collected'],
            ['h3', 'Reading the site'],
            ['p', 'You can read everything without an account. The site does not use advertising cookies and does not build a profile of you. Article views and kudos are counted with a salted, one-way hash of your IP address and browser, which cannot be turned back into either. Your light or dark theme choice and your cookie choice are stored in your own browser.'],
            ['h3', 'Comments'],
            ['p', 'When you comment, I store your name, your email address and your comment. Your email is never shown. It is used to recognise returning commenters, to send you a reply notification if you ask for one, and to show your Gravatar picture. Showing it means your browser requests the picture from gravatar.com, which sees your IP address and a hash of your email. A salted hash of your IP address and browser is also stored to limit spam. Comments are reviewed before they appear and can be removed on request.'],
            ['h3', 'Contact form'],
            ['p', 'When you write to me, I store your name, email, message and a salted hash of your IP address. I use them only to reply. You are asked to agree to this before sending. Messages and my replies are deleted automatically 12 months after they arrive.'],
            ['h3', 'Spam protection'],
            ['p', 'Forms may use Cloudflare Turnstile to tell people from bots. Cloudflare receives technical details such as your IP address when the check runs, under its own privacy policy.'],
            ['h3', 'Videos and embeds'],
            ['p', 'YouTube and CodePen embeds load only after you click them. Nothing from those services is loaded before that. YouTube videos use the privacy-enhanced youtube-nocookie.com domain.'],
            ['h3', 'Signing in'],
            ['p', 'Only the site administrator signs in, with Google. Visitors do not need or get an account.'],
            ['h3', 'Analytics (only with your consent)'],
            ['p', 'If you accept, Google Analytics (Google LLC) counts visits and shows me which tutorials are read. It sets cookies in your browser and receives your IP address, browser details and the pages you view, and Google may process this outside Malaysia. Nothing is loaded and no analytics cookie is set unless you accept. If you decline, or withdraw later, the analytics cookies are removed.'],
            ['h2', 'Cookies'],
            ['p', 'Technical cookies needed for forms and security are always used. Analytics cookies (_ga and _ga_…) are set only if you click Accept on the cookie notice. You can change your choice at any time with Cookie settings in the footer.'],
            ['h2', 'Who sees your data'],
            ['p', 'Your data is not sold or shared for marketing. It is stored on the web hosting provider that runs the site, and backed up. It is shared only with the services named above, and where the law requires it.'],
            ['h2', 'How long it is kept'],
            ['ul', [
                'Contact messages: deleted after 12 months.',
                'Comments: kept while the article is online, or until you ask me to remove them.',
                'View and kudos hashes: kept only as long as needed to avoid counting the same visit twice.',
            ]],
            ['h2', 'Your rights'],
            ['p', 'Under the PDPA you can ask to see the personal data I hold about you, ask me to correct it, and withdraw your consent. To remove a comment or a message, contact me with the email address you used and the page it was sent from. I will respond within 21 days.'],
            ['h2', 'Changes'],
            ['p', 'If this policy changes, the date above changes with it.'],
        ]);

        $this->page('terms', 'Terms of Service', 3, 'The rules for using Code The Pixel, its content and its comments.', [
            ['p', 'Last updated: 17 October 2026. By using Code The Pixel (codethepixel.com) you agree to these terms.'],
            ['h2', 'Using the content'],
            ['p', 'The tutorials, text and images are published for you to read and learn from. Code samples can be used in your own projects, personal or commercial. Please do not republish an article in full without my permission. Linking to it, quoting a short part with credit, or writing about it is welcome.'],
            ['h2', 'No warranty'],
            ['p', 'I test tutorials, but software changes and setups differ. Everything is provided as is, without warranty of any kind. Back up your work and try new steps in a safe environment first. I am not liable for loss or damage from following a tutorial or using its code.'],
            ['h2', 'Comments and messages'],
            ['p', 'Be respectful and stay on topic. I may edit or remove comments that are spam, abusive, unlawful, or that promote products without a clear reason. Comments are reviewed before they appear, and you remain responsible for what you write.'],
            ['h2', 'Links to other sites'],
            ['p', 'Articles may link to other websites and services. I do not control them and am not responsible for their content or privacy practices.'],
            ['h2', 'Privacy'],
            ['p', 'How personal data is handled is described in the Privacy Policy.'],
            ['h2', 'Changes'],
            ['p', 'I may update these terms. The date above shows when they last changed, and continuing to use the site means you accept the current version.'],
            ['h2', 'Governing law'],
            ['p', 'These terms are governed by the laws of Malaysia.'],
        ]);
    }

    /** @param list<array{0: string, 1: string|list<string>}> $blocks */
    private function page(string $slug, string $title, int $sort, string $excerpt, array $blocks): void
    {
        $text = fn (string $t) => [['type' => 'text', 'text' => $t]];
        $content = array_map(fn (array $b) => match ($b[0]) {
            'h2' => ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => $text($b[1])],
            'h3' => ['type' => 'heading', 'attrs' => ['level' => 3], 'content' => $text($b[1])],
            'ul' => ['type' => 'bulletList', 'content' => array_map(fn (string $li) => ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => $text($li)]]], $b[1])],
            default => ['type' => 'paragraph', 'content' => $text($b[1])],
        }, $blocks);

        // Saving through the model renders body_rendered, the TOC and the cache flushes.
        $page = Page::firstOrNew(['slug' => $slug]);
        $page->fill(['title' => $title, 'excerpt' => $excerpt, 'body' => ['type' => 'doc', 'content' => $content], 'sort' => $sort, 'is_published' => true])->save();
    }
}
