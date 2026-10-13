# Festes Majors de Catalunya

A web app that shows the *festes majors* of Catalonia's municipalities. It has an upcoming-festivals carousel, a "near you" list (Haversine distance), an interactive map and a month calendar that stay in sync, Instagram Story sharing, and a full admin panel.

**Stack:** Laravel 13 · PHP 8.3 · MariaDB 11 · Blade · vanilla ES modules + Vite · Leaflet · Intervention Image (GD) · Pest 4.
**No CSS framework:** every line of CSS is hand-written.

---

## Quick start (Podman, recommended)

Everything runs in containers. PHP, Composer and Node are not needed on the host.

```bash
cp .env.example .env
podman-compose build
podman-compose up -d                      # first start runs composer install (see below)
podman-compose exec web php artisan key:generate
podman-compose exec web php artisan migrate --seed
podman-compose exec web npm install
podman-compose exec web npm run build
```

Open <http://localhost:8000>. The admin panel is at <http://localhost:8000/admin>.

| Demo admin | |
|---|---|
| Email | `admin@festesmajors.test` |
| Password | `festes2026` |

> Change the password (or create a new admin and delete this one) before deploying. The seeder lives in `database/seeders/DatabaseSeeder.php`.

> **Performance on Windows/macOS.** The source is bind-mounted into the container, and file access through that mount is slow. `vendor/` and `node_modules/` therefore live in named volumes, and `docker/php/entrypoint.sh` runs `composer install` automatically when the `vendor` volume is empty. PHP also caches paths and compiled scripts (`docker/php/app.ini`), and `artisan serve` runs 4 workers (`--no-reload`). That brings requests down from ~10 s to well under a second. After changing `.env` or `composer.json`, run `podman-compose restart web` (or `podman-compose exec web composer install`).
>
> If `podman` cannot connect, your default machine may be a different one: `export CONTAINER_CONNECTION=podman-machine-default`.

Useful commands:

```bash
podman-compose exec web npm run dev            # Vite dev server with HMR (port 5173)
podman-compose exec web php artisan test       # Pest test-suite (uses the festes_test DB)
podman-compose exec web php artisan festes:brand-assets   # regenerate PWA icons + OG image
podman-compose logs -f web
```

### Without containers

You need PHP 8.3 (extensions `gd` with WebP/FreeType, `pdo_mysql`, `intl`, `zip`, `exif`), Composer, Node 22 and MySQL/MariaDB. Set `DB_HOST=127.0.0.1` in `.env`, then:

```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

For the tests, create a `festes_test` database (see `docker/db/init/01-test-database.sql`).

---

## Design

### Palette: "nit de festa"

The senyera reinterpreted for a festive night: a deep night background, a sophisticated red and gold, and soft neon accents for the fireworks.

| Token | Value | Use |
|---|---|---|
| `--night` | `#0F1020` | Background (dark theme) |
| `--red` | `#E63946` | Primary, "Ara mateix", buttons (`--btn` is darkened 14 % for AA contrast with white text) |
| `--gold` | `#FFB703` | Secondary, dates, selected states |
| `--mint` | `#5EEAD4` | Neon accent, focus ring, "Aquest mes" |
| `--violet` | `#A78BFA` | Neon accent, "Més endavant" |
| Paper `#FBF7F0` / Ink `#15162B` | | Light theme, with text-safe variants (`--red-text #B3152A`, `--gold-text #8A5300`, `--mint-text #0F766E`) |

The red, gold and night colours can be changed from **Admin → Ajustos**. They are injected as `--brand-*` custom properties on every page.

### Typography

- **Bricolage Grotesque** (display): a characterful grotesque with optical sizes, for titles.
- **Inter** (text): highly legible UI sans.
- Fluid type scale with `clamp()` (`--step--2` … `--step-4`).
- Both are loaded from Google Fonts for the web. Static TTFs live in `resources/fonts` for the server-side Story renderer (SIL Open Font License).

### Markup rules

Each block has **one** parent with a class or id. Its children carry no classes and are styled from the parent:

```html
<article class="fiesta">
  <img …> <h3><a …>Festa Major de Gràcia</a></h3> <p>Barcelona · Barcelonès</p>
  <time datetime="2026-08-15">15–21 d’agost</time> <mark>Falten 3 dies</mark> <a …>share</a>
</article>
```

```css
.fiesta > img { … }   .fiesta > h3 { … }   .fiesta > time { … }   .fiesta > a:last-child { … }
```

Children are targeted mainly by **element type**, `>`, `+`, `:has()`, `:is()` and attributes (`[aria-pressed]`, `[aria-current="date"]`, `[data-estat]`). Positional selectors are used only where position *is* the meaning, for example the first weekday column (`li:first-child { grid-column-start: var(--start) }`). Reordering or adding children therefore doesn't silently break the styles. Two more examples:

- The facts `<dl>` uses `grid-auto-flow: column` so each `dt`/`dd` pair forms a tile with no `nth-child` at all.
- State lives in ARIA attributes, so styles and accessibility share one source of truth.

### CSS organisation (`resources/css`)

| File | Content |
|---|---|
| `app.css` | Entry point: `@layer vendor, base, layout, components, pages, admin` and the imports |
| `base.css` | Tokens (`:root`), light/dark themes, reset, typography, form controls, focus, keyframes, `prefers-reduced-motion`, View Transitions |
| `layout.css` | Header, hero, search combobox, section headers, footer |
| `cards.css` | Festival card (container queries), scroll-snap carousel, card grids, skeletons |
| `calendar.css` | 7-column grid calendar, list view, day detail |
| `map.css` | Filters, Leaflet theming, markers and heat scale, legend |
| `story.css` | Instagram Story `<dialog>` |
| `fiesta.css` | Festival page |
| `admin.css` | Admin panel and login |

It uses native nesting, `color-mix()`, `backdrop-filter`, `scroll-snap`, container queries, `:has()`, `@keyframes` and View Transitions: cross-document page transitions, the card image morphing into the festival cover, and a month slide in the calendar. Dark is the default theme, and light follows `prefers-color-scheme`. The header button cycles **auto → light → dark** and stores the choice. Leaflet's CSS sits in the lowest `vendor` layer, so overrides never need `!important`.

---

## Architecture

```
app/
├── Http/
│   ├── Controllers/            Home, Fiesta (show + .ics), Story, Sitemap, Locale
│   │   ├── Api/                FiestaController (index, proximas, cercanas, share), MunicipioController (search)
│   │   ├── Admin/              Dashboard, Fiesta (CRUD + duplicate + CSV), Municipio, Comarca, Setting
│   │   └── Auth/LoginController.php
│   ├── Middleware/             SetLocale (ca/es), EnsureUserIsAdmin
│   ├── Requests/               Form Requests (Admin/*, Api/FiestaIndexRequest, Auth/LoginRequest)
│   └── Resources/FiestaResource.php
├── Models/                     Comarca, Municipio, Fiesta (scopes), Acto, Setting (cached), User (is_admin)
├── Policies/                   Fiesta, Municipio, Comarca, Setting
├── Services/
│   ├── StoryImage.php          1080×1920 Instagram Story renderer (3 templates, QR, cache)
│   ├── CoverArt.php            Procedural artwork (fireworks + castell) for seeds / fallbacks
│   ├── ImageStore.php          Upload → resize → WebP (+ thumbnail)
│   ├── HtmlSanitizer.php       Whitelist sanitizer for the rich-text editor
│   ├── IcsCalendar.php         RFC 5545 export
│   └── FiestaCsv.php           CSV import / export
└── Support/FiestaCache.php     Versioned cache, invalidated on every admin edit
database/
├── data/fiestas.php            50 real festivals, 39 municipalities (2026 editions)
├── factories/ migrations/ seeders/
lang/{ca,es}/                   app.php (site + JS strings), admin.php, validation.php, auth.php
resources/
├── css/                        see above
├── js/
│   ├── app.js  admin.js        entry points
│   ├── lib/                    api (fetch + dedupe), store (shared filter state), i18n, card renderer
│   ├── modules/                theme, search, carousel, nearby, filters, map, calendar, story, minimap, leaflet
│   └── admin/                  confirm (<dialog>), editor, repeater, uploads (preview + slug), picker
├── fonts/                      TTFs for the Story renderer
└── views/                      layouts, components (fiesta-card, icon), home, fiestas/show, admin/*, auth/login
routes/  web.php  api.php  console.php (festes:brand-assets)
public/  sw.js  manifest.webmanifest  icons/  img/covers/  uploads/ (user images, git-ignored)
tests/   Feature (scopes, API, pages, admin) + Unit (sanitizer)
```

### Data model

`comarcas (nombre, provincia, slug, geojson_id)` → `municipios (comarca_id, nombre, slug, lat, lng)` → `fiestas (municipio_id, nombre, slug, descripcion, fecha_inicio, fecha_fin, imagen, destacada, publicada, visitas, compartidos)` → `actos (fiesta_id, dia, hora, titulo, lugar)`. There is also `settings (key, value)` and `users.is_admin`.

Scopes on `Fiesta`: `publicadas()`, `proximas()`, `enCurso()`, `cercanas($lat, $lng, $km)`, `delMes($mes, $any)`, `deProvincia()`, `deComarca()`, `filtrar($filters)`.

`cercanas()` computes the **Haversine** great-circle distance in SQL as a `distancia` column (km). It pre-filters with a lat/lng bounding box so the `(lat, lng)` index can be used.

### Internal API

| Endpoint | Description |
|---|---|
| `GET /api/fiestas?mes=&any=&provincia=&comarca=&tipo=` | Calendar and map data. `mes` accepts `10` (+ `any`) or `2026-10`; `tipo` accepts one or more slugs (`festa-major,barraques`). Without `mes`, it returns everything that hasn't finished yet. |
| `GET /api/fiestas/proximas?limit=` | Next festivals |
| `GET /api/fiestas/cercanas?lat=&lng=&radio=` | Nearby festivals with `distancia` (km) |
| `GET /api/municipios?q=` | Search autocomplete (accent-insensitive) |
| `POST /api/fiestas/{slug}/share` | Story share counter |

All endpoints accept `?lang=ca|es`. Responses are cached as plain arrays keyed by filters, day and locale. A version key (`FiestaCache`) is bumped whenever a festival, municipality or comarca is saved or deleted, which invalidates every derived entry at once. Laravel 13's cache never unserializes objects, so models are never cached.

### Map and calendar sync

`resources/js/lib/store.js` holds the shared state (`provincia`, `comarca`, `mes`, `calMonth`, `day`, `geo`) and mirrors the filters in the URL, so `/?comarca=gironès` is shareable.

- Changing a filter refreshes both views.
- Navigating the calendar moves the month filter, so the map follows.
- Clicking a day shows only that day's festivals on the map.
- Searching a municipality with no upcoming festival flies the map to it.

### Instagram Stories: why server-side rendering

`GET /story/{slug}?t=nit|senyera|minimal` returns a 1080×1920 JPEG rendered with **Intervention Image + GD**. I chose this over html2canvas and Browsershot:

- **It's deterministic.** html2canvas re-implements CSS in JavaScript and breaks on cross-origin images, `backdrop-filter`, web fonts and blend modes, so results differ per device.
- **It's light.** Browsershot needs Node + Puppeteer + a headless Chrome inside the container (hundreds of MB, slow cold starts).
- **It's a real URL.** The image can be cached on disk (`storage/app/stories`, re-rendered once a day because of the countdown), crawled, downloaded on desktop, and fetched as a `File` for `navigator.share({ files })` on mobile, which opens the native share sheet with Instagram.

The three templates (names, accent colour, enabled, default) are configured in **Admin → Ajustos**. Each Story has the background image, title, municipality, dates, countdown, logo or wordmark, the short URL and a QR code to the festival page.

### Map hero and festival types

The home page opens on the **map**. On desktop it fills the screen and three glass panels float over it: title + search, filters (festival type chips, province, comarca, month), and **"Al mapa"**, the list of festivals inside the current view, re-computed on every pan/zoom. Hovering a list item highlights its marker and vice versa; the pin button flies to the municipality and opens its popup. On phones the blocks stack: title, map, legend, filters, list.

**Comarques** are drawn from `public/geo/comarques.geojson` (43 comarques including Lluçanès, 2025 IGN boundaries, simplified with mapshaper to 210 kB / 54 kB gzip). They are shaded by the number of festivals matching the current filters, show a tooltip on hover, and **clicking one filters by that comarca** (click again to clear). They are joined to the database through `comarcas.geojson_id` (official code `01`–`43`).

**Festival types** (`tipos` + `fiesta_tipo` pivot) are many-to-many: a festival can be *Festa Major + Barraques + Focs*. Seeded types are Festa Major, Barraques, Fira, Focs d'artifici, Concerts, Medieval, Festa d'hivern, Tres Tombs and Carnaval, each with a colour, editable in **Admin → Tipus**. The filter uses OR semantics (`/api/fiestas?tipo=barraques,focs`), is shared by map and calendar, and is kept in the URL (`/?tipo=barraques`). The CSV has a `tipos` column with slugs separated by `|`.

### Map tiles

The map uses **OpenStreetMap** tiles (no API key). In dark mode they're inverted with a CSS filter, so one tile set serves both themes. CARTO basemaps now require a key, so I dropped them.

Festivals are shown as **markers per municipality**, coloured by how soon the next festival starts: now, this week, this month, later.

### Accessibility

- Skip link and semantic landmarks (`header`, `main`, `section`, `article`, `nav`, `aside`, `dialog`, `footer`).
- Visible `:focus-visible` rings and AA contrast. Text-safe colour variants per theme, and darkened button reds.
- The search is an ARIA 1.2 combobox with a listbox (arrow keys, Enter, Escape).
- Calendar: roving tabindex, arrow keys, Home/End, PageUp/PageDown, `aria-current="date"`, `aria-pressed`, and descriptive `aria-label`s ("dimarts, 13 d’octubre: 2 festes").
- Map markers are keyboard-focusable buttons (Enter opens the popup), and the legend is real text.
- `prefers-reduced-motion` disables animations and view transitions.

### Admin panel (`/admin`)

- Festival CRUD:
  - Automatic slug with year (live preview).
  - Municipality picker grouped by comarca.
  - Lightweight rich-text editor (sanitised server-side).
  - Image upload with instant preview; the server resizes to 1600 px WebP plus a 640 px thumbnail.
  - Programme **repeater**, featured/published flags, and **duplicate to next year** (copies programme and image as a draft).
- Municipality CRUD with a **click-on-map lat/lng picker**, and comarca CRUD.
- **Ajustos:** hero texts (ca/es), logo, brand colours, social links, SEO + OG image, short URL, Story templates.
- **CSV import/export:** `;` or `,`; rows matched by slug; errors reported per line.
- **Dashboard:** festivals this month, ongoing, published, drafts, total views/shares, most viewed, most shared.
- **Safety:** Form Requests, Policies plus `admin` middleware, flash messages, delete confirmation in a native `<dialog>`, and login rate limiting.

### SEO, PWA and performance

- `sitemap.xml` and `robots.txt` (both dynamic).
- `Event` JSON-LD with `subEvent`s from the programme, plus Open Graph and Twitter Card tags.
- `manifest.webmanifest` + `sw.js`: network-first pages with an `/offline` fallback, stale-while-revalidate for assets, covers and tiles. The service worker is registered in production builds only.
- WebP uploads with lightweight thumbnails for cards, `loading="lazy"`, explicit dimensions, and `fetchpriority="high"` on the cover.
- Leaflet is code-split and loaded only when the map scrolls into view.
- Measured bundle sizes: app JS ≈ 7 kB gzip, CSS ≈ 13 kB gzip.

---

## Seed data

There are 43 comarques (the official 42 plus Lluçanès, created in 2023) and **54 real festivals**, each tagged with its types. Examples: Gràcia, La Mercè, Sants, Santa Eulàlia, La Patum de Berga, Santa Tecla, Sant Magí, Fires de Sant Narcís, Sant Pere and Misericòrdia de Reus, Vilafranca, Santa Teresa del Vendrell, Santa Úrsula de Valls, Les Santes de Mataró, Festes de Maig de Badalona, Sitges, Blanes, Tortosa, Solsona, Olot.

Dates are the **2026 editions and are approximate** for festivals whose calendar moves each year, so check the official programme and adjust them in the admin. Festivals that have already finished at seeding time also get their 2027 edition, created with the same "duplicate to next year" logic the admin uses. Cover images are generated procedurally (no third-party photos are shipped); replace them from the admin.

## Tests

```bash
podman-compose exec web php artisan test
```

There are 43 tests:

- Scopes: `proximas`, `enCurso`, `delMes`, `cercanas` (with real Barcelona–Badalona–Lleida distances), slugs, duplication.
- API endpoints: filters, validation, limits, distances, share counter, localisation, accent-insensitive search.
- Public pages: JSON-LD, drafts, `.ics`, a 1080×1920 Story, sitemap, language switch.
- Admin: authentication and authorisation, CRUD, sanitising, CSV round-trip, settings injected as CSS variables.
- The HTML sanitizer (unit tests).

## Notes

- The brief asked for Laravel 11. It is end-of-life, and Composer refuses to install it because of unpatched security advisories. The project therefore uses **Laravel 13**, which keeps the same slim application structure introduced in Laravel 11.
- Authentication is a small custom implementation (`Auth\LoginController` + `LoginRequest` with rate limiting), so no Breeze/Tailwind views are pulled in.
