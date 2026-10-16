{{--
    Temporary preview of the tutorial layout (tutorial_web_template.html), using the real legacy categories.
    Replaced by real data in Phase 2 (T2.01 home, T2.03 article page).
--}}
@php
    $categories = [
        ['name' => 'CakePHP', 'icon' => 'code-xml', 'url' => url('/category/cakephp'), 'count' => 42, 'open' => true, 'items' => [
            ['title' => 'CakePHP 4 Print PDF Using CakePDF', 'url' => '#', 'active' => true],
            ['title' => 'CakePHP 4 Authentication Using Authentication Plugin', 'url' => '#'],
            ['title' => 'Handling Irregular Plural Table Names in CakePHP', 'url' => '#'],
        ]],
        ['name' => 'Joomla!', 'icon' => 'server', 'url' => url('/category/joomla'), 'count' => 3, 'items' => [
            ['title' => 'Upgrade from Joomla 4 to Joomla 5', 'url' => '#'],
            ['title' => 'Configure Joomla! From Live Server To Local Server', 'url' => '#'],
        ]],
        ['name' => 'Tools & Software', 'icon' => 'wrench', 'url' => url('/category/tools-software'), 'count' => 3, 'items' => [
            ['title' => 'Formatting PHP in VSCode Using Prettier', 'url' => '#'],
            ['title' => 'Upgrade PHP and Apache in Laragon 6', 'url' => '#'],
        ]],
        ['name' => 'Misc', 'icon' => 'braces', 'url' => url('/category/misc'), 'count' => 11, 'items' => [
            ['title' => 'Clone GitHub Private Repository from cPanel', 'url' => '#'],
            ['title' => 'How to Make an Interactive SVG Map', 'url' => '#'],
        ]],
        ['name' => 'User Interface', 'icon' => 'layout-template', 'url' => url('/category/user-interface'), 'count' => 1, 'items' => [
            ['title' => 'Dark Mode in Bootstrap 5.3.3', 'url' => '#'],
        ]],
    ];

    $toc = [
        ['id' => 'introduction', 'label' => 'Introduction', 'level' => 2],
        ['id' => 'installation', 'label' => 'Installation Process', 'level' => 2],
        ['id' => 'utility-classes', 'label' => 'Core Utility Classes', 'level' => 2],
        ['id' => 'spacing', 'label' => 'Spacing', 'level' => 3],
        ['id' => 'typography', 'label' => 'Typography', 'level' => 3],
        ['id' => 'responsive-design', 'label' => 'Responsive Design', 'level' => 2],
        ['id' => 'conclusion', 'label' => 'Conclusion', 'level' => 2],
    ];
@endphp

<x-layouts.tutorial title="Getting Started with Tailwind CSS" description="Tailwind CSS is a utility-first CSS framework.">
    <x-slot:left>
        <x-site.category-sidebar :categories="$categories" />
    </x-slot:left>

    <article class="rounded-2xl border border-border bg-card p-6 shadow-sm transition-colors duration-300 sm:p-8">
        <div class="prose-ctp">
            <span class="text-sm font-semibold tracking-wide text-primary uppercase">User Interface</span>
            <h1 class="mt-2 mb-4">Getting Started with Tailwind CSS</h1>
            <p class="mb-8 text-sm text-muted-foreground">Published on October 15, 2026 • 5 min read</p>

            <p class="lead">Tailwind CSS is a utility-first CSS framework packed with classes like flex, pt-4, text-center and rotate-90 that can be composed to build any design, directly in your markup.</p>

            <h2 id="introduction">Introduction</h2>
            <p>Unlike traditional CSS frameworks, Tailwind doesn't give you pre-built components. Instead, it gives you low-level utility classes that let you build completely custom designs without ever leaving your HTML.</p>

            <h2 id="installation">Installation Process</h2>
            <p>The simplest way to use Tailwind CSS is via the Play CDN for development. However, for production, you should install it via npm.</p>
            <pre><code>npm install -D tailwindcss
npx tailwindcss init</code></pre>
            <p>Next, configure your template paths in the <code>tailwind.config.js</code> file.</p>

            <h2 id="utility-classes">Core Utility Classes</h2>
            <p>Here are some of the most commonly used utility concepts in Tailwind.</p>
            <h3 id="spacing">Spacing</h3>
            <p>Tailwind includes a comprehensive spacing scale. You can use classes like <code>p-4</code> for padding and <code>m-4</code> for margin.</p>
            <h3 id="typography">Typography</h3>
            <p>Control font size, weight, color, and more. For example, <code>text-xl font-bold text-slate-800</code>.</p>

            <h2 id="responsive-design">Responsive Design</h2>
            <p>Every utility class in Tailwind can be applied conditionally at different breakpoints. This makes it incredibly easy to build complex responsive interfaces without writing a single media query.</p>
            <pre><code>&lt;div class="w-full md:w-1/2 lg:w-1/3"&gt;
    &lt;!-- Content goes here --&gt;
&lt;/div&gt;</code></pre>

            <h2 id="conclusion">Conclusion</h2>
            <p>Once you get used to utility-first CSS, you'll find that building UIs is faster and more maintainable. Start small, read the documentation, and build your next tutorial using Tailwind!</p>
        </div>

        <div class="mt-12 flex items-center justify-between gap-4 border-t border-border pt-8">
            <a href="#" class="flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-primary">
                <x-lucide-chevron-left class="size-4" /> Previous: CSS Grid
            </a>
            <a href="#" class="flex items-center gap-2 text-right text-sm font-medium text-primary transition-colors hover:text-primary-hover">
                Next: Master Flexbox <x-lucide-chevron-right class="size-4" />
            </a>
        </div>
    </article>

    <x-slot:right>
        <x-site.toc :items="$toc" />
    </x-slot:right>
</x-layouts.tutorial>
