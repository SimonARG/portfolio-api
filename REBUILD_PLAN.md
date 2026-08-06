# Portfolio Rebuild Plan — `simon-dev.com` v2

**Status:** planning complete, session 1 not started
**Written:** 2026-08-06
**Shape:** 10 sessions, each self-contained and under 1M tokens, each ending with the starter prompt for the next.

---

## 0. How to run this plan

### The loop

1. Open a fresh session. Paste the **starter prompt** produced by the previous session (session 1's is at the bottom of §4.1).
2. Read this file first. It is the single source of truth for scope, conventions and acceptance criteria.
3. Do the session's work. Commit atomically as you go (see §2.6).
4. Update the **Progress ledger** (§6) in this file with what actually shipped and anything that deviated.
5. **End by printing the next session's starter prompt**, updated to reflect reality — file paths that changed, decisions that were revised, work that slipped. This is mandatory, not optional.

### Budget guard

Each session has a rough shape sized to land well under 1M tokens. If you cross ~70% of budget with material work outstanding:

- Stop adding scope. Commit what works.
- Write the remainder into §6 as `DEFERRED → session N+1`.
- Emit the handoff prompt with the deferral called out at the top.

Never leave the tree broken at a session boundary. `npm run build` and `php artisan test` must pass before you hand off.

### Standing rules for every session

- **Frontend sessions (5, 6, 7, 9) must invoke the `frontend-design` skill before writing any component.** Not after, not "if it looks off" — before.
- Use Context7 (or web docs if the MCP is unavailable, as it was during planning) to confirm API surface for Nuxt, Laravel, PrimeVue, Tailwind and i18n before writing against them. These moved fast in 2026; do not code from memory.
- Atomic commits, one logical change each (per the standing git instruction in `~/.claude/CLAUDE.md`).
- Every DB mutation goes through a committed migration or seeder. No ad-hoc SQL.
- The legacy site at `~/portfolio` stays live and untouched until session 10. Do not push to its `production` or `gh-pages` branches.

---

## 1. What exists today

### 1.1 Inventory

Two hand-written static pages, vanilla everything, no build step, no tests, no package manager.

| Path | Lines | Role |
|---|---|---|
| `index.html` | 248 | Landing |
| `projects.html` | 582 | Project gallery + 6 popups |
| `css/index.css` | 1683 | The entire stylesheet |
| `js/locale.js` | 68 | Language toggle via `hidden` class + `localStorage` |
| `js/menu.js` | 36 | Side-menu popup triggers |
| `js/popups.js` | 38 | Popup closers + outside-click |
| `js/index.js` | 25 | Hero hover animation, projects nav |
| `js/projects.js` | 94 | Six hand-wired project popups |

Media lives **only** on the `production` / `gh-pages` branches (gitignored on `main`). Pull it with:

```bash
git archive origin/production imgs vids docs | tar -x
```

| Asset | Size | Notes |
|---|---|---|
| `imgs/*.avif` ×5 | 137 KB | Fine |
| `imgs/mixtorrents.jpg` | 247 KB | The only unoptimised thumbnail |
| `imgs/re;noise.avif` | 32 KB | **Semicolon in filename** — URL-encoding hazard |
| `imgs/favicon.png` | 1 KB | 16px PNG only |
| `vids/*.mp4` ×6 | **55 MB** | See 1.4 |
| `docs/CV-{ES,EN}.pdf` | 305 KB | No Japanese CV |

### 1.2 The design DNA — preserve this

Everything in this subsection is **locked**. The palette and the two typefaces are Simón's; the rest of the visual system is what makes the site read as his.

**Palette** — eight custom properties named by brightness rank, used everywhere, never a raw hex:

```css
--1brightest: #fbfafe;   /* display text, icons */
--2:          #e8e5f9;   /* body copy in popups */
--3:          #b6afec;   /* secondary text, borders on hover, closers */
--4:          #7465d9;   /* accent: tech tags, headings, links, footer label */
--5:          #3f2cad;   /* glows, scrollbar thumb */
--6:          #271a63;   /* hover fills, menu alternate rows, glows */
--7:          #150c2d;   /* surfaces: cards, popups, menu, footer */
--8darkest:   #0e0719;   /* page background edges */
```

The page background is a **horizontal** `linear-gradient` on desktop and a **vertical** one on mobile, running `--8darkest → --7 (wide centre band) → --8darkest`. It reads as a soft vertical light shaft behind the content.

**Type**

- Display: **Sofia Sans Extra Condensed**, weight 810 (name) / 730 (page titles), all-caps, `--1brightest`, sitting on a `--7` block.
- UI/body: **Noto Sans** variable, and critically `font-variation-settings: "wdth" 62.5` — the compressed width axis is the signature. Weights 300–600.
- Japanese currently falls back to whatever the OS provides (neither family ships CJK). Fixing this is a session-5 item.

**Motion signatures** — these are the character of the site, keep them recognisable:

1. **The name sweep.** Hovering the name runs a 1.4s diagonal gradient wipe (`120deg`, mostly `--1brightest` with a `--5`/`--4` band) behind the text, growing `background-size` from `0%` to `100%`, while the text itself uses `mix-blend-mode: darken` — so the name inverts as the light passes through it.
2. **The trilingual role stack.** Three lines — `Programador` / `Programmer` / `プログラマー` — all visible at once, each with a small circular ES/EN/JA button beside it that switches the site language. On hover, each line's text slides up out of a clipped 3.3rem box and a different tech list slides in behind it (`PHP HTML JS CSS`, `LARAVEL SQL VUE`, `GIT JAVA PYTHON C++` — three *different* lists, not translations of one). Releases after a 1.6s delay.
3. **The peek menu.** A fixed vertical strip of five tabs on the left edge, rows alternating `--6`/`--7`, showing only their icon. On hover a row slides right by 45% on `cubic-bezier(0.175, 0.885, 0.32, 1.275)` — a slight overshoot — revealing its label. Below 600px it becomes a horizontal strip along the top that slides *down* instead.
4. **Card hover.** Thumbnail scales to 1.1 with `brightness(55%) blur(2px)` while a bordered "VER PROYECTO" button fades in over it.
5. **The arrow tell.** The PROYECTOS button grows 2rem of right padding on hover and an `➔` fades in from off-edge into the gap.
6. **Glow, not shadow.** Every elevated element uses `box-shadow: 0 0 Npx Mpx var(--4|--5|--6)` — a coloured halo, no offset, no blur-down. Borders are 1–2px `--3`/`--4`.
7. **No rounded corners** anywhere except the circular language buttons.

**Layout geometry**

- Landing: everything vertically centred in one `100svh` column — name, three role lines, PROYECTOS button. Menu fixed left, footer band at the bottom (`--7`, `--6` top border, `Email:` in `--4` + address in `--3`, hover adds a `--3` text-shadow).
- Projects: page title in the same sweep treatment, then a 2-column grid of cards (1 column under 600px; the 1303px breakpoint shuffles which projects pair up).
- Project popup: fixed, centred, 95vw. **Landscape** — video 63% left, info 37% right, side by side. **Portrait** — stacked, centred text, larger type. Info column is `h3` project name / `h2` "TECNOLOGÍAS USADAS" / tech chips / dashed divider / `h3` "DESCRIPCIÓN:" / scrolling body / source + demo buttons.

### 1.3 Content inventory

Everything below must survive the rebuild verbatim in all three languages (ES / EN / JA).

- **Profile**: name in three scripts (`SIMÓN P. CHASNOVSKY` / `SIMON P. CHASNOVSKY` / `サイモン・チャスノブスキ`), one long About paragraph ×3, email `simonchasnovsky@gmail.com`.
- **Hero role lines** ×3: `{locale, role label, tech string}` as listed above.
- **Menu** ×5: About, GitHub (→ `github.com/SimonARG`), CV, Socials, LinkedIn (→ `linkedin.com/in/simonpaul99`).
- **Socials** ×6: Instagram, LinkedIn, Facebook, Last.FM, RYM, Letterboxd — each with a label in three languages (JA labels are katakana transliterations) and a URL.
- **Documents** ×2: CV-ES, CV-EN.
- **Projects** ×6, in display order `mpi, mixtorrents, newtab, ambient, blog, portfolio`:

| key | Title | Tech | Repo | Live |
|---|---|---|---|---|
| `mpi` | PDA MPI | Laravel, HTML, CSS, JS, VueJS, TailwindCSS, MySQL | — | — |
| `mixtorrents` | MIXTORRENTS | Laravel, HTML, CSS, JS, Markdown, MySQL | `SimonARG/MixTorrents` | — |
| `newtab` | CHROME NEWTAB | HTML, CSS, JS | `SimonARG/chrome-newtab` | — |
| `ambient` | RE;NOISE | VueJS, HTML, CSS, JS | `SimonARG/re-noise` | `re-noise.simon-dev.com` |
| `blog` | PHP BLOG | PHP, MySQL, HTML, CSS, JS, Markdown | `SimonARG/php-blog` | — |
| `portfolio` | PORTAFOLIOS | HTML, CSS, JS | `SimonARG/portfolio` | — |

Each project has a description in three languages, currently a `<br>`-separated bullet list. These become Markdown in the new model. **The `portfolio` project's own copy will need rewriting in session 2** — it currently says "Built in vanilla HTML, CSS and JavaScript", which stops being true.

### 1.4 Video reality check

This is the single biggest performance problem on the current site.

| File | Codec | Resolution | fps | Bitrate | Duration | Size |
|---|---|---|---|---|---|---|
| `ambiance.mp4` | H.264 | 1364×678 | 60 | 1.62 Mbps | 44.5s | 9.7 MB |
| `blog.mp4` | **HEVC** | 1920×974 | 60 | 0.97 Mbps | **185.5s** | 22.8 MB |
| `mixtorrents.mp4` | H.264 | 1364×692 | 60 | 0.26 Mbps | 46.2s | 2.4 MB |
| `mpi.mp4` | H.264 | 1920×974 | 60 | 0.41 Mbps | 53.1s | 2.8 MB |
| `newtab.mp4` | H.264 | 1920×974 | 60 | 2.79 Mbps | 38.9s | 13.6 MB |
| `portfolio.mp4` | H.264 | 1920×974 | 60 | 1.81 Mbps | 26.7s | 6.1 MB |

Findings:

- **`blog.mp4` is HEVC with Opus audio in an MP4 container.** Firefox and most Chrome-on-desktop configurations cannot decode it. That project's video is almost certainly broken for a large share of visitors right now.
- Everything is 60fps. These are screen recordings; 30fps costs nothing visually and roughly halves the bitrate requirement.
- `newtab` at 2.79 Mbps and `blog` at 3 minutes are both far past what a portfolio loop needs.
- `ambiance.mp4` carries AAC audio and its `<video>` tag deliberately omits `muted` — Re;Noise is an ambient-sound app, so the sound is the point. Preserve that; every other video should be stripped of audio.

Encoders confirmed available in the Windows ffmpeg build at `/mnt/d/Programs/ffmpeg/bin/ffmpeg.exe`: `libsvtav1`, `libvpx-vp9`, `libx264`, `libopus`. There is no ffmpeg inside WSL — call the `.exe` with Windows paths (see the Blu-ray workflow in `~/.claude/CLAUDE.md` for the calling convention).

### 1.5 Defects and gaps to fix on the way through

Carry this list into the rebuild; each item should end up genuinely resolved, not just re-created.

**Correctness**
1. `projects.html:32` — English page title reads `PROYECTS`.
2. `projects.html:333-336` — `</ñ>` closing tags instead of `</li>` in the Re;Noise tech list.
3. `index.html:7` — meta description says `Porfolio`.
4. `</br>` used instead of `<br>` in the About copy.
5. Four of six `<source>` tags declare `type="video/webm"` on `.mp4` files.
6. `css/index.css` references `var(--2brightest)`, which is not defined.
7. `.scroll-of-x { overflow-x: croll; }` — typo.
8. `.min-h-fit { min-width: fit-content; }` — should be `min-height`.
9. `html { line-sizing: normal; }` — not a real property.
10. `.adress` — misspelled class, and the `<address>` element wraps a label that isn't part of the address.
11. `menu.js` throws if any of `.menu-about` / `.menu-cv` / `.menu-socials` is absent, and `popups.js` throws on load if it runs before `menu.js`. The `reactive` parameters threaded through `togglePopup` / `checkAndclosePopup` / `eventTargetRollOver` are dead — passed by value, reassigned, discarded.

**SEO**
12. All three languages sit in the DOM simultaneously with two `hidden`. Crawlers see triplicated content on one URL, diluting every keyword.
13. One URL per page. No per-locale URLs, no `hreflang`, no canonical, no sitemap, no robots.txt, no structured data, no OG/Twitter cards.
14. Projects are popups — six pieces of real content with no addressable URL between them.
15. `index.html` declares `lang="es"`, `projects.html` declares `lang="en-us"`; JS overwrites both on load.

**Performance**
16. 55 MB of video, no `preload="none"`, no `poster`, all six `<video>` elements instantiated on page load.
17. Font Awesome 6.4.0's full CDN stylesheet (~100 KB plus webfonts) for roughly a dozen glyphs.
18. Two render-blocking CDN origins (Google Fonts, cdnjs) before first paint.
19. `mixtorrents.jpg` at 247 KB, unoptimised, next to five AVIFs averaging 27 KB.
20. Asset filenames aren't content-hashed, so a CSS or JS change can be masked by the Cloudflare edge for up to an hour (documented in `CLAUDE.md`).

**Accessibility**
21. `button, input { outline: none }` with no `:focus-visible` replacement — keyboard users get no focus indicator anywhere.
22. `--4` (`#7465d9`) on `--7` (`#150c2d`) is ~3.6:1. That passes for large/bold text but **fails WCAG AA for the small text it's currently used on** (tech chips at ~20px regular). `--3` on `--7` is ~8.3:1. Resolution that keeps the palette intact: reserve `--4` for large text, borders and glows; use `--3` for small text on dark surfaces.
23. Popups are plain `div`s toggled by class — no `role="dialog"`, no focus trap, no Escape handling, no return focus.
24. No `prefers-reduced-motion` handling for the sweep, slides and autoplaying video.
25. Japanese text is not marked `lang="ja"` inside Spanish/English documents.
26. Heading order is inverted in project popups (`h3` project name above `h2` section heading).
27. `alt` text is a single word per thumbnail.

**Maintenance**
28. Four near-identical media-query blocks (600 / 768 / 1158 / 1303) repeat the same twenty rules with a font-size change.
29. Adding a project means hand-editing five places across two files (documented in `CLAUDE.md` §"Adding a project").
30. Above 1303px the root font-size is pinned at 17px, so the layout is unchanged from a 1366px laptop to a 3840px monitor.

---

## 2. Target architecture

### 2.1 The decisions, and why

| Decision | Choice | Reasoning |
|---|---|---|
| Client framework | **Nuxt 4.5** | PrimeVue and Pinia are Vue-only, so Astro would mean Vue islands with awkward cross-island state and PrimeVue theming. Nuxt 3 hit EOL 2026-07-31; 4.5 is current (4.5.1, July 2026). |
| Rendering | **SSR + ISR**, Nitro as a node service behind nginx | Confirmed with Simón. Static-equivalent TTFB after first hit via Redis-backed ISR, but content edits go live without a rebuild — which is what makes the API-client model real at runtime rather than at build time. |
| Backend | **Laravel 13** on PHP 8.4 | Current stable (min PHP 8.3; VPS runs 8.4.16, WSL runs 8.4.13). Matches the harmless-pleasure stack. |
| Database | **PostgreSQL 17** (prod) / 16 (WSL) | Already on the VPS. JSONB is what makes the translation model cheap. |
| Auth | **Sanctum personal access tokens, stateless** | No sessions, no cookies, no CSRF — same convention as harmless-pleasure's `src/lib/axios.ts`. |
| Translations | **JSONB columns + Spatie Translatable** | Matches the house pattern. One row per entity regardless of locale count; adding a language is a data change, not a schema change. |
| Repos | **`portfolio-api` + `portfolio-client`**, siblings of `~/portfolio` | Simón's choice. Two repos means the existing `/opt/scripts/deploy.sh` takes each by URL with no script surgery. |
| API origin | **Same-origin `/api/*`**, proxied by nginx | No extra DNS lookup, no second TLS handshake, no CORS preflight on the critical path. An `api.simon-dev.com` alias can be added later for demo purposes. |
| Project URLs | **Real routes** `/proyectos/[slug]`, rendered as a modal over the grid on client-side navigation | Crawlers and direct visitors get a full standalone page; in-session navigation keeps the popup feel. Fixes defect 14 without losing the interaction. |

### 2.2 Stack

**`portfolio-api`**

```
PHP 8.4 · Laravel 13 · PostgreSQL 17 · Redis 8
Sanctum (PAT only) · Spatie Translatable · Spatie Permission (admin roles)
Pest · Larastan · Pint
```

**`portfolio-client`**

```
Nuxt 4.5 · TypeScript (strict) · Vite 8
Tailwind CSS v4 (CSS-first @theme, Vite plugin — no postcss, no JS config)
PrimeVue 4 + @primevue/nuxt-module + tailwindcss-primeui
Pinia 3 · @nuxtjs/i18n 10 · @nuxtjs/seo · @nuxt/image · @nuxt/fonts · @nuxt/icon
Vitest · Playwright · @nuxt/eslint · vue-tsc
```

Notes that will bite if forgotten:

- **Tailwind v4 has no `tailwind.config.js`.** Theme lives in CSS under `@theme`. The PrimeUI bridge must be the CSS version (`tailwindcss-primeui`), not the v3 JS plugin.
- **PrimeVue is scoped by surface.** The admin gets the full kit — DataTable, FileUpload, Toast, ConfirmDialog — where it earns its bundle. Public pages get only Dialog, Select and Toast. Tree-shake via the module's `components.include`.
- Theme the Aura preset with `definePreset()` mapping semantic tokens onto the eight palette variables, so PrimeVue inherits the brand instead of fighting it.
- **`@nuxtjs/i18n` v10 needs `seo: true`** to emit hreflang, and pairs with `@nuxtjs/sitemap` for per-locale alternates.

### 2.3 Runtime topology

```
                    Cloudflare (proxied, Full strict, cache rules)
                                    │
                          nginx :443  www.simon-dev.com
                                    │
        ┌───────────────────────────┼────────────────────────────┐
        │                           │                            │
   /api/*                    /_nuxt/*, /media/*                  /*
        │                    (served from disk,                  │
        ▼                     immutable, no node)                ▼
   PHP-FPM 8.4                                            Nitro node svc
   Laravel 13                                             127.0.0.1:3000
        │                                                        │
        └──────────► PostgreSQL 17 ◄─────────────────────────────┘
                     Redis 8  (Laravel response cache
                               + Nitro ISR store)
```

Cache invalidation is one lever: any admin write bumps a `content_version` key. That key is part of every Laravel cache key and every Nitro ISR key, and the publish action additionally fires a Cloudflare purge. One write, one coherent flush.

### 2.4 Data model

Translatable columns are JSONB shaped `{"es": "...", "en": "...", "ja": "..."}`, marked below with `†`.

```
locales               code PK · name · native_name · is_default · is_enabled · sort_order

profile               (singleton) email · name† · about_md† · about_html†
                      github_url · linkedin_url · meta_title† · meta_description†

hero_lines            id · locale_code · role_label · tech_string · sort_order
                      -- three rows; NOT translations of each other

menu_items            id · key · label† · icon · kind(popup|external) · target · sort_order

social_links          id · key · label† · url · icon · sort_order · is_published

documents             id · key · label† · locale_code · media_id · sort_order · is_published

technologies          id · slug · name · sort_order            -- untranslated by design

projects              id · key · slug† · title · summary† · description_md† · description_html†
                      repo_url · live_url
                      thumbnail_media_id · video_media_id · poster_media_id
                      sort_order · is_published · published_at
                      meta_title† · meta_description†

project_technology    project_id · technology_id · sort_order

media                 id · disk · path · mime · width · height · duration_ms
                      byte_size · checksum · alt† · renditions(jsonb)

users                 admin only · Sanctum PATs · Spatie roles
settings              key · value(jsonb)    -- content_version lives here
```

`slug` is translatable so URLs can localise (`/proyectos/portafolios`, `/en/projects/portfolio`). `description_html` is pre-rendered and sanitised server-side so the client ships no Markdown parser.

### 2.5 API surface

Versioned under `/api/v1`, stateless, JSON only.

```
GET    /api/v1/health
GET    /api/v1/bootstrap?locale=es      → profile + hero_lines + menu + socials + documents
GET    /api/v1/projects?locale=es
GET    /api/v1/projects/{slug}?locale=es
GET    /api/v1/technologies

POST   /api/v1/auth/login               → { token }
POST   /api/v1/auth/logout
GET    /api/v1/auth/me

GET    /api/v1/admin/projects           full CRUD, ability-scoped PAT
POST   /api/v1/admin/media
POST   /api/v1/admin/publish            bumps content_version, purges Redis + Cloudflare
```

Public GETs carry `ETag`, `Last-Modified` and `Cache-Control: public, max-age=0, s-maxage=3600, stale-while-revalidate=86400`. Throttled. CORS restricted to the site origin plus localhost dev ports.

`/bootstrap` exists so the shell is one round trip rather than five.

### 2.6 Conventions

- **Branches**: `main` for development, `production` for deploys, matching harmless-pleasure. Never work on `production`; merge `main` into it.
- **Commits**: atomic, one logical change, imperative mood. No bundling.
- **Data**: migrations for schema, seeders for content. Nothing ad-hoc.
- **Naming**: DB snake_case, PHP StudlyCase/camelCase, Vue components PascalCase, composables `useThing`, Pinia stores `useThingStore`.
- **Locale codes**: `es` (default), `en`, `ja` — everywhere, including URL prefixes.
- **Dates**: Carbon server-side, native `Intl` client-side (no Day.js — the site formats maybe two dates).

### 2.7 Responsive contract

Mobile-first, and the target range is wide: **1280×720 on a 13" laptop through 3840×2160 on a 32" monitor.**

- **Fluid typography via `clamp()`** with a viewport term, min anchored at 360px and max at 2560px. This replaces the current stepped root font-size, which stops adapting above 1303px (defect 30).
- **Content max-width** around 1600–1920px so line lengths stay sane on 4K; the background gradient keeps filling the viewport.
- **Short-viewport handling.** A 1280×720 laptop has ~620px of usable height after browser chrome. The hero must fit without scrolling: add a `@media (max-height: 760px)` compaction pass that tightens vertical rhythm rather than relying on `svh` alone.
- **Grid**: 1 column < 600px, 2 columns ≥ 600px, 3 columns ≥ 1920px.
- Keep the aspect-ratio switch for the project view (stacked in portrait, side-by-side in landscape) — it's the right call and survives the rewrite.

Verification matrix for sessions 6–8: 390×844, 768×1024, **1280×720**, 1440×900, 1920×1080, 2560×1440, **3840×2160**.

### 2.8 Performance budget

Enforced in session 8, asserted in CI.

| Metric | Budget |
|---|---|
| Lighthouse (mobile + desktop, both public routes) | 100 / 100 / 100 / 100 |
| LCP (Slow 4G) | < 1.2s |
| CLS | < 0.02 |
| INP | < 100ms |
| JS on `/`, gzipped | < 90 KB |
| Total transfer on `/` | < 250 KB |
| Total transfer on `/proyectos` (no video) | < 400 KB |
| All six videos combined | **< 8 MB** (from 55 MB) |
| Blocking third-party origins | 0 |

---

## 3. Local environment

Verified 2026-08-06 on this machine:

| Tool | Status |
|---|---|
| PHP 8.4.13 CLI | ✅ |
| Composer 2.8.12 | ✅ |
| Node 22.20.0 / npm 10.9.3 | ✅ |
| PostgreSQL 16 server | ✅ running, accepting on `/var/run/postgresql:5432` |
| Redis | ✅ running |
| `gh` CLI 2.95.0 | ✅ authed as `SimonARG` |
| ffmpeg | ⚠️ **not in WSL** — use `/mnt/d/Programs/ffmpeg/bin/ffmpeg.exe` with Windows paths |
| PHP `intl`, `gd`/`imagick`, `redis` | ❌ **missing — session 1 installs these** |

---

## 4. The ten sessions

---

### 4.1 Session 1 — Foundations

**Goal:** both applications boot, talk to each other, and every quality gate is wired before a line of feature code exists.

**Do**

1. Install the missing PHP extensions (`intl`, `gd` or `imagick`, `redis`); confirm `php -m`.
2. `gh repo create SimonARG/portfolio-api` and `SimonARG/portfolio-client`, both public, cloned to `~/portfolio-api` and `~/portfolio-client`. Create `main` and `production` on each.
3. Scaffold Laravel 13. Configure Postgres (`portfolio` DB, dedicated local user), Redis for cache and queue, `.env.example` committed. Add Pest, Larastan (level 6), Pint. `GET /api/v1/health` returns `{"status":"ok","version":...}`.
4. Scaffold Nuxt 4.5 with TypeScript strict. Add and configure — but do not yet style — Tailwind v4, `@primevue/nuxt-module`, Pinia, `@nuxtjs/i18n`, `@nuxt/image`, `@nuxt/fonts`, `@nuxt/icon`, `@nuxt/eslint`, Vitest, Playwright.
5. Confirm current versions against live docs before pinning. Do not trust memory for Nuxt/Laravel/PrimeVue/Tailwind API surface.
6. Write `~/portfolio-client/app/lib/api.ts` — a typed `$fetch` wrapper reading `NUXT_PUBLIC_API_BASE`, with a shared error shape. Prove it end to end: a placeholder page renders the health payload, SSR'd.
7. Extract the design tokens from `~/portfolio/css/index.css` into `app/assets/css/tokens.css` — the eight palette variables plus a fluid type scale and spacing scale. No component styling yet.
8. Build `content-inventory.json` in `portfolio-api/database/data/` — every ES/EN/JA string, URL and asset reference harvested from the legacy HTML. This is session 2's seeder input, so it must be complete and verbatim.
9. Copy this plan file to both repos as `REBUILD_PLAN.md` (single source of truth, kept in sync).
10. Minimal GitHub Actions on each repo: install, lint, typecheck, test.

**Done when**

- `php artisan test` and `npm run build` both pass in a clean clone.
- Nuxt SSRs a page whose content came from Laravel over HTTP.
- `content-inventory.json` round-trips every string in §1.3 with nothing lost.
- CI is green on both repos.

**Do not** design anything, model any domain tables, or touch the legacy repo beyond reading it.

---

### 4.2 Session 2 — Data model and content migration

**Goal:** Postgres holds every piece of the current site's content, in all three languages, reproducibly.

**Do**

1. Migrations for the full schema in §2.4. JSONB for translatable columns, GIN indexes where they'll be queried, FKs with sensible cascade rules.
2. Eloquent models with the Spatie Translatable trait (mirror harmless-pleasure's `HasTranslations`), casts, relationships, scopes (`published()`, `ordered()`).
3. Factories for every model.
4. Seeders reading `content-inventory.json`: `LocaleSeeder`, `ProfileSeeder`, `HeroLineSeeder`, `MenuSeeder`, `SocialLinkSeeder`, `TechnologySeeder`, `ProjectSeeder`, `DocumentSeeder`, plus an `AdminUserSeeder` reading credentials from env.
5. Convert the `<br>`-list descriptions to Markdown. Render to sanitised HTML on save (a model observer), store both.
6. Rewrite the `portfolio` project's own description — it currently claims vanilla HTML/CSS/JS, which this rebuild makes false. Write it in all three languages, describing the new stack.
7. `content_version` in `settings`, plus the observer that bumps it on any content write.
8. Pest tests: translation fallback behaviour, ordering, publish scopes, Markdown→HTML rendering and sanitisation, seeder idempotency.

**Done when**

- `php artisan migrate:fresh --seed` produces a database containing every string from §1.3, byte-identical to the legacy copy except the deliberate `portfolio` rewrite.
- Running it twice changes nothing.
- Tests green, Larastan clean.

**Watch for:** the Japanese strings carry inline `font-size` overrides in the legacy HTML. Those are presentation, not content — strip them, and record in §6 which strings had them so session 5 can handle CJK sizing properly in CSS.

---

### 4.3 Session 3 — The API

**Goal:** a complete, cached, tested, stateless REST API.

**Do**

1. `routes/api.php` under `/api/v1`, public and admin groups.
2. API Resources for every entity. Locale resolution from the `?locale=` query with a fallback chain (`requested → es`). Never leak untranslated JSONB blobs to the client.
3. Public controllers: `HealthController`, `BootstrapController`, `ProjectController` (index + show by translated slug), `TechnologyController`.
4. Sanctum PAT auth: login/logout/me, token abilities (`admin:read`, `admin:write`), no sessions and no CSRF anywhere.
5. Admin controllers: full project CRUD, profile, socials, documents, technologies, media, publish.
6. Form Requests for every write, with translation-shape validation (every translatable field must carry all enabled locales or explicitly null).
7. Caching: Redis response cache keyed on `(route, locale, content_version)`; `ETag` + `304` handling; the `Cache-Control` header from §2.5.
8. Rate limiting (generous public, strict auth), CORS restricted to the site origin plus localhost dev ports.
9. OpenAPI 3.1 spec at `docs/openapi.yaml`, generated or hand-maintained, kept accurate.
10. Pest feature tests covering every endpoint: shapes, locale fallback, 404s, auth boundaries, ability enforcement, cache headers, `304` on repeat, and that a content write invalidates.

**Done when**

- Every endpoint in §2.5 responds correctly for all three locales.
- A second identical request returns `304`.
- An admin write flushes the cache and bumps `content_version`.
- Unauthenticated admin calls `401`; wrong-ability calls `403`.
- Coverage on `app/Http` above 90%.

---

### 4.4 Session 4 — Media pipeline

**Goal:** 55 MB of video becomes under 8 MB with no visible quality loss, and every asset is served optimally.

**Do**

1. Write `scripts/encode-media.sh` — committed, reproducible, documented. It calls the Windows ffmpeg binary with Windows paths (see `~/.claude/CLAUDE.md`).
2. Per video: 30fps, two renditions — **AV1/WebM** (`libsvtav1`) primary and **H.264/MP4** (`libx264`, High profile, `faststart`) fallback — at 1280px wide, plus a 1920px variant only where it's genuinely legible at 4K. Strip audio from all except `ambiance`, which keeps its AAC/Opus track because Re;Noise's sound is the demo.
3. Fix `blog.mp4` specifically: transcode out of HEVC (broken in Firefox and much of desktop Chrome) and trim from 185s to roughly 45s of the most representative footage.
4. Extract a **poster frame** per video as AVIF + WebP. Posters are what the popup shows until play.
5. Re-encode `mixtorrents.jpg` to AVIF at parity with the other five thumbnails. Rename `re;noise.avif` → `re-noise.avif` (the semicolon is a URL hazard).
6. Generate a real favicon set — SVG, 180px apple-touch, 192/512 PNG, `site.webmanifest`.
7. `media` table populated by a committed seeder, with dimensions, duration, byte size, checksum and the renditions map.
8. Media storage: `public` disk under `/var/www/portfolio-api/shared/media`, symlinked, served by nginx directly with immutable caching and content-hashed filenames.
9. `@nuxt/image` configured against that path with the right providers and sizes.
10. Admin upload endpoint (`POST /api/v1/admin/media`) that runs the same pipeline on upload: validate, hash, derive renditions, persist.
11. Record before/after byte counts in §6.

**Done when**

- All six videos, both renditions, total under 8 MB.
- Every video plays in Chrome, Firefox and Safari — verify `blog` explicitly.
- Every image has an AVIF and a WebP rendition with correct dimensions recorded.
- No filename contains a character needing URL encoding.

---

### 4.5 Session 5 — Design system and app shell

> **Invoke the `frontend-design` skill before writing any component.**

**Goal:** the brand, rendered as a system. Every later frontend session composes from what this one builds.

**Do**

1. **Tailwind v4 theme** in CSS (`@theme`): the eight palette variables as colour tokens, the fluid `clamp()` type scale, spacing, the glow shadows, timing functions including the overshoot curve `cubic-bezier(0.175, 0.885, 0.32, 1.275)`.
2. **Fonts via `@nuxt/fonts`, self-hosted.**
   - Sofia Sans Extra Condensed (display) and Noto Sans (UI).
   - **Verify the `wdth` axis survives the download.** The compressed `wdth: 62.5` setting is the site's signature. If `@nuxt/fonts` flattens it, fall back to registering the variable TTF manually with explicit `@font-face` and `font-variation-settings`.
   - **Japanese needs its own face.** Neither family ships CJK, so today it renders in an OS fallback. Subset Noto Sans JP to exactly the glyphs used in the content (the set is small and known — extract it from the seeded DB), producing roughly 15–30 KB of woff2, loaded only for `locale=ja`.
   - Size-adjust fallback metrics to hold CLS under 0.02.
3. **Icons via `@nuxt/icon`**, server-bundled so they inline as SVG and no CDN is touched: `@iconify-json/simple-icons` for brands (github, linkedin, instagram, facebook, lastdotfm, letterboxd) and `@iconify-json/tabler` for UI. This deletes Font Awesome and one blocking origin.
4. **PrimeVue preset**: `definePreset()` over Aura mapping semantic tokens to the palette. `tailwindcss-primeui` for the bridge, with layer order set so Tailwind utilities win. Restrict registered components to what's actually used.
5. **Motion primitives** as composables/utilities: the name sweep, the peek-slide, the clipped text-swap, card hover. Each honours `prefers-reduced-motion` — under reduced motion the sweep becomes an instant state change, slides become fades, nothing animates for longer than 100ms.
6. **Layout** `layouts/default.vue`: background gradient (horizontal on desktop, vertical on mobile), the fixed peek menu, the footer. The menu is a real `<nav>` with a list, keyboard-operable, `:focus-visible` rings in `--3`.
7. **i18n**: `strategy: 'prefix_except_default'`, default `es` → `/`, `/en`, `/ja`. Localised route paths (`/proyectos` vs `/projects`). `seo: true`. Browser detection cookie-based with `redirectOn: 'root'` and `alwaysRedirect: false` so crawlers are never bounced.
8. **Pinia stores**: `useContentStore` (bootstrap payload, SSR-hydrated), `useLocaleStore`, `useUiStore` (open dialog, menu state).
9. **Base components**: `AppButton` (with the `➔` tell), `AppDialog` (PrimeVue Dialog themed — real focus trap, Escape, return focus, fixing defect 23), `TechChip`, `LangPill`, `GlowPanel`.
10. **Apply the contrast fix from defect 22**: `--4` for large text, borders and glows; `--3` for small text on dark. Verify every pair with a contrast checker and record the results.
11. Storybook-less component gallery at `/_dev/components`, excluded from production builds and from the sitemap.

**Done when**

- The gallery route renders every primitive in all three locales at every breakpoint in §2.7.
- Zero external network requests on first paint.
- Every interactive element has a visible focus state.
- Reduced-motion is honoured throughout.
- Contrast results recorded in §6 with no AA failures.

---

### 4.6 Session 6 — Home page

> **Invoke the `frontend-design` skill first.**

**Goal:** the landing page, rebuilt — same character, better craft, correct from 1280×720 to 3840×2160.

**Do**

1. `pages/index.vue`, SSR'd from `/bootstrap`, ISR-cached.
2. **The name** with the sweep animation (§1.2 motion #1). It's an `<h1>` and must stay one.
3. **The trilingual role stack** (§1.2 motion #2) — three lines, each with its language pill, each swapping to its own tech list on hover. On touch devices, where hover doesn't exist, the swap needs a deliberate alternative: cycle on an interval, or trigger on tap without swallowing the language switch. Decide, implement, note the choice.
4. **Language pills** driving real i18n navigation (`/`, `/en`, `/ja`) rather than a class toggle — this is what fixes defects 12 and 13. Keep the current placement: beside each role line on desktop, a top row on mobile.
5. **PROYECTOS button** with the arrow tell, as a real `<NuxtLink>`.
6. **About / CV / Socials** as `AppDialog` instances driven by the menu, content from the store. The About copy is long — verify it fits at 1280×720 without the dialog overflowing the viewport.
7. **Footer** with the email. Note that Cloudflare's email obfuscation is deliberately off (see `~/portfolio/CLAUDE.md`); the address must remain in the served HTML as plain text.
8. Full responsive pass across the §2.7 matrix, with real attention to the two extremes: the 720p laptop's vertical squeeze and the 4K monitor's need for fluid scale rather than a tiny centred island.
9. Playwright: locale switching persists and updates the URL, dialogs trap focus and restore it, reduced-motion is respected, the hero fits without scroll at 1280×720.

**Done when**

- Visually faithful to the original's character while measurably better spaced and timed.
- Screenshots at all seven viewports reviewed and attached to §6.
- No layout shift on font load.
- Keyboard-only traversal of the whole page works.

---

### 4.7 Session 7 — Projects

> **Invoke the `frontend-design` skill first.**

**Goal:** the gallery and project detail, now individually addressable and cheap to load.

**Do**

1. `pages/proyectos/index.vue` — the grid. 1 / 2 / 3 columns per §2.7. Cards with the hover treatment (§1.2 motion #4), thumbnails through `@nuxt/image` with `srcset`, AVIF first, correct `sizes`, above-fold cards eager and the rest lazy.
2. `pages/proyectos/[slug].vue` — the project view. Rendered as a **modal over the grid on client-side navigation**, and as a **full standalone page on direct load or crawl**. Both share one component.
3. **`ProjectVideo` component**, and this is the performance-critical piece:
   - `preload="none"`, poster shown until play.
   - `<source>` elements injected only when the view opens — nothing fetched for projects the visitor never opens.
   - AV1/WebM first, H.264/MP4 fallback, correct `type` attributes (fixing defect 5).
   - Autoplay muted on open, pause on close, and no autoplay at all under `prefers-reduced-motion`.
   - `ambiance` keeps audio; give it a visible unmute control since browsers will block sound on an autoplay.
4. Preserve the landscape/portrait split (video-beside-info vs stacked).
5. Tech chips from the pivot, in order, at the contrast-corrected colour.
6. Correct heading hierarchy — `h1` project title, `h2` sections — fixing defect 26.
7. Source and live-demo links where present. `mpi` has neither; the layout must not leave a hole.
8. Prev/next navigation between projects, keyboard-operable.
9. Playwright: deep-linking to `/proyectos/mpi` renders standalone; clicking a card from the grid opens the modal and updates the URL; browser back closes it; no video bytes are fetched until a project opens.

**Done when**

- Every project has a working URL in all three locales.
- The grid loads no video bytes at all — verify in the network panel.
- Videos play in Chrome, Firefox and Safari.
- Screenshots across the §2.7 matrix reviewed.

---

### 4.8 Session 8 — SEO, accessibility, performance

**Goal:** turn the priorities into measured, enforced numbers.

**Do**

1. **`@nuxtjs/seo`** configured end to end: per-route titles and descriptions from the DB, canonicals, OG and Twitter cards.
2. **`nuxt-og-image`**: a branded template using the real palette and typefaces, generating a card per project and per locale.
3. **`nuxt-schema-org`**: `Person` for Simón, `WebSite`, `ProfilePage` on the home page, `SoftwareSourceCode` or `CreativeWork` per project, `BreadcrumbList` on detail pages.
4. **Sitemap** with per-locale entries and `xhtml:link` alternates; **robots.txt** allowing everything public and blocking `/admin` and `/_dev`.
5. **hreflang** verified for all three locales plus `x-default`.
6. Structured-data validation, and confirmation that each locale's page contains **only** that locale's text — the actual fix for defect 12.
7. **Lighthouse CI** in the GitHub Actions workflow, asserting the §2.8 budget. The build fails if a budget regresses.
8. **axe-core in Playwright** across every route and locale, zero violations.
9. Manual a11y pass: full keyboard traversal, screen-reader landmarks, `lang` on every locale boundary (defect 25), heading order, focus order, and dialog semantics.
10. Core Web Vitals under throttling; fix whatever misses.
11. Verify the caching chain end to end: Nitro ISR hit, nginx, Cloudflare `cf-cache-status`, and that a publish actually purges all three.

**Done when**

- Lighthouse 100/100/100/100 on `/`, `/proyectos` and a project page, mobile and desktop.
- Zero axe violations.
- Rich Results and hreflang validators clean.
- CI enforces the budget; a deliberate regression fails the build.

---

### 4.9 Session 9 — Admin panel

> **Invoke the `frontend-design` skill first** — the admin should look like it belongs to the same site, not like a bare CRUD scaffold.

**Goal:** Simón can edit every piece of content without touching a seeder.

**Do**

1. `/admin/**` as `ssr: false`, `robots: false`, excluded from the sitemap and code-split so the public bundle carries none of it.
2. Login page, PAT stored appropriately, auto-refresh, logout, route middleware.
3. **Projects**: PrimeVue DataTable with drag-reorder, create/edit form, Markdown editor with live preview, tech-tag multiselect, media pickers, publish toggle.
4. **Translation editor**: the three locales side by side per field, with a clear indicator of what's missing. This is the piece that makes the JSONB model worth having.
5. **Media library**: FileUpload with drag-drop, running the session-4 pipeline server-side, showing renditions and byte sizes, with alt-text editing per locale.
6. **Profile / socials / documents / technologies** CRUD.
7. **Publish** action: bumps `content_version`, purges the Laravel cache, purges Nitro ISR, purges Cloudflare. Surface the result — a purge that silently failed is worse than no purge.
8. Toast and ConfirmDialog throughout. Optimistic updates where safe, rollback on error.
9. Playwright happy path: log in, edit a project in all three languages, upload media, publish, confirm the public page reflects it.

**Done when**

- Every content type in §2.4 is editable.
- Publishing propagates through all four cache layers.
- The public bundle contains zero admin code — verify in the build analysis.
- Admin is unreachable to crawlers and unauthenticated users.

---

### 4.10 Session 10 — Deployment and cutover

**Goal:** the new stack live at `www.simon-dev.com`, with the old one intact as a rollback.

Read `~/portfolio/CLAUDE.md` and `~/harmless-pleasure/CLAUDE.md` first. The VPS is a shared box — `api.harmlesspleasure.com` and `pointgeek.store` are live co-tenants. **Never reload nginx without `nginx -t` first.**

**Do**

1. **Postgres**: `portfolio` database and `portfolio_user` on the VPS, matching the harmless-pleasure pattern (local socket auth). Credentials into `~/harmless-pleasure/CLAUDE.md` §"Credentials & Secrets" alongside the others.
2. **API slot**: `/var/www/portfolio-api/{blue,green,current,shared/{.env,storage,media}}`. Reuse `/opt/scripts/deploy.sh` unchanged.
3. **Client slot**: `/var/www/portfolio-web/{blue,green,current,shared}` and a **new `/opt/scripts/deploy-node.sh`** — clone to standby, `npm ci`, `npm run build`, flip the symlink, restart the service. Building into standby means the live slot keeps serving throughout; on a 2-core box expect a 1–3 minute build.
4. **OpenRC service** `/etc/init.d/portfolio-web` running `node /var/www/portfolio-web/current/.output/server/index.mjs` bound to `127.0.0.1:3000`, env from the shared `.env`, restart-on-failure. Verify Node's version in Alpine 3.22's repos first.
5. **nginx vhost**: `/api/` → PHP-FPM; `/_nuxt/` and `/media/` served from disk with immutable caching (never through node); everything else → `127.0.0.1:3000`. A **dedicated PHP-FPM pool for the portfolio** — do not touch harmless-pleasure's.
6. **Webhooks**: add `portfolio-api` and `portfolio-web` to the `apps` map in `/var/www/webhook/deploy.php`, plus the GitHub hooks on both repos. The existing static `portfolio` entry stays until cutover succeeds.
7. **Staging first.** Bring the whole thing up on `next.simon-dev.com` — add the record in Cloudflare and extend the existing DNS-01 cert lineage (it's the same account-scoped token at `/etc/letsencrypt/cloudflare.ini`). Verify everything there before touching `www`.
8. **Seed production** via the committed seeders. Create the admin user from env, never from a literal in a migration.
9. **Cutover**: repoint `www` to the new vhost. Verify with headers rather than checksums — the old and new trees are different, but the §"Verifying by checksum is still a trap" warning in `CLAUDE.md` still applies to reasoning about which layer answered. Test the origin directly with `curl -sI --resolve www.simon-dev.com:443:148.230.91.169`.
10. **Cloudflare**: cache rules bypassing `/api/*` and `/admin/*`, caching `/_nuxt/*` and `/media/*` aggressively. Keep Email Obfuscation **off** — that was a deliberate decision, documented, and re-enabling it silently changes the served HTML.
11. **Rollback plan, written and tested**: `rollback-node.sh` flips the node slot; beyond that, the legacy static slots under `/var/www/portfolio/` remain untouched, so pointing the vhost root back restores the old site immediately. Test this before declaring done.
12. **Smoke tests** against production: all three locales, all six projects, video playback, admin login, a publish round-trip, Lighthouse on the live origin.
13. **Documentation**: rewrite `CLAUDE.md` in both new repos to describe the real deployed system. Update `~/portfolio/CLAUDE.md` to mark the legacy repo frozen and point at the successors. Update the memory file `portfolio-vps-migration.md`.
14. Leave the legacy repo and its branches in place. `gh-pages` remains the ultimate fallback per the existing standing instruction.

**Done when**

- `https://www.simon-dev.com` serves the new site over TLS in all three locales.
- A push to `production` on either repo deploys automatically.
- Rollback has been executed successfully at least once in testing.
- Lighthouse budgets hold against the live origin, not just locally.
- Docs match reality.

---

## 5. Risks

| Risk | Mitigation |
|---|---|
| The `wdth: 62.5` axis is lost when fonts are self-hosted, flattening the site's signature look | Verify explicitly in session 5; manual `@font-face` with the variable TTF is the fallback |
| Nitro node service on a 2-core box shared with Laravel, Postgres and Redis | ISR means most requests never reach node; static assets bypass it entirely; memory-capped service with restart-on-failure; static prerender remains an escape hatch |
| Video re-encode degrades quality visibly | Side-by-side frame comparison at each quality step before committing; keep originals until session 10 signs off |
| Cutover breaks a co-tenant on the shared nginx | `nginx -t` before every reload, separate PHP-FPM pool, staging host first |
| Locale-prefixed URLs are new — the old single URL has whatever ranking it has | 301 the legacy paths (`/index.html`, `/projects.html`) to their new equivalents; submit the new sitemap; keep `x-default` pointing at `/` |
| PrimeVue's weight leaking onto the landing page | Registered components restricted; bundle analysis asserted in CI against the 90 KB budget |
| A session runs long and lands the tree broken | The §0 budget guard: stop, commit, defer explicitly, hand off |

---

## 6. Progress ledger

Each session appends here before writing its handoff prompt. Keep it factual — what shipped, what deviated, what's deferred.

| # | Session | Status | Notes |
|---|---|---|---|
| 1 | Foundations | ⬜ not started | |
| 2 | Data model | ⬜ not started | |
| 3 | API | ⬜ not started | |
| 4 | Media pipeline | ⬜ not started | |
| 5 | Design system | ⬜ not started | |
| 6 | Home page | ⬜ not started | |
| 7 | Projects | ⬜ not started | |
| 8 | SEO / a11y / perf | ⬜ not started | |
| 9 | Admin panel | ⬜ not started | |
| 10 | Deploy + cutover | ⬜ not started | |

### Decisions revised mid-build

_(append here — date, what changed, why)_

### Deferred work

_(append here — item, deferred from session N to session M, why)_

---

## 7. Starter prompt — Session 1

Paste this into a fresh session to begin.

```
Session 1 of 10 — Foundations — of the simon-dev.com portfolio rebuild.

Read /home/simon/portfolio/REBUILD_PLAN.md in full before doing anything. It is the
single source of truth. Your scope is §4.1 exactly; §0 has the standing rules and the
budget guard; §2 has the architecture and conventions; §3 lists what's already
installed on this machine and what is missing.

Build the foundations: install the missing PHP extensions, create the portfolio-api
and portfolio-client repos, scaffold Laravel 13 and Nuxt 4.5 with the full toolchain
from §2.2, wire a typed API client, extract the design tokens, harvest the complete
ES/EN/JA content inventory from the legacy site, and get CI green on both repos.

Confirm current library versions against live documentation before pinning anything —
Nuxt, Laravel, PrimeVue, Tailwind and @nuxtjs/i18n all moved during 2026 and the plan's
version notes are from 2026-08-06.

Do not design any UI, do not model domain tables, and do not modify anything under
/home/simon/portfolio beyond reading it and updating REBUILD_PLAN.md — that repo is
still serving the live site.

When you finish: update the progress ledger in §6, then print the starter prompt for
Session 2 (Data model and content migration), adjusted for anything that actually
changed.
```
