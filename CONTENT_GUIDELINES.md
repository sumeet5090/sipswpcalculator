# Content Authoring Guidelines

> **Why this file exists:** These constraints are *automatically enforced* by `tests/Integration/SeoMetadataValidatorTest.php`
> and will cause `composer check-all` to fail if violated. Read before editing any page metadata,
> `.md` frontmatter, or Twig page config blocks.

---

## 1. `<title>` Tag

| Rule | Value | Test line |
|---|---|---|
| Minimum length | **10 characters** | L116 |
| Maximum length | **65 characters** | L121 |
| Exactly one `<title>` per page | Required | L108 |

**Example (✅ valid — 57 chars):**
```
SIP Calculator — High Accuracy Step-Up Wealth Planner 2026
```

> [!IMPORTANT]
> Keep titles strictly between 50 and 65 characters. Titles exceeding 65 characters are truncated on Google desktop SERPs (~580px) and will cause `SeoMetadataValidatorTest::testSeoRulesOnAllRoutes` to fail immediately.

**Where to set it:**
- Homepage → `content/meta_pages.json` → `"title"`
- Calculator pages → `content/calculators/*.md` frontmatter → `title:`
- Blog posts → `content/blog/**/*.md` frontmatter → `title:`
- Static pages (about, faq, glossary) → `content/meta_pages.json` or Twig `{% set page_config = { 'title': '...' } %}`

---

## 2. `<meta name="description">` Tag

| Rule | Value | Test line |
|---|---|---|
| Minimum length | **40 characters** | L138 |
| Maximum length | **200 characters** | L143 |
| Exactly one meta description per page | Required | L129 |

**Where to set it:** Same sources as title above, using `meta_desc` key / `meta_desc:` frontmatter field.

---

## 3. `<h1>` Tag

| Rule | Value | Test line |
|---|---|---|
| Exactly one `<h1>` per page | Required | L98 |
| `<h1>` must not be empty | Required | L104 |

> **Note:** If your `<h1>` has multiple `<span>` children (like the homepage), that's fine — the DOM counts it as one `<h1>`. Do NOT add a second `<h1>` anywhere on the page.

---

## 4. Canonical URL

| Rule | Value | Test line |
|---|---|---|
| Exactly one `<link rel="canonical">` per page | Required | L150 |
| Must start with `https://sipswpcalculator.com` | Required | L159 |
| Must point exactly to canonical path | Required | L163 |
| Must not be empty | Required | L158 |

**How canonicals are generated (SSoT chain — do not hardcode):**
```text
APP_URL (.env)
    → SiteConfig::getBaseUrl()
    → ViewRenderer → Twig global: site_url
    → base.twig: canonical_url = page_config.canonical|default(site_url ~ current_uri)
```

**Do NOT add** a `canonical:` field in `.md` frontmatter or Twig `page_config` unless the auto-generated URL would be wrong (e.g., a redirect alias).

---

## 5. OpenGraph & Author Tags

| Tag | Requirement | Rule / Value | Test line |
|---|---|---|---|
| `og:title` | Required | Must be present and non-empty | L170–174 |
| `og:description`| Required | Must be present, 40–200 characters | L176–190 |
| `og:url` | Required | Must **exactly match** the canonical URL | L192–200 |
| `og:type` | Required | `article` on blog posts, `website` on calculators/pages | L202–211 |
| `author` | Required | Must be present and non-empty (`Sumeet Boga` by default) | L213–218 |

---

## 6. Robots Directives

| Route Type | Directive Required | Test line |
|---|---|---|
| Indexable Pages (Home, Calculators, Blog, Static) | `max-image-preview:large, max-snippet:-1` | L228–229 |
| Legal / Utility Pages (`/privacy`, `/terms`) | `noindex, nofollow` | L226 |

---

## 7. JSON-LD Structured Data

| Rule | Value | Test line |
|---|---|---|
| At least one `<script type="application/ld+json">` per page | Required | L234 |
| Each JSON-LD block must be **valid JSON** | Required | L243 |
| Each JSON-LD block must have `@context: "https://schema.org"` | Required | L247 |
| Each JSON-LD block must have `@type` | Required | L252 |

### Mandatory Dual-Schema Rule for Calculators
Every calculator page (routes matching `'calculator'` or `'/'`) **MUST** include both schemas:
1. `SoftwareApplication` (with `operatingSystem`, `applicationCategory`, and `offers`)
2. `FAQPage` (with structured `mainEntity` questions and answers)

*Enforced automatically in `SeoMetadataValidatorTest.php` L257–268.*

---

## 8. Twig `{% set page_config %}` Canonical Limitation

When writing inline `page_config` blocks in Twig templates (e.g., `about.twig`, `faq.twig`), you **cannot** reference Twig variables or globals inside a `{% set %}` block — it is evaluated at parse time before globals are available.

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

---

## Quick Reference Summary

```text
title:     10–65 chars   (enforced by SeoMetadataValidatorTest L121)
meta_desc: 40–200 chars  (enforced by SeoMetadataValidatorTest L143)
h1:        exactly 1 per page, non-empty
canonical: auto-generated — do NOT hardcode in page_config or .md frontmatter
og:url:    auto-synced to canonical — do NOT override independently
og:type:   'article' for resource posts, 'website' for all others
robots:    'max-image-preview:large, max-snippet:-1' on indexable; 'noindex' on legal
JSON-LD:   valid JSON + @context + @type; dual SoftwareApplication + FAQPage on calculators
```
