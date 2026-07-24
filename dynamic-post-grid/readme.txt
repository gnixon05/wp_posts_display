=== Dynamic Post Grid + Filter ===
Contributors: gnixon05
Tags: post grid, wpbakery, salient, filter, ajax, taxonomy, carousel, masonry
Requires at least: 5.6
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.3.6
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
* Filter bar: per-instance multi-select taxonomy dropdowns (admin-assigned,
  "All" default, custom labels), keyword search, AND across taxonomies (OR
  within a taxonomy), live or
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

= 1.3.6 =
* Taxonomy pills now render below the excerpt / description (above the read-more
  or button) instead of above the title, across every card style.

= 1.3.5 =
* Fix invalid nested anchors in the overlay / magazine / education card styles:
  when "Taxonomy pills" (which are links) were shown, they sat inside the
  whole-card link. Those styles now use a stretched "cover" link so the card is
  still fully clickable while the pill links remain individually clickable and
  the markup stays valid.

= 1.3.4 =
* Education card link uses flex: 0 1 auto (hugs content) so the media and title
  heights stay consistent across cards; the previous flex: 1 grew the link and
  threw the alignment off.

= 1.3.3 =
* Education card link now fills the card via flex (flex: 1) instead of
  height: 100%, avoiding percentage-height rendering quirks while keeping the
  "Learn more" button pinned to the bottom.

= 1.3.2 =
* Cards now keep their content top-aligned and pin the button / read-more link
  to the bottom, so buttons line up across a row regardless of how much text
  each card has (Education "Learn more" and the classic/minimal read-more link).

= 1.3.1 =
* New "Filter selection" option: choose multi-select (checkboxes, several terms)
  or single select (radios, one term) per element. Defaults to multi-select.
* Raised the filter dropdown panel's z-index so it always sits above adjacent
  page content.

= 1.3.0 =
* Filter taxonomies are now multi-select: each filter is a compact dropdown of
  checkboxes, so visitors can pick several terms per taxonomy at once (combined
  with OR within a taxonomy, AND across taxonomies). Built on a <details>
  element so it still works without JavaScript and submits as a normal GET.
  Shareable URLs now carry multiple values per taxonomy.

= 1.2.4 =
* New "Taxonomy pills" card-content option: shows the post's terms in the filter
  taxonomies (or an explicit list) as small, modern, neutral pills so visitors
  can see what exists. In the Education / Featured Magazine layout they appear
  directly above the title; in other layouts, above the title too. Themeable via
  `--dpg-tax-pill-*` scoped variables.

= 1.2.3 =
* Fix: the "Field background color" / "Field text color" controls now actually
  drive the keyword search input (it previously had a hard-coded white/dark
  style that ignored them). Selects and search now both follow the configurable
  field colours via scoped vars with !important so they survive theme CSS.
* Renamed the filter colour controls to American spelling ("color").

= 1.2.2 =
* Filter bar: all fields (dropdowns and search) now share one consistent width
  and height via a `--dpg-field-width` variable (default 200px), so Category,
  Tag and Search line up evenly.

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
