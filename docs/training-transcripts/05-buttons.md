# 05. Buttons

**Summary:** Overview of the Buttons repeater component, which appears on several layouts:
button text and link, style and outline, and the three icon fields, including uploading a custom
icon. **Example page:** Training: Buttons (`training-buttons`, draft, a Call to Action row with
two contrasting buttons).

---

Buttons show up on quite a few layouts, Call to Action, Content and Image, Full Width Image,
Columned Content, and Stats, and they all use exactly the same set of fields, so once you know
this one component, you know it everywhere.

I've set up Training: Buttons with a Call to Action row carrying two buttons, so you can compare
them side by side.

[ON SCREEN: open the Buttons repeater on the training page]

Every button starts with Button Text and Button Link, straightforward, that's your label and
your destination: an internal page, an external URL, or an anchor like `#enquire` to jump down
the same page.

Button Style picks the colour, Green, Orange, Yellow, or White, our brand palette. Right next to
it is Outline, a toggle: switched off gives you a solid button, switched on gives you an outlined
one in the same colour. Have a look at the two buttons on the training page, the first is a solid
Orange button, the second is an outlined Green one, same fields, different combination.

Then the icon fields, three of them, and they only appear once you need them. Icon picks from a
bundled set, arrows, a phone, a chevron, an external link icon, a chat bubble, or None if you
don't want one. Choose one and Icon Position appears, Before or After the text. If none of the
bundled icons fit, choose Custom, and a third field appears, Icon SVG, where you upload your own.
It needs to be an SVG file, and here's the one technical detail worth knowing: it has to use
"currentColor" as its fill rather than a fixed colour, that's what lets your uploaded icon change
colour automatically to match the button style and hover state, exactly like the bundled ones do.

That's the whole component: text, link, style, outline, and an optional icon. Learn it once here
and you'll recognise it on every layout that offers buttons.
