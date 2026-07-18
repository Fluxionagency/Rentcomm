# Handoff: Rentcom Digital Platform

## Overview
Rentcom is a Lagos rental-intelligence and workforce-housing investment brand. This handoff covers the full public + admin platform: a split Investor/Realtor entry homepage, a portfolio section, a public report-download page, dedicated Investor and Realtor journey pages (the Realtor journey unlocks a marketing asset portal post-registration), and an internal Admin Portal for managing leads, dispatches, and marketing assets.

## About the Design Files
The files in this bundle (`*.dc.html`) are **design references built in a prototyping tool** — they render correctly in a browser and demonstrate exact layout, copy, colors, typography, and click-through behavior, but they are **not production code to copy as-is**. Treat them as a high-fidelity spec. Your task is to **recreate these designs in whatever stack you set up for production** (plain HTML/CSS/JS, or a framework if you prefer) and wire up **real backend behavior** — the prototypes simulate all forms, auth, and data with local component state only (nothing persists, nothing sends email, "login" accepts any click).

`support.js` is prototyping-tool runtime scaffolding the `.dc.html` files depend on to render in-browser — **do not ship it to production**; it has no purpose outside this design tool.

## Fidelity
**High-fidelity.** Colors, type, spacing, and copy shown are final — recreate pixel-for-point exactly as specified below, don't restyle.

## Responsive Behavior
All five pages are **fluid-responsive down to phone widths** — this was built with CSS only (`clamp()` for type/spacing scaling, `grid-template-columns: repeat(auto-fit, minmax(…))` so multi-column sections collapse to one column as width shrinks, and `flex-wrap` on navs/footers), **no media-query breakpoints**. Preserve this approach in production (fluid/container-based scaling) rather than reintroducing fixed breakpoints, so it keeps degrading gracefully at any width. Two exceptions worth flagging for the Admin Portal specifically: the 260px sidebar wraps to a full-width block above the main content on narrow viewports (it does not collapse into a hamburger/drawer — add one if that's preferred for the real product), and the data tables (Investors/Realtors/Dispatch) scroll horizontally on narrow screens rather than reflowing into stacked cards.

## Hosting context
Target is **Qserver shared/cPanel-style web hosting** — plan for static file hosting (HTML/CSS/JS + PHP if Qserver offers it) rather than assuming a Node server process is always running. Concretely this means:
- All forms (report download, investor enquiry, realtor registration) need a real submit target — a PHP script, a form-to-email service, or an API you stand up separately — since there's no client-side backend today.
- The Admin Portal's "login" is a UI mockup only (hardcoded email/password placeholders, no real auth check). Production needs real authentication (session/cookie-based, since this is shared hosting — no serverless auth providers assumed) before this ships.
- Directory/dispatch/asset data in the Admin Portal is hardcoded sample data in the page's JS — production needs a real data store (MySQL is standard on Qserver-style hosting) and CRUD endpoints.

## Screens / Views

### 1. Homepage (`Rentcom Hifi.dc.html` — home state)
**Purpose:** Split entry point. A pill toggle in the nav ("Investor" / "Realtor") swaps the entire hero, stats, CTA, and the lower teaser section's copy + image + link target — no page reload, pure client-state swap.
**Layout:** Full-width sections, all content padded `72px` left/right. Dark (`#111`) hero, white body sections, dark (`#1A1A1A`) teaser section, dark (`#111`) footer.
**Nav** (`72px` tall, `#111` bg, border-bottom `1px solid #1e1e1e`): logo left (see Design Tokens → Logo), right side: "Reports" link → Report Page, "Portfolio" text link (active state: orange text + orange underline) that switches to Portfolio List view in-page, and the Investor/Realtor pill toggle (dark `#1E1E1E` track, `4px` padding/gap, each pill `10px 22px` padding, active pill `#FF6200` bg + white text, inactive `#888` text on transparent).
**Hero section** (`104px 72px 96px` padding): eyebrow (orange, uppercase, `11px`, letter-spacing `.2em`, preceded by a `32px` orange rule), H1 (`Playfair Display` 700, `68px/1.03`), subhead paragraph (`DM Sans` 400, `18px/1.8`, `#666`), CTA button (orange bg `#FF6200`, white text, `19px 40px` padding, `DM Sans` 600 `17px`) linking to the Investor or Realtor Journey page. Right column (`420px` wide, left border `1px solid #282828`, `64px` left padding): two stat blocks (`Playfair Display` 700 `58px` value + uppercase `11px` label) stacked with `40px` gap, then a top-bordered footnote paragraph.
  - Investor copy: eyebrow "Rental Intelligence · Lagos, Nigeria", headline "We understand the Lagos rental market better than anyone.", stats "15+ Lagos Submarkets Tracked" / "₦4.2M Average Annual Rent", CTA "Speak with a Portfolio Manager →" → Investor Journey.
  - Realtor copy: eyebrow "Realtor Partner Program · Fadayee", headline "Sell the Revenue Generating Portfolio.", stats "Aug 30 Groundbreaking Event" / "Free Brochure & Marketing Kit", CTA "Register as a Realtor →" → Realtor Journey.
**Free Report section** (white bg, `96px 72px` padding): two-column grid (`100px` gap). Left: report cover image (`240×310px`, `object-fit:cover`, using `assets/lagos-rent-report-cover.jpg`), title "The Lagos Rent Report 2026" (`Playfair Display` 700 `34px`), subtitle "Revenue Per Asset Index", meta line "July 2026 · 15 Submarkets · PDF" (`#C4BFB7`), "What's Inside" uppercase label + 4 bullet rows (small orange dot + `15px` text: average rent by submarket, YoY rent movement, revenue per asset index, market outlook 2026–27). Right: form card (`#F7F5F0` bg, `56px 48px` padding) with Name / Email / WhatsApp fields (styled as bordered input-look divs in the prototype — build as real `<input>`s) and orange "Download Report →" submit button. On submit, an inline "Thank You!" confirmation state replaces the form (no separate page/route).
**Portfolio teaser section** (`#1A1A1A` bg, `96px 72px` padding): two-column grid, left = eyebrow/H2/paragraph/CTA-with-arrow-swatch, right = photo. Content and photo swap by audience:
  - Investor: eyebrow "Our Portfolio", H2 "Revenue Generating Portfolio", body copy about the partnership-driven serviced-residential portfolio, CTA "Explore Portfolio" (button `#FF6200` + arrow swatch `#E55A00`) → navigates in-page to Portfolio List. Photo: `assets/falcon-house.jpg`.
  - Realtor: eyebrow "Groundbreaking Campaign", H2 "Aug 30th Groundbreaking", body copy about registering for brochure/invite packs/marketing kit, CTA "Register as a Realtor" → Realtor Journey page. Photo: placeholder (no asset yet — labelled "Groundbreaking Site Photo").
**Footer** (all pages, `#111` bg, `36px 72px`, top border `1px solid #1C1C1C`): logo left, "© 2026 Rentcom · Lagos, Nigeria" right (`#555`, `13px`).

### 2. Portfolio List (`Rentcom Hifi.dc.html` — list state, reached via "Portfolio" nav or `#portfolio` hash)
**Layout:** Dark header block (`56px 72px 80px`) with "← Back to Homepage" link, eyebrow "Portfolio", H1 "Our Portfolio", intro paragraph. Below, white section (`88px 72px`) with a 2-column grid (`40px` gap) of project cards.
**Card:** `1px solid #E6E2DB` border, photo on top (`280px` tall, real image if the project has one via `assets/`, else a placeholder tile with a circular glyph + "Project Photo" label), then `32px` padding block with project name (`Playfair Display` 700 `26px`), tagline (`15px`, `#666`), and "View Details →" (orange, `14px`). Click anywhere on card → Portfolio Detail for that project.
**Current projects (data-driven — add more by extending the `PROJECTS` array in the page's JS):**
  1. **Revenue Generating Portfolio** (id `lwfhd`) — Lagos, Long-term horizon, Serviced communities. No photo yet (placeholder tile).
  2. **Falcon Apartment** (id `falcon-house`) — Fadeyi, Lagos. Long-term horizon, Serviced. Real photo at `assets/falcon-house.jpg`. Description: rental income asset in Fadeyi comprising 3 studio units (₦114M) and 2 one-bedroom apartments (₦116M); building amenities — elevator, central power (generator), borehole water, WiFi connectivity, CCTV surveillance, 24/7 security personnel, on-site management, coffee pod common area.

### 3. Portfolio Detail (`Rentcom Hifi.dc.html` — detail state)
**Layout:** Dark header (back link, eyebrow, project name as H1, tagline). White section (`88px 72px`), 2-column grid: left column (flex, `420px` fixed right column) has the photo (`460px` tall, real image or placeholder as above), "About This Portfolio" H2, description paragraph, then a 3-column stat strip (top border `1px solid #ECEAE5`, each cell `28px` bold value + `11px` uppercase label — Location / Investment Horizon / Communities, divided by `1px solid #ECEAE5` verticals).
Right column (`#F7F5F0` bg, `48px 40px` padding): "Interested in this Portfolio?" enquiry form (Name/Email/WhatsApp + "I'm Interested →" button), a divider, then a small cross-promo card linking to the free report. Submitting the form swaps to an inline "Thank You!" confirmation (no page nav).

### 4. Report Page (`Rentcom Report Page.dc.html`)
**Purpose:** Standalone shareable landing page for the free report (linked from every page's nav as "Reports").
**Layout:** Same nav pattern (logo left → Homepage, "Reports"/"Portfolio" links right, with "Reports" shown active/orange here). Hero (dark, `56px 72px 88px`): "← Back to Homepage" link, eyebrow, H1 "The Lagos Rent Report 2026", intro paragraph, and the report cover image (`assets/lagos-rent-report-cover.jpg`, `100%` width × `340px` tall, `object-fit:cover`) below the copy. A "What's Inside" section follows (white bg), then the same Name/Email/WhatsApp download form pattern with inline "Thank You" confirmation on submit.

### 5. Investor Journey (`Rentcom Investor Journey.dc.html`)
**Purpose:** Corporate/yield-focused landing page for the Fadayee (LWFHD) project, for prospective investors.
**Nav** (`80px` tall, `#111` bg): logo, "Reports" link, active "Fadayee Investment" label, and an "Realtor Partner? →" pill link (bordered, `#555` text) over to the Realtor Journey.
**Hero** (`#111` bg, `110px 80px 100px`): eyebrow "Fadayee · Revenue Generating Portfolio", H1 "Own workforce housing built for yield.", body paragraph, orange CTA "Speak with a Portfolio Manager →" (scrolls/links to the enquiry form). Right column (`460px`, left-bordered): "Lagos" / Project Location and "Long-term" / Investment Horizon stat blocks, plus a footnote on the 15+ submarkets of backing data.
**Why Fadayee** (white, `100px 80px`): 3-column grid, each a heading + paragraph — "Data-led site selection", "Serviced, managed communities", "Partnership-driven structure".
**Project photo + details** (white, `0 80px 100px`): 2-column grid — left: photo placeholder (`420px` tall, labelled "Fadayee Site Photo" — no asset yet) + "About This Portfolio" H2 + full LWFHD description paragraph. Right (`#F7F5F0` card): "Talk to a Portfolio Manager" form (Name/Email/WhatsApp + "Request a Call →"), inline "Thank You!" confirmation on submit.
**Footer:** standard.

### 6. Realtor Journey (`Rentcom Realtor Journey.dc.html`)
**Purpose:** Two-phase experience — a pre-registration campaign hub, then (after registering) a gated Marketing Asset Portal. Toggled entirely by client-side state (`showPortal`); no route change.
**Nav:** logo, "Reports" link, "Realtor Partner Program" label, "Investor? →" pill link to the Investor Journey.
**Phase 1 — Campaign Hub** (`showCampaign` true by default):
- Hero (dark gradient `#161200→#0D0D0D`, `96px 80px 72px`): eyebrow "Fadayee Groundbreaking · August 30, 2026", H1 "Sell Fadayee. Earn big.", intro paragraph, orange CTA "Register as a Realtor →" that smooth-scrolls down to the registration form (implemented via `getBoundingClientRect` + `window.scrollTo`, **not** `scrollIntoView`, to avoid disrupting host page scroll — preserve this approach in production). Right: a dark event card with "Aug 30" date, "Fadayee Site · Lagos, Nigeria", and a note about invite-pack priority for registered realtors.
- "What You Get" section (`#111` bg): 3-column cards — Fadayee Brochure (download icon), Physical Invite Packs (mail icon), Marketing Kit Access (grid icon) — each with a `44px` circular icon badge, heading, and description.
- Registration form section (`#0D0D0D` bg): centered card (`620px` max-width, `#161616` bg, `1px solid #262626` border) — Name/Email/WhatsApp fields + "Register & Unlock Marketing Kit →" button. On submit (`register()` handler), state flips to `showPortal: true` and the page scrolls to top.
**Phase 2 — Marketing Asset Portal** (`showPortal` true, gated — only reachable after registering, per design decision that the kit unlocks immediately post-registration):
- Success banner (green-tinted `#132313` bg, checkmark badge): "You're registered! Your invite pack ships before August 30th…"
- H1 "Marketing Asset Portal" + intro.
- 2×2 grid of downloadable asset cards (Fadayee Brochure PDF, Social Media Pack ZIP, Site Walkthrough Video MP4, Unit Price List PDF) — each a file-type swatch + name + description + "Download →" link.
- "Book a Site Visit" callout bar (dark gold `#1A1600` bg) with a "Schedule a Visit →" button.
**Footer:** standard.

### 7. Admin Portal (`Rentcom Admin Portal.dc.html`)
**Purpose:** Internal tool for the Rentcom team to manage investor/realtor leads, dispatch invite packs/site visits, and swap out marketing assets. **Gate this behind real authentication in production — do not expose publicly.**
**Login screen:** centered card (`420px` wide) on `#111` bg — logo, "Admin Portal" heading, Email/Password fields, "Log In" button. (Prototype accepts any click — build real auth.)
**Dashboard shell:** fixed `260px` dark (`#111`) sidebar + main content area (`#F5F4F1` bg).
  - Sidebar nav items (design decision: **orange text on the dark sidebar for visibility**, active item gets solid orange `#FF6200` pill background with white text): Overview, Investors Directory, Realtors Directory, Invite Dispatch Tracker, Asset Manager. Log Out at the bottom.
  - Top bar (`80px`, white, bottom border): page title (`Playfair Display` 700 `22px`) left, a search input look-alike right (build as a real search field).
  - **Overview:** 4 stat cards (Total Investors, Total Realtors, Invite Packs Pending — orange value, Site Visits Scheduled) computed from the underlying data, then two side-by-side "Recent Investor Leads" / "Recent Realtor Signups" list cards (first 3 rows of each directory).
  - **Investors Directory:** table — Name / Email / WhatsApp / Status, status shown as a colored pill (Contacted = amber, Meeting Scheduled = green, Cold = gray, New = orange-tinted).
  - **Realtors Directory:** table — Name / Email / WhatsApp / Onboarding Date.
  - **Invite Dispatch Tracker:** table — Realtor / Invite Pack status pill (Pending/Dispatched/Delivered) / Site Visit status pill (Not Scheduled/Scheduled/Completed) / Visit Date.
  - **Asset Manager:** dashed drop-zone card ("Upload a New Asset — Drop a file here, or click to browse — replaces the current version on the site instantly") + 2-column grid of existing asset cards (file-type swatch, name, "Updated <date>", "Replace File →").
All sample data (5 investors, 5 realtors, 5 dispatch rows, 4 assets) is currently hardcoded in the page's JS for demonstration — replace with real queries against your data store.

## Interactions & Behavior
- **No full page reloads within a flow**: audience toggle, portfolio browsing, and all form submissions use client-side state, not navigation, except for the explicit cross-page links listed above (Reports, Portfolio↔Home hash, Investor↔Realtor journey, Homepage↔Journey pages, Admin Portal is a standalone page).
- **All forms** (report download ×2, investor enquiry, realtor registration) replace themselves with an inline "Thank You!" confirmation state on submit — there are intentionally no separate "thank you" pages/routes.
- **Realtor registration** immediately unlocks the Marketing Asset Portal (no manual approval step) — this was an explicit product decision.
- Hover states: links darken from `#FF6200` → `#E55A00`; nav items brighten from `#888`/`#555` to white or orange when active (see per-screen notes above for exact active-state treatment).
- Cross-links: every page's nav includes "Reports" (→ Report Page) and the appropriate reciprocal Investor/Realtor link where relevant.

## State Management
Each `.dc.html` page owns its own local state (a single component-level object) — there is no shared client store across pages today:
- Homepage: `{ view: 'home'|'list'|'detail', selectedId, reportSubmitted, enquirySubmitted, audience: 'investor'|'realtor' }`
- Report Page: `{ submitted }`
- Investor Journey: `{ submitted }`
- Realtor Journey: `{ showPortal }`
- Admin Portal: `{ loggedIn, view: 'overview'|'investors'|'realtors'|'dispatch'|'assets' }`
In production, form submissions should hit real endpoints (persist to your data store / send email) and the Admin Portal's `loggedIn` state should be backed by real server-side session auth rather than client state alone.

## Design Tokens

**Colors**
- Brand accent: `#FF6200` (orange), hover `#E55A00`
- Logo dot: `#FF3C3C` (red)
- Dark backgrounds: `#111` (primary dark/nav/footer), `#0D0D0D` (realtor journey base), `#1A1A1A` / `#1A1600` (secondary panels), `#1E1E1E` (pill track), `#161616` / `#242424` (cards on dark), `#132313` (success banner)
- Dark borders: `#2A2A2A`, `#2E2E2E`, `#282828`, `#222`, `#1E1E1E`, `#1C1C1C`
- Dark text: white `#fff`, body grays `#666`/`#777`/`#888`/`#999`, faint `#555`, disabled/placeholder `#484848`
- Light backgrounds: `#fff`, `#F7F5F0` (form cards), `#F2F0EB` (photo placeholders / stat cards), `#F5F4F1` (admin canvas)
- Light borders: `#E6E2DB`, `#ECEAE5`, `#E2DED7`
- Light text: `#111` (headings), `#333`, `#555`, `#666`, `#999`, placeholder tones `#C4BFB7`/`#BFBBB3`/`#C8C4BC`/`#DDD9D1`/`#D8D4CC`
- Status pills (Admin Portal): amber `bg #FFF3E0 / text #C46A00`, green `bg #E8F5E9 / text #2E7D32`, gray `bg #F0EFEC / text #999`, orange `bg #FDE8DB / text #FF6200`, blue `bg #E3F2FD / text #1565C0`

**Typography**
- Display/headings: **Playfair Display** (weights 400/600/700), serif
- Body/UI: **DM Sans** (weights 400/500/600)
- Logo wordmark: **Nunito** (weight 800)
- All loaded via Google Fonts in each page's `<head>`.
- Scale in use: hero H1 `62–68px`, section H2 `48–52px`, card/detail H2/H3 `22–38px`, body `14–18px`, labels/eyebrows `10–13px` uppercase with `.08–.2em` letter-spacing.

**Spacing**
- Section horizontal padding: `72px` (marketing pages) / `80px` (journey pages) / `40px` (admin content area)
- Section vertical padding: `56–120px` depending on section weight
- Card padding: `24–56px`
- Grid gaps: `14–100px` depending on relationship (tight for icon+label, wide for 2-column layouts)

**Borders / Radius**
- The visual language is intentionally sharp/editorial: **no rounded corners** on cards, buttons, or sections.
- Exceptions: circular elements (logo dot, icon badges, avatar-style thumbnails) use `border-radius: 50%`; status pills use `border-radius: 3px`.

## Assets
- `assets/falcon-house.jpg` — Falcon Apartment building photo, sourced from a client-supplied campaign image. Used on the Portfolio List/Detail card and the Homepage investor-mode portfolio teaser.
- `assets/lagos-rent-report-cover.jpg` — "The Lagos Rent Report 2026" cover photo, client-supplied. Used on the Homepage and Report Page.
- Remaining photo slots still showing placeholder tiles (need real assets before launch): Revenue Generating Portfolio (LWFHD) card/detail photo, Fadayee site photo on the Investor Journey, Groundbreaking site photo on the Homepage realtor-mode teaser.

## Files
- `Rentcom Hifi.dc.html` — Homepage (Investor/Realtor toggle) + Portfolio List + Portfolio Detail
- `Rentcom Report Page.dc.html` — Standalone report landing page
- `Rentcom Investor Journey.dc.html` — Investor-focused Fadayee page
- `Rentcom Realtor Journey.dc.html` — Realtor campaign hub + gated Marketing Asset Portal
- `Rentcom Admin Portal.dc.html` — Internal admin dashboard
- `assets/` — image assets referenced above
- `support.js` — prototyping-tool runtime only; **not needed and not for production use**
