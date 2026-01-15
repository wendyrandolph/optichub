# Renlo Subdomain Setup (Local & Production)

## Local (nip.io)
- Add to `.env`:
  - `APP_ROOT_DOMAIN=127.0.0.1.nip.io`
  - `APP_LOCAL_PORT=8000`
  - `SESSION_DRIVER=database`
- Local base URLs:
  - Tenant app: `http://app.127.0.0.1.nip.io:8000`
  - Admin app (canonical login): `http://admin.127.0.0.1.nip.io:8000`
- Cookies:
  - Admin session: `renlo_admin_session`
  - App session: `renlo_app_session`
- Commands: `php artisan optimize:clear` then `npm run dev` / `php artisan serve`.

## Production
- DNS: point `admin.renloapp.com` and `app.renloapp.com` to the app load balancer.
- HTTPS: terminate TLS for both subdomains.
- `.env` keys:
  - `APP_ROOT_DOMAIN=renloapp.com`
  - `APP_LOCAL_PORT=` (leave empty)
  - `SESSION_DRIVER=database`
  - `SESSION_SECURE_COOKIE=true`
  - `SESSION_SAME_SITE=lax`
- Base URLs:
  - Tenant app: `https://app.renloapp.com`
  - Admin app / login: `https://admin.renloapp.com`
- Cookies remain split (`renlo_admin_session`, `renlo_app_session`) and scoped to `.renloapp.com`.
