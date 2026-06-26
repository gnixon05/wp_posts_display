=== Dynamic Post Grid + Filter ===
Contributors: gnixon05
Tags: post grid, wpbakery, salient, filter, ajax, taxonomy, carousel, masonry
Requires at least: 5.6
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Configurable post grid for any post type with multiple card styles, an Education / Featured Magazine preset, and an AJAX multi-criteria filter bar. Ships a WPBakery element and an equivalent shortcode.

== Description ==

Dynamic Post Grid renders posts, pages or any registered public post type in a
configurable grid. It mirrors the feature set of the Salient "Dynamic Post Grid"
element and adds two capabilities on top:

* An **Education / Featured Magazine** layout preset (portable, scoped CSS).
* A configurable, AJAX-driven **filter bar** supporting any taxonomy on the
  queried post type plus a keyword search.

It registers a WPBakery (js_composer) element via `vc_map()`, a Gutenberg block
(`dpg/post-grid`) *and* an equivalent `[dynamic_post_grid]` shortcode — all three
sharing one server render path — so it works whether or not the page is built in
WPBakery. Everything is namespaced under a `dpg-` prefix and CSS custom
properties are scoped to the component root, so it coexists cleanly with the
Salient theme and renders (unstyled-but-functional) on any theme.

= Features =

* Source/query controls: post type, taxonomy include/exclude, count, offset,
  order/orderby, include/exclude IDs, exclude current, sticky handling, and an
  advanced JSON `meta_query` passthrough. All querying uses `WP_Query`.
* Layouts: 1–5 responsive columns, grid or carousel, and card styles —
  classic (meta below), overlay, minimal, magazine (featured), and
  Education / Featured Magazine.
* Card meta toggles: featured image (size + fallback), title, excerpt (length),
  date, author + avatar, primary term badge, read-more. Hover effects:
  zoom / overlay fade / lift.
* Pagination: none, numbered, AJAX load-more, or infinite scroll.
* Filter bar: per-instance taxonomy dropdowns (admin-assigned, "All" default,
  custom labels), keyword search, AND combination across taxonomies, live or
  on-submit apply, debounced keyword input, reset/clear, and URL sync for
  shareable / back-button friendly results. No-JS fallback submits as GET.

== Installation ==

Requirements: WordPress 5.6+ (the Gutenberg block needs 5.8+; it is skipped
gracefully on older cores while the shortcode and WPBakery element keep working),
PHP 7.2+. No build step. WPBakery and the Salient theme are both optional.

The installed plugin must live at
`wp-content/plugins/dynamic-post-grid/dynamic-post-grid.php`.

= From the WordPress admin (zip) =

1. Obtain a zip whose top-level folder is `dynamic-post-grid`. From the source
   repository you can build one with:
   `zip -r dynamic-post-grid.zip dynamic-post-grid -x '*.git*'`
2. In wp-admin go to Plugins -> Add New -> Upload Plugin.
3. Choose the zip, click Install Now, then Activate.

= Manual (FTP/SFTP/SSH) =

1. Copy the `dynamic-post-grid` directory into `wp-content/plugins/`.
2. Go to Plugins in wp-admin and activate "Dynamic Post Grid + Filter".

= WP-CLI =

* From a zip: `wp plugin install /path/to/dynamic-post-grid.zip --activate`
* Already copied in: `wp plugin activate dynamic-post-grid`

= After activation =

Activation only flushes rewrite rules; no database tables or options are created.

Smoke test: add `[dynamic_post_grid posts_per_page="6" columns="3"
pagination="loadmore"]` to any page and confirm a grid with a working Load more
button renders. Add `filter_enable="yes" filter_taxonomies="category,post_tag"`
to confirm AJAX filtering (dropdowns/search update results without reload; with
JS off it falls back to a normal GET submit).

= Updating =

Replace the `dynamic-post-grid` folder (or re-upload the zip). The version
constant busts CSS/JS caches automatically.

= Uninstalling =

Deactivate then Delete on the Plugins screen, or
`wp plugin deactivate dynamic-post-grid && wp plugin delete dynamic-post-grid`.
No options or tables are left behind.

== Usage ==

Shortcode:

`[dynamic_post_grid post_type="post" style="education" columns="3" pagination="loadmore" filter_enable="yes" filter_taxonomies="category,post_tag"]`

In WPBakery: add the **Dynamic Post Grid** element from the Content category and
configure it through the params panel (Source, Layout, Card Content, Pagination,
Filter Bar tabs).

In the block editor (Gutenberg): add the **Dynamic Post Grid** block (Widgets
category). It shows a live server-rendered preview and exposes the same options
in the block sidebar.

== Security ==

All AJAX/REST traffic is nonce-protected; inputs are sanitised, term IDs cast to
int, and taxonomy/orderby values whitelisted. Output is escaped. The element
config that travels to AJAX is fully re-sanitised server-side on every request.

== Changelog ==

= 1.2.1 =
* Filter bar polish: readable dark search-input text on white with !important
  guards (font-size/border/label), padding tweaks, higher-specificity search
  button selector to survive theme button styles, and Clear spacing fix.
* Education: tighter "Learn more" button (padding/size).
* Featured images: !important on width/height to fully defeat theme overrides.
* Excerpts: strip leading headings and a "Category | Month YYYY" header line (or
  a repeated post title) so previews start at the body copy.

= 1.2.0 =
* Education preset redesigned to match the reference grid: even, centred cards
  with a "Category | Month Year" meta line, centred title/excerpt and a navy
  "Learn more" pill button (no forced hero item).
* Fix: featured images now use an absolute-fill technique so themes that force
  `img { height: auto !important }` (e.g. Salient) can no longer collapse them.
* Fix: excerpts now generate from page-builder content (WPBakery `[vc_*]`) by
  stripping shortcode brackets but keeping the inner text.
* New: configurable card corner radius (`card_radius`).
* Filter bar: more compact, keyword search right-aligned, the Clear control on
  its own line above the filters, and configurable bar/field colours
  (`filter_bg`, `filter_text`, `filter_field_bg`, `filter_field_text`).

= 1.1.0 =
* Add a dynamic Gutenberg block (`dpg/post-grid`) with a live ServerSideRender
  preview and full InspectorControls, delegating to the shared render path.

= 1.0.0 =
* Initial release: query builder, shared render layer, five card styles incl.
  Education preset, WPBakery element + shortcode, AJAX filter bar + load-more.

== Notes ==

The Education preset reproduces the texascensus.org/education grid (even, centred
cards with category/date meta and a "Learn more" pill) and is fully driven by the
`--dpg-edu-*` scoped CSS variables for easy pixel-tuning.
