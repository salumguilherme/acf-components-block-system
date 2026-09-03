# 05. Buttons

**Summary:** Overview of the Buttons repeater component, which appears on several layouts:
button text and link, style and outline, and the three icon fields, including uploading a custom
icon. **Example page:** Training: Buttons (`training-buttons`, draft, a Call to Action row with
two contrasting buttons).

---

Buttons show up on several layouts, Call to Action, Content and Image, Full Width Image, Columned
Content, Stats, and they all use the same fields, learn it once here.

I've set up Training: Buttons with two buttons side by side to compare.

[ON SCREEN: open the Buttons repeater]

Button Text and Button Link are your label and destination.

Button Style picks the brand colour, and Outline next to it toggles solid or outlined. On the
training page, the first button's solid Orange, the second's outlined Green, same fields,
different combination.

Then three icon fields, only shown once needed. Icon picks from the bundled set, as you can see,
or None. Choose one and Icon Position appears, before or after the text. Choose Custom instead
and you get Icon SVG, your own upload.

[ON SCREEN: point to the icon fields]

One technical detail: the SVG needs "currentColor" as its fill, that's what lets it change colour
with the button automatically.

And that's the whole component.
