# Homepage Spec — Post-Hero Sections

## Scope
Covers everything **below your existing header** and **below your existing hero**, down through the sections shown in the reference screenshots. Layout/behavior only — all copy below is original, written fresh for an Iceland beach resort, not sourced from any third-party site.

## Section Order
1. **Search / Filter Bar** — placed as a floating bar straddling the hero and the next section (overlaps both, per your request)
2. **Explore Stays carousel** — horizontal scroll of stay-type cards
3. **Book With Confidence** — 3-column trust/feature cards
4. **Unlock Your Stay** — full-width statement + photo strip carousel
5. **Loyalty / Partners crosssell** — two-column text + image
6. **Footer banner strip** — dark full-width closing statement + CTA link

---

## 1. Search / Filter Bar
**Placement:** Positioned as a floating/overlapping bar — bottom edge sits on the hero, top edge intrudes into section 2's background. On mobile, this collapses to a pill-shaped summary at the top of scroll (tap to expand full bar).

**Anatomy (desktop, left → right):**
- Destination input — icon + label "Where to? (Required)" + placeholder "Search a beach, region, or hotel"
- Check-in date picker — icon + label
- Check-out date picker — icon + label
- Occupancy selector — icon + "1 room, 1 guest" (opens stepper popover)
- "Special rates" link/button (secondary style)
- "Find a stay" primary button (pill, high-contrast)

**States:** default, hover (each field), focus-visible (each field), filled (shows entered value not placeholder), error (e.g. missing destination on submit), loading (searching)

**Mobile:** Same fields stacked in a bottom-sheet or modal triggered by the collapsed pill; must be reachable via keyboard/screen reader in the same tab order as desktop.

---

## 2. Explore Stays Carousel
**Pattern:** Horizontal card carousel with prev/next arrow controls (circular, bottom-right of section). 3–4 cards visible on desktop, 1 with peek on mobile.

**Card anatomy:** Full-bleed photo, dropdown-style label overlaid bottom-left (chevron icon implies expandable), rounded-corner container.

**Original copy (yours, Iceland-beach-resort themed):**
- Card 1: **"Glacier Bay Suites"** — oceanview rooms facing the black sand coastline
- Card 2: **"The Hot Spring Collection"** — rooms with private geothermal soaking pools
- Card 3: **"Northern Lights Lofts"** — top-floor rooms with retractable skylight roofs
- Card 4: **"Fjord Family Villas"** — multi-room villas for groups

**Section intro copy (left-aligned text block before/beside carousel):**
> **New ways to stay by the sea**
> We're expanding beyond the standard room — from geothermal suites to skylight lofts built for aurora season, there's a new way to experience the coast every year.
> [Explore new stays →]

**States:** card default/hover (lift + shadow), arrow controls default/hover/focus-visible/disabled (at start/end), swipe/drag on touch.

---

## 3. Book With Confidence (3-card row)
**Anatomy:** Section heading + one-line supporting copy, then 3 equal-width bordered cards, each: icon, bold title, 2–3 line description.

**Original copy:**
> **Book with confidence**
> Book directly with us for the best rate, flexible changes, and a stay you can manage start to finish — no third parties involved.

- **Card 1 — "The best rate, guaranteed"** — icon: price tag. *Book directly on our site and you'll always get our lowest rate, with free changes up to 48 hours before arrival.*
- **Card 2 — "Member perks"** — icon: star. *Direct bookings earn credit toward future stays and unlock seasonal upgrades like late checkout and spa access.*
- **Card 3 — "Manage it all in one place"** — icon: checklist/phone. *Handle check-in, room requests, and check-out from your confirmation email — no app required.*

**States per card:** default, hover (subtle elevate), focus-visible on any interactive element inside.

---

## 4. Unlock Your Stay (statement + photo strip)
**Anatomy:** Full-width background photo (aerial/wide landscape), left-aligned large heading + supporting paragraph over a soft gradient scrim, with a horizontal photo-strip carousel docked at the bottom edge (partially overlapping into next section on desktop).

**Original copy:**
> **Unlock the coast, every season**
> One property, four coastlines of experience — from summer's midnight sun to winter's aurora skies. However you stay, there's a season here worth planning around.

**Photo strip cards below (4 visible desktop, swipeable mobile), each with its own mini-heading + 2–3 line description + CTA button:**
- **"Geothermal stays"** — *Our signature soaking pools are steps from every room in this wing — the perfect way to end a day on the coast.* → [Explore geothermal rooms]
- **"Coastal stays"** — *Whether you want total quiet or an active base for hikes and boat tours, our coastal rooms flex to your pace.* → [Explore coastal rooms]
- **"Aurora stays"** — *Skylight ceilings, blackout curtains on demand, and a wake-up call the moment the lights appear overhead.* → [Explore aurora rooms]
- **"Family stays"** — *Connecting rooms, a kids' pool deck, and gear rental for the whole group.* → [Explore family stays]

**States:** heading/paragraph static; photo-strip cards get hover/focus states same as section 2; arrow nav same pattern as section 2.

---

## 5. Loyalty / Partners Crosssell (two-column)
**Anatomy:** Text column (left, desktop) + single large photo (right), reversed stack on mobile (photo first or text first — match your brand's existing pattern elsewhere on the page for consistency).

**Original copy:**
> **More ways to earn from your stay**
> Earn credit on transfers, tours, and dining with our local partners — from glacier hikes to harborside restaurants — and put it toward your next trip.
> [See our partners →]

**States:** CTA link/button default, hover, focus-visible.

---

## 6. Footer Banner Strip
**Anatomy:** Full-width dark background band, centered or left-aligned single line of bold statement text + inline link, sits just above the site footer.

**Original copy:**
> **Great stays start with great people.** Our team's care for every guest is what keeps people coming back season after season. [Plan your next stay.]

**States:** inline link default, hover, focus-visible.

---

## Shared Component Notes
- All carousels (sections 2 and 4): must expose prev/next controls to keyboard and screen readers, not rely on swipe alone.
- All CTA buttons: same primary/secondary token pairing as your existing header/hero for visual continuity.
- Overlapping search bar (section 1): verify z-index and contrast — text/icons inside the bar must stay legible against both the hero image behind it and the section-2 background it overlaps into.
- Long destination names, room names, and card copy must wrap, not truncate with ellipsis, consistent with your rooms-page spec.

## Responsive Behavior
- Search bar: floating/overlapping on desktop → collapses to tappable summary pill on mobile, expands to full-screen or bottom-sheet form
- Carousels: peek-next-card pattern on mobile (partial card visible at right edge signals scrollability)
- Two-column crosssell (section 5): stacks vertically on mobile
- Footer banner: text reflows to 2–3 lines on mobile, link stays inline (not orphaned on its own line if avoidable)

## QA Checklist
- [ ] Search bar legible and usable at the hero/section-2 seam on all breakpoints
- [ ] All carousels keyboard-navigable (arrow buttons focusable, tabbable)
- [ ] Card hover/focus states consistent across sections 2 and 4
- [ ] All copy is original resort copy — no placeholder or reference-site text shipped
- [ ] CTA button styles match existing header/hero tokens
- [ ] Two-column crosssell stacks correctly on mobile with no orphaned image
