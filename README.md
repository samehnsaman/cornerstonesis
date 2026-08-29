# Cornerstone SIS POC

Laravel 13, React 19/Inertia 3, PostgreSQL 18 and Valkey proof-of-concept for a credit-hour college. It is permanently marked **DEMO — NOT OFFICIAL** and must contain synthetic data only.

## Implemented foundation

- UUID-based, historically traceable admissions, catalog, curriculum, periods, enrollment, registration, grades and credentials schema.
- Transactional registration checks for period, holds, capacity/waitlists, prerequisites, clashes and override reasons.
- Append-only balanced account postings, reversals and separate ISO-currency balances.
- Applicant authentication, responsive English/Arabic RTL portal, catalog/registration/account views and role scopes.
- Moodle boundary, immutable outbox, retry/dead-letter job and grade proposal service. Sync is off by default.
- OAuth2 client-credential API and [OpenAPI definition](docs/openapi.yaml).
- Bilingual transcript PDF with QR verification and permanent demo watermark.
- ARM64 images, isolated Compose topology, rotated logs, limits, backups and CI.

This is an executable POC foundation, not a production-complete college SIS. Staff workflow screens, complete historical-import mapping/reconciliation, MFA enrollment, comprehensive policy engines, reconciliation dashboards and full WCAG acceptance remain before real use.

## Verify

```bash
composer install
npm ci
cp .env.testing .env
php artisan migrate:fresh --seed
npm run build
php artisan test
```

Demo login: `applicant@example.test` / `DemoPassword123!`.

## Deployment gates

1. Publish CI ARM64 targets and set `SIS_IMAGE` / `SIS_WEB_IMAGE` to immutable digests.
2. Copy `secrets/*.example` without `.example`, generate unique secrets, and chmod `0600`.
3. Create `runtime/{postgres,valkey,storage,backups}` on `/mnt/90g` with correct ownership.
4. Confirm DNS/origin, `proxy`, unused hostname/router, `websecure`, and resolver `le`.
5. Confirm stable memory/swap, root use below 85%, and Oracle security-list restrictions.
6. Run Compose config and `scripts/preflight.sh`; verify Moodle version, `auth_userkey`, least-privilege token/functions and break-glass admin before enabling sync.
7. Pull, back up, migrate once, start, verify HTTPS/health/browser/Moodle, and perform a disposable restore.

Compose references but never manages Traefik. PostgreSQL and Valkey are internal-only.

## Audited host status (2026-08-29 UTC)

- Root: 63% used; `/mnt/90g`: 84 GiB available.
- About 2.3 GiB memory available; swap 4/4 GiB used — deployment blocked pending stability.
- DNS is Cloudflare-proxied rather than directly returning the stated origin; strict DNS gate needs origin verification.
- `proxy` exists; Traefik 3.6.4 ARM64 uses `le`; no router/hostname collision found.
- Traefik still uses mutable `traefik:latest`; change only in separate maintenance.
- Existing public 5432, 6543, 8000, 8443 and 5001 require Oracle security-list review.
- Ubuntu Pro/ESM is not attached; this Ubuntu 20.04 host is unsuitable for production.
- Moodle is 5.1.3+ (build 20260306), and `auth_userkey` is missing. Live synchronization and SSO remain disabled.

No SIS containers were started because mandatory gates remain open.
