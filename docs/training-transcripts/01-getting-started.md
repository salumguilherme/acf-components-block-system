# 01. Getting Started

**Summary:** How to create a page with the Page Builder plugin from scratch: setting the page
template, adding your first row, and the two settings that show up on almost every row, padding
and grid columns. **Example page:** Training: Getting Started (`training-getting-started`,
draft, Page Builder template, a Full Width Image row followed by a Content and Image row).

---

Hey team, in this video I'll show you how we build a page with our Page Builder plugin from
scratch.

[ON SCREEN: Pages, Add New]

Every Page Builder page starts the same way. Create a new page, give it a title, and in the Page
Attributes panel on the right, set the Template to "Page Builder." That one setting is what
hands rendering over to our layout system instead of the normal content editor. You'll notice the
usual content box disappears once you do this, that's expected, we build the page entirely from
rows below.

[ON SCREEN: scroll to the Page Content panel]

Scroll down and you'll find the Page Content panel. This is where you add rows, we call them
"layouts." Click Add Row and you'll see a list: Full Width Image, Accordions, Call to Action,
Stats, and so on. Pick one, and a set of fields appears just for that layout. Stack as many rows
as you like, in any order, and that's your page.

I've built an example for you: Training: Getting Started. It's a Full Width Image hero followed
by a Content and Image section, have a look while you follow along.

Two things come up on almost every row, so let's cover those quickly.

Padding lives under the Other Settings tab, and it controls the empty space above and below a
section: Default, Small, Large, or turned off on the top or bottom only. It's there so two
sections sitting next to each other don't crowd, or don't leave a big gap if you want them close.

Grid is different, it only shows up on layouts that repeat, like Stats or Icon List, and it
controls how many items sit side by side. Because a phone screen can't fit six columns, the
plugin asks for that number three times, once for desktop, once for tablet, once for mobile, so a
six-column row on desktop can drop to two on a phone without you doing anything extra.

That's the whole mental model really: rows, padding, and grid. Everything else we'll cover layout
by layout.
