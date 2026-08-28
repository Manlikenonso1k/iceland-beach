# UI/Animation Redesign Prompt — Iceland Beach Resort

## 🚫 Non-negotiable ground rules (read first, apply to every section below)

- **UI and animation only.** Do not touch business logic, data fetching, API calls, auth flow, form validation, Filament resource classes/schemas, Convex functions, or route definitions.
- **Never hide a working feature behind a redesign.** Every button, link, form field, and admin action that currently works must still be reachable and functional after the redesign. If a component's markup needs restructuring, preserve all `id`s, `name`s, event handlers, and prop contracts unless you flag a rename to me first.
- Work component by component. After each component, tell me what changed and what you touched, so I can sanity-check nothing broke.
- If a visual idea genuinely requires a logic/schema change (e.g. a new field), stop and ask instead of quietly adding it.
- Keep existing responsive breakpoints working — test mobile, tablet, and desktop.
- Respect existing accessibility (focus states, aria labels, contrast) — don't regress it for the sake of style.

---

## 🎨 Design direction (applies everywhere)

Think **calm, cinematic, geothermal-coast Iceland** — not tropical Bali, not corporate Hilton navy-and-gold. Reference feel: black volcanic sand meeting pale turquoise water, steam rising off hot springs, soft overcast light, aurora hints at night.

- **Palette:** deep basalt black / charcoal, glacier white, muted teal-blue, one warm ember/amber accent for CTAs, optional faint aurora-green/violet gradient for hero backgrounds at night mode.
- **Typography:** one elegant serif or high-contrast display font for headings (editorial, resort-magazine feel), clean geometric sans for body/UI.
- **Texture:** glassmorphism panels (frosted, low-opacity, subtle border) over the 3D scene rather than flat cards — like looking through mist.
- **Motion language:** slow, drifting, weighted easing (think GSAP `power2.out` / `expo.out`, 0.8–1.6s durations) — nothing snappy or bouncy. This is a "relax the user's nervous system" site, not a SaaS dashboard.

---

## 1. Home Page

**Structural reference:** Hilton.com's home layout — sticky header with search/booking bar, full-bleed hero, horizontally-scrolling "stay type" card rail, offer cards, brand/amenity grid, footer. Keep that information architecture (it converts well for resort bookings), but reskin every layer.

**Hero (the "crazy 3D" moment):**
- Full-viewport Three.js scene: a layered volumetric **cloud/fog field** drifting slowly across a dusk sky, parallaxing gently with mouse move / device tilt (subtle, max ~15px offset — no motion-sickness-inducing swing).
- Suggest either: (a) instanced sprite clouds with soft alpha-blended textures drifting on a slow noise-driven path, or (b) a raymarched volumetric fog shader if performance budget allows — fall back to (a) on lower-end devices via a simple capability check.
- Underneath the clouds: a stylized dark-sand coastline silhouette or the resort's real hero photo, color-graded to match the palette, with a soft light-rays/god-ray effect breaking through the clouds.
- On load: clouds fade/drift in over ~2s, headline text reveals with a soft blur-to-focus + slight upward drift (GSAP), booking search bar glass-panel slides up last.
- Keep performance in mind: cap particle/instance count, use `will-change` sparingly, pause the Three.js render loop when the hero scrolls out of view (IntersectionObserver), and provide a static image fallback for `prefers-reduced-motion`.

**Header:**
- Transparent/glass over the hero, solidifying (blur + background fill) on scroll via a scroll-triggered class toggle (Framer Motion `useScroll` or GSAP ScrollTrigger) — don't rebuild it from scratch, just restyle + add the scroll transition.
- Logo, nav links, Sign In / Book Now CTA — keep exact same links/handlers, just re-skin.

**Stay-type / room rail:** horizontally scrollable cards (like Hilton's "Unlock your staying power" rail) — each card gets a hover lift + slow image zoom (scale 1.0 → 1.05 on hover, 0.6s ease-out), frosted glass caption panel over the image bottom third.

**Offers / amenities grid:** convert to soft glass cards on a dark section background, staggered fade-up-on-scroll reveal (Framer Motion `whileInView`, staggerChildren ~0.08s).

**Footer:** simplify visually to match the dark/glacier palette; keep every link.

**Micro-interactions throughout:** buttons get a soft glow/scale on hover (no hard shadows), section transitions use gentle fade+drift rather than hard cuts, scroll-triggered reveals throughout (GSAP ScrollTrigger or Framer `whileInView`).

---

## 2. Login Page (Filament/Laravel)

- Filament's auth pages are Blade + Livewire under a `filament-panels::` layout — **only override the rendered view/theme (published/overridden auth view or a custom theme CSS + a thin Blade wrapper), never the `Login` Livewire component's form logic, validation rules, or submit handlers.**
- Visual direction: split-screen — one side a cropped, looping ambient loop or static shot of the cloud/coast scene (can reuse a lighter/cheaper version of the hero, e.g. a pre-rendered video or a lightweight CSS/SVG cloud drift instead of full Three.js, since this page needs to load fast) — other side the glass-panel login card (email/password fields, remember me, submit) in the same frosted style as the home page cards.
- Keep every Filament form field, its `wire:model` bindings, validation error rendering, and the submit button's Livewire action completely intact — you're restyling the wrapper markup and CSS classes, not the form internals.
- Add subtle input focus animations (border glow, label float) and a soft loading state on submit (spinner/pulse on the button) — purely cosmetic, layered on top of Filament's existing loading state hooks (e.g. `wire:loading`), not replacing them.

---

## 3. Filament Admin Dashboard

- Use Filament's official theming approach: a custom theme CSS file compiled via their Tailwind/Vite pipeline, plus panel-level color/branding config (`->colors()`, `->font()`, `->brandLogo()`, etc.) in the Panel provider. **Do not modify Resource classes, table columns, form schemas, actions, or widgets' data logic** — only their rendered appearance via `->extraAttributes()`, custom view overrides for layout wrappers, or theme CSS.
- Visual direction: keep it calmer and more legible than the marketing site (admins need to scan data fast) — dark or light mode toggle in the resort palette, glass panels for stat widgets/cards, subtle hover states on table rows, soft skeleton-loading shimmer instead of default spinners.
- Widgets (stats, charts): restyle the chart color scheme to the teal/amber/glacier palette; keep the underlying chart data/query untouched.
- Sidebar/nav: restyle active-state indicator as a soft glow/left-border accent instead of a hard block fill; keep every route intact.
- Keep motion minimal and fast here (150–250ms) — admin UI should feel responsive, not dreamy; save the slow cinematic easing for the public-facing home page.

---

## How to use this

Paste **Ground rules + Design direction** at the top of every session, then paste the relevant numbered section for whichever page you're working on that session. Doing them in separate passes (rather than all three in one giant prompt) will give Claude Code more room to actually look at your existing component tree before touching it.