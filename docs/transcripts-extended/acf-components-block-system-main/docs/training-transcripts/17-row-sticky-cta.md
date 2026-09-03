# 17. Row: Sticky CTA

**Summary:** Walkthrough of the Sticky CTA layout: content and buttons, which breakpoints it
shows on, top versus bottom positioning and what each means for how the bar behaves, the four
trigger options, and the display count and period that stop it becoming annoying. **Example
page:** Training: Sticky CTA (`training-sticky-cta`, draft, a bottom bar with a download button,
triggered after thirty seconds, set to always show).

---

Sticky CTA is the newest layout we've got, and it behaves differently to everything else on
this list, it's a floating bar that slides in over the page rather than sitting in the normal
flow of content.

[ON SCREEN: open Training: Sticky CTA]

Content is a heading and short message, the same wysiwyg box as always, plus an optional Image
and the usual Buttons repeater, style, outline, icon, all of it.

A few fields below that are unique to this layout. Display on lets you choose which screen sizes
the bar appears on at all, Desktop, Tablet, Mobile, tick any combination, so you could show a bar
on mobile only if that's where the action matters most. Sticky on picks Top or Bottom: Top fixes
the bar to the very top of the viewport the moment the page loads, Bottom floats it in along the
bottom edge as the visitor scrolls past wherever the row actually sits on the page, worth knowing
because a bottom bar placed near the end of a long page has a lot more room to float than one
placed near the top.

Then the trigger itself, Show Call to action on, four choices: on page load, once the visitor
scrolls a set number of pixels, once a chosen element on the page becomes visible, or after a set
number of seconds. Whichever one you pick, the field below it, Value of X, takes the matching
number, or for "element is visible," a CSS selector for that element. On the training example
it's set to trigger after thirty seconds, giving a visitor time to read the page before the bar
appears.

Display count and Display period work together to stop the bar becoming annoying: set the count
to zero and it shows every time the trigger fires, set it to one or more and it shows that many
times per session or per day, your choice, then stays hidden until that window resets. It's set
to always show on the training example. Visitors can always close the bar themselves too, using
the tab that sits on it.

Two things carry over from the layouts you already know: it's the one layout with no Intro tab,
since its own heading already lives in Content, and Grid & Display only offers Content Alignment
here, no columns, because a floating bar isn't a grid of items, it's a single box. Other
Settings, background, ID, padding, works exactly as it does everywhere else.
