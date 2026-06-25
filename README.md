# wp_posts_display

**Dynamic Post Grid + Filter** — a self-contained WordPress plugin that renders
posts, pages or any registered public post type in a configurable grid, with an
optional AJAX multi-criteria filter bar. It targets sites running the **Salient**
theme with **WPBakery Page Builder** but works on any theme and with or without
WPBakery.

The plugin lives in [`dynamic-post-grid/`](dynamic-post-grid/).

## What it does

It mirrors the feature set of Salient's "Dynamic Post Grid" element and adds two
capabilities on top:

1. **Education / Featured Magazine** layout preset (portable, scoped CSS — not
   dependent on theme styles).
2. A configurable, **AJAX-driven filter bar** supporting any taxonomy on the
   queried post type plus a keyword search.

It registers a WPBakery element (`vc_map`, gated on `function_exists('vc_map')`),
a Gutenberg block (`dpg/post-grid`, server-rendered), **and** an equivalent
`[dynamic_post_grid]` shortcode — all three sharing one render path — so it is
usable inside or outside WPBakery. Everything is namespaced under `dpg-` and CSS
custom properties are scoped to the component root — no `:root`, no Salient
collisions.

## Feature summary

| Area | Options |
| --- | --- |
| Source / query | post type (any public CPT/`page`), taxonomy include/exclude, count, offset, order/orderby, include/exclude IDs, exclude current, sticky handling, JSON `meta_query` passthrough — all via `WP_Query` |
| Layout | 1–5 responsive columns, grid or carousel, card styles: classic / overlay / minimal / magazine / **education** |
| Card meta | featured image (size + fallback), title, excerpt (length), date, author + avatar, primary term badge, read-more; hover effects: zoom / overlay / lift |
| Pagination | none / numbered / AJAX load-more / infinite scroll |
| Filter bar | admin-assigned taxonomy dropdowns ("All" default, custom labels), keyword search, AND across taxonomies, live or on-submit apply, debounce, reset, URL sync, no-JS GET fallback |

## Quick start

Shortcode:

```text
[dynamic_post_grid post_type="post" style="education" columns="3"
  pagination="loadmore" filter_enable="yes" filter_taxonomies="category,post_tag"]
```

In WPBakery: add the **Dynamic Post Grid** element (Content category) and use the
params panel.

## Architecture

```
dynamic-post-grid/
  dynamic-post-grid.php          # header, constants, bootstrap, hooks
  includes/
    class-query.php              # attribute schema + sanitisation + WP_Query builder
    class-render.php             # shared card + grid render (all presets, all paths)
    class-filter.php             # filter-bar markup + taxonomy discovery
    class-assets.php             # conditional, versioned enqueue + localisation
    class-ajax.php               # nonce'd admin-ajax endpoints (filter + load-more)
    class-shortcode.php          # [dynamic_post_grid]
    class-wpbakery.php           # vc_map registration (guarded)
    class-block.php              # Gutenberg dynamic block (guarded)
  block/
    block.json                   # block metadata + attributes
  assets/
    css/dynamic-post-grid.css    # scoped, variable-driven; includes Education preset
    js/dynamic-post-grid.js      # vanilla; filter + load-more + infinite + carousel
    js/dpg-block.js              # no-build editor script (InspectorControls + SSR)
  templates/
    card-classic.php  card-overlay.php  card-minimal.php
    card-magazine.php  card-education.php
    filter-bar.php
```

A single shared render path (`DPG_Render`) produces the markup for the initial
server render, the load-more append, the filter AJAX replace, **and** the
WPBakery / Gutenberg / shortcode entry points — so there are no divergent markup
paths.

## Build notes / assumptions

These were locked in at build time (the interactive confirm step was unavailable
in the build environment); adjust any and the relevant option flips:

1. **Filter-bar interpretation** — the dropdowns are admin-assigned taxonomies
   (not hardcoded); the struck-through column in the design reference means
   "assignable, not fixed"; the keyword field runs an `s=` search. The engine is
   fully generic.
2. **Education preset** — shipped as one selectable layout option; the default
   layout is the classic grid.
3. **First-pass target** — `post` with `category` + `post_tag` pre-wired as
   example filter taxonomies (most portable for testing on any site).
4. **Integration priority** — shortcode → WPBakery → Gutenberg. All three ship:
   the shortcode, the WPBakery `vc_map` element, and a server-rendered Gutenberg
   block (`dpg/post-grid`) added in 1.1.0.

The two reference URLs (`themenectar.com/salient/...` and
`texascensus.org/education/`) could not be fetched from the build environment —
the network policy denied both hosts at the proxy gateway. The Salient parity set
was implemented from its documented option set, and the Education preset uses the
standard featured-magazine composition driven entirely by `--dpg-edu-*` scoped
CSS variables, so it can be pixel-tuned against the live page without markup
changes.
