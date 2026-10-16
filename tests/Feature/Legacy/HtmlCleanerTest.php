<?php

use App\Legacy\CleanResult;
use App\Legacy\HtmlCleaner;

/**
 * T5.06: fixtures shaped like real legacy bodies (CRUD #3, CakePDF #14, SVG map #45,
 * Prettier #61, Joomla 5 #63), trimmed to the patterns that matter.
 */
function cleanLegacy(string $html, ?callable $images = null): CleanResult
{
    return app(HtmlCleaner::class)->clean($html, $images);
}

it('turns <br> runs into paragraphs, bold lines into H2 and "- " lines into lists (#3)', function () {
    $r = cleanLegacy("<p>CRUD creation is one of my favourite features.<br />\r\n<br />\r\n<strong>Database configuration</strong><br />\r\nThe database should be connected.<br />\r\n<br />\r\nIt will generate:<br />\r\n- Model<br />\r\n- Controller<br />\r\n<br />\r\nThat all. Happy coding :)</p>");

    expect($r->html)->toBe(implode("\n", [
        '<p>CRUD creation is one of my favourite features.</p>',
        '<h2>Database configuration</h2>',
        '<p>The database should be connected.</p>',
        '<p>It will generate:</p>',
        '<ul><li><p>Model</p></li><li><p>Controller</p></li></ul>',
        '<p>That all. Happy coding :)</p>',
    ]))->and($r->headings)->toBe(1);
});

it('uses 14pt bold spans as H2 and plain bold lines as H3 in the same article (#45, #63)', function () {
    $r = cleanLegacy('<p>Intro.<br /><br /><span style="font-size: 14pt; color: #3598db;"><strong>Get The Map</strong></span><br />Text.<br /><br /><strong>Why upgrade?</strong><br />Because.</p>');

    expect($r->html)->toContain('<h2>Get The Map</h2>')->toContain('<h3>Why upgrade?</h3>')
        ->not->toContain('style=')->not->toContain('3598db');
});

it('moves "File Location" and inline paths onto the next code block, and keeps code byte-for-byte (#14)', function () {
    $r = cleanLegacy('<p><strong>Engine Configuration</strong><br />File Location: ...\config\bootstrap.php<br />At the end of the file, load the following codes.</p>'
        .'<pre class="language-php"><code>Configure::write(\'CakePdf\', ['."\n".'    \'engine\' =&gt; \'CakePdf.DomPdf\','."\n".']);</code></pre>'
        .'<p><br />or manually add the following code to ...\src\Application.php</p><pre class="language-php"><code>$this-&gt;addPlugin(\'CakePdf\');</code></pre>');

    preg_match_all('/data-config="([^"]+)"/', $r->html, $m);
    $configs = array_map(fn ($c) => json_decode(html_entity_decode($c), true), $m[1]);

    expect($r->html)->not->toContain('File Location')
        ->and($configs[0])->toMatchArray(['language' => 'php', 'filename' => 'config/bootstrap.php', 'code' => "Configure::write('CakePdf', [\n    'engine' => 'CakePdf.DomPdf',\n]);"])
        ->and($configs[1])->toMatchArray(['filename' => 'src/Application.php', 'code' => "\$this->addPlugin('CakePdf');"]);
});

it('turns shell-only code into Terminal blocks and maps Prism languages (#3, #45)', function () {
    $r = cleanLegacy("<pre>\n<code class=\"language-php\">composer require friendsofcake/cakepdf\nbin/cake plugin load CakePdf</code></pre><pre class=\"language-markup\"><code>&lt;div&gt;Map&lt;/div&gt;</code></pre>");

    expect($r->html)->toContain('data-id="terminal"')->toContain('data-id="code"')
        ->and($r->html)->toContain('&quot;language&quot;:&quot;html&quot;')
        ->and($r->terminals)->toBe(1)->and($r->codeBlocks)->toBe(1);
});

it('reads one-line <div> layouts and keeps numbered lists counting after a break (#61)', function () {
    $r = cleanLegacy('<div>Intro text.</div><div>&nbsp;</div><div>1. Install Prettier</div><div>2. Set it as default:</div><div style="padding-left: 40px;">a) File - Preferences</div><div>3. Install Intelephense</div>');

    expect($r->html)->toContain('<p>Intro text.</p>')
        ->toContain('<ol><li><p>Install Prettier</p></li><li><p>Set it as default:</p></li></ol>')
        ->toContain('<p>a) File - Preferences</p>')
        ->toContain('<ol start="3"><li><p>Install Intelephense</p></li></ol>');
});

it('converts CodePen iframes, rewrites old internal links and hands images to the resolver (#45, #14)', function () {
    $seen = [];
    $r = cleanLegacy('<p>See <a href="https://codethepixel.com/articles/CakePHP-4-Sending-Email" target="_blank">CakePHP 4 Email</a>.<br /><img src="/ctp/webroot/upload/images/tutorial/x/crud-min.jpg" alt="" style="width:100%" /></p>'
        .'<p><iframe src="https://codepen.io/Asyraf/embed/ExoaarX?default-tab=html%2Cresult" title="Malaysia SVG Map" height="300"></iframe></p>',
        function (string $src, string $alt) use (&$seen) {
            $seen[] = $src;

            return '<p>[image]</p>';
        });

    expect($r->html)->toContain('<a href="/cakephp-4-sending-email">CakePHP 4 Email</a>')
        ->toContain('<p>[image]</p>')
        ->toContain('&quot;url&quot;:&quot;https://codepen.io/Asyraf/pen/ExoaarX&quot;')
        ->and($seen)->toBe(['/ctp/webroot/upload/images/tutorial/x/crud-min.jpg'])
        ->and($r->internalLinks)->toBe(1);
});

it('cleans tables and promotes a bold first row to the header', function () {
    $r = cleanLegacy('<table style="width: 100%;" border="1"><tbody><tr><td><strong>Field</strong></td><td><strong>Type</strong></td></tr><tr><td style="width: 50%;">id</td><td>int</td></tr></tbody></table>');

    expect($r->html)->toBe('<table><tr><th><p><strong>Field</strong></p></th><th><p><strong>Type</strong></p></th></tr><tr><td><p>id</p></td><td><p>int</p></td></tr></table>');
});

it('does not treat sentences or long bold text as headings', function () {
    $r = cleanLegacy('<p><strong>Note:</strong> run the migration first.<br /><br /><strong>This is a whole sentence in bold.</strong></p>');

    expect($r->headings)->toBe(0)->and($r->html)->toContain('<strong>Note:</strong> run the migration first.');
});
