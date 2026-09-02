# 04. Grid & Display

**Summary:** A closer look at the Grid & Display tab: grid columns across desktop, tablet, and
mobile, grid alignment, and the Content Box option that turns items into cards with their own
background colour. **Example page:** Training: Grid & Display (`training-grid-display`, draft,
three rows contrasting the full, columns-only, and card-only versions of this tab: Icon List,
Image Gallery, and Call to Action).

---

This one builds on the tabs overview, we're going deeper into Grid & Display, because it's the
tab people ask about most.

I've set up Training: Grid & Display with three rows on purpose, because between them they show
every version of this tab that exists.

[ON SCREEN: open the Icon List row's Grid & Display tab]

Start with Icon List, it has the full set. Grid Layout (Columns) sets how many items sit across
the row, and you'll notice it's asked three times, once plain, once labelled Tablet, once
labelled Mobile. That's deliberate: six columns might look great on a desktop screen but would be
unreadable squeezed onto a phone, so you choose a smaller number for each breakpoint. Leave
tablet or mobile blank and it just keeps using the desktop number.

Right below that is Grid Alignment: Left, Center, or Right, that's the text alignment inside each
item, not the row itself.

Then Content Box, really a separate feature bolted onto the same tab. Leave it on Seamless and
items sit directly on the section background. Switch it to Card and each item gets its own boxed
background, with a Card BG Colour picker that opens up once you do, our usual palette plus a
custom colour if none of them fit.

Now look at Image Gallery, [ON SCREEN: switch to the Image Gallery row], it only has the column
fields, no alignment, no card, because a gallery is images in a strip, there's nothing to align
or box.

And Call to Action [ON SCREEN: switch to the CTA row] is the opposite, it only has Content Box,
no columns at all, because a call to action is one block, not a repeating grid.

So the rule of thumb: if a layout repeats items, like Stats or Testimonials, you'll see columns.
If it can be boxed as a card, you'll see Content Box. Some layouts get both, some get one, some
get neither, and now you know why.
