# Content Authoring Guidelines

> **Why this file exists:** These constraints are *automatically enforced* by `tests/Integration/SeoMetadataValidatorTest.php`
> and will cause `composer check-all` to fail if violated. Read before editing any page metadata,
> `.md` frontmatter, or Twig page config blocks.

---

## 1. `<title>` Tag

| Rule | Value | Test line |
|---|---|---|
| Minimum length | **10 characters** | L116 |
| Maximum length | **85 characters** | L120 |
| Exactly one `<title>` per page | Required | L108 |

**Example (✅ valid — 67 chars):**
```
SIP SWP Calculator Together — Step-Up, Lumpsum & SWP Planner 2026
```

**Where to set it:**
- Homepage → `content/meta_pages.json` → `"title"`
- Calculator pages → `content/calculators/*.md` frontmatter → `title:`
- Blog posts → `content/blog/**/*.md` frontmatter → `title:`
- Static pages (about, faq, glossary) → Twig `{% set page_config = { 'title': '...' } %}`

---

## 2. `<meta name="description">` Tag

| Rule | Value | Test line |
|---|---|---|
| Minimum length | **40 characters** | L137 |
| Maximum length | **200 characters** | L142 |
| Exactly one meta description per page | Required | L128 |

**Where to set it:** Same sources as title above, using `meta_desc` key / `meta_desc:` frontmatter field.

---

## 3. `<h1>` Tag

| Rule | Value | Test line |
|---|---|---|
| Exactly one `<h1>` per page | Required | L98 |
| `<h1>` must not be empty | Required | L104 |

> **Note:** If your `<h1>` has multiple `<span>` children (like the homepage), that's fine — the DOM
> counts it as one `<h1>`. Do NOT add a second `<h1>` anywhere on the page.

---

## 4. Canonical URL

| Rule | Value | Test line |
|---|---|---|
| Exactly one `<link rel="canonical">` per page | Required | L150 |
| Must start with `https://sipswpcalculator.com` | Required | L159 |
| Must not be empty | Required | L158 |

**How canonicals are generated (SSoT chain — do not hardcode):**

```
APP_URL (.env)
    → SiteConfig::getBaseUrl()
    → ViewRenderer → Twig global: site_url
    → base.twig: canonical_url = page_config.canonical|default(site_url ~ current_uri)
```

**Do NOT add** a `canonical:` field in `.md` frontmatter or Twig `page_config` unless the
auto-generated URL would be wrong (e.g., a redirect alias). When in doubt, omit it.

---

## 5. OpenGraph Tags

| Rule | Value | Test line |
|---|---|---|
| `og:title` — must be present and non-empty | Required | L166 |
| `og:description` — must be present and non-empty | Required | L172 |
| `og:url` — must **exactly match** the canonical URL | Required | L182 |

> `og:url` and `canonical` are kept in sync automatically in `base.twig` via the shared
> `canonical_url` variable. This works only if you don't override them independently.

---

## 6. JSON-LD Structured Data

| Rule | Value | Test line |
|---|---|---|
| At least one `<script type="application/ld+json">` per page | Required | L190 |
| Each JSON-LD block must be **valid JSON** | Required | L198 |
| Each JSON-LD block must have `@context: "https://schema.org"` | Required | L202 |
| Each JSON-LD block must have `@type` | Required | L207 |

> The test validates **every** `<script type="application/ld+json">` block on the page.
> If you add a new JSON-LD block anywhere, ensure it is valid JSON with `@context` and `@type`.

---

## 7. Twig `{% set page_config %}` Canonical Limitation

When writing inline `page_config` blocks in Twig templates (e.g., `about.twig`, `faq.twig`),
you **cannot** reference Twig variables or globals inside a `{% set %}` block — it is evaluated
at parse time before globals are available.

```twig
{# ❌ This will throw a Twig error: #}
{% set page_config = {
    'canonical': site_url ~ '/about'
} %}

{# ✅ Correct: omit canonical and let base.twig auto-generate it: #}
{% set page_config = {
    'title': 'About | SIP & SWP Calculator',
    'meta_desc': '...'
} %}
```

The only exception is `noindex` pages (privacy, terms) where the canonical doesn't matter for
indexing anyway.

---

## Quick Reference

```
title:     10–85 chars   (enforced by SeoMetadataValidatorTest)
meta_desc: 40–200 chars  (enforced by SeoMetadataValidatorTest)
h1:        exactly 1 per page, non-empty
canonical: auto-generated — do NOT hardcode in page_config or .md frontmatter
og:url:    auto-synced to canonical — do NOT override independently
JSON-LD:   valid JSON + @context + @type required on every block
```
