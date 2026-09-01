# ACF Components Block System — CLAUDE.md

Session primer for the ACF Components Block System (ACBS), forked from the Elementor Repeater
and Dynamic Conditions Addon (ERDC) on 27/08/2026. Mirrors the team's "ACBS Handbook"
artifact, keep both in sync whenever a module, field, hook, decision or pending item changes.

- **Version:** 1.0.0
- **Repo:** `https://github.com/salumguilherme/acf-components-block-system` (`origin`), plus a
  mirror outside the webroot at `~/acbs-git-mirror/acbs.git` (`mirror`). Folder name unchanged:
  `elementor-repeater-and-dynamic-conditions-addon`
- **Text domain:** `erdc`, deliberately (see §08)
- **Namespace:** `ACBS\`, renamed from `ERDC\` on 31/08/2026
- **Doc date:** 01/09/2026

**This file is tracked in git.** Write for the team, not for one machine.

> Read **§05 ACF loop rules** before writing anything that iterates rows, and **§09 Traps**
> before debugging anything that "looks right but does nothing". Every entry in §09 shipped,
> looked correct, and silently did nothing.

---

## 01 — What it is

A Five Creative in-house plugin that renders ACF Flexible Content rows as **PHP template
parts**, the way WooCommerce renders its templates. An editor composes a page out of ACF
Flexible Content rows; each row is rendered by `templates/rows/{layout}.php`, overridable from
the theme.

It is a fork of ERDC 1.0.36, which did the same job through Elementor: a custom document type,
a theme location, a widget, and Theme Builder display conditions choosing a template per row.
**All of that is gone.** ACF Pro is the only hard dependency.

### State of the migration

| Phase | | State |
|---|---|---|
| 00 | Decisions, fork, version control | **Done 27/08/2026** |
| 01 | Boot without Elementor | **Done 28/08/2026** |
| 02 | Render layer | **Done 28/08/2026** |
| 03 | Site kit | **Parked** (decision 6) |
| 04 | Bootstrap, tokens, structure.scss, per-row webpack entries | **Done 01/09/2026** |
| 05 | Field set rebuild, page template, row templates | **In progress**: 7 of 12 templates real, 5 still stubs |
| 06 | Theme integration, PHPStan, PHPCS | |

The front end works end to end. `https://erdc-plugin.local/5562-2/` renders through the Page
Builder template with no Elementor anywhere in the request.

### What the plugin provides

1. **The row rendering system.** ACF loop → template cascade → per-row assets. The core product.
2. **The field subsystem.** `page_sections`, the contributor-group location rules, the merge
   pipeline, the Buttons/Intro custom field types.
3. **Components.** Buttons, Intro, Other Settings, Grid & Display: shared field sets injected
   into every layout that opts in, each overridable by a site tagging its own field group.
4. **Page Header and Theme Settings** field groups.
5. **A styling layer.** Design tokens, a scoped Bootstrap 5 build, and `structure.scss`.

> **Client scoping.** Anything prefixed `fs_` or `sv_` belongs to two *different* client builds
> and must never be mixed or cross-referenced. Under this fork they leave core entirely for
> per-client add-on plugins (decision 10).

---

## 02 — Environment

| Item | Value |
|---|---|
| Local path | `~/DevKinsta/public/erdc-plugin/wp-content/plugins/elementor-repeater-and-dynamic-conditions-addon` |
| Stack | WordPress 7.x · PHP 8.3.3 · DevKinsta |
| Hard dependency | **ACF Pro only**, declared once in `Core\Module_Base::is_active()` |
| Requires | WP 6.0+ · PHP 8.0+ |
| Theme | A **child theme** is the real target. Both parent and child matter: `locate_template()` searches child first, so a child override wins over a parent override wins over the plugin |
| Elementor | Not active. Nothing in the plugin references it |
| Build | **webpack**. Sources in `src/`, output to `assets/css` and `assets/js`. `npm run dev` (watch) · `npm run build:dev` · `npm run build` |
| Release | `npm run release` (patch) · `release:minor` · `release:major` · `npm run release -- 1.1.0` · `npm run package`. Runs `bin/release.js`. `SHIP_DIRS` is `['assets','modules','core','templates','vendor']`. The staged directory and zip are named `acf-components-block-system` while the main PHP file keeps its `elementor-repeater-…` name, so `SOURCE_SLUG` and `SLUG` are separate constants on purpose |
| Tests / lint | **None.** Phase 06 |
| Updates | On, pointed at the fork. See §08 |

---

## 03 — Locked decisions

Answered 27/08/2026 unless noted. Do not relitigate these without saying so explicitly.

| # | Decision |
|---|---|
| 1 | **Template resolution is a candidate cascade**, most specific first, filterable. |
| 2 | The Elementor documents in `assets/data/flexible-layouts/` are **reference only**. No converter. |
| 3 | **Per-row assets enqueue in the footer**, because rows render at runtime, so assets follow them. Always-loaded sheets (structure, Bootstrap, tokens) go in the head: they need no discovery. |
| 4 | **Row types are objects.** One class per layout answering for its own fields, template, assets and wrapper classes. The registry is a **public API**. |
| 5 | **Styling layers: plugin first, theme second**, wired as a stylesheet dependency. A theme overrides without copying the sheet. This differs from templates, where the theme's file *replaces* the plugin's. |
| 6 | **Site kit is parked.** `_tokens.scss` hand-declares what rows need. |
| 7 | **Shortcodes removed entirely** (revised 31/08/2026). The original decision was to port four of them into row templates first. On inspection none survived the field-set rebuild: their layouts no longer exist. Nothing was ported and nothing is outstanding. |
| 8 | **Source resolution lives in the flexible-layout-template module**, not `core/`. |
| 9 | **Drilldown menu removed**, its webpack/Sass bundler kept and grown into the asset pipeline. |
| 10 | **Client-specific layouts and query filters leave core** into per-client add-ons. |
| 11 | **Bootstrap 5, compiled and scoped** (revised 01/09/2026). The plugin ships a full Bootstrap build whose every selector is prefixed `.acbs.fl-acbs` at build time, so it styles layout rows and cannot touch the theme. Handle name is filterable. |
| 12 | **Caching dropped entirely.** Page cache at the server handles it. |
| 13 | **Every layout gets a template**, stub or real, so the full set exists from day one. |
| 14 | **Tokens are unprefixed** (01/09/2026): `--brand-primary`, not `--acbs-brand-primary`. They are declared on `html`, so a theme's `:root` block wins on specificity and can restate any of them. |
| 15 | **A single top-level wrapper** (01/09/2026). `acbs_render_rows()` always emits one `<div class="acbs fl-acbs">` around every `section.fl-section`, even for one row, because the scoped Bootstrap has nothing to attach to without it. |

---

## 04 — Architecture

### Bootstrap chain

```
elementor-repeater-and-dynamic-conditions-addon.php
  ├─ defines ACBS_VERSION / ACBS__FILE__ / ACBS_PATH / ACBS_URL
  │            (ERDC_* aliased to each, for one release)
  ├─ defines ACBS_UPDATE_REPO, guarded by !defined()
  └─ plugins_loaded            → require plugin.php

plugin.php  — ACBS\Plugin singleton
  ├─ spl_autoload_register     → path-derived autoloader
  ├─ updater()
  ├─ Core\Upgrades::maybe_run()   one-shot cleanup, gated on erdc_version
  └─ init()  → new Core\Modules_Manager → do_action('acbs/init')
                                        → do_action('erdc/init')  deprecated alias
```

**Why `plugins_loaded` and not `init` priority 5.** ACF fires `acf/init` from *inside* `init`
at priority 5, verified in `advanced-custom-fields-pro/acf.php`:
`add_action('init', array($this,'init'), 5)`, and that method's last statement is
`do_action('acf/init', ACF_MAJOR_VERSION)`. Bootstrapping at `init:5` would leave whether our
`acf/init` listeners are registered before ACF fires it down to plugin load order, so the field
groups would register or silently not register depending on the alphabet. `plugins_loaded` is
unambiguously earlier, and ACF's api functions already exist by then because `acf.php` calls
`acf()->initialize()` at include time. **Do not "tidy" this onto `init`.**

`Row_Registry::boot()` *is* on `init` at priority 20, because it reads
`get_current_layouts()` and therefore needs `acf/init` to have already run.

### The autoloader is load-bearing

`Plugin::autoload()` derives a file path from the class name by regex: PascalCase segments
become kebab-case, underscores become path separators, everything lowercases.

```
ACBS\Modules\FlexibleLayoutTemplate\Fields\Grid_Display
  → modules/flexible-layout-template/fields/grid-display.php
```

A class that does not sit at exactly the matching path **silently fails to load**, with no
error. First thing to check when a class "doesn't exist".

### File map

```
core/
  module-base.php             instance() / is_active() / get_name()
  modules-manager.php         class_exists() guard + ACF dependency
  environment.php             no callers left; kept for a client add-on
  upgrades.php                one-shot option and transient cleanup
  admin/settings-page.php     add_tab / add_section / add_field facade

modules/flexible-layout-template/
  module.php                  hooks, asset registration, wrapper classes and style
  page-template.php           the Page Builder page template
  source-resolver.php         whose page_sections to read
  settings.php                disabled layouts
  layouts-export.php          the JSON export
  rows/
    row.php                   value object: layout, position, source, label
    row-type.php              interface (public API)
    row-type-base.php         abstract, defaults
    layout-row-type.php       concrete, built from a Page_Content slice
    row-registry.php          name => Row_Type
    renderer.php              the have_rows() loop + the top-level wrapper
    template-loader.php       candidate cascade, locate_template(), parts and partials
    wrapper.php               templates/wrapper.php, action points, inline custom properties
    template-tags.php         plain functions, required not autoloaded
    assets.php                footer collector
  fields/                     24 files

templates/                    overridable from {theme}/acbs/
  page-builder.php            the Page Builder page template
  wrapper.php
  parts/intro.php
  parts/buttons.php
  rows/default.php            ships empty
  rows/{layout}.php           × 12
  rows/{layout}/item.php      × 4

src/css/
  _tokens.scss                Sass literals + $theme-colors, imported by both entries
  rows-bootstrap.scss         tokens + full Bootstrap, scoped at build time
  structure.scss              the plugin's own row structure, unscoped
  rows/{layout}.scss          per-row sheets, discovered by glob

tools/
  fields/assemble.php         WP-free harness that assembles the merged field set
  fields/build-layouts.js     regenerates the layout array in page-content.php
  fixture/build.js            builds dist/fixture.html, every layout and every modifier
```

### The render path

```php
acbs_render_rows();                                       // queried object, page_sections
acbs_render_rows( [ 'field' => 'page_sections', 'source' => $post_id ] );
$html = acbs_get_rows();                                  // returns instead of echoing
```

`acbs_render_rows()` echoes; `acbs_get_rows()` returns. The renderer builds a string, which is
what makes asset capture and the top-level wrapper tractable: ERDC's widget wrote straight to
the output buffer in half a dozen places.

Output shape:

```html
<div class="acbs fl-acbs">
  <section class="fl-section fl-type-cta fl-bg-light fl-item fl-p-both">…</section>
  <section class="fl-section fl-type-stats …">…</section>
</div>
```

**The wrapper is a template too.** `Wrapper::render()` computes the classes, id and inline
custom properties, then includes `templates/wrapper.php` with `$row` in scope. The PHP fallback
inside `Wrapper::render()` fires only if that file has been deleted and the theme supplies none.

**The loop-depth guard is not optional.** `Wrapper::include_template()` records
`count(acf()->loop->loops)` before the include and calls `reset_rows()` back down to it
afterwards, warning under `WP_DEBUG`. Without it, one template that `break`s a nested
`while(have_rows())` without `reset_rows()` corrupts every row after it on the page.

### Template cascade

```
rows/{layout}-{post_type}-{post_slug}.php    // WP_Post
rows/{layout}-{taxonomy}-{term_slug}.php     // WP_Term
rows/{layout}-{post_type}.php
rows/{layout}-{taxonomy}.php
rows/{layout}-{context}.php        // archive | tax | options | front-page
rows/{layout}.php
rows/default.php                   // ships empty
```

Resolved through `locate_template()`, so child theme → parent theme → plugin works, per site on
multisite, for free. Filters: `acbs/template/candidates`, `acbs/template/path`. Memoised per
request.

Templates reach shared markup through **parts** and **partials**, deliberately not by naming a
class: a theme's copied templates are the one place a rename cannot reach.

| Call | Resolves | Loop context |
|---|---|---|
| `acbs_row_part('intro', $row)` | `templates/parts/intro.php` | the row's own loop |
| `acbs_row_partial('item', $row)` | `templates/rows/{layout}/item.php` | the *nested* repeater loop, already advanced by `the_row()` |

An item partial therefore reads its fields with a bare `get_sub_field('title')` and never
unpacks an item object. There is no `Items_Source` and no `Item` class: they were built,
then removed on 31/08/2026 when the field-set rebuild dropped the three layouts that carried a
`source` selector. Every repeater in the current set is a plain nested `have_rows()`.

### The Page Builder page template

`Modules\FlexibleLayoutTemplate\Page_Template` registers a page template named **Page Builder**
that a page selects in the editor's Template dropdown.

| Const | Value |
|---|---|
| `SLUG` | `acbs-page-builder.php`, the value stored in `_wp_page_template` |
| `FILE` | `page-builder.php`, the actual file in `templates/` |
| `MIGRATION_OPTION` | `erdc_page_template_migration` |

The template is header, rows, footer, and nothing else:

```php
get_header();
acbs_render_rows();
get_footer();
```

`add_template()` puts it in the dropdown via `theme_page_templates`; `serve()` swaps in the
plugin's file on `template_include`, with `locate()` letting a theme replace it at
`{theme}/acbs/page-builder.php`.

**Two field groups are gated on it.** `page_sections` and Page Header both use ACF's
`page_template` location rule against `SLUG`, so a page that is not a Page Builder page shows
neither. `page_sections` also sets `hide_on_screen => ['the_content']`, since the classic
editor has nothing to do on such a page.

`migrate_existing_pages()` assigns the template to pages that already have saved
`page_sections` data, once, recorded in `MIGRATION_OPTION`.

---

## 05 — ACF loop rules

Verified against ACF Pro's `includes/api/api-template.php`, `includes/loop.php` and
`pro/fields/class-acf-field-flexible-content.php` on 27/08/2026. **The renderer is
`have_rows()` / `the_row()` / `get_row_layout()` and nothing else.**

**1. The loop stack is keyed on selector plus normalised post id.** `have_rows()` builds
`"selector={$selector}/post_id={$post_id}"` *after* `acf_get_valid_post_id()`. Same key means
same loop; a different key means a nested child loop (if the selector resolves to a sub field
of the active loop) or a new parent loop. `Source_Resolver` normalises once and passes the same
value everywhere: a `WP_Term` in one call and `"term_12"` in the next would silently open a
second loop.

**2. A completed loop cleans itself up; a broken one does not.** When there is no next row,
`have_rows()` calls `acf_remove_loop('active')` and returns false. That is the *only* thing
that pops the stack. `break` out of a `while(have_rows())` and the loop stays on the stack half
consumed, and the next `have_rows()` with the same key resumes it.

```php
while ( have_rows( 'cards' ) ) {
    the_row();
    if ( $found ) { reset_rows(); break; }   // mandatory
}
```

**3. Nested loops work for free, and must be called with no post id.** Inside an active
`page_sections` row, `have_rows('columns')` is detected as a child loop of the current layout,
so the repeater is already loaded and already formatted. Passing a post id takes a different
branch and breaks the moment rows are rendered for something other than the global post.

**4. `get_row_index()` is a position, not an identity.** It returns `row_index_offset + i`,
offset defaults to **1**, so it is 1-based while ACF's internal `i` is 0-based. `acf_add_loop()`
also runs `array_values()` over the row set, and flexible content's `load_value()` skips
editor-disabled rows, so disabling one row renumbers every row after it. **Never derive a
persistent anchor id or `href` target from it.**

**5. "Unformatted" is not cheap, and rows are keyed by field key.** Flexible content's
`load_value()` walks every layout's sub fields calling `acf_get_value()` on each, so
`get_field('page_sections', $id, false)` hydrates the whole page. It returns each row keyed by
**field key** plus an `acf_fc_layout` entry, not by field name. Templates read through
`get_sub_field()`, which is also what ACF's own formatting requires. For just the layout names,
use `acf_get_metadata()`: raw meta, which for a flexible content field is exactly the ordered
list of layout names, with no sub field values loaded.

**6. Disabled rows are already filtered out.** `load_value()` drops rows an editor toggled off,
so the renderer needs no check of its own. `acbs/row/show` layers on top of that.

---

## 06 — Field subsystem

### The 12 layouts

Rebuilt 31/08/2026 from a supplied field list, generated by `tools/fields/build-layouts.js`
into the array literal in `page-content.php` (975 lines, down from 1,724).

| Layout | Iterates | Grid & Display fields |
|---|---|---|
| `accordions` | `accordions` repeater | none |
| `columned_content` | `columns` repeater | full set + cards |
| `contact_page_form` | | display + cards |
| `content_left_image_right` | | none |
| `cta` | | display + cards |
| `full_width_image` | | none |
| `icon_leaders` | `icon_leaders` repeater | columns + alignment only |
| `icon_list` | `icon_list` repeater | full set + cards |
| `image_gallery` | gallery field | columns 1–8, default 7 |
| `logo_gallery` | gallery field | columns 1–8, default 7 |
| `stats` | `stats` repeater | full set + cards |
| `testimonials` | `testimonials` repeater | full set + cards |

Item partials exist for `icon_leaders`, `icon_list`, `stats`, `testimonials`. Templates written:
`accordions`, `contact_page_form`, `cta`, `icon_leaders`, `icon_list`, `stats`, `testimonials`.
Still stubs: `columned_content`, `content_left_image_right`, `full_width_image`, `image_gallery`,
`logo_gallery`.

**Layouts and fields are matched by name, never by key.** The rebuild regenerated every key in
the file except one (see §09), so any tooling that reconciles old data against new definitions
must key on `name`.

### The four components

`Flexible_Layout_Components::MODULES` is a hand-maintained flat array, mirroring
`Core\Modules_Manager::MODULES` one level up. Each entry implements
`Flexible_Layout_Component` and is a clone *source* only, registered inactive against a dummy
location and never shown on an edit screen itself.

| Component | Contributes | Notes |
|---|---|---|
| `Buttons` | the `buttons` repeater: `button_text`, `button_link`, `button_style`, `button_outline` | A real ACF field type, not a Clone, because ACF's field group editor permanently flattens a seamless clone resolving to a single field. Layouts take it as a **clone** of the component's field |
| `Intro` | `section_title`, `section_content` | Injected into every layout. Suppression via `erdc_disable_layout_intro` is kept but **suppresses nothing by default**, on purpose: it is there for theme-side use |
| `Other_Settings` | the "Other Settings" tab: `section_bg`, `section_container_id`, `vertical_padding`, `vertical_padding_xs` | `vertical_padding_mobile` was renamed `vertical_padding_xs` for consistency with the `-sm`/`-xs` grid steps |
| `Grid_Display` | the "Grid & Display" tab: `layout_columns`, `layout_columns_sm`, `layout_columns_xs`, `layout_columns_alignment`, `layout_display`, `layout_display_bg`, `layout_display_bg_colour` | New 31/08/2026. Group key `group_6a9620d1a4c37` |

`Grid_Display` is per-layout, which the other three are not. `LAYOUTS` maps each layout to the
fields it takes, in display order; `OVERRIDES` adjusts labels, choices and defaults per layout
(galleries go 1–8 and start at seven; `icon_leaders` says "Layout Columns", defaults to four and
aligns `default` where the grids centre). `fields_for_layout()` then prefixes every key
`field_grid_{layout}_{key}` and rewrites the conditional-logic targets to match, because the
same field appears once per layout and ACF's runtime cache is keyed on field key alone.

A layout absent from `LAYOUTS`, or mapped to an empty array, gets no Grid & Display tab.

### The colour palette

`Colour_Palette::choices()` is the single list of background colours, shared by Section BG
Colour (`Other_Settings`) and Card BG Colour (`Grid_Display`). The two lists had drifted: the
section offered accents the card did not, the card offered a custom colour the section did not.

```
default (Transparent) · white · light · lighter · accent-1 · accent-2
dark · darker · primary · secondary · tertiary
```

Every key is a class: `fl-bg-{key}` on a section, `fl-card-bg-{key}` on a card, with a rule for
each in `structure.scss`. `default` is emitted like the rest but deliberately has no rule, so it
stays transparent. Default value on both fields is `default`.

Three filters, layered:

| Filter | Scope |
|---|---|
| `erdc/colour_palette/choices` | **Both** lists. The one to use for relabelling site-wide |
| `erdc/section_bg/choices` | Section BG only, applied after the shared one |
| `erdc/grid_display/bg_choices` | Card BG only, applied after the shared one |

Removing a key removes the option but **not** any value already saved against it: ACF does not
rewrite stored values to match a narrowed choice list, so an existing row keeps its colour and
its class.

### Three location rules, all of them tags

None match a real edit screen. They exist so a site can mark a field group as a contributor,
and the plugin merges its fields elsewhere.

| Rule | param | Says |
|---|---|---|
| Flexible Layout | `erdc_flexible_layout` | "This group is a source of **page_sections layouts**." Merged by `Site_Layouts` |
| Flexible Layout Row | `erdc_flexible_layout_row` | "Add these fields to **one layout**, or to every layout present and future." Supports `!=`. Merged by `Common_Fields` |
| Flexible Layout Component | `erdc_flexible_layout_component` | "Override the fields of a **component**." Merged by `Site_Fields_Base` |

### How a layout is assembled

```
Page_Content::get_current_layouts()
  1. get_base_layouts()                  the 12 layouts, hardcoded
  2. remove_disabled_layouts()           settings checkbox list
  3. Site_Layouts::merge()               groups tagged "= Page Content"
  4. apply_filters('erdc/flexible_layout/layouts')
  5. Site_Layouts::sort()                alphabetical by label

Common_Fields::inject_common_fields()  on acf/load_field for page_sections
  per layout, sub_fields become:
    [ "Content" tab ] + [ layout's own fields ]
      + [ Layout Row contributed fields ]
      + [ Intro fields ]
      + [ "Grid & Display" tab + Grid_Display fields ]
      + [ "Other Settings" tab + fields ]
```

**That order is the fix for a real bug.** Contributed Layout Row fields used to be merged
*after* the Intro fields, which put them under the Intro tab instead of the layout's own
Content tab. They now merge before it.

### Key classes

| Class | Responsibility |
|---|---|
| `Page_Content` | Owns `page_sections`, mostly one generated array literal |
| `Common_Fields` | Injects the tab structure and every component's fields into every layout |
| `Contributor_Groups` | Finds field groups tagged with a rule value. **Lower Order No. wins** (merged last). No memoisation |
| `Field_Merge` | The one merge implementation. Same-`name` replaces; a replacement keeps *our* position unless it sits below a field the site genuinely added |
| `Site_Fields_Base` | Single implementation of component override. `Buttons` overrides `merge()` because its fields are repeater row fields, not a flat list |
| `Conditional_Logic` | Feeds the plugin's fields to ACF's conditional-logic dropdown, which otherwise only sees fields in the DOM of the group being edited |
| `Colour_Palette` | The shared background list |
| `Grid_Display` | The per-layout Grid & Display tab |

### Fields the wrapper reads

| Source | Fields | Emits |
|---|---|---|
| `Other_Settings` | `section_bg` | `fl-bg-{x}` |
| | `section_container_id` | the section's `id` |
| | `vertical_padding`, `vertical_padding_xs` | `fl-p-{x}`, `fl-p-xs-{x}` |
| `Grid_Display` | `layout_columns`, `_sm`, `_xs` | `fl-loop-grid-columns-{n}`, `-sm-{n}`, `-xs-{n}` |
| | `layout_columns_alignment` | `fl-loop-grid-columns-align-{x}` |
| | `layout_display`, `layout_display_bg` | `fl-card-box`, `fl-card-bg-{x}` |
| | `layout_display_bg_colour` | `--fl-card-box-bg` as an inline custom property |
| layouts | `grid_type`, `image_fit` | `fl-loop-grid-{x}`, `fl-image-fit-{x}` |

Always present: `fl-section`, `fl-type-{layout}`, `fl-item`.

---

## 07 — Styling

### Two entries, one scoped

| Entry | Source | Output | Scoped |
|---|---|---|---|
| Bootstrap | `src/css/rows-bootstrap.scss` | `assets/css/rows-bootstrap.css` | **Yes**, every selector prefixed `.acbs.fl-acbs` |
| Structure | `src/css/structure.scss` | `assets/css/structure.css` | No |
| Per row | `src/css/rows/{layout}.scss` | `assets/css/rows/{layout}.css` | No |

`webpack.config.js` does the scoping with `postcss-prefix-selector` under
`SCOPE_SELECTOR = '.acbs.fl-acbs'`. Selectors in `ROOT_SELECTORS` (`:root`, `html`, `body`,
`:host`) are hoisted out rather than prefixed, since prefixing them would confine Bootstrap's
own custom properties to a wrapper that does not contain them.

Two things about this build that are not obvious:

- **The transform re-visits its own output**, so it carries an idempotence guard. Without it,
  105 selectors came out prefixed twice.
- **The clean step is scoped**, `clean: { keep: (a) => !a.startsWith('css/rows/') }`, so a
  deleted layout's stale CSS stops shipping while hand-written files in `assets/` survive.

Per-row entries are discovered by glob (`rowEntries()`), not listed, so adding
`src/css/rows/foo.scss` is the whole job.

### Tokens

`src/css/_tokens.scss` holds both the Sass literals and `$theme-colors`, and is imported by both
entries so Bootstrap compiles against the brand palette rather than its own. Tokens are declared
as custom properties on `html` (specificity 0,0,1), which lets a theme restate any of them from
`:root` (0,1,0) without `!important`.

`$theme-colors` includes `white`, `accent-1`, `accent-2`, `tertiary`, `lighter` and `darker`
alongside Bootstrap's own, so `.btn-white`, `.text-accent-1` and friends exist. Adding a colour
to `Colour_Palette::choices()` without adding it here gives you a class that does nothing.

### Button and link hover

Bootstrap's `color-contrast()` picks black for secondary and tertiary, because both are light
enough by luminance. The plugin overrides hover and active to `--secondary-text` /
`--tertiary-text` with `--secondary-hover` / `--tertiary-hover` behind them, through Bootstrap's
own `--bs-btn-*` custom properties rather than competing rules.

**Those overrides must be written at the full `.acbs.fl-acbs` scope.** Written `.fl-acbs`
(two classes) they lose to the scoped Bootstrap (three) and silently do nothing. This applies to
anything in `structure.scss` that overrides a Bootstrap class.

Resting colour on secondary and tertiary buttons is still black. That is Bootstrap's contrast
choice, untouched: only hover was in scope.

### The fixture

`tools/fixture/build.js` builds `dist/fixture.html`, every layout crossed with every modifier
(54 sections), with the CSS **inlined** rather than linked, because the preview pane renders it
as a `data:` URL and a linked stylesheet never loads. It needs no WordPress.

---

## 08 — Hooks, options, constants

### Hooks

| Hook | Purpose |
|---|---|
| `acbs/init` | Action, after `Modules_Manager` is built (`erdc/init` still fires too) |
| `acbs/admin/settings` | The settings page's registration pass. Tab at 20, sections at 21, `Layouts_Export` at 30: those priorities are load-bearing |
| `acbs/rows/register` | Row type registration. Passed the `Row_Registry` class name |
| `acbs/rows/wrapper_classes` | Classes on the **single top-level** `<div>`. Default `['acbs','fl-acbs']`. Changing these breaks the scoped Bootstrap |
| `acbs/rows/enqueue_base_styles` | Whether to enqueue structure and Bootstrap at all on this request |
| `acbs/row/show` | Skip **one row** at render time |
| `acbs/row/wrapper_classes` · `/wrapper_id` | Attributes on **one** `section.fl-section`. Note the singular/plural distinction from `acbs/rows/wrapper_classes` |
| `acbs/row/wrapper_style` | Inline declarations on one section. Custom properties only |
| `acbs/row/before` · `/after` | Extension points emitted by the wrapper |
| `acbs/template/candidates` · `/path` | Template cascade |
| `acbs/styles/enqueue_default` | Drop the plugin's base sheet for a layout |
| `acbs/bootstrap/handle` | Handle the Bootstrap build registers under, so a site can point rows at its own |
| `erdc/flexible_layout/layouts` | Add or adjust the fully merged layout set |
| `erdc/flexible_layout/location` | Narrow where the Page Content group appears |
| `erdc/colour_palette/choices` | The shared background palette. **The** filter for relabelling |
| `erdc/section_bg/choices` · `erdc/grid_display/bg_choices` | Section-only and card-only, after the shared one |
| `erdc/grid_display/layout_fields` | Which Grid & Display fields a layout takes |
| `erdc/intro/fields` · `erdc/buttons/fields` | Component base fields |
| `erdc/page_header/fields` · `/location` | Page Header |
| `erdc/theme_settings/fields` · `/options_page_slugs` | Theme Settings |
| `erdc/dynamic_taxonomy_fields/pairs` · `/post_type_pairs` | Source→choices field pairs |
| `erdc_disable_layout_intro` | Layouts that should not get Intro fields. Suppresses nothing by default |

**Gone:** `acbs/row/items_query`, `acbs/row/items_spec` (removed with `Items_Source`),
`erdc/flexible_layout/bypass_cache`, `erdc/flexible_layout/cache_segment`,
`erdc/conditions_portability/types`, `erdc/loop_grid_repeater/before_loop`.

### Options

```
erdc_version                        last version Core\Upgrades ran for (autoloaded)
erdc_disabled_flexible_layouts      settings checkbox list
erdc_page_template_migration        one-shot page template assignment
erdc_page_header_enabled            'yes' / absent
erdc_page_header_exclusions         post types + taxonomies to hide on
erdc_acf_copy_to_clipboard_enabled
```

Option keys stay `erdc_*`: renaming buys nothing and needs a migration.

`Core\Upgrades::maybe_run()` swept the dead ones once on 27/08/2026, including
`erdc_flexible_layout_row_versions`, which was **autoloaded** and being read into memory on
every request for a cache that no longer exists, and every `_fl_cache_*` transient through
`delete_transient()` so the timeout rows went with them.

### The updater

**On 28/08/2026 the update checker destroyed this fork.** The header had been reset to `1.0.0`
while `PucFactory::buildUpdateChecker()` still pointed at the original ERDC repository, which
publishes `1.0.4`. wp-admin offered "1.0.0 → 1.0.4" as a normal update, and WordPress's plugin
upgrader deletes the entire plugin directory before installing. That took the 1.0.36 codebase,
the phases 00–02 work, and the `.git` directory that lived inside the plugin folder.
`wp-content/upgrade-temp-backup/` had already been emptied, so there was no rollback.

Rebuilt from a packaged 1.0.33 release plus the Claude Code session transcripts in
`~/.claude/projects/`, which carried the phase 01/02 files verbatim.

**Re-enabled 31/08/2026** and pointed at the fork's own repository,
`https://github.com/salumguilherme/acf-components-block-system`, via `ACBS_UPDATE_REPO` in the
main plugin file (guarded by `!defined()` so a site can override it). The upstream ERDC repo,
`https://github.com/salumguilherme/elementor-repeater-and-dynamic-conditions-addon`, is the
read-only source of record for pre-fork code and **must never** be what `ACBS_UPDATE_REPO`
points at: a fork whose update checker points upstream can be overwritten by upstream at any
time, and a lower fork version turns that into a silent downgrade rather than a visible error.

The `.git` directory still lives inside the plugin folder, which a plugin update would still
delete. There is now a mirror outside the webroot and `origin` on GitHub. **Keep pushing to
both.**

**Check before each release:** the fork's version must be above every version its own repository
has published, or a stale tag reads as an upgrade and the same delete-then-install cycle runs.

### Constants and the namespace

`ACBS_VERSION`, `ACBS__FILE__`, `ACBS_PATH`, `ACBS_URL` are the real definitions, with `ERDC_*`
defined as aliases for one release. `ERDC_UPDATER_TOKEN` comes from `wp-config.php` and keeps
its name: it is a site's constant, not ours.

**The PHP namespace was renamed `ERDC\` → `ACBS\` on 31/08/2026**, across 82 files and 198
references, plus the one string-built class name in `Modules_Manager::get_module_class()`. No
autoloader change and no file moves were needed: `Plugin::autoload()` strips `__NAMESPACE__`
dynamically and the on-disk paths never encoded the vendor prefix.

Uppercase `ERDC\` and lowercase `erdc` were never the same thing, and only the first moved:

| Stays | Why |
|---|---|
| `'erdc'` text domain | Renaming it orphans every translation |
| `erdc_*` option keys | Needs a migration, buys nothing |
| `erdc/*` hooks on fields and components | Public API a site may already hook |
| `ERDC_UPDATER_TOKEN` | A site's own `wp-config.php` constant |

---

## 09 — Traps

Everything here shipped, looked correct, and silently did nothing. Each was found by checking
output, not by a failing build. That is the pattern: **this codebase fails quietly.**

- **Never change `field_6a0a99f262aaf`.** The `page_sections` field key. Every post that ever
  saved page sections carries a `_page_sections` postmeta row holding it. Change it and
  `get_field('page_sections')` returns raw layout names instead of formatted rows, and every
  existing page silently stops rendering. Every *other* key in that file was deliberately
  regenerated; only this one is pinned.
- **The autoloader is path-derived.** A misplaced file does not error, it never loads.
- **`get_sub_field()` returns `false` for a name that does not exist**, exactly as it does for
  an empty value. Renaming `columns_alignment` to `layout_columns_alignment` left the wrapper
  reading the old name: no error, no warning, the class just stopped being emitted.
- **Specificity: `.fl-acbs` is two classes, `.acbs.fl-acbs` is three.** Overrides written at the
  shorter scope lose to the scoped Bootstrap. See §07.
- **A colour needs three registrations**, not one: a key in `Colour_Palette::choices()`, a rule
  in `structure.scss`, and an entry in `$theme-colors` if a `.btn-*` or `.text-*` is wanted.
  `.btn-white` was referenced for a while and never existed.
- **`rows` is a reserved word in MySQL 8.** The page-template migration used it as a table
  alias, found nothing, and recorded itself as done. Renamed to `sections`, flag cleared,
  re-run: nine pages.
- **`safecss_filter_attr()` silently drops custom properties.** Hence `Wrapper::style()`
  validating `--*` declarations against its own regex rather than passing them through
  WordPress's filter.
- **The postcss scoping transform re-visits its own output.** Idempotence guard, §07.
- **Break a `have_rows()` loop without `reset_rows()` and you corrupt the next one.** §05.2.
- **`get_row_index()` renumbers when a row is disabled.** §05.4.
- **Contributor group priority is inverted on purpose.** `order_by_priority()` sorts highest
  Order No. first so the *lowest* number is merged last and wins.
- **Site fields are re-keyed on merge.** ACF's runtime field cache is keyed by field key alone,
  so re-using a site's fields verbatim under our group resolves back to a stale copy whose
  `parent_layout` points at the site's layout, and ACF silently drops the field. Hence the
  `_site` suffix and the conditional-logic rewriting with it. `Grid_Display` does the same thing
  for the same reason with its `field_grid_{layout}_{key}` prefix.
- **Theme templates *replace*; theme styling *layers*.** Deliberate (decision 5), and the one
  thing most likely to surprise a theme developer. Say it in the theme docs.
- **Footer-enqueued CSS applies after first paint.** Only per-row sheets go in the footer;
  structure, tokens and Bootstrap stay in the head.
- **Generated files:** everything under `assets/css/` that has a `.map` beside it, plus
  `assets/css/rows/`. Editing them is undone by the next build. The four hand-written ACF admin
  scripts in `assets/js` are **not** in the pipeline and are edited in place.

---

## 10 — What is left

### Row templates

Five still print only their layout label:

| Layout | Notes |
|---|---|
| `columned_content` | Highest value. A `columns` repeater of `icon` (image, svg), `content` (wysiwyg), `column_alignment` (button group), plus a Buttons clone, plus the full Grid & Display set |
| `content_left_image_right` | |
| `full_width_image` | |
| `image_gallery` | Gallery field, 1–8 columns |
| `logo_gallery` | Gallery field, 1–8 columns |

### Row stylesheets

Only `icon_leaders.scss` and `icon_list.scss` exist. `stats`, `testimonials`, `cta`,
`accordions` and `contact_page_form` have real templates and no sheet.

### Open questions

- **`button_outline` toggle labels look inverted.** Worth a read before anyone relies on them.
- **`layout_display_bg` still defaults to `light`** while both colour fields now default to
  `default`. Probably wants aligning.
- **Secondary and tertiary buttons rest on black text.** Bootstrap's contrast choice; only hover
  was changed. If black at rest and white on hover is wrong, it is one line each.
- **Is `layouts.json` an export, or the source of truth?** `build-layouts.js` currently
  generates the array literal in `page-content.php`. Making `Page_Content` a loader instead is
  a phase of its own.

### Carried-over defects

| Item | Where | Severity |
|---|---|---|
| Eager ACF registration on `acf/init` forces full field-group load on every request | `page-content.php` | Structural |
| Nothing is translatable: no `languages/`, no `load_plugin_textdomain()` | repo-wide | Structural |
| N+1 term walk and `numberposts => -1` | `dynamic-taxonomy-fields/ajax.php` | Medium |

AJAX capability checks were fixed on 27/08/2026: all three handlers go through
`Ajax::require_capability()` (`edit_posts`).

### Repo hygiene

- `vendor/` is committed (update checker only) while `composer.json` is gitignored, the inverse
  of the usual arrangement.
- Version lives in the plugin header and `define('ACBS_VERSION')`. `bin/release.js` fails hard
  if the pattern does not match exactly once, so they cannot drift.
- `Core\Environment` has no callers left. Kept in case a per-client add-on registering product
  row types wants it; delete it if none appears.

---

## 11 — Tooling

| Skill | When |
|---|---|
| `wp-plugin-development` | Architecture, hooks, activation, security, release packaging |
| `wp-phpstan` | Highest value here, now that Elementor's untyped surface is gone |
| `sass-scss` | The structure/Bootstrap/row sheet split |
| `wp-performance` | Row hydration cost, autoloaded options |
| `wp-wpcli-and-ops` | Safe WP-CLI, db export/import |
| `acf-skill:acf` | Reading and writing ACF data on a live site |
| `code-review` · `simplify` · `security-review` | Review passes over a diff |
| `artifact-design` · `dataviz` | Before writing any artifact or chart |

| MCP server | Use for |
|---|---|
| `emcp-erdc-plugin-local` | The local dev site: pages, ACF read/write, WP-CLI dispatch, DB queries |
| `Claude_Browser` | Previewing rendered pages, DOM, console |
| `figma-desktop` · `Figma` | Design context while authoring layouts |

### Starting a session on this repo

Read §03 (decisions), §05 (ACF loop rules) and §09 (traps), then say which item in §10 you are
on. Local site: `https://erdc-plugin.local/`, a working Page Builder page at `/5562-2/`.

---

*ACF Components Block System · v1.0.0 · forked from ERDC 1.0.36 · Five Creative · 01/09/2026*
