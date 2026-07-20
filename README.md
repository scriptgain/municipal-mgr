# MunicipalMGR

Self-hosted municipal and government website platform by [ScriptGain](https://scriptgain.com).

A city, town, or village website that staff fully manage from the backend, with a
public front end built for residents: news and notices, an events calendar,
department pages and a staff directory, elected officials, meetings with agendas
and minutes, a searchable document library, report-an-issue intake with public
status tracking, job postings and procurement, a forms builder, and a site-wide
emergency alert banner.

## Install

```
curl -fsSL https://install.scriptgain.com | sudo bash -s -- municipal-mgr DOMAIN=www.example.gov SSL=1 EMAIL=you@example.gov
```

Or from a checkout on a fresh Debian/Ubuntu host, as root:

```
DOMAIN=www.example.gov SSL=1 EMAIL=you@example.gov ./deploy/install-master.sh
```

Then finish setup at `https://your.domain/admin/setup` — create the first admin
account and enter your license key.

## Layout

- Public site: `/`
- Staff panel: `/admin`
- First-run wizard: `/admin/setup`

## Commands

| Command | What it does |
| --- | --- |
| `php artisan municipal:bootstrap` | Seeds default menus, document categories, and the contact form. Idempotent. |
| `php artisan municipal:bootstrap --force-demo` | Also seeds the Cottonwood Springs demonstration site. |
| `php artisan municipal:license <key>` | Sets or re-checks the ScriptGain license key. |
| `php artisan municipal:publish-due` | Publishes scheduled content, retires expired alerts and postings. Runs every minute via the scheduler. |
| `php artisan municipal:housekeeping` | Prunes audit logs past their retention window. |
| `php artisan app:update` | Applies a newer signed release. |
| `php artisan firewall:clear` | Escape hatch if an IP allowlist locks you out. |

## Front end

Tailwind v4 via browser CDN plus Alpine — **no Vite, no npm, no build step**.
Design tokens live in `resources/css/app.css` and are inlined at runtime by the
`x-tailwind-cdn` component. Site JavaScript is in `public/js/municipal.js`.

## Configuration

Operator settings live in the database (`settings` table), not `.env`, and are
overlaid onto config at boot by `AppServiceProvider`. Site identity is edited at
**Settings → Site Identity**.

If you do set a hex colour in `.env`, quote it — an unquoted `#` starts a comment
and silently yields an empty value.
