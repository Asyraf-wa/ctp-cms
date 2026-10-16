# Design System — CTP

> **Status:** v0.1 · **Date:** 2026-10-16 · See [PRD.md](PRD.md)
> The brand follows the **CTP SaaS** project (`../codethepixel/docs/DESIGN.md`, v0.6 "TRACE styling"). The tokens are copied so that both products look like one family.

## 1. Foundations

| Item | Decision |
|---|---|
| CSS | Tailwind CSS v4 (compiled with Vite, **no CDN**) |
| Font | **Inter** 400/500/600/700, self-hosted (`fontaine` fallback metrics so there's no CLS). Body −0.005em, headings −0.02em tracking |
| Mono font | **JetBrains Mono** 400/600, self-hosted (code blocks) |
| Icons | Lucide |
| Dark mode | Light / Dark / System. `.dark` class on `<html>`, set inline in `<head>` before paint |
| Contrast | **WCAG 2.2 AA**: text ≥ 4.5:1, UI ≥ 3:1 |
| Admin | Filament `primary => Color::Teal`, `gray => Color::Slate`, Inter |

## 2. Palette (from the SaaS)

| Role | Light | Dark |
|---|---|---|
| Canvas `--background` | `#F1F5F9` | `#0A1120` |
| Card `--card` | `#FFFFFF` | `#131C2E` |
| Surface 2 | `#F8FAFC` | `#0F1726` |
| Foreground | `#0F172A` | `#E8EEF7` |
| Muted text | `#56657A` | `#A3B1C6` |
| **Primary (teal)** | `#0F766E` (hover `#115E59`) | `#2DD4BF` (hover `#5EEAD4`) |
| Text on primary | `#FFFFFF` | `#042F2E` |
| Brand soft (active / selection) | `#F0FDFA` / text `#115E59` | `#14353F` / text `#5EEAD4` |
| Border / strong line | `#E2E8F0` / `#CBD5E1` | `#223049` / `#2F3F5C` |
| Destructive | `#B91C1C` | `#F87171` |
| **Brand mark** | gradient 135° `#14B8A6` → `#115E59`, with a soft glow | same |

**Callout colours** (always paired with an icon and a label, never colour alone):

| Callout | Icon | Light bg / border / text | Dark bg / border / text |
|---|---|---|---|
| Note | `info` | `#EFF6FF` / `#BFDBFE` / `#1D4ED8` | `#14213D` / `#1E3A8A` / `#93C5FD` |
| Tip | `lightbulb` | brand-soft / teal-200 / `#115E59` | `#14353F` / `#115E59` / `#5EEAD4` |
| Warning | `triangle-alert` | `#FFFBEB` / `#FDE68A` / `#854D0E` | `#2A2410` / `#854D0E` / `#FACC15` |
| Danger | `octagon-alert` | `#FEF2F2` / `#FECACA` / `#B91C1C` | `#2A1215` / `#7F1D1D` / `#F87171` |

**Difficulty badges:**
- Beginner: green `#166534` / `#4ADE80`
- Intermediate: amber `#854D0E` / `#FACC15`
- Advanced: red `#B91C1C` / `#F87171`

## 3. Geometry & Elevation
- Radius: **14px** cards, **10px** buttons and inputs, **8px** menu items and chips, **7px** badges.
- Elevation: hairline `shadow-xs` at rest. On hover, cards lift with `shadow-md` and `-translate-y-0.5`.
- Layout width: `max-w-[1440px]`. The article column is capped at about **72ch** for readability.
- Grid on the article page: left `w-64` (lg+), centre flexible, right `w-64` (xl+).

## 4. Code Blocks
- **Always dark**, in both themes: background `#0B1220`, border `#223049`, radius 10px. This reads as "code" and keeps contrast predictable.
- Header bar: filename or language label on the left; **Copy** on the right, which shows a "Copied!" toast state.
- Highlight theme tuned to the palette: keywords teal `#5EEAD4`, strings `#FDE68A`, comments `#7C8BA1` (≥ 4.5:1), functions `#93C5FD`.
- Inline `code`: brand-soft background, 6px radius, no backticks.

## 4b. Layout Reference
**`tutorial_web_template.html` (the owner's template) is the source of truth for structure.** Colours come from the tokens above (teal instead of the template's blue).

- **Header:** white card bar with a bottom border, `py-6`, shrinking to `py-3` with a shadow and blur after 50 px. The logo goes from `text-3xl` to `text-xl`. The nav is centred.
  - Responsive: the full search pill shows from `xl`; between `sm` and `xl` it is an icon (Ctrl K still works). The fullscreen button shows from `lg`. The logo is `text-xl` below `lg`. Logo and nav never wrap.
  - Right side: a pill search input (`rounded-full`, `w-56`, "Ctrl K" badge), the theme toggle, a **fullscreen** toggle and a pill **Subscribe** button (links to RSS).
- **Main:** `max-w-[1440px]`, with 3 columns.
  - Left: `w-64` category accordion, sticky, from `lg`.
  - Centre: the article as a `rounded-2xl` card with a border and `p-8`.
  - Right: `w-64` "On this page" TOC with scroll-spy, from `xl`.
- **Footer:** 4 columns: brand (2 columns wide), Platform, Company. The bottom bar holds the copyright and social icons.
- **Additions to the template:** a mobile drawer (nav, search, Subscribe), a skip link, a 3-state theme (Light, Dark, System), and search that works without JS.

## 5. Key Components
- **Header:** translucent and blurred after scroll. The logo shrinks, as in the template. Nav, a search trigger button (`Search… Ctrl K`) that opens the palette, theme toggle, and a GitHub icon button.
- **Search palette:** modal, keyboard-first (↑↓, Enter, Esc). Results are grouped (Tutorials, Blog, Tags). Recent searches are kept in localStorage.
- **Category sidebar:** accordion, as in the template, with a teal 2px active bar and counts.
- **TOC:** scroll-spy with a teal active bar, H3 indented. A "Back to top" link sits at the bottom.
- **Article meta row:**
  - category chip, date or "Updated" date, reading time, difficulty badge
  - "Tested with" chips (`Laravel 13` `PHP 8.4`)
- **TL;DR card:** brand-soft background, `sparkles` icon, bulleted list.
- **Series navigator:** a card that shows "Part 2 of 5" and a progress list.
- **Article card** (lists):
  - cover image (16:9)
  - category chip and difficulty
  - title, two-line excerpt
  - date and reading time
- **Project card:**
  - cover
  - name and status badge
  - tagline
  - stack chips
  - Live and Code buttons
- **Comment:** Gravatar, name ("Author" badge for the owner), relative time, body, Reply.
- **Empty states and skeletons** for search and lists.
- **Topbar notification:** full-width, 40px, with an icon by style (info, success, warning, maintenance) and a soft tinted background (the callout colours). Inline link, and a dismiss ✕ when that's enabled. It sits above the header and doesn't overlap it.
- **Ribbon:** a 45° band in the top-right corner (112px box, 170px band), `position: fixed` above the header, 11px uppercase semibold text. While it is on, the header and notice bar get extra right padding so no control sits under it.
  - background from settings; text colour auto-picked to reach ≥ 4.5:1
  - subtle shadow
  - on hover, the band darkens 8%
  - below 640 px it becomes a small rounded badge fixed at the bottom-right
- **Contact page:**
  - left column: an intro headline, topic cards (icon, title, one-line description; clicking one preselects the topic), a reply-time chip, an email with a copy button, social icons, and a "Before you ask" callout
  - right column: the form card
    - topic as segmented pill buttons
    - 2-column name and email
    - fields that appear for some topics slide in with a 200 ms height animation
    - textarea with a live counter
    - consent checkbox
    - full-width primary submit with a spinner
  - success state: replaces the card with a check-circle animation, a thank-you line, the reply time and 3 popular tutorials

## 6. Page Styles
- **Tutorials:** 3-column layout (the template, improved).
- **Blog:** single column, larger type (`prose-lg`), no sidebars, optional cover hero. It should feel like a journal.
- **Projects:** a showcase grid with bigger imagery. The detail page has a hero, an "At a glance" side panel (stack, status, links, dates) and a gallery lightbox.
- **Home:**
  - hero with a subtle teal gradient mesh and a large search trigger
  - "Latest" grid, then "Explore by category" tiles with icons, then "Learning paths" (series)
  - "From the blog", then "Projects" strip

## 7. Motion
- 150–300 ms ease-out for hover, accordion and theme changes. Everything respects `prefers-reduced-motion`.
- No parallax and no scroll-jacking.
