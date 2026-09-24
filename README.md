# Rentcom — Production Website & Admin Portal

Production implementation of the Rentcom digital platform (Lagos rental
intelligence + Fadayee workforce-housing investment brand), built from the
high-fidelity design handoff in [`design/`](design/README.md).

**Two deployment targets share this repo:**

- **Qserver / cPanel** (the original, full-featured target) — static
  HTML/CSS/JS + PHP 8 + MySQL. Lead forms write to MySQL and email the team;
  the Admin Portal (login, investor/realtor directories, dispatch tracker,
  asset manager) is fully working. See "Deploying to Qserver / cPanel" below.
- **Vercel** (Phase 1 — added for a fast, credential-free "get it live now"
  path) — the exact same static HTML/CSS/JS, but the lead forms hit Node
  serverless functions in `/api/*.js` instead of the PHP ones, which email
  the team via [Resend](https://resend.com) and stop there. **There is no
  database and no Admin Portal on Vercel yet** — see "Deploying to Vercel"
  below for what that means in practice and what Phase 2 adds.

Both targets use the identical HTML/CSS/JS in `public_html/` — the forms call
extension-less endpoints (`api/investor-enquiry`, not `api/investor-enquiry.php`)
that resolve differently per host: Apache's `.htaccess` rewrite maps them to
the `.php` files on Qserver, while Vercel serves `/api/*.js` at that same path
natively.

## Repository layout

```
design/            The original design handoff (prototype .dc.html files + spec) — reference only, not deployed
public_html/       Everything that gets uploaded to the Qserver web root, and (via vercel.json) served as-is on Vercel
  index.html         Homepage: Investor/Realtor toggle + Portfolio list/detail (client-side views)
  report.html        The Lagos Rent Report 2026 landing page
  investor.html      Fadayee investor journey
  realtor.html       Realtor campaign hub → gated Marketing Asset Portal
  workforce-apartments.html  The Workforce Apartments property page (placeholder figures — see below)
  admin/             Admin Portal (PHP, session-auth) — Qserver only, not available on Vercel yet
  api/               Form endpoints, PHP version (PHP → MySQL + email notification) — Qserver target
  assets/            CSS, JS, images
  downloads/         Marketing assets uploaded via the Admin Portal (gitignored)
api/               Form endpoints, Node version (validate + email via Resend, no DB yet) — Vercel target
vercel.json        Tells Vercel the static site lives in public_html/
package.json       Minimal project manifest for Vercel (no dependencies — uses global fetch)
schema.sql         MySQL schema + seed (assets + initial admin user) — Qserver target
seed-demo.sql      Optional sample data for previewing the Admin Portal — Qserver target
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

## Deploying to Vercel (Phase 1 — fast path, forms-to-email only)

This gets the site live in minutes using only free, self-service accounts —
no FTP or cPanel credentials needed, and nothing here touches the Qserver
setup above.

1. **Push this repo to GitHub** (if it isn't already) and import it in the
   [Vercel dashboard](https://vercel.com/new) — pick "Other" as the framework
   preset; `vercel.json` handles the rest. Every `git push` to your default
   branch auto-deploys from then on.
2. **Create a [Resend](https://resend.com) account** (free tier) and generate
   an API key under *API Keys*.
3. In the Vercel project → **Settings → Environment Variables**, add:
   - `RESEND_API_KEY` — the key from step 2
   - `NOTIFY_EMAIL` — where lead notifications should land (e.g. your team inbox)
   - `MAIL_FROM` — optional; defaults to `Rentcom Website <onboarding@resend.dev>`,
     which works immediately but only for testing. To send from your own
     address (e.g. `no-reply@rentcom.com`), verify that domain in Resend
     first, or sends will fail — check the deployment's Function logs if a
     lead notification doesn't arrive.
4. Redeploy (Vercel → Deployments → ⋯ → Redeploy) after adding the env vars
   so the functions pick them up.
5. **Domain** — if you want Vercel to actually serve your live domain instead
   of just a `*.vercel.app` URL, point the domain at Vercel from wherever
   it's registered (Qserver, in this case): either its nameservers, or an A
   record to `76.76.21.21` + a CNAME for `www` to `cname.vercel-dns.com` —
   Vercel's own Domains tab shows the exact values once you add the domain
   there. This only changes where the site is *hosted*; the domain can stay
   registered at Qserver.

**What this does and doesn't do:** all four lead forms validate and email the
team — that part is real and works today. What it does *not* do: nothing is
saved to a database (an email is the only record of a lead — if Resend fails
to deliver, that lead is gone, so keep an eye on Function logs early on), the
`/admin` portal doesn't exist on Vercel, and the report PDF still isn't
auto-attached (same `TODO` as the PHP version). Closing those gaps — a
hosted database plus a Node/Vercel rebuild of the Admin Portal — is Phase 2,
a real follow-up project rather than a quick patch.

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
- `workforce-apartments.html` is entirely placeholder content (unit count,
  pricing, location, amenities, site photo) — swap in the real figures
  before launch.
- If serving the live domain from Vercel long-term: build Phase 2 (hosted
  database + a Node/Vercel Admin Portal) so leads are actually stored and
  the team has a dashboard again, not just an inbox of notification emails.
