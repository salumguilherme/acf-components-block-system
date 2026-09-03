# 17. For Developers

**Summary:** Internal video for the Five Creative dev team: how components let a theme add
fields without forking core, the difference between template overrides (replace) and style or
script overrides (layer), the ACF field key copy button, and where to find the deeper reference
material. **Example page:** none, this one uses the existing `/test/` sample page, which already
carries one example of every layout.

---

Quick one for the dev team: how to extend this plugin from a theme without touching plugin code.

Three things: components, template overrides, and style overrides. They behave differently,
mixing them up costs you an afternoon.

Components: Buttons, Intro, Other Settings and Grid & Display aren't separate field groups,
they're shared field sets injected into every layout that opts in, by Common_Fields. To add a
field to every layout, or the layouts already on a site, tag a field group with the "Flexible
Layout Row" location rule rather than touching the eleven layout definitions. Same-name fields
replace on merge, so a site group can override a component field too.

Templates versus styles is the one that catches people out. A file at
`theme/acbs/rows/your-layout.php` replaces the plugin's template entirely. A CSS file at the
matching path under `css/rows/` layers on top instead, loaded second, same for row JavaScript.
Get that backwards and you'll lose behaviour, or wonder why your override isn't landing.

One genuinely useful shortcut: hover any field in the ACF field group editor and click to copy
its field key. Conditional logic and repeater selectors both target the key, never the name,
guess wrong and the field just silently does nothing.

[ON SCREEN: hover a field to show the key]

Past this, CLAUDE.md at the plugin root is the real reference, including a Traps section worth
reading, every bug that shipped looking correct and did nothing. The ACBS Handbook artifact
linked from that file has the narrative version, and the source lives at
github.com/salumguilherme/acf-components-block-system, that's what an update checker should point
at, not the old ERDC repo it was forked from.

Components for fields, templates for markup, styles for layering, field key copy for anything
conditional.
