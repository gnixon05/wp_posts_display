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

## Installation (end-to-end)

### Requirements

| | Minimum | Notes |
| --- | --- | --- |
| WordPress | 5.6+ | The Gutenberg block needs **5.8+** (block.json metadata API). On older cores the block is skipped gracefully; the shortcode and WPBakery element still work. |
| PHP | 7.2+ | Tested on PHP 8.4. |
| Build tools | none | No `npm`/Composer/webpack step — the plugin ships ready to run. |
| WPBakery Page Builder | optional | Only needed for the visual element; registration is gated on `vc_map()`. |
| Salient theme | optional | Not required — the plugin renders on any theme. |

> The plugin code lives in the [`dynamic-post-grid/`](dynamic-post-grid/)
> subdirectory of this repo. WordPress expects a plugin's main file at the top of
> its own folder, so whichever method you use, the installed path must be
> `wp-content/plugins/dynamic-post-grid/dynamic-post-grid.php`.

### Option A — Upload a packaged zip (recommended)

Build a zip whose **top-level folder is `dynamic-post-grid`**, then upload it:

```bash
git clone https://github.com/gnixon05/wp_posts_display.git
cd wp_posts_display
zip -r dynamic-post-grid.zip dynamic-post-grid -x '*.git*' '*.DS_Store'
```

In `wp-admin`: **Plugins → Add New → Upload Plugin** → choose
`dynamic-post-grid.zip` → **Install Now** → **Activate**.

### Option B — Copy into wp-content/plugins (FTP / SFTP / SSH)

Copy just the `dynamic-post-grid` directory onto the server:

```bash
# from a local clone of this repo
rsync -av dynamic-post-grid/ \
  user@server:/var/www/html/wp-content/plugins/dynamic-post-grid/
```

Then activate under **Plugins** in `wp-admin` (or with WP-CLI, below).

### Option C — WP-CLI

```bash
# from a built zip (installs + activates in one step)
wp plugin install /path/to/dynamic-post-grid.zip --activate

# …or, if you already copied the folder into wp-content/plugins:
wp plugin activate dynamic-post-grid
```

### Option D — Git clone directly on the server

```bash
cd wp-content/plugins
git clone https://github.com/gnixon05/wp_posts_display.git wp_posts_display
# expose the plugin folder at the expected path (copy is most host-compatible):
cp -r wp_posts_display/dynamic-post-grid ./dynamic-post-grid
wp plugin activate dynamic-post-grid
```

(Symlinking `dynamic-post-grid → wp_posts_display/dynamic-post-grid` works on
most hosts but is rejected by some; copying/moving is the safe default.)

### Activation

Activate **Dynamic Post Grid + Filter** on the Plugins screen. Activation only
flushes rewrite rules (reserved for a future REST route) — **no database tables
or options are created**.

### Verify the install (2-minute smoke test)

1. Create a post or page and add this shortcode:
   ```text
   [dynamic_post_grid post_type="post" posts_per_page="6" columns="3" pagination="loadmore"]
   ```
2. View the page — you should see a 3-column grid of your latest posts and a
   working **Load more** button.
3. Add the filter bar and confirm AJAX filtering:
   ```text
   [dynamic_post_grid filter_enable="yes" filter_taxonomies="category,post_tag" pagination="loadmore"]
   ```
   Changing a dropdown or typing in **Search** updates results without a page
   reload; the active filters appear in the URL (shareable). With JavaScript
   disabled it falls back to a normal `GET` form submit.
4. (Optional) In the block editor, add the **Dynamic Post Grid** block (Widgets
   category) and confirm the live preview renders; in WPBakery, add the
   **Dynamic Post Grid** element (Content category).

### Updating

Replace the `dynamic-post-grid` folder with the newer version, or re-upload the
zip (WordPress prompts to replace). The `DPG_VERSION` constant versions the CSS/JS
handles, so asset caches bust automatically on upgrade.

### Uninstalling

Deactivate, then delete, on the Plugins screen — or:

```bash
wp plugin deactivate dynamic-post-grid && wp plugin delete dynamic-post-grid
```

Because the plugin stores no options or custom tables, removal leaves no residue.

### Troubleshooting

| Symptom | Likely cause / fix |
| --- | --- |
| Grid renders unstyled | Assets enqueue **only** on pages that actually contain the element/shortcode/block. Confirm the shortcode/block is present and not inside a cached fragment. |
| Block missing in the editor | Requires WP **5.8+**. On older cores use the shortcode or WPBakery element. |
| WPBakery element missing | Ensure WPBakery (`js_composer`) is active — registration is gated on `function_exists('vc_map')`. |
| AJAX filter returns nothing or 403 | A full-page cache may be stripping the nonce. Exclude `admin-ajax.php` from caching, or hard-refresh to get a fresh nonce. |
| No posts shown | Check the `post_type`/term filters and that matching published posts exist. |

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
