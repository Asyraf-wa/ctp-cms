// Regenerates resources/data/simple-icons.json (slug => [title, hex]) for the Technologies picker.
// Run after updating codeat3/blade-simple-icons: ./dev npm run icons:data
import * as icons from 'simple-icons';
import { readdirSync, writeFileSync } from 'node:fs';

// Only icons that the Blade package actually ships.
const available = new Set(readdirSync('vendor/codeat3/blade-simple-icons/resources/svg').map((f) => f.replace(/\.svg$/, '')));

const data = {};
for (const icon of Object.values(icons)) {
    if (icon?.slug && available.has(icon.slug)) data[icon.slug] = [icon.title, `#${icon.hex}`];
}

const sorted = Object.fromEntries(Object.entries(data).sort(([a], [b]) => a.localeCompare(b)));
writeFileSync('resources/data/simple-icons.json', JSON.stringify(sorted));
console.log(`${Object.keys(sorted).length} of ${available.size} icons written`);
