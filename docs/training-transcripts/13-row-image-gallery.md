# 13. Row: Image Gallery

**Summary:** Walkthrough of the Image Gallery layout: the images field, aspect ratio, its
columns-only Grid & Display tab, and what it becomes on the front end, a carousel with a
lightbox. **Example page:** Training: Image Gallery (`training-image-gallery`, draft, six sample
photos).

---

Image Gallery is a scrolling strip of photos that visitors can click through to view larger, a
carousel with a lightbox, and it's one of the more visual layouts we've got.

[ON SCREEN: open Training: Image Gallery]

In Content, Images is a standard gallery field, add as many photos as you like, order matters,
that's the order they'll scroll in. Below that, Aspect Ratio, a required text field, written as
width to height, the default is 1.42 to 1, which matches our usual crop, but you could enter 1 to
1 for square, or 16 to 9 for a wider, more cinematic strip. Every image in the gallery is cropped
to whatever ratio you set here, so it's worth picking before you upload, rather than after.

Grid & Display only gives you columns on this layout, desktop, tablet, mobile, no alignment or
card option, a gallery doesn't need either. On the training page it's three columns on desktop,
dropping to one on mobile, and I've left six photos in as an example.

On the front end, this becomes a swipeable carousel, and clicking any image opens it full-screen
with next and previous controls, that behaviour is built in, there's nothing extra to configure
for it.

That's the layout: upload your photos, pick your ratio, set your columns, and the carousel and
lightbox take care of themselves.
