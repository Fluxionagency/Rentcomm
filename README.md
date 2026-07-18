# Rentcom — Production Website & Admin Portal

Production implementation of the Rentcom digital platform (Lagos rental
intelligence + Fadayee workforce-housing investment brand), built from the
high-fidelity design handoff in [`design/`](design/README.md).

Stack: **static HTML/CSS/JS + PHP 8 + MySQL** — sized for Qserver/cPanel-style
shared hosting (no Node process, no build step).

## Repository layout

```
design/            The original design handoff (prototype .dc.html files + spec) — reference only, not deployed
public_html/       Everything that gets uploaded to the web root
  index.html         Homepage: Investor/Realtor toggle + Portfolio list/detail (client-side views)
  report.html        The Lagos Rent Report 2026 landing page
  investor.html      Fadayee investor journey
  realtor.html       Realtor campaign hub → gated Marketing Asset Portal
  admin/             Admin Portal (PHP, session-auth): leads, dispatch tracker, asset manager
  api/               Form endpoints (PHP → MySQL + email notification)
  assets/            CSS, JS, images
  downloads/         Marketing assets uploaded via the Admin Portal (gitignored)
schema.sql         MySQL schema + seed (assets + initial admin user)
seed-demo.sql      Optional sample data for previewing the Admin Portal
```

## Deploying to Qserver / cPanel

1. **Database** — In cPanel, create a MySQL database and user, grant all
   privileges, then run `schema.sql` against it (phpMyAdmin → SQL tab).
2. **Config** — Copy `public_html/api/config.sample.php` to
   `public_html/api/config.php` and fill in the DB credentials, the team
   notification email, and a from-address on your domain.
3. **Upload** — Upload the *contents* of `public_html/` into your hosting
   web root (usually also called `public_html`).
4. **Permissions** — Make `downloads/` writable by PHP (755, or 775 if needed)
   so the Admin Portal's Asset Manager can replace files.
5. **Admin login** — Visit `/admin/`, sign in with `team@rentcom.com` /
   `RentcomAdmin2026!`, and **change the password immediately** (see the note
   at the bottom of `schema.sql`).
6. **Real assets** — Upload the brochure, social pack, walkthrough video and
   price list via Admin → Asset Manager, and replace the two stand-in images in
   `assets/img/` (`lagos-rent-report-cover.jpg`, `falcon-house.jpg`) with the
   real campaign photos.

## What's wired up

- **All four lead forms** (report download ×2, investor enquiry ×2 sources,
  realtor registration) POST to `api/*.php`, validate + honeypot-check, insert
  into MySQL, email the team, and swap to the inline "Thank You!" state — no
  page reloads, matching the design spec.
- **Realtor registration** immediately unlocks the Marketing Asset Portal
  (explicit product decision — no approval step). The unlock persists via
  `localStorage`, and a dispatch-tracker row is created automatically.
- **Admin Portal** uses real server-side session auth (bcrypt password, CSRF
  tokens, login throttling). Overview stats are computed live; Investors
  status pills and the Dispatch tracker are editable inline; the Asset Manager
  replaces files in `downloads/` instantly.
- **Responsive** — fluid CSS only (`clamp()`, `auto-fit` grids, `flex-wrap`),
  no media-query breakpoints, per the design spec. Admin tables scroll
  horizontally on narrow screens.

## Still to do before launch

- Replace the two stand-in images in `assets/img/` with the real photos.
- Upload the four real marketing asset files (Asset Manager or FTP).
- Point the "Schedule a Visit" WhatsApp link in `realtor.html` at the real
  number (currently a placeholder `wa.me` URL).
- Optionally: email the report PDF to leads automatically (marked `TODO` in
  `api/report-download.php`).
- Real photos for the remaining placeholder tiles (LWFHD portfolio, Fadayee
  site, groundbreaking site) when available.
