# 06. Row: Accordions/FAQs

**Summary:** Walkthrough of the Accordions/FAQs layout: the repeating Title and Content pairs,
and how it behaves on the front end, one panel open at a time. **Example page:** Training:
Accordions/FAQs (`training-accordions`, draft, four sample FAQ items).

---

Accordions, or FAQs, is one of the simplest layouts we've got.

[ON SCREEN: open Training: Accordions/FAQs]

Inside the Content tab there's one repeater, Accordions. Add a row for each question: Title is
the question itself, Content is the answer, that's it, two fields per item, add as many as you
need.

I've left four questions on the training page as an example, add a row for a fifth or sixth and
you'll see the pattern continue.

Worth knowing for the front end: this is a group. Open one question and any other open question
closes automatically, it's designed as a single accordion rather than a set of independent
toggles, that's what keeps it reading as a clean FAQ list rather than a page that keeps growing as
visitors click through it.

Above the accordion list you've got the usual Intro field for a heading and short lead-in, and
under Other Settings, the usual background colour, section ID, and padding. No Grid & Display tab
here, an accordion list doesn't sit in a grid.

That's genuinely the whole layout: a title, an answer, repeat. If you've got a page of frequently
asked questions to get up, or a service explainer, this is almost always the layout you reach for.
