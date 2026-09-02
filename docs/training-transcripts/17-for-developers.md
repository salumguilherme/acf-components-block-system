# 17. For Developers

**Summary:** Internal video for the Five Creative dev team: how components let a theme add
fields without forking core, the difference between template overrides (replace) and style or
script overrides (layer), the ACF field key copy button, and where to find the deeper reference
material. **Example page:** none, this one uses the existing `/test/` sample page, which already
carries one example of every layout.

---

This one's for the dev team, not for editors, a quick tour of how to extend this plugin from a
theme without touching plugin code.

Three things to know: components, template overrides, and style overrides. They behave
differently, and mixing them up is the easiest way to lose an afternoon.

First, components. Buttons, Intro, Other Settings and Grid & Display aren't separate field
groups, they're shared field sets injected into every layout that opts in, by a class called
Common_Fields. If you want to add a field to every layout, or just to the layouts already on a
site, don't touch the eleven layout definitions, tag a field group with the "Flexible Layout Row"
location rule instead, that's how a site adds fields without forking core. Same-name fields
replace on merge, so a site group can override a component field's label or choices too.

Second, templates versus styles, and this is the one that catches people out. Drop a file at
`theme/acbs/rows/your-layout.php`, and it replaces the plugin's template entirely, nothing from
the plugin's version survives. Styling works the opposite way: drop a CSS file at the matching
path under `css/rows/`, and it layers on top of the plugin's sheet instead, loaded second, so
you're overriding rules, not replacing the whole file. Same logic for row JavaScript, a theme
script layers alongside the plugin's, it doesn't replace it. Get that backwards and you'll either
lose behaviour you needed, or wonder why your override isn't taking effect.

Third, a genuinely useful shortcut day to day: hover any field in the ACF field group editor and
you'll see its field key, click it and it copies to your clipboard. You'll want that constantly,
conditional logic rules target a field's key, never its name, and so does the sub-field selector
when you're scripting a repeater write. Guess the wrong string and the field just silently does
nothing, no error.

For anything past this five-minute version, the CLAUDE.md file at the plugin root is the real
reference, it covers the ACF loop rules, the full hook list, and a section called Traps, genuinely
worth reading, it's every bug that shipped looking correct and did nothing, written down so it
doesn't happen twice. There's also the ACBS Handbook artifact, linked from that same file, for a
narrative version of the same material. And the source lives at
github.com/salumguilherme/acf-components-block-system, that's the repository to point an update
checker at, not the old ERDC repo it was forked from, that one's read-only history now.

Components for fields, templates for markup, styles for layering, and the field key copy button
for anything conditional. That'll get you through most of what you'll touch in here.
