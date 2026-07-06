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
- **Action:** Inform the user to visit `/admin_insights/migrate` in their browser to explicitly run the schema updates. The database migration workflow is manual/explicit by design.

### 4. Broken Tailwind Styles in New Twig Files
If a newly created Twig layout has broken CSS/styling:
- **Action:** Check if the layout includes the Vite frontend assets. 
- You must include the following in the `<head>` of your new Twig layouts, or extend a layout (`admin/layouts/admin.twig`) that already includes it:
```html
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/resources/js/app.js"></script>
```

### 5. Always Verify First
- **Action:** Before stating you have fixed an issue, you must verify the fix locally by running a `curl` request against the affected route, or by running the PHPUnit tests (`composer check-all`). Do not blindly assume your fix worked.
