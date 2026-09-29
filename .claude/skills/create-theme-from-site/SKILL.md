---
name: create-theme-from-site
description: >
  Create a new Chamilo color theme (colors + logo) derived from an external
  website's actual branding, given a theme name and the site's URL — or, if
  given a local path to a logo image instead, derived from that logo's own
  dominant colors. Mirrors exactly what an admin can do manually through
  /admin/configuration/colors, done entirely via the Chamilo REST API. Use
  when the user wants a new theme "based on" / "matching" a given website or
  a given logo file, or runs /create-theme-from-site.
---

# Create Theme From Site

Given a **name** (e.g. `beeznest2`) and a **source** — either a **website URL**
(e.g. `https://beeznest.com`) or a **local path to a logo image**
(e.g. `/home/user/logos/acme.svg`) — create a new Chamilo `ColorTheme`,
activate it as the platform's default theme, and attach a logo — all through
the same API the admin Vue UI (`/admin/configuration/colors`) uses. No direct
database or filesystem writes for the theme itself: every step from Step 5
onward is a real HTTP call against the running Chamilo instance, which is
what keeps this safe and correct (it reuses tested code paths — entity
validation, slug generation, `colors.css` rendering, image sanitization —
instead of reinventing them).

---

## Step 0: Confirm inputs and scope, and detect the mode

Ask for whichever of these are missing:
- **Theme name** → becomes the `ColorTheme.title`; the theme's folder `slug`
  is auto-generated from it server-side (Gedmo slug) — never guess or derive
  the slug yourself, always read it back from the API response.
- **Source** → either a website URL, or a local path to a logo image file.
- **Activate immediately?** Setting a theme "active" makes it the platform's
  default look for every user on this access URL, immediately. Default to
  yes only if the user has clearly asked for it (as in "set it as default
  theme"); otherwise create the theme and ask before activating.

**Detect which mode applies** — don't ask the user to specify it, just check
the source string itself:

```bash
if [[ "$SOURCE" =~ ^https?:// ]]; then
    MODE=site
elif [[ -f "$SOURCE" ]] && file --mime-type -b "$SOURCE" | grep -q '^image/'; then
    MODE=logo
else
    echo "Not a URL and not a readable local image file: $SOURCE"
fi
```

- **`site` mode** → follow Steps 1–3 as written below (scrape the site's CSS
  for brand colors, then locate its logo separately).
- **`logo` mode** → skip Steps 1–3 entirely and follow **Step 1–3 (logo
  mode)** just below instead: the given file supplies *both* the colors and
  the logo. Then continue at Step 4 as normal — everything from there on is
  identical regardless of mode.

---

## Step 1–3 (logo mode): derive colors and logo from a local image file

Use this instead of Steps 1–3 when `MODE=logo`.

### Extract the dominant colors

If the file is an **SVG**, try the cheap, exact route first — grep the source
for literal fill colors, which are the real authored brand hex values with no
quantization or anti-aliasing noise to filter out:

```bash
grep -oE '(fill|stop-color)\s*[:=]\s*"?#[0-9A-Fa-f]{6}' logo.svg | grep -oE '#[0-9A-Fa-f]{6}' | sort | uniq -c | sort -rn
```

If that yields nothing usable (no literal hex fills — e.g. the SVG uses
`currentColor` or CSS classes instead), or the file is a raster format
(PNG/JPG/WebP), fall back to a histogram of the rasterized image. Flatten
onto an implausible fill color first (bright magenta) so transparent-
background pixels are unambiguously distinguishable from genuine logo pixels
— including any real white, black, or near-white/black elements the logo
itself actually draws, which must **not** be discarded as "background":

```bash
convert logo.svg -background "#FF00FF" -flatten -resize 150x150 -colors 12 -depth 8 histogram:info:- \
  | sed -E 's/^\s*([0-9]+):.*(#[0-9A-Fa-f]{6}).*/\1 \2/' \
  | grep -vi '#FF00FF' \
  | sort -rn
```

(`convert` rasterizes SVGs automatically via its delegate library — the same
command works unchanged for PNG/JPG input.)

Either way, you now have a frequency-ranked list of `count #RRGGBB` pairs.
Map them the same way Step 2 would, minus the "grep usage context" part
(there's no CSS to check a selector against here — frequency in the logo
itself *is* the signal):
- **primary** — the most frequent genuine color (excluding the magenta
  background marker).
- **secondary** — the next most frequent, clearly distinct color.
- **tertiary** — a third distinct color if the logo has one; if the logo is
  genuinely two-tone, fall back to a dark neutral (e.g. `35 35 35`) rather
  than forcing a weak third color into the role.

Continue to **Step 4** using these three as the primary/secondary base colors
(same downstream harmonization of success/info/warning/danger applies
unchanged).

### Prepare the logo

The given file *is* the logo — there's no site to search. Apply the same
sizing rule as Step 3's site-mode logo handling:

```bash
identify logo.png   # check WxH (skip for SVG — no fixed raster size)
convert logo.png -resize 190x60 logo_header.png   # only if it exceeds 190x60
```

If the source file is an SVG, use it directly for `header_svg`/`email_svg`,
and additionally rasterize a PNG rendition for `header_png`/`email_png`
(`convert logo.svg logo.png`, then resize per above if needed) — Step 8
uploads whichever fields you have.

---

## Step 1 (site mode): Fetch the source site

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

## Step 2 (site mode): Extract the real brand colors

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
4. **Do not fully replace** `success`/`info`/`warning`/`danger` with site
   colors. Those carry meaning (green=good, red=bad) independent of brand
   identity, so their hue family must never change — but they should still
   feel like part of the same palette as `primary`/`secondary` rather than
   looking bolted on. Step 4 covers the harmonization (a small, capped hue
   nudge + a partial saturation blend toward the site's own primary/
   secondary) that keeps each one clearly still "green"/"blue"/"yellow"/
   "red" while relating to the brand.

---

## Step 3 (site mode): Find the logo

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
`#`, no commas, no `rgb()` wrapper. `*-gradient` values are computed as each
RGB channel × 0.75 (rounded down) — a plain, explainable 25% darken, applied
to whatever the final base color is (site-derived for primary/secondary/
tertiary, harmonized for the four semantic colors below). (Chamilo's own
shipped defaults use inconsistent, hand-tuned offsets, some of them
out-of-range/negative — that's tolerated, since nothing validates these
values, but there's no need to replicate that exact formula.)

### 4a. Harmonize the semantic colors (success/info/warning/danger)

Don't hardcode Chamilo's defaults verbatim, and don't replace them with raw
site colors either — nudge them: a small, capped hue rotation toward the
site's own primary/secondary hue, plus a partial blend of saturation, using
basic color-wheel harmony (analogous-hue nudging). This keeps every one of
them unmistakably still green/blue/yellow/red while making the whole palette
read as one family instead of "brand colors + unrelated stock colors bolted
on". Run this script with the primary-base and secondary-base RGB you picked
in Step 2 (before formatting them as `"R G B"` strings):

```bash
cat > /tmp/harmonize_semantic_colors.py << 'EOF'
#!/usr/bin/env python3
"""
Nudge Chamilo's default semantic colors (success/info/warning/danger) toward
a site's derived primary/secondary brand colors, without losing their
semantic hue family (green stays green, red stays red, etc).

Usage: harmonize_semantic_colors.py PR PG PB SR SG SB
  PR PG PB = primary-base RGB (0-255 each)
  SR SG SB = secondary-base RGB (0-255 each)
Prints the 4 harmonized base colors as "R G B" strings.
"""
import colorsys
import math
import sys

DEFAULTS = {
    "success": (119, 170, 12),
    "info": (13, 123, 253),
    "warning": (245, 206, 1),
    "danger": (223, 59, 59),
}

MAX_HUE_SHIFT_DEG = 12   # cap: never enough to cross into a different semantic hue family
SATURATION_BLEND = 0.25  # how much of the site's own saturation profile to mix in
MIN_SATURATION = 0.45    # floor: keep semantic colors from washing out to gray
WEAK_CHROMA_THRESHOLD = 0.12  # below this combined chroma, treat brand hue as undefined


def rgb_to_hsl(rgb):
    r, g, b = rgb
    h, l, s = colorsys.rgb_to_hls(r / 255, g / 255, b / 255)
    return h * 360, s, l


def hsl_to_rgb(h_deg, s, l):
    r, g, b = colorsys.hls_to_rgb((h_deg % 360) / 360, l, s)
    return tuple(max(0, min(255, round(c * 255))) for c in (r, g, b))


def shift_hue_toward(h0_deg, href_deg, max_shift_deg=MAX_HUE_SHIFT_DEG):
    diff = (href_deg - h0_deg + 180) % 360 - 180
    shift = max(-max_shift_deg, min(max_shift_deg, diff))
    return (h0_deg + shift) % 360


def reference_hue_and_saturation(primary_rgb, secondary_rgb):
    hp, sp, _ = rgb_to_hsl(primary_rgb)
    hs, ss, _ = rgb_to_hsl(secondary_rgb)

    # Saturation-weighted circular mean: a near-gray color has no meaningful
    # hue, so it must not skew the reference hue (a naive unweighted average
    # would wrongly pull a vivid primary's hue toward an arbitrary "hue" that
    # colorsys assigns to gray).
    x = sp * math.cos(math.radians(hp)) + ss * math.cos(math.radians(hs))
    y = sp * math.sin(math.radians(hp)) + ss * math.sin(math.radians(hs))
    chroma_strength = math.hypot(x, y)
    href = math.degrees(math.atan2(y, x)) % 360 if chroma_strength >= WEAK_CHROMA_THRESHOLD else None

    sref = (sp + ss) / 2
    return href, sref


def harmonize(primary_rgb, secondary_rgb):
    href, sref = reference_hue_and_saturation(primary_rgb, secondary_rgb)

    out = {}
    for name, rgb in DEFAULTS.items():
        h0, s0, l0 = rgb_to_hsl(rgb)
        new_h = shift_hue_toward(h0, href) if href is not None else h0
        new_s = max(MIN_SATURATION, s0 * (1 - SATURATION_BLEND) + sref * SATURATION_BLEND)
        out[name] = hsl_to_rgb(new_h, new_s, l0)
    return out


if __name__ == "__main__":
    pr, pg, pb, sr, sg, sb = (int(x) for x in sys.argv[1:7])
    result = harmonize((pr, pg, pb), (sr, sg, sb))
    for name, (r, g, b) in result.items():
        print(f"{name} {r} {g} {b}")
EOF
python3 /tmp/harmonize_semantic_colors.py <primary_r> <primary_g> <primary_b> <secondary_r> <secondary_g> <secondary_b>
```

Lightness is deliberately left unchanged (only hue and saturation move), so
the existing button-text contrast pairing (white on success/info/danger,
black on warning) stays valid — no need to recompute those.

### 4b. Assemble the full variable set

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
    "--color-success-base": "<harmonized success, from 4a>",
    "--color-success-gradient": "<harmonized success * 0.75>",
    "--color-success-button-text": "255 255 255",
    "--color-info-base": "<harmonized info, from 4a>",
    "--color-info-gradient": "<harmonized info * 0.75>",
    "--color-info-button-text": "255 255 255",
    "--color-warning-base": "<harmonized warning, from 4a>",
    "--color-warning-gradient": "<harmonized warning * 0.75>",
    "--color-warning-button-text": "0 0 0",
    "--color-danger-base": "<harmonized danger, from 4a>",
    "--color-danger-gradient": "<harmonized danger * 0.75>",
    "--color-danger-button-text": "255 255 255",
    "--color-form-base": "<same as primary-base>"
  }
}
```

`primary`/`tertiary` `button-text` reuse their own base color (Chamilo's
convention for outline/ghost-style buttons); `secondary-button-text` and
`primary-button-alternative-text` are white (used on solid-color buttons).
Semantic `button-text` values are unchanged from Chamilo's defaults (see
4a — lightness never moves, so the existing contrast pairing still holds).

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
one-line justification for each (what element on the source site — or, in
logo mode, which part of the logo image — they came from), a brief note that
success/info/warning/danger were harmonized rather than left as stock
defaults or fully replaced, where the logo came from (site mode: which
lookup in Step 3 found it; logo mode: the given file path), whether it's now
active, and a link to `/admin/configuration/colors` so they can review/tweak
it visually.

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
- **Logo mode's histogram trick relies on `-background "#FF00FF"` being a
  color the real logo doesn't use.** It's an implausible pick for virtually
  any brand, but if the logo genuinely is magenta/pink-based, pick a
  different rare marker color (e.g. `#00FF00`) instead and adjust the
  `grep -vi` filter to match — otherwise you'll silently discard a real
  brand color as if it were background.
- **Photographic or gradient-heavy "logos" aren't real logos.** If the given
  local image looks like a photo or a busy illustration rather than a
  wordmark/icon (many distinct colors, no small set of 2–4 dominant ones),
  the histogram approach will surface arbitrary noise instead of brand
  colors. Flag this to the user rather than silently proceeding — ask them
  to confirm the file is actually meant to be a logo.
- **`convert` needs an SVG rasterization delegate (usually `librsvg`) to
  handle `.svg` input.** If it's missing, `convert logo.svg ...` fails or
  produces a blank raster. The SVG-source `grep` for literal fill colors
  doesn't need it — prefer that path for SVGs when it yields results.
