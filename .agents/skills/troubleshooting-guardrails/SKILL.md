---
name: troubleshooting-guardrails
description: Triggers whenever debugging, fixing errors, resolving 500 status codes, or handling broken layouts.
---

# Troubleshooting Guardrails (Junior Model Instructions)

As an AI executing tasks on this project, you must follow strict safety protocols when debugging to prevent causing more damage or technical debt. 

### 1. The "Do No Harm" Protocol
When fixing a bug (like a 500 internal server error or a syntax issue), **apply surgical, single-line fixes**. 
- **Action:** DO NOT rewrite entire files, classes, or architectures to fix a minor bug. If the bug appears to require a massive structural change, **pause immediately** and ask the user to escalate to a higher-tier model for review.

### 2. Proper Logging & Debugging
- **Action:** DO NOT use `echo`, `print_r()`, or `var_dump()` to debug variables. This will corrupt the HTTP response, break API payloads, and destroy Twig rendering.
- **Action:** ALWAYS use `error_log()` and check the terminal logs (`php -S` server output) to inspect variable states.

### 3. Missing Database Tables (SQLite)
If you encounter a `500 Internal Server Error: SQLSTATE[HY000]: General error: 1 no such table: main.user_calculations`:
- **Action:** DO NOT write PHP scripts or modify `DatabaseManager` to automatically create tables.
- **Action:** Execute the database migrations via CLI: `php bin/migrate` (or `php migrate.php`). Database schema migrations are executed strictly via CLI for environment security; there are no administrative web migration endpoints.

### 4. Broken Tailwind Styles in New Twig Files
If a newly created Twig layout has broken CSS/styling:
- **Action:** Ensure the Twig layout includes Vite assets via the built-in Twig functions, or extends `layouts/base.twig` / `admin/layouts/admin.twig`:
```twig
{{ vite_client() }}
{{ vite_css('resources/js/app.ts') }}
```
and before `</body>`:
```twig
<script type="module" src="{{ vite_asset('resources/js/app.ts') }}"></script>
```

### 5. Always Verify First
- **Action:** Before stating you have fixed an issue, you must verify the fix locally by running a `curl` request against the affected route, or by running the PHPUnit tests (`composer check-all`). Do not blindly assume your fix worked.
