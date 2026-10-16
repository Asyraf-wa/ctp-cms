<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Seo\Sitemap;
use Illuminate\Http\Response;
use XMLWriter;

// SEO-05: /sitemap.xml (index) and /sitemaps/{section}.xml with image extensions.
class SitemapController extends Controller
{
    public function index(Sitemap $sitemap): Response
    {
        $xml = $this->start('sitemapindex', ['xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9']);
        foreach ($sitemap->index() as $section => $lastmod) {
            $xml->startElement('sitemap');
            $xml->writeElement('loc', route('sitemap.section', $section));
            if ($lastmod) {
                $xml->writeElement('lastmod', $lastmod);
            }
            $xml->endElement();
        }

        return $this->finish($xml);
    }

    public function section(string $section, Sitemap $sitemap): Response
    {
        abort_unless(in_array($section, Sitemap::SECTIONS, true), 404);
        $entries = $sitemap->entries($section);
        abort_if($entries->isEmpty(), 404);

        $xml = $this->start('urlset', [
            'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
            'xmlns:image' => 'http://www.google.com/schemas/sitemap-image/1.1',
        ]);
        foreach ($entries as $entry) {
            $xml->startElement('url');
            $xml->writeElement('loc', $entry['loc']);
            if ($entry['lastmod'] ?? null) {
                $xml->writeElement('lastmod', $entry['lastmod']);
            }
            foreach ($entry['images'] ?? [] as $image) {
                $xml->startElement('image:image');
                $xml->writeElement('image:loc', $image['loc']);
                $xml->endElement();
            }
            $xml->endElement();
        }

        return $this->finish($xml);
    }

    /** @param  array<string, string>  $attributes */
    private function start(string $root, array $attributes): XMLWriter
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement($root);
        foreach ($attributes as $name => $value) {
            $xml->writeAttribute($name, $value);
        }

        return $xml;
    }

    private function finish(XMLWriter $xml): Response
    {
        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
