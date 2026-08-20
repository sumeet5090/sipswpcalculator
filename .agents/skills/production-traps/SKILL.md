---
name: production-traps
description: Comprehensive encyclopedia of 65 production server, security, concurrency, deployment, and architectural traps across CloudLinux, LiteSpeed, SQLite, Vite, and Twig.
---

# Production Server, Security & Concurrency Traps (65 Points)

This skill provides an exhaustive guide to runtime traps, hosting quirks (CloudLinux / LiteSpeed / Hostinger), database concurrency constraints, and security standards for this application.

---

## 1. CloudLinux Shared Hosting & CageFS Virtualization Traps
1. **CageFS Ephemeral `/tmp` Namespace**: Never write persistent files or lockfiles to `/tmp`. CageFS virtualizes `/tmp` per execution context. Standardize on `var/` and `shared/`.
2. **CloudLinux LVE Memory OOM Killer**: Payload buffers in image uploads or PDF generation must be strictly clamped (5MB chart Base64 / 2MB logo) to prevent silent worker termination (`503/500`).
3. **Inode Exhaustion Throttling**: Shard file storage into 2-character hex subdirectories with opportunistic pruning (`FileRateLimitStorage`).
4. **Max Entry Process Saturation**: Avoid blocking synchronous file locks or database queries to stay well within Hostinger's concurrent entry process limits.
5. **Symlink Owner Matching (`SymLinksIfOwnerMatch`)**: Ensure deployment symlinks are created under the cPanel user (`u821438535`) rather than root.

---

## 2. LiteSpeed Web Server & LSAPI Runtime Quirks
6. **Disregard for `.htaccess` `php_value`**: Primary PHP runtime configuration must reside in `.user.ini`. Wrap `.htaccess` PHP directives in `<IfModule mod_php.c>`.
7. **LiteSpeed `.user.ini` Cache TTL**: Runtime changes to `.user.ini` can take up to 300s to reflect. Treat it as a versioned static file.
8. **LSAPI Authorization Header Stripping**: Preserve `Authorization` header via `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]`.
9. **Reverse Proxy Stream Buffering**: Send `X-Accel-Buffering: no` on PDF and CSV streams to prevent reverse proxy buffer deadlocks.
10. **Custom 404/500 Header Interception**: Catch all exceptions in kernel and render explicit Twig error pages to prevent web server error page hijacking.

---

## 3. Zero-Downtime Atomic Symlink Deployments
11. **OPcache In-Flight File Handle Race**: Swapping symlinks while active PHP workers have open file handles requires realpath cache busting and OPcache resets.
12. **Persistent Shared Database Drift**: SQLite database must reside in `shared/database.sqlite` and be auto-discovered by `CoreServiceProvider`.
13. **Stale Asset 404s (Tab Drift)**: Keep at least 3 historical releases in `releases/` and set `immutable` caching on `/dist/assets/*`.
14. **Shared `.env` Decoupling**: Symlink root persistent `.env` into every release.
15. **Directory Creation Race Conditions**: Guard all `mkdir` calls: `if (!is_dir($d) && !mkdir($d, 0775, true) && !is_dir($d))`.

---

## 4. CI/CD Pipeline (GitHub Actions)
16. **Rsync `.env` Overwrite**: Always `--exclude='.env'` during automated deployments.
17. **SSH Host Key Verification**: Pin host fingerprints with `KNOWN_HOSTS`.
18. **No Dev Dependencies in Production**: Build production images via `composer install --no-dev`.
19. **CLI PHP SAPI Version Mismatch**: Use explicit binary path or `#!/usr/bin/env php` for PHP 8.2+.
20. **Migration Return Code Verification**: Ensure migrations exit with status code 0 or 1 and pipeline fails on errors.

---

## 5. Database, SQLite WAL Mode & Concurrency
21. **WAL File Splitting & Directory Permissions**: Parent directory holding SQLite database must have `0775` permissions for `-wal` and `-shm` creation.
22. **SQLite `busy_timeout`**: Always set `PRAGMA busy_timeout = 5000;` on PDO instantiation.
23. **Unbounded Telemetry Growth**: Opportunistically prune telemetry older than 180 days in `AnonymizedInsightLogger`.
24. **Long-Lived Transactions on Analytical Reads**: Keep reporting queries in `InsightRepository` outside write transactions.
25. **Dynamic SQL Concatenation**: Parameterize all queries and whitelist column/bucket identifiers.

---

## 6. Sessions, Edge Caching & CSRF
26. **Lazy Session Initialization**: Do not call `session_start()` on anonymous public GET routes to enable Cloudflare/LiteSpeed edge caching.
27. **Complete Session Expiration on Logout**: Destroy session data and expire the session cookie with a past timestamp in `SessionManager::destroy()`.
28. **Multi-Tab CSRF Token Stability**: Use session-bound tokens via `ensureCsrfToken()`.
29. **`hash_equals()` Type Safety**: Validate token input is string before passing to `hash_equals()`.
30. **Session Fixation Defense**: Execute `session_regenerate_id(true)` upon successful admin login.

---

## 7. HTTP Routing & Security Headers
31. **Trailing Slash Redirect Query String Preservation**: Prevent duplicate query strings (`?a=1?b=2`).
32. **Native HTTP `HEAD` Support**: Map `HEAD` method requests directly to `GET` action handlers.
33. **Consecutive Slash Path Normalization**: Normalize `//admin_insights` to `/admin_insights` in `Request::getUri()`.
34. **Clickjacking & CSP `frame-ancestors`**: Enforce `frame-ancestors 'self'` in Content-Security-Policy.
35. **Route Slug Regex Boundaries**: Tighten router slug regex to `[a-zA-Z0-9_-]+` and enforce `realpath()` boundary checks.

---

## 8. Rate Limiting & Proxy IP Security
36. **Client IP Spoofing**: Validate `REMOTE_ADDR` against trusted reverse proxy subnets before reading `X-Forwarded-For` or `CF-Connecting-IP`.
37. **Non-Blocking Rate Limit Storage**: Use atomic non-blocking read/write operations on rate limit JSON files.
38. **Admin Login Rate Limiting**: Enforce 5 attempts / 5 mins per IP on `/admin_insights`.
39. **Directory Access Denial**: Block direct web access to `var/` via `.htaccess`.

---

## 9. PDF Generation (Dompdf) & Asset Processing
40. **Dompdf CSS Layout Crashes**: Strip complex CSS (`position: fixed`, `calc()`, `grid`) via `HtmlSanitizer::sanitizeTableHtml()`.
41. **Base64 Image Bomb**: Clamp chart data to 5MB and logo uploads to 2MB.
42. **Image EXIF & Polyglot Neutralization**: Re-encode uploaded images via GD (`imagecreatefromstring`).
43. **Font Cache Directory Isolation**: Explicitly configure `fontDir`, `fontCache`, and `tempDir` in `PdfGeneratorService` to `var/cache/`.
44. **RFC 5987 Download Headers**: Set `filename*=` headers on PDF downloads.

---

## 10. CSV Export & Spreadsheet Security
45. **CSV Formula Injection (DDE)**: Prefix non-numeric formula cells starting with `=`, `+`, `-`, `@`, `\t`, `\r`, `|` with a single quote `'`.
46. **UTF-8 Byte Order Mark**: Output `\xEF\xBB\xBF` at the start of CSV streams for Excel INR (`₹`) compatibility.

---

## 11. Frontend Pipeline (Vite 5 & Tailwind CSS v4)
47. **Production Vite Dev Socket Bypass**: Skip `fsockopen` socket checks in production environment.
48. **Missing Manifest Fallback**: Return empty string instead of raw `.ts` file paths in production.
49. **Tailwind JIT Class Purging**: Avoid dynamic class concatenation (`bg-{{ color }}-500`); use static mapping tables in Twig.
50. **Data Island XSS Mitigation**: Use `json_island` Twig filter enforcing `JSON_HEX_*` flags on embedded JSON tags.
51. **PWA Manifest vs Hashed Asset Caching**: Never apply `immutable` caching to root `/manifest.json`; scope 1-year immutable caching strictly to `/dist/assets/*`.
52. **Vite HMR Content-Security-Policy**: Include `http://localhost:5173` and `ws://localhost:5173` in CSP headers during local development to prevent WebSocket and script blocking.
53. **Dompdf CSS 2.1 Isolation**: Never inject modern Vite/Tailwind stylesheets into Dompdf; keep PDF styling strictly within `PdfReportStylesheet.php`.

---

## 12. Financial Math Engine & Parity
51. **0-Year Singularity Guard**: Return baseline balance vectors without compounding loops when `years = 0`.
52. **SWP Negative Compounding Prevention**: Clamp balance to 0.00 upon fund depletion.
53. **Floating Point Rounding Parity**: Use `Number.EPSILON` in TypeScript and verify exact parity via `tests/parity_check.php`.
54. **Budget 2024 LTCG Tax Sync**: Synchronize ₹1.25L / 12.5% tax thresholds across PHP and TypeScript.
55. **Step-Up SIP Float Overflow**: Enforce upper input bounds on annual step-up percentages.

---

## 13. Content Management, Parsedown & SEO
56. **Markdown Safe Mode**: Enforce `Parsedown::setSafeMode(true)` to escape embedded HTML script tags.
57. **JSON-LD Script Breakout Protection**: Encode structured data with `SchemaHelper::JSON_FLAGS`.
58. **XML Sitemap Encoding**: Clean UTF-8 XML declaration without trailing/leading whitespace.
59. **Canonical URL Uniformity**: Centralized `SiteConfig` resolving canonical host and protocol.
60. **Unified Redirect Priority**: Server rewrite -> Trailing slash middleware -> Router actions.

---

## 14. AI Assistant Execution & Developer Guardrails
61. **Zero Debug Echoes**: Never use `echo`, `print_r()`, or `var_dump()` inside controllers or services; log via `error_log()`.
62. **CLI-Only Migrations**: Execute database migrations strictly via `bin/migrate`; no web migration triggers.
63. **Dual-Maintenance Parity Check**: Mandate running `php tests/parity_check.php` on math engine edits.
64. **Explicit Environment Discovery**: Standardize `.env` traversal across parent release directories.
65. **PHPStan Level 8+ Strictness**: Ensure zero static analysis errors and PSR-12 code style compliance.
