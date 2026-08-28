# ACF Components Block System — CLAUDE.md

Session primer for the ACF Components Block System (ACBS), forked from the Elementor Repeater and Dynamic Conditions Addon (ERDC) on 27/08/2026. Mirrors the team's "ACBS Handbook" artifact — keep both in sync whenever a module, field, hook, decision or pending item changes.

- **Version:** 1.0.0
- **Repo:** fresh history, one commit. Folder name unchanged: `elementor-repeater-and-dynamic-conditions-addon`
- **Text domain:** `erdc` (not yet renamed)
- **Namespace:** `ERDC\` — deliberately not renamed yet, see §08 Constants
- **Doc date:** 28/08/2026 (phases 00–02 complete)

**This file is now tracked in git.** The old ERDC repo ignored it; this one does not (`.gitignore` has `#CLAUDE.md` commented out). Write for the team, not for one machine.

> **Read `§05 ACF loop rules` before writing anything that iterates rows.** Four of the six items there are undocumented in ACF, and two existing bugs in this codebase come straight from not knowing them.

---

## 01 — What it is

A Five Creative in-house plugin that renders ACF Flexible Content rows as **PHP template parts**, the way WooCommerce renders its templates. An editor composes a page out of ACF Flexible Content rows; each row is rendered by `templates/rows/{layout}.php`, overridable from the theme.

It is a fork of ERDC 1.0.36, which did the same job through Elementor: a custom document type, a theme location, a widget, and Theme Builder display conditions choosing a template per row. **All of that is being removed.** ACF stays and is the only hard dependency.

### State of the migration

Phases 00, 01 and 02 are done. See §10 for what remains and for the two checkpoints still
needing a browser.

| | |
|---|---|
| **Phase 00** | Repo forked, version control started. Plugin header renamed to *ACF Components Block System* 1.0.0, `Requires Plugins` reduced to `advanced-custom-fields-pro`. WooCommerce layouts removed. |
| **Phase 01** | Boots with no Elementor at all. `Core\Module_Base` replaces `Elementor\Core\Base\Module`; `Modules_Manager` guards each name with `class_exists()` and is down to six modules; the bootstrap runs from `plugins_loaded` (see §04 for why *not* `init`); `Source_Resolver` lifted out of the deleted `repeater-dynamic-tags`; `Core\Admin\Settings_Page` replaces the Elementor settings tab as an ACF submenu, with all five registrants moved by changing one hook name; ten modules plus the widget, conditions, documents and row cache deleted; `Core\Upgrades` sweeps the orphaned options and transients once. |
| **Phase 02** | The render layer: `Row`, `Row_Type`, `Row_Type_Base`, `Layout_Row_Type`, `Row_Registry`, `Renderer`, `Template_Loader`, `Wrapper`, `Assets`, the five action points, `acbs_render_rows()` / `acbs_get_rows()` / `[acbs_rows]`, and `templates/` with `wrapper.php`, an empty `default.php`, all 15 stubs and 5 item stubs. |
| **First code change** | 27/08/2026: `products_archive_feed` and `product_feed` removed from `page-content.php` (1,724 → 1,590 lines, 15 layouts → **15**), along with `remove_woocommerce_layouts_if_inactive()`, its call in `get_current_layouts()` and the then-unused `Environment` import. Ten product-related reference JSONs deleted. 16 layout and 28 loop-item JSONs remain. |
| **Live consequence** | Page **11 "Rental Fleet"** (published) has an orphaned row: `[full_width_content, full_width_content, products_archive_feed]`. The renderer skips it, wrapper included — see §05.7. It is not the only one; §10 lists three more pages, orphaned by the contributor groups all being in the trash. |
| **Still open in the main file** | The `Description` header reads "Compoennt block system for ACF Pro" — Gui's to correct. The namespace is still `ERDC\`; the text domain stays `erdc` deliberately. |

### What the plugin provides

1. **The row rendering system.** ACF loop → template cascade → per-row assets. The core product.
2. **The field subsystem.** `page_sections`, the contributor-group location rules, the merge pipeline, the Buttons/Intro custom field types. Carried over untouched — it has no Elementor dependency at all.
3. **Component field groups.** Page Header and Theme Settings, extended or overridden by a site tagging its own field group.
4. **A site kit** of design tokens and global classes, loaded from JSON. *Parked by decision — see §03.*

> **Client scoping.** Anything prefixed `fs_` or `sv_` belongs to two *different* client builds and must never be mixed or cross-referenced. Under this fork they leave core entirely for per-client add-on plugins (§03, decision 4).

---

## 02 — Environment

| Item | Value |
|---|---|
| Local path | `~/DevKinsta/public/erdc-plugin/wp-content/plugins/elementor-repeater-and-dynamic-conditions-addon` |
| Stack | WordPress 7.0.4 · PHP 8.3.3 · DevKinsta |
| Hard dependency | **ACF Pro only**, declared once in `Core\Module_Base::is_active()`. WooCommerce is optional and no longer referenced anywhere except `Source_Resolver`'s guarded shop-page branch |
| Requires | WP 6.0+ · PHP 8.0+ |
| Elementor | Free to deactivate. The local environment is a copy; a backup exists. Elementor Pro 4.2.1 is still installed for reference while porting the 22 exported layout documents. |
| Build | **webpack**. Sources in `src/`, output to `assets/js` and `assets/css`. `npm run dev` (watch) · `npm run build:dev` · `npm run build`. `src/` is down to one file, `css/frontend.sass`, since the drilldown sources went; the config's only entry is that one, and phase 04 adds the per-row entries. `foundation-sites` is out of `package.json`. The four hand-written ACF admin scripts in `assets/js` are **not** in the pipeline and are edited in place. |
| Release | `npm run release` (patch) · `release:minor` · `release:major` · `npm run release -- 1.1.0` · `npm run package`. Runs `bin/release.js`. `SHIP_DIRS` is `['assets','modules','core','templates','vendor']` — `templates` was added in phase 02, and the version pattern re-pointed from `ERDC_VERSION` to `ACBS_VERSION`. |
| Tests / lint | **None.** No PHPUnit, PHPCS, PHPStan or CI. With Elementor's untyped surface gone this is now cheap to add; it is phase 06. |
| Updates | GitHub-based via `yahnis-elsts/plugin-update-checker`. Needs `ERDC_UPDATER_TOKEN` in `wp-config.php`; absent, it warns in wp-admin rather than fataling. The update URL still points at the old repo. |

---

## 03 — Locked decisions

Answered 27/08/2026. Do not relitigate these without saying so explicitly.

| # | Decision |
|---|---|
| 1 | **Template resolution is a candidate cascade**, most specific first, filterable. Replaces Theme Builder conditions. |
| 2 | The 22 Elementor documents in `assets/data/flexible-layouts/` are **reference only**. No converter. |
| 3 | **Assets enqueue in the footer**, not through a head pre-pass — rows render at runtime, so assets follow them. Always-loaded sheets (structure, Bootstrap, tokens) still go in the head, since they need no discovery. |
| 4 | **Row types are objects.** One class per layout answering for its own fields, template, assets and wrapper classes. The registry is a **public API** — client add-ons register through it. |
| 5 | **Styling layers: plugin first, theme second**, wired as a stylesheet dependency. A theme overrides without copying the sheet. Note this differs from templates, where the theme's file *replaces* the plugin's. |
| 6 | **Site kit is parked.** Come back to it after the render layer works. Until then `structure.sass` hand-declares the few custom properties rows need. |
| 7 | **Shortcodes module removed** — but port `fs-fl-image-cards-sublinks`, `sv-full-width-image-section`, `fs-acf-image-object-fit` and `fs-fl-faq-schema` into row templates *before* deleting. |
| 8 | **Source resolution moves into the flexible-layout-template module**, not `core/`. |
| 9 | **Drilldown menu removed.** The webpack/Sass bundler it introduced **stays** and becomes the per-row asset pipeline. Drop `foundation-sites` from `package.json`. |
| 10 | **WooCommerce layouts leave core** (`products_archive_feed`, `product_feed`) along with every `fs_`/`sv_` query filter, into per-client add-ons. |
| 11 | **Bootstrap 5**: plugin bundles a fallback, only enqueued when the theme has not registered its own. Handle name is filterable — sites use different handles. |
| 12 | **Caching dropped entirely.** Page cache at the server handles it. |
| 13 | **Every layout gets a stub template** printing just its layout title, so the full set exists from day one. |

**Still open:** is the base-fields JSON export just an export, or does `layouts.json` eventually *replace* the array literal in `page-content.php` and make `Page_Content` a loader? Recommendation is JSON as source of truth, but as its own phase after the render layer — not while the render layer is also changing.

---

## 04 — Architecture

### Bootstrap chain

```
elementor-repeater-and-dynamic-conditions-addon.php
  ├─ defines ACBS_VERSION / ACBS__FILE__ / ACBS_PATH / ACBS_URL
  │            (ERDC_* aliased to each, for one release)
  └─ plugins_loaded            → require plugin.php

plugin.php  — ERDC\Plugin singleton
  ├─ spl_autoload_register     → path-derived autoloader
  ├─ updater()
  ├─ Core\Upgrades::maybe_run()   one-shot cleanup, gated on erdc_version
  └─ init()  → new Core\Modules_Manager → do_action('acbs/init')
                                        → do_action('erdc/init')  deprecated alias
```

**Why `plugins_loaded` and not `init` priority 5.** ACF fires `acf/init` from *inside*
`init` at priority 5 — verified in `advanced-custom-fields-pro/acf.php`:
`add_action('init', array($this,'init'), 5)`, and that method's last statement is
`do_action('acf/init', ACF_MAJOR_VERSION)`. Bootstrapping at `init:5` would leave whether
our `acf/init` listeners are registered before ACF fires it down to plugin load order, so
the field groups would register or silently not register depending on the alphabet.
`plugins_loaded` is unambiguously earlier, and ACF's api functions already exist by then
because `acf.php` calls `acf()->initialize()` at include time. **Do not "tidy" this onto
`init`.**

`Row_Registry::boot()` *is* on `init`, at priority 20, because it reads
`get_current_layouts()` and therefore needs `acf/init` to have already run.

### The autoloader is load-bearing

`Plugin::autoload()` derives a file path from the class name by regex: PascalCase segments become kebab-case, underscores become path separators, everything lowercases.

```
ERDC\Modules\FlexibleLayoutTemplate\Fields\Site_Layouts
  → modules/flexible-layout-template/fields/site-layouts.php
```

A class that does not sit at exactly the matching path **silently fails to load** — no error. First thing to check when a class "doesn't exist". Renaming the namespace to `ACBS\` needs no autoloader change, since it strips `__NAMESPACE__` dynamically.

### Render layer — as built

```
core/
  module-base.php             instance() / is_active() / get_name()
  modules-manager.php         class_exists() guard + ACF dependency
  environment.php             unchanged, and now unreferenced (see below)
  upgrades.php                one-shot option and transient cleanup
  admin/
    settings-page.php         add_tab / add_section / add_field facade

modules/flexible-layout-template/
  module.php                  hooks, field registration, wrapper classes  (258 lines)
  source-resolver.php         whose page_sections to read  (decision 8)
  settings.php                disabled layouts
  layouts-export.php          the JSON export
  rows/
    row.php                   value object: layout, position, source, label
    row-type.php              interface  (public API, decision 4)
    row-type-base.php         abstract, defaults
    layout-row-type.php       concrete, built from a Page_Content slice
    row-registry.php          name => Row_Type
    renderer.php              the have_rows() loop
    template-loader.php       candidate cascade + locate_template()
    wrapper.php               locates templates/wrapper.php, emits the action points
    template-tags.php         plain functions, required not autoloaded
    assets.php                footer collector  (decision 3)
    items-source.php          NOT YET — phase 05
  fields/                     21 files, unchanged

templates/                    overridable from {theme}/acbs/
  wrapper.php
  rows/default.php            ships empty
  rows/{layout}.php           × 15
  rows/{layout}/item.php      × 5
```

**The wrapper is a template too.** `Wrapper::render()` computes the classes and id, then
includes `templates/wrapper.php` with `$row` and nothing else in scope; that template reads
`$row->wrapper_class()`, `$row->wrapper_id()` and `$row->content()`. The PHP fallback inside
`Wrapper::render()` only fires if that file has been deleted and the theme supplies none.

**The loop-depth guard is not optional.** `Wrapper::include_template()` records
`count(acf()->loop->loops)` before the include and calls `reset_rows()` back down to it
afterwards, warning under `WP_DEBUG`. Without it one template that `break`s a nested
`while(have_rows())` without `reset_rows()` corrupts every row after it on the page.

### Public render API

```php
acbs_render_rows();                                       // queried object, page_sections
acbs_render_rows( [ 'field' => 'page_sections', 'source' => $post_id ] );
echo do_shortcode( '[acbs_rows]' );
```

The renderer **returns** a string; the template tag echoes it. ERDC's widget wrote straight to the output buffer in half a dozen places, which is what made its caching and asset capture so tangled.

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

Resolved through `locate_template()` so child theme → parent theme → plugin works, per site on multisite, for free. Filters: `acbs/template/candidates`, `acbs/template/path`. Memoise per request — a 12-row page with a 5-candidate cascade is otherwise 60 `file_exists()` calls.

---

## 05 — ACF loop rules

Verified against ACF Pro's `includes/api/api-template.php`, `includes/loop.php` and `pro/fields/class-acf-field-flexible-content.php` on 27/08/2026. **The renderer is `have_rows()` / `the_row()` / `get_row_layout()` and nothing else.**

**1. The loop stack is keyed on selector plus normalised post id.** `have_rows()` builds `"selector={$selector}/post_id={$post_id}"` *after* `acf_get_valid_post_id()`. Same key means same loop; a different key means a nested child loop (if the selector resolves to a sub field of the active loop) or a new parent loop. `Source_Resolver` must normalise once and pass the same value everywhere — a `WP_Term` in one call and `"term_12"` in the next would silently open a second loop.

**2. A completed loop cleans itself up; a broken one does not.** When there is no next row, `have_rows()` calls `acf_remove_loop('active')` and returns false. That is the *only* thing that pops the stack. `break` out of a `while(have_rows())` and the loop stays on the stack half consumed, and the next `have_rows()` with the same key resumes it.

```php
while ( have_rows( 'cards' ) ) {
    the_row();
    if ( $found ) { reset_rows(); break; }   // mandatory
}
```

There is already one of these: `Module::switch_to_preview_query()` breaks on a layout match with no `reset_rows()`. It gets away with it because it runs on a dynamic-tag hook rather than in the render path. **The wrapper should record loop depth before including a row template and reset back down to it afterwards**, so one bad template cannot poison the rest of the page.

**3. Nested loops work for free.** Inside a row, `have_rows('cards')` is detected as a child loop of the active `page_sections` row. So the repeater branch of `Items_Source` is not a query at all — it is a nested `have_rows()`. Only taxonomy and post type need to build anything.

**4. `get_row_index()` is a position, not an identity.** It returns `row_index_offset + i`, offset defaults to **1**, so it is 1-based while ACF's internal `i` is 0-based. `acf_add_loop()` also runs `array_values()` over the row set, and flexible content's `load_value()` skips editor-disabled rows — so disabling one row renumbers every row after it. **Never derive a persistent anchor id or `href` target from it.**

**5. "Unformatted" is not cheap, and rows are keyed by field key.** Flexible content's `load_value()` walks every layout's sub fields calling `acf_get_value()` on each, so `get_field('page_sections', $id, false)` hydrates the whole page. It returns each row keyed by **field key** plus an `acf_fc_layout` entry — not by field name. Templates should read through `get_sub_field()`, which is also what ACF's own formatting (image arrays, galleries, link arrays, the Buttons/Intro field types) requires. For just the layout names, use `acf_get_metadata()` — raw meta, which for a flexible content field is exactly the ordered list of layout names, with no sub field values loaded.

**6. Disabled rows are already filtered out.** `load_value()` drops rows an editor toggled off, so the renderer needs no check of its own. `acbs/row/show` layers on top of that, not instead of it.

---

## 06 — Field subsystem

**Carried over unchanged.** All 21 files in `modules/flexible-layout-template/fields/` — 5,161 lines — contain zero Elementor references. Do not "port" them; leave them alone.

### Three location rules, all of them tags

None match a real edit screen. They exist so a site can mark a field group as a contributor, and the plugin merges its fields elsewhere.

| Rule | param | Says |
|---|---|---|
| Flexible Layout | `erdc_flexible_layout` | "This group is a source of **page_sections layouts**." Merged by `Site_Layouts`. |
| Flexible Layout Row | `erdc_flexible_layout_row` | "Add these fields to **one layout**, or to every layout present and future." Supports `!=`. Merged by `Common_Fields`. |
| Flexible Layout Component | `erdc_flexible_layout_component` | "Override the fields of a **component** — Page Header, Theme Settings, Buttons, Intro, Other Settings." Merged by `Site_Fields_Base`. |

### How a layout is assembled

```
Page_Content::get_current_layouts()
  1. get_base_layouts()                  15 layouts, 117 fields, hardcoded
  2. remove_woocommerce_layouts_if_inactive()
  3. remove_disabled_layouts()           settings checkbox list
  4. Site_Layouts::merge()               groups tagged "= Page Content"
  5. apply_filters('erdc/flexible_layout/layouts')
  6. Site_Layouts::sort()                alphabetical by label

Common_Fields::inject_common_fields()  on acf/load_field for page_sections
  per layout, sub_fields become:
    [ Intro fields ] + [ "Content" tab ] + [ layout's own fields ]
      + [ Layout Row contributed fields ] + [ "Other Settings" tab + fields ]
```

### Key classes

| Class | Responsibility |
|---|---|
| `Page_Content` | Owns `page_sections`. 1,724 lines, mostly one array literal. |
| `Common_Fields` | Injects Intro, the tab structure, layout-row fields and "Other Settings" into every layout. |
| `Contributor_Groups` | Finds field groups tagged with a rule value. **Lower Order No. wins** (merged last). No memoisation. |
| `Field_Merge` | The one merge implementation. Same-`name` replaces; a replacement keeps *our* position unless it sits below a field the site genuinely added. |
| `Site_Fields_Base` | Single implementation of component override. Buttons overrides `merge()` because its fields are repeater row fields, not a flat list. |
| `Conditional_Logic` | Feeds the plugin's fields to ACF's conditional-logic dropdown, which otherwise only sees fields in the DOM of the group being edited. |
| `Buttons_Field_Type` | A real ACF field type, not a Clone — ACF's field group editor permanently flattens a seamless clone resolving to a single field. |

### Common fields the wrapper reads

From `other-settings.php`: `section_bg`, `section_container_id`, `vertical_padding`, `vertical_padding_mobile`. From the layouts themselves: `grid_type`, `layout_columns`, `columns_alignment`, `image_fit`. From `intro.php`: `section_title`, `section_content`. From `buttons.php`: `buttons` repeater of `button_text` / `button_link` / `button_style`.

### The 17 base layouts

`content_left_image_right` · `contact_page_form` · `full_width_content` · `columned_content` · `full_width_content_with_read_more` · `full_width_image` · `full_width_image_cta` · `icon_leaders`\* · `image_cards_grid`\* · `image_cards_with_sublinks_grid` · `image_cards_multi_grid`\* · `image_gallery` · `logo_gallery` · `products_archive_feed`\*† · `team_members_grid`\* · `testimonial`\*

\* has an item stub at `templates/rows/{layout}/item.php` — † leaves core (decision 10)

**Correction to the plan: three layouts have a `source` field, not five or six.** Verified
against the live `page_sections` field on 27/08/2026. Only `icon_leaders`,
`image_cards_grid` and `image_cards_multi_grid` carry the repeater/taxonomy/post_type
`source` selector, so only those three need `Items_Source`. `team_members_grid` and
`testimonial` iterate a plain repeater (`team_members`, `testimonials`) with no source
choice — a nested `have_rows()` and nothing else. They keep their item stubs, since they
still iterate.

---

## 07 — Module dispositions

| Module | Lines | Disposition | State |
|---|---|---|---|
| `flexible-layout-template/fields` | 5,027 | **Keep** — zero Elementor references | Untouched |
| `theme-settings` | ~410 | **Keep** — base-class swap | Done |
| `page-header` | ~520 | **Keep** — base class + settings hook | Done |
| `dynamic-taxonomy-fields` | ~600 | **Keep** — capability checks added | Done |
| `acf-copy-to-clipboard` | ~230 | **Keep** — base class + settings hook | Done |
| `settings` | ~120 | **Rewrite** — owns `Core\Admin\Settings_Page`, an ACF submenu | Done |
| `flexible-layout-template/module.php` | 933 → 258 | **Rewrite** | Done |
| `…/widgets/widget.php` | 1,207 | **Rewrite** → `Rows\Renderer` (~210 lines) | Done, deleted |
| `…/layouts-export.php` | ~230 | **Keep** — re-pointed at `acbs/admin/settings` | Done |
| `…/row-cache.php` | 271 | **Drop** | Deleted; `Core\Upgrades` sweeps the transients |
| `…/conditions` + `documents` | 850 | **Drop** | Deleted |
| `shortcodes` | 1,510 | **Drop** | Deleted — see the porting note below |
| `repeater-dynamic-tags` | 1,047 | **Drop** | Deleted; `Source_Resolver` lifted first |
| `loop-grid-repeater` | 759 | **Drop** | Deleted; skins read first |
| `legacy-migration` | 1,447 | **Drop** | Deleted |
| `default-templates` | 743 | **Drop** | Deleted |
| `kit-defaults` | 732 | **Drop** | Deleted |
| `drilldown-menu` | 800 | **Drop** | Deleted, bundler kept, `foundation-sites` dropped |
| `acf-dynamic-conditions` | 573 | **Drop** | Deleted |
| `page-template-condition` | 258 | **Drop** | Deleted |
| `loop-template-portability` | 245 | **Drop** | Deleted |

Six modules remain in `Modules_Manager::MODULES`: `flexible-layout-template`,
`dynamic-taxonomy-fields`, `theme-settings`, `page-header`, `settings`,
`acf-copy-to-clipboard`. The manager now guards each name with `class_exists()` (a typo
used to be a fatal) and `Core\Module_Base::is_active()` declares the ACF dependency once,
so no module loads with ACF deactivated.

### `Core\Environment` has no callers left

`is_woocommerce_active()` was the only method and `page-content.php` was its only caller,
which went with the WooCommerce layouts. `Source_Resolver` guards its own shop-page branch
with `function_exists('is_shop')` instead, so nothing references the class. Kept rather than
deleted because a per-client add-on registering the product row types back will want it, but
if phase 05 comes and goes without one, delete it.

### The four shortcodes still to port (phase 05)

Read before deletion; the bodies live in the first commit. Port them into the row template
that used them, not back into a shortcode.

| Shortcode | Belongs to | Notes |
|---|---|---|
| `fs_fl_image_cards_sublinks` | `image_cards_with_sublinks_grid` | Nested `have_rows('sublinks')`. Already escapes; `esc_attr` on an `href` should become `esc_url` |
| `sv_full_width_image_section` | `full_width_image` | Emits a per-row `<style>` block. **Three bugs to not carry across**: the mobile branch reads `height_tablet`, one media query says `max-width: 767.98)` with no unit or bracket, and the element id is `md5(time())` so two rows in the same second collide. Per-breakpoint heights belong in `structure.sass` plus a CSS custom property, not an inline block |
| `fs_acf_image_object_fit` | any layout with `image_fit` | Three lines; already covered by the wrapper's `fl-image-fit-*` class. Probably delete rather than port |
| `fs_fl_faq_schema` | — | **The file of that name held no FAQ schema code.** See the defect table |

Enable or disable a module by editing `Modules_Manager::MODULES`.

---

## 08 — Hooks & options

Hooks rename `erdc/*` → `acbs/*`. Only `erdc/init` is currently aliased; the rest changed
name outright, because the filters that carried them (`…/wrapper_classes`,
`…/wrapper_id`, `…/show_section`) were only ever called from code that no longer exists.
`Wrapper::classes()` does accept a space-separated string back from
`acbs/row/wrapper_classes`, so a client callback written against the old string contract
still works once its hook name is updated.

| Hook | Purpose |
|---|---|
| `acbs/init` | Action, after `Modules_Manager` is built (`erdc/init` still fires too) |
| `acbs/admin/settings` | The settings page's registration pass. Tab at 20, sections at 21, `Layouts_Export` at 30 — those priorities are load-bearing |
| `acbs/rows/register` | Row type registration. Passed the `Row_Registry` class name; a client add-on registers its own `Row_Type` here |
| `acbs/flexible_layout/layouts` | Add or adjust the fully merged layout set |
| `acbs/flexible_layout/location` | Narrow where the Page Content group appears |
| `acbs/row/show` | Skip a row at render time (was `…/show_section`) |
| `acbs/row/wrapper_classes` · `/wrapper_id` | Wrapper attributes |
| `acbs/row/items_query` | The one query filter, replacing six client-specific ones. **Not implemented yet** — arrives with `Items_Source` in phase 05 |
| `acbs/template/candidates` · `/path` | Template cascade |
| `acbs/styles/enqueue_default` | Drop the plugin's base sheet for a layout |
| `acbs/bootstrap/handle` · `/script_handle` | Handle to detect before bundling Bootstrap. **Not implemented yet** — phase 04 |
| `acbs/row/before` · `/after` · `/{layout}/before` · `/{layout}/content` · `/{layout}/after` | Extension points emitted by the wrapper, so sites extend without copying templates |
| `erdc/intro/fields` · `erdc/buttons/fields` | Component base fields (unchanged) |
| `erdc/page_header/fields` · `/location` | Page Header (unchanged) |
| `erdc/theme_settings/fields` · `/options_page_slugs` | Theme Settings (unchanged) |
| `erdc/dynamic_taxonomy_fields/pairs` · `/post_type_pairs` | Source→choices field pairs (unchanged) |
| `erdc_disable_layout_intro` | Layouts that should not get Intro fields (unchanged) |

**Dropped:** `erdc/flexible_layout/bypass_cache`, `erdc/flexible_layout/cache_segment`, `erdc/conditions_portability/types`, `erdc/loop_grid_repeater/before_loop`.

### Options

```
erdc_version                        last version Core\Upgrades ran for (autoloaded)
erdc_disabled_flexible_layouts      settings checkbox list
erdc_page_header_enabled            'yes' / absent
erdc_page_header_exclusions         post types + taxonomies to hide on
erdc_acf_copy_to_clipboard_enabled
```

Option keys stay `erdc_*` — renaming buys nothing and needs a migration.

**Cleaned up 27/08/2026** by `Core\Upgrades::maybe_run()`, once, gated on `erdc_version`:
`erdc_fresh_install`, `erdc_pending_modals`, `erdc_legacy_migration`,
`erdc_intro_migration`, `erdc_intro_notice_dismissed`,
`erdc_flexible_layout_row_versions` (this one was **autoloaded**, so it was being read into
memory on every request for a cache that no longer exists), and every `_fl_cache_*`
transient, deleted through `delete_transient()` so the timeout rows go with them.

### The updater is OFF, and must stay off until the fork has its own repo

**On 28/08/2026 the update checker destroyed this fork.** The header had been reset to
`1.0.0`, while `PucFactory::buildUpdateChecker()` still pointed at the original ERDC
repository, which publishes `1.0.4`. wp-admin offered "1.0.0 → 1.0.4" as a normal update,
and WordPress's plugin upgrader deletes the entire plugin directory before installing. That
took the 1.0.36 codebase, the phases 00–02 work, and the `.git` directory that lived inside
the plugin folder. `wp-content/upgrade-temp-backup/` had already been emptied by WordPress,
so there was no rollback.

Everything was rebuilt from a packaged 1.0.33 release found in `wp-content/`, plus the
Claude Code session transcripts in `~/.claude/projects/`, which carried the phase 01/02
files verbatim.

`Plugin::updater()` now returns early unless **`ACBS_UPDATE_REPO`** is defined in
`wp-config.php`. There is no default URL on purpose. Before re-enabling it, both of these
have to be true:

1. The URL points at **this fork**, not at ERDC. A fork whose update checker points
   upstream can be overwritten by upstream at any time, and a lower fork version turns
   that into a silent downgrade rather than a visible error.
2. The fork's version is above every version that repository has ever published, so a
   stale tag cannot read as an upgrade.

**Also: do not keep the only copy of `.git` inside the plugin directory.** Any plugin
update deletes it. Push to a remote, or keep a `git bundle` mirror outside the webroot.

### Constants

`ACBS_VERSION`, `ACBS__FILE__`, `ACBS_PATH`, `ACBS_URL` are now the real definitions, with
`ERDC_*` defined as aliases of each for one release. `ERDC_UPDATER_TOKEN` still comes from
`wp-config.php` and keeps its name (it is a site's constant, not ours).
`ERDC_DISABLE_LATE_ATOMIC_FLUSH` went with `loop-grid-repeater`.

**The PHP namespace is still `ERDC\`.** Neither phase 01 nor phase 02 lists the rename, and
doing it mid-stream would have buried this diff in 40-odd files of mechanical churn. It is a
self-contained pass whenever you want it: the autoloader strips `__NAMESPACE__`
dynamically, so renaming `ERDC\` → `ACBS\` needs no autoloader change and no file moves.

---

## 09 — Gotchas

- **Never change `field_6a0a99f262aaf`.** The `page_sections` field key. Every post that ever saved page sections carries a `_page_sections` postmeta row holding it. Change it and `get_field('page_sections')` returns raw layout names instead of formatted rows — every existing page silently stops rendering. Every *other* key in that file was deliberately regenerated; only this one is pinned.
- **The autoloader is path-derived.** A misplaced file does not error, it never loads.
- **Break a `have_rows()` loop without `reset_rows()` and you corrupt the next one.** See §05.2. This is the single most likely new bug in the render layer.
- **`get_row_index()` renumbers when a row is disabled.** See §05.4.
- **Contributor group priority is inverted on purpose.** `Contributor_Groups::order_by_priority()` sorts highest Order No. first so the *lowest* number is merged last and wins.
- **Site fields are re-keyed on merge.** ACF's runtime field cache is keyed by field key alone, so re-using a site's fields verbatim under our group resolves back to a stale copy whose `parent_layout` points at the site's layout, and ACF silently drops the field. Hence the `_site` suffix and the conditional-logic rewriting with it.
- **Theme templates *replace*; theme styling *layers*.** Deliberate (decision 5), and the one thing most likely to surprise a theme developer. Say it in the theme docs.
- **Keep plugin row CSS specificity flat** — single class selectors, no `!important` — or theme overrides turn into a specificity fight.
- **Footer-enqueued CSS applies after first paint.** Only per-row sheets go in the footer; structure, tokens and Bootstrap stay in the head where no discovery is needed.
- **`assets/css/frontend.css`, `assets/css/drilldown-menu.css` and `assets/js/drilldown-menu.js` are generated.** Editing them is undone by the next build. Sources are `src/css/frontend.sass` and `src/modules/drilldown-menu/frontend.{js,sass}` — the latter two are being deleted. Everything else in `assets/` is hand-written and untouched by the build.
- **`src/css/frontend.sass` is mostly unusable.** The majority of its 395 lines target `.e-con-inner`, `.elementor-grid`, `.e-loop-item`, `.elementor-widget-*`. The padding, background and column *concepts* are worth keeping; the selectors are not. Bootstrap's grid replaces the hand-rolled column maths.
- **`.gitignore` says `demo_sdk_default/`** where it means `demo_sdk_vault/` — a typo. The directory is currently absent, so this is latent rather than live.

---

## 10 — Pending work

### Phases

| # | Phase | Size | State |
|---|---|---|---|
| 00 | Decisions, fork, version control | S | **Done 27/08/2026** |
| 01 | Boot without Elementor: module base, manager guards, `plugins_loaded` bootstrap, `Source_Resolver`, settings page under ACF, drop the ten modules, option cleanup | M | **Done 28/08/2026** |
| 02 | Render layer: `Row`, `Row_Type`, `Row_Registry`, `Renderer`, `Template_Loader`, `Wrapper`, action points, footer assets, 15 stub templates | M | **Done 28/08/2026** |
| 03 | Site kit | M | **Parked** (decision 6) |
| 04 | `structure.sass` against Bootstrap's grid, Bootstrap fallback + handle filter, per-row webpack entries | M | |
| 05 | Fill in the 15 row templates. Three need `Items_Source`; do one early | L | **Next** |
| 06 | Theme integration in `five-starter` — **now blocking a working Elementor-free front end**, see the phase 01 checkpoint — plus PHPStan + PHPCS and the carried-over criticals (`templates/` is already in `SHIP_DIRS`) | M | |

**Checkpoint for 01 — passed 28/08/2026.** Verified by filtering `option_active_plugins` so
Elementor and Elementor Pro never load, then reading the plugin's state back on a real
request. With `class_exists('\Elementor\Plugin') === false`: all six modules load, `acbs/init`
fires (and the `erdc/init` alias with it), `ACBS_*` and the `ERDC_*` aliases are defined,
`page_sections` registers with all 15 layouts, and the settings facade collects all five
registrants into the right order — sections `intro` (20), then disabled-layouts / page-header
/ clipboard (21), then the export button (30) last — with all four options registered
through `register_setting()`.

**The theme, not the plugin, is what breaks without Elementor.**
`themes/five-starter/footer.php:14` calls `elementor_theme_do_location()` unguarded, so the
front end fatals there. That is phase 06's job, and it is the only thing standing between
this install and a genuinely Elementor-free front end.

**Checkpoint for 02 — passed 28/08/2026.** Verified through a temporary mu-plugin calling
`acbs_get_rows()` (theme integration is phase 06, so nothing calls it yet). Every page
matched, wrappers included — the classes below are read through `get_sub_field()` inside the
row's live ACF loop, which is the thing most likely to break:

| Page | Saved rows | Rendered | Notes |
|---|---|---|---|
| 11 Rental Fleet | 3 (`products_archive_feed` last) | **2** | no empty third `<section>`; classes `fl-bg-dark fl-type-full_width_content fl-item fl-p-bottom fl-p-mobile-bottom` |
| 4138 About Us | 7 (`icon_list` at position 3) | **6** | order preserved across the skipped row |
| 12 Enquire | 1 | **1** | |
| 5240, 5264 | 1 each, both orphans | **0** | no output at all |

`Renderer::layouts_on_page(11)` returns all three saved names from `acf_get_metadata()`, so
the orphan is still findable by whoever cleans the data up — which is the point of skipping
rather than deleting.

**Not yet exercised: the `WP_DEBUG` paths.** `wp-config.php` on this install defines
`WP_DEBUG` false, and an mu-plugin runs too late to change that. So the template-cascade
HTML comment, the "no row type registered" notice and the loop-depth warning are all
unverified at runtime.

### Every contributor field group on the dev site is in the trash

Checked 27/08/2026 against `wp_posts`. "Subtext", "Additional Flexible Layouts", "Aspire
Flexible Layouts", the two Flexible Component groups and the Theme Settings component test
are all `post_status = trash`, so **nothing is currently contributing layouts or component
overrides on this site**. That is why `icon_list`, `icon_cards` and `icon_leaders_2` are
orphaned rows rather than working ones. Restore the groups from the trash before concluding
the merge pipeline is broken — `Site_Layouts::merge()` and the contributor rule are untouched
and still correct.

Phase 02 gates everything. Do not start filling in row templates before the `Row` object
and wrapper contract are settled, or all fifteen get rewritten.

### Defects carried over from ERDC

Fix these in the files being rewritten anyway, rather than porting them.

| Item | Where | Severity |
|---|---|---|
| 4 unescaped output sites — wrapper attrs, image section, card sublinks, category list | 4 files | Critical |
| Debug `error_log()` writing to web root, one per row per request on one site | `flexible-layout.php:193`, `module.php:411,519` | Critical |
| `the_sub_field()`'s 2nd arg is `$format_value`, not a post ID — ACF Taxonomy Text never reads from the term | `acf-taxonomy-text.php:56`, `acf-repeater-text.php:88` | High (dies with the module) |
| Taxonomy Image reads out-of-scope `$wp_query` global | `acf-taxonomy-image.php:71` | High (dies with the module) |
| FAQ schema tests `$tags` not `$faq_tags` | *not found* | **The claim does not hold.** `modules/shortcodes/content/fs-fl-faq-schema.php` contained no FAQ schema code at all — the filename and its contents had diverged, and `faq_tags`/`FAQPage` appear nowhere in the repo. Nothing to port. Recover the file from the first commit if the real emitter turns up elsewhere |
| ~~AJAX endpoints check nonce but not capability~~ — **fixed 27/08/2026**: all *three* handlers now go through `Ajax::require_capability()` (`edit_posts`). The N+1 term walk and `numberposts => -1` are still there | `dynamic-taxonomy-fields/ajax.php` | ~~High~~ / Medium |
| Eager ACF registration on `acf/init` forces full field-group load on every request | `page-content.php:1609` | Structural |
| Nothing is translatable — no `languages/`, no `load_plugin_textdomain()`, 95 `'erdc'` calls | repo-wide | Structural — cheap to fix during the rename |
| `Shortcodes\Module::SHORTCODES` is empty; `tags/acf-field-url.php` not registered | modules | Dies with the modules |

### Repo hygiene

- `vendor/` is committed (update checker only) while `composer.json` is gitignored — the inverse of the usual arrangement.
- Version lives in two places: the plugin header and `define('ACBS_VERSION')`. `bin/release.js` fails hard if the pattern doesn't match exactly once, so they cannot drift — it was re-pointed at `ACBS_VERSION` when the constants were renamed.
- Plugin `Description` header reads "Compoennt block system for ACF Pro" — typo, and Gui's to correct.
- `.gitignore`'s `demo_sdk_default/` typo is fixed; it now reads `demo_sdk_vault/`.

---

## 11 — Tooling

### Skills worth reaching for

| Skill | When |
|---|---|
| `wp-plugin-development` | Architecture, hooks, activation, security, release packaging |
| `wp-phpstan` | Highest value here — static analysis is now cheap with Elementor gone |
| `sass-scss` | The structure/row stylesheet split |
| `wp-performance` | Row hydration cost, autoloaded options |
| `wp-wpcli-and-ops` | Safe WP-CLI, db export/import |
| `acf-skill:acf` | Reading and writing ACF data on the live site |
| `code-review` · `simplify` · `security-review` | Review passes over a diff |
| `artifact-design` · `dataviz` | Before writing any artifact or chart |

### MCP servers

| Server | Scope | Use for |
|---|---|---|
| `emcp-erdc-plugin-local` | This project | The local dev site: pages, ACF read/write, WP-CLI dispatch, DB queries. Still useful post-decoupling for everything except the Elementor-specific tools. |
| `Claude_Browser` | Global | Previewing rendered pages, DOM, console |
| `mengram` | Global | Cross-session memory |
| `figma-desktop` · `Figma` | Global | Design context while re-authoring the 15 layouts |

### Starting a session on this repo

Read §03 (decisions) and §05 (ACF loop rules) first, then say which phase in §10 you are on. Confirm whether Elementor is currently active — the answer changes what "working" looks like.

---

*ACF Components Block System · v1.0.0 · forked from ERDC 1.0.36 · Five Creative · 28/08/2026*
