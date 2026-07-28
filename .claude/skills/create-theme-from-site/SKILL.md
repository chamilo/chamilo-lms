---
name: create-theme-from-site
description: >
  Create a new Chamilo color theme (colors + logo) derived from an external
  website's actual branding, given a theme name and the site's URL. Mirrors
  exactly what an admin can do manually through /admin/configuration/colors,
  done entirely via the Chamilo REST API. Use when the user wants a new theme
  "based on" / "matching" a given website, or runs /create-theme-from-site.
---

# Create Theme From Site

Given a **name** (e.g. `beeznest2`) and a **source website URL**
(e.g. `https://beeznest.com`), create a new Chamilo `ColorTheme`, activate it
as the platform's default theme, and attach the source site's logo — all
through the same API the admin Vue UI (`/admin/configuration/colors`) uses.
No direct database or filesystem writes: every step below is a real HTTP call
against the running Chamilo instance, which is what keeps this safe and
correct (it reuses tested code paths — entity validation, slug generation,
`colors.css` rendering, image sanitization — instead of reinventing them).

---

## Step 0: Confirm inputs and scope

Ask for whichever of these are missing:
- **Theme name** → becomes the `ColorTheme.title`; the theme's folder `slug`
  is auto-generated from it server-side (Gedmo slug) — never guess or derive
  the slug yourself, always read it back from the API response.
- **Source URL** → the site to derive colors and a logo from.
- **Activate immediately?** Setting a theme "active" makes it the platform's
  default look for every user on this access URL, immediately. Default to
  yes only if the user has clearly asked for it (as in "set it as default
  theme"); otherwise create the theme and ask before activating.

---

## Step 1: Fetch the source site

```bash
curl -sL -A "Mozilla/5.0 (compatible; ChamiloThemeBot/1.0)" "<url>" -o home.html
```

Extract every same-origin `<link rel="stylesheet">` href from `home.html` and
fetch each one. Skip (or heavily discount) generic CDN/vendor stylesheets
(Bootstrap CDN builds, Google Fonts, common carousel/slider libraries) — they
inject well-known framework-default colors (Bootstrap's stock
`#0d6efd`/`#dc3545`/`#198754`/`#ffc107`/`#0dcaf0`/`#6c757d`) that will
otherwise masquerade as "brand colors" if you just count frequency.

---

## Step 2: Extract the real brand colors

1. Collect every `#rrggbb` (and `rgb()`/`rgba()`) color across the fetched
   CSS, tally frequency, and shortlist the top ~20.
2. For each shortlisted color, grep its actual usage context (the selector
   and property it appears in) — don't trust raw frequency alone. A color is
   a genuine brand signal when it shows up on: link hover states, button/CTA
   backgrounds, active nav states, headline accents/underlines. A color is
   framework noise when it only appears in unmodified `.btn-primary`,
   `.btn-success`, etc. rule blocks that match Bootstrap's own stock hex
   values verbatim.
3. Pick:
   - **primary** — the single most consistently-used deliberate accent color
     (CTAs, hovers, active states). This is almost always obvious once vendor
     noise is filtered out.
   - **secondary** — a second deliberate accent if one exists, otherwise the
     site's own custom neutral/text-link color (not a generic Bootstrap gray).
   - **tertiary** — the site's actual body text color (often a dark gray
     close to, but distinct from, `#212529`).
4. **Do not** derive `success`/`info`/`warning`/`danger` from the site. Those
   carry meaning (green=good, red=bad) independent of brand identity — keep
   Chamilo's own defaults for these four unless the user explicitly asks
   otherwise.

---

## Step 3: Find the logo

Check, in this order, stopping at the first hit:
1. JSON-LD `"logo"` field (`<script type="application/ld+json">` containing
   `"@type":"Organization"`).
2. An `<img>` near the header/nav whose `class`, `alt`, or `src` contains
   "logo" or "brand" (case-insensitive) — grep `home.html` for this.
3. Common conventional paths on the same origin: `/logo.svg`, `/logo.png`,
   `/assets/logo.svg`, `/images/logo.png`.
4. Open Graph `og:image` — usually a generic share image, not the nav logo,
   so treat this as a last resort only.
5. `apple-touch-icon` / favicon, as an absolute last resort (a square icon,
   not a wordmark, but better than nothing).

Download whatever you find. If it's SVG, also try to get a PNG rendition
(many sites serve both a `logo.svg` and a responsive PNG version — check
near the same `<img>` tags). If only a raster logo is available and its
dimensions exceed the platform's header limit, resize it:

```bash
identify logo.png   # check WxH
convert logo.png -resize 190x60 logo_header.png   # only if it exceeds 190x60
```

`email_png`/`email_svg` have no dimension limit — use the original there.

---

## Step 4: Map colors to the 23 `ColorTheme` variable keys

Values are **strings of three space-separated integers `"R G B"`** — no
`#`, no commas, no `rgb()` wrapper. `*-gradient` values are computed here as
each RGB channel × 0.75 (rounded down) — a plain, explainable 25% darken.
(Chamilo's own shipped defaults use inconsistent, hand-tuned offsets, some
of them out-of-range/negative — that's tolerated, since nothing validates
these values, but there's no need to replicate that exact formula.)

```json
{
  "title": "<name>",
  "variables": {
    "--color-primary-base": "R G B",
    "--color-primary-gradient": "R*0.75 G*0.75 B*0.75",
    "--color-primary-button-text": "<same as primary-base>",
    "--color-primary-button-alternative-text": "255 255 255",
    "--color-secondary-base": "R G B",
    "--color-secondary-gradient": "R*0.75 G*0.75 B*0.75",
    "--color-secondary-button-text": "255 255 255",
    "--color-tertiary-base": "R G B",
    "--color-tertiary-gradient": "R*0.75 G*0.75 B*0.75",
    "--color-tertiary-button-text": "<same as tertiary-base>",
    "--color-success-base": "119 170 12",
    "--color-success-gradient": "80 128 -43",
    "--color-success-button-text": "255 255 255",
    "--color-info-base": "13 123 253",
    "--color-info-gradient": "-33 83 211",
    "--color-info-button-text": "255 255 255",
    "--color-warning-base": "245 206 1",
    "--color-warning-gradient": "189 151 -65",
    "--color-warning-button-text": "0 0 0",
    "--color-danger-base": "223 59 59",
    "--color-danger-gradient": "180 -13 20",
    "--color-danger-button-text": "255 255 255",
    "--color-form-base": "<same as primary-base>"
  }
}
```

`primary`/`tertiary` `button-text` reuse their own base color (Chamilo's
convention for outline/ghost-style buttons); `secondary-button-text` and
`primary-button-alternative-text` are white (used on solid-color buttons).

---

## Step 5: Authenticate

Every remaining call requires `ROLE_ADMIN`.

```bash
TOKEN=$(curl -s http://<host>/api/authentication_token \
  -X POST -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"<password>"}' \
  | python3 -c "import json,sys; print(json.load(sys.stdin)['token'])")
```

Use `Authorization: Bearer $TOKEN` on every call below.

---

## Step 6: Create the theme

```bash
curl -s http://<host>/api/color_themes \
  -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/ld+json" \
  --data-binary @theme.json
```

Capture the returned `id` and `slug` from the response — you need both for
the next steps. (`ColorTheme` has no `GET`/`GetCollection` operation; to list
existing themes later, `GET /api/access_url_rel_color_themes` instead —
each entry embeds its `colorTheme`.)

Creating the theme does **not** activate it — a link row is created
automatically for the current access URL, but with `active: false`.

---

## Step 7: Activate it (only if the user wants it as default now)

```bash
curl -s http://<host>/api/access_url_rel_color_themes \
  -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d "{\"colorTheme\": \"/api/color_themes/$ID\"}"
```

This deactivates whatever theme was previously active and activates this
one. Idempotent — safe to call again.

---

## Step 8: Upload the logo

```bash
curl -s "http://<host>/themes/$SLUG/logos" \
  -X POST -H "Authorization: Bearer $TOKEN" \
  -F "header_png=@logo_header.png;type=image/png" \
  -F "email_png=@logo_email.png;type=image/png" \
  -F "header_svg=@logo.svg;type=image/svg+xml" \
  -F "email_svg=@logo.svg;type=image/svg+xml"
```

Only include the fields you actually have a file for. This endpoint always
responds `201` even when individual fields are rejected — **inspect the
`results` object**, not just the status code:
- `header_png`/`email_png`: mime must be `image/png` (or `.png` extension),
  must pass `getimagesize`. **`header_png` only** is capped at ≤190×60 —
  resize before uploading if needed (Step 3). `email_png` has no size cap.
- `header_svg`/`email_svg`: mime `image/svg+xml` (or `.svg` extension),
  sanitized server-side. These are also gated by the platform setting
  `editor.enabled_support_svg` — if it's off, expect `"skipped"` for these
  fields. That is not a failure; PNG already covers the logo. Don't try to
  work around it (it's a deliberate anti-XSS control).
- Any other result value (`invalid_mime`, `invalid_image`,
  `invalid_dimensions_header_png`) means that specific field was rejected;
  the other fields still get processed independently.

---

## Step 9: Verify

```bash
curl -s "http://<host>/themes/$SLUG/colors.css"          # the rendered palette
curl -s "http://<host>/themes/$SLUG/logo/header"          # NOT logo/header_png — route is /logo/{header|email}
curl -s "http://<host>/api/access_url_rel_color_themes" -H "Authorization: Bearer $TOKEN"   # confirm exactly one active:true
```

---

## Step 10: Report back

Summarize for the user: theme name/slug, the three chosen colors with a
one-line justification for each (what element on the source site they came
from), where the logo was found, whether it's now active, and a link to
`/admin/configuration/colors` so they can review/tweak it visually.

---

## Gotchas learned the hard way

- **Stale OPcache after a cache:clear.** If API calls suddenly start
  returning a generic 500 error page right after any `cache:clear`/
  `cache:warmup`, a long-running Apache/mod_php worker is very likely still
  serving the previous compiled container from PHP's OPcache. The fix is an
  Apache restart — but if the box hosts other unrelated vhosts, that
  briefly interrupts all of them, so **ask before restarting** rather than
  doing it unilaterally.
- **`var/cache/prod/<subdir>` not writable by the webserver.** If a request
  fails with `"The directory ... is not writable"` in the flash/error
  output, a cache subdirectory was likely created by a different OS user
  (e.g. a CLI session) than the one the webserver runs as. Fix ownership/
  permissions on that specific subdirectory rather than the whole cache tree.
- **Don't guess the slug.** It's derived from the title server-side; always
  read it from the `POST /api/color_themes` response.
