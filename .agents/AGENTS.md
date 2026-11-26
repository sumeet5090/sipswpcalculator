# Workspace Instructions & Engineering Rules

Adhere to the following protocols for all development, architectural planning, design, and implementation tasks.

---

### 1. Development & Quality Assurance Standards
* **Engineering Standards:** Write code that is Object-Oriented, modular, scalable, and robust. Strictly follow **SOLID** principles, **Postel's Law** (Robustness Principle), and **Command-Query Separation (CQS)**. Avoid hardcoding and ad-hoc workarounds.
* **Validation Protocol:** Before planning or implementing, assess if the request aligns with industry best practices. If a request deviates from best practices, flag it to the user with a brief explanation before proceeding.
* **Zero-Ambiguity & Zero-Guesswork Mandate:** Under no circumstances should an agent guess, extrapolate, or assume requirements on the fly. If any question, confusion, trade-off, or fork in the road arises during the planning stage, the agent MUST immediately pause and approach the human admin for full clarification. In the implementation stage, there must be absolute zero ambiguity.
* **Hook Compliance & Test Gates:** Before finalizing any task, ensure the implementation passes the complete quality verification pipeline (`npm run typecheck && npm run build`, `php tests/parity_check.php`, and `composer check-all`). If a build fails due to linting, typing, or test errors, prioritize fixing the issue before submitting the code.
* **Avoid Hallucinations:** Ensure code is clean, structured, and strictly functional. Never invent phantom classes, un-routed endpoints, or un-imported dependencies.
* **Strict Git Commit & Branch Governance (Human-Only Commits & Merges):** Under NO circumstances should AI agents execute `git commit`, `git merge`, `git push`, or switch/merge git branches (`git checkout stage`, `git checkout main`, etc.). The human administrator will inspect, stage, and commit all changes regularly and manage branch merges to target branches independently. AI agents must strictly limit their activities to code creation, editing, refactoring, documentation updates, and running verification tests (`npm run build`, `php tests/parity_check.php`, `composer check-all`). Leave the working tree uncommitted for human review and commit.

---

### 2. Design System & Theme Governance (Understated Executive FinTech Theme)
* **Design Philosophy (Linear & Stripe Aesthetic with Warm Amber & Glassmorphism):** The application adheres to an understated executive fintech aesthetic modeled directly after Linear and Stripe:
  - **Luminous Interactive Workspace & Authentic Glassmorphism:** Sliders, input cards, and stage panels use frosted glass surfaces (`bg-white/85` to `bg-white/95`, `backdrop-blur-md`, and `backdrop-blur-xl` for modal backdrops), precision micro-borders (`border-slate-200/90`, `border-slate-300`), inner specular highlights (`box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.9)`), and dark high-contrast typography (`text-slate-900`, `text-slate-700`) for zero visual fatigue and optimal readability.
  - **Disciplined Dual-Engine Palette (Emerald Wealth Accumulation & Warm Amber Golden Harvest):**
    - **Stage 1 (SIP / Wealth Accumulation):** Institutional Emerald (`emerald-600` / `emerald-700`, badges `bg-emerald-100/90 text-emerald-900 border-emerald-300/80`) representing organic wealth generation.
    - **Stage Transition Bridge:** Multi-stop gradient connector (`from-emerald-600 via-teal-600 to-amber-600`) symbolizing capital maturity transition.
    - **Stage 2 (SWP / Retirement Cashflow & Payouts):** Warm Amber & Golden Harvest (`amber-600` / `amber-700` with high-contrast `amber-900` text on light cards, badges `bg-amber-100/90 text-amber-900 border-amber-300/80`), symbolizing retirement distribution, financial freedom, and living payouts. **Generic blue is completely eliminated.**
    - **Sovereign Instruments (Fixed Deposit):** Sovereign Indigo (`indigo-600` / `indigo-700`, `text-indigo-950`).
  - **Disciplined Focal Anchors & Conversion CTAs:** Heavy, oversaturated color masses are avoided in favor of precise, high-authority architectural surfaces:
    1. **Hero Header Canopies:** Luminous Architectural Daylight Canopy (`bg-gradient-to-b from-slate-50 via-white to-slate-50/60 text-slate-900 border-b border-slate-200/90 shadow-subtle`) with soft ambient emerald aura (`bg-emerald-500/8 blur-3xl`), commanding dark slate-900 typography, and a solid emerald `[Free Excel Model]` conversion CTA (`bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold`).
    2. **Strategy Blueprint Toolbars:** Crisp Daylight Console Strips (`bg-slate-50/80 backdrop-blur-md border border-slate-200/90 shadow-card relative overflow-hidden`) with frosted white persona cards (`bg-white/90 backdrop-blur-sm border-2 border-slate-200/80 text-slate-800`), muted slate tenure chips (`bg-slate-100/90 text-slate-700 border border-slate-200/80`), and solid emerald active states (`bg-emerald-700 text-white shadow-card`).
    3. **Primary Climax KPI Anchor (Card 4 — Final Corpus):** Polished Executive Daylight Anchor (`bg-gradient-to-br from-white via-emerald-50/40 to-teal-50/30 text-slate-900 border-2 border-emerald-600 shadow-card`) with crisp high-contrast dark numerals (`text-slate-900 font-black`), emerald-700 subtext, and a solid emerald pill badge (`bg-emerald-600 text-white`).
    4. **Precision Ledger Headers & Unified Section Cadence:** Data table headers use clean `bg-slate-50 text-slate-700 border-b border-slate-200 text-micro tracking-wider`, while below-the-fold sections follow a calm alternating rhythm of pure white (`bg-white`) and soft architectural neutral slate (`bg-slate-50/80 border-y border-slate-200/70`).
    5. **Architectural Footers & Floating Action Docks:** Clean daylight grounded surfaces (`bg-slate-100` / `bg-white/98 border-t-2 border-slate-200/90 text-slate-900`) with solid emerald fulfillment triggers.
  - **Prohibited Tokens:** Heavy dark surface blocks (`bg-slate-900`, `bg-slate-950`), arbitrary black vignettes, and untyped gray classes (`bg-gray-900`, `*-gray-*`) remain strictly prohibited on card and header surfaces; all neutrals must use Cool Slate (`slate-*`).
* **Ambient Lighting System:** Subtle ambient lighting balances soft luminous washes (`emerald-500/12`, `amber-500/12`) behind frosted surfaces without cluttering high-contrast typography.

---

### 3. SEO, Localization & Content Strategy
* **Strategic Optimization:** Maintain strict SEO friendliness. Use semantic HTML, descriptive page titles, accurate meta tags, and structured schemas. Page `<title>` tags must strictly remain between **10 and 65 characters** (enforced by `tests/Integration/SeoMetadataValidatorTest.php`).
* **Zero Self-Served Ratings:** Self-served `AggregateRating` schemas are strictly forbidden to comply with Google Search Central guidelines and protect rich snippet eligibility.
* **Mandatory Schemas:** Every calculator route outputs `SoftwareApplication`, `FAQPage`, `BreadcrumbList`, and `HowTo` via `SchemaFactory.php` and `HomeSchemaBuilder.php`.
* **Localization:** Content must be tailored for the **Indian audience**. Use Indian numbering conventions (Lakhs, Crores, e.g. `₹1,00,000`, `₹1.50 Lakh`, `₹2.50 Crore`) and terminology standard in the Indian mutual fund industry (SIP, SWP, Step-up, Lumpsum, CAGR, SWR, LTCG Section 112A).
* **Authenticity:** Ensure content is professional and avoids sounding "AI-generated." It should be precise, clear, and authoritative.

---

### 4. Product Mission & Vision
* **Core Mission:** Provide a reliable, high-accuracy SIP and SWP calculator backed by precise, transparent financial data. Supplement this with high-value, actionable resources on Indian wealth planning.
* **Product Vision:** Establish this platform as the leading Indian mutual fund investment planner. The calculators are the anchor; all peripheral features must support or enhance this core utility.

---

### 5. End-to-End Workflow & Governance Protocols (5-Stage Lifecycle)
Every development task must follow this exact sequential 5-stage lifecycle:

```
┌─────────────────────────────────────────────────────────────────────────┐
│ Stage 1: Context Anchoring (Zero-Exploration Protocol)                  │
│ • Consult ARCHITECTURE_MAP.md & activeContext.md first                  │
└────────────────────────────────────┬────────────────────────────────────┘
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ Stage 2: Planning & Zero-Guesswork Protocol                             │
│ • Draft technical implementation_plan.md artifact                       │
│ • If trade-offs or ambiguities arise: PAUSE & ASK HUMAN ADMIN           │
│ • Wait for explicit user approval before touching code                  │
└────────────────────────────────────┬────────────────────────────────────┘
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ Stage 3: Implementation Protocol                                        │
│ • Execute plan surgically adhering to SOLID, DRY, POLA, and CQS         │
│ • Zero console/echo debugging in runtime classes (use error_log)        │
└────────────────────────────────────┬────────────────────────────────────┘
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ Stage 4: Deterministic Tiered Verification                              │
│ • Tier 1: Local asset build & parity checks                             │
│ • Tier 2: Local server curl / integration verification                  │
│ • Tier 3: Full quality gate (composer check-all: PHPStan L8 + PHPUnit)  │
└────────────────────────────────────┬────────────────────────────────────┘
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ Stage 5: Anti-Drift Documentation & Handoff Ledger                      │
│ • Update README.md, ARCHITECTURE_MAP.md, activeContext.md               │
│ • NEVER commit or merge branches; hand off to human admin for commit    │
└─────────────────────────────────────────────────────────────────────────┘
```

#### Detailed Stage Rules:
* **Stage 1 (Context Anchoring - Zero-Exploration Protocol):** Before performing broad directory searches or greps across the repository, ALWAYS consult `.agents/ARCHITECTURE_MAP.md` to identify exact route, controller, Twig template, and TypeScript engine paths. Check `.agents/memory/activeContext.md` and `.agents/memory/systemPatterns.md` for current project state and architectural invariants.
* **Stage 2 (Planning & Zero-Guesswork Protocol):** Always start by generating an exhaustive `implementation_plan.md` artifact detailing the architecture, files touched, minute technical specifications, and rationale. Wait for explicit user approval before executing any code changes.
  - *Zero-Guesswork Mandate:* If any question, confusion, trade-off, or architectural fork in the road arises, the agent MUST immediately pause and approach the human admin for full clarification. Never guess, extrapolate, or assume requirements on the fly.
* **Stage 3 (Implementation Protocol):** Follow the approved `implementation_plan.md` precisely. Do not rewrite architectures or introduce unapproved patterns. Strictly respect the "Trace the Data Flow" protocol: trace parameters back to their origins (`routes.php`, `InvestmentInputs`) before modifying routing or rendering.
* **Stage 4 (Tiered Verification):**
  - *Tier 1 (Fast-Path Local Build & Parity):* Run `npm run typecheck && npm run build` and `php tests/parity_check.php` (verifying all 119 mathematical parity tests).
  - *Tier 2 (Local Server Curl Verification):* Run a `curl` command on the local PHP development server for any specific routes modified (or `vendor/bin/phpunit tests/Integration` with `BypassSandbox: true` when testing ephemeral loopback server ports).
  - *Tier 3 (Pre-Commit Full Gate):* Run `composer check-all` (validating PHPStan Level 8 across 285 files, PSR-12 coding standard, and all 957 PHPUnit tests [743 Unit + 214 Integration] with 18,722 assertions).
* **Stage 5 (Documentation Maintenance & Anti-Drift Protocol):** Whenever any code, route, class, template, or configuration changes, immediately perform an audit of referencing documentation:
  1. Check and update `README.md` to reflect new/modified behavior.
  2. Check and update `.agents/ARCHITECTURE_MAP.md` if any route, controller, template, driver, engine, or subsystem coordinate shifted.
  3. Search and rectify any obsolete references or deprecated patterns in `.agents/memory/`, `.agents/skills/`, and `docs/`. Never leave stale file paths or outdated architectural guidelines in the repository.
  4. Update `.agents/memory/activeContext.md` with the latest task state and changes made, ensuring seamless continuity across subsequent conversations.
  5. **Human-Only Commit & Merge Handoff:** NEVER run `git commit`, `git merge`, or `git push`. Hand off the verified, uncommitted changes directly to the human developer, who will review, commit to the appropriate branch, and merge branches as needed.

---

### 6. Architectural & Technical Invariants
* **Calculator Computation Paradigm:** Never replace the web frontend's native `MathEngine.ts` computations with AJAX/API calls. The instant, zero-latency feedback loop on slider manipulation is a core product experience. The calculation parity between PHP and TS is strictly enforced via `tests/parity_check.php`, mitigating dual-maintenance risks. API endpoints for calculations should only be developed as headless services for external consumption (e.g., mobile apps).
* **Client Owns the Component:** To ensure zero-latency feedback (60fps) and eliminate duplicated rendering logic, PHP (`Twig`) serves only SEO metadata, base layouts, and empty skeleton containers. TypeScript calculates and hydrates dynamic DOM elements on page load and controls all live updates.
* **Data Island Pattern:** NEVER inject global JavaScript state (`window.mode = 'sip'`) or scatter state across HTML attributes. State hydration is performed strictly via `<script type="application/json" id="calculator-app-state">`.
* **Principle of Least Astonishment (POLA):** Ensure components and variables behave in a way that least surprises the next developer (or AI). If a variable's natural assumption is structural (e.g., `category = calculator`), do not overload it to mean semantic domains (e.g., `category = growth`). Refactor the codebase to match the natural assumption rather than writing workarounds.
* **Explicit is Better Than Implicit:** Avoid magic behavior like conditionally building URLs based on the presence of a slash, or inferring types from loose string matching. Data structures and configurations must be explicit and decoupled.

---

### 7. Architectural Planning & Pre-Implementation Discussions
* **Multi-Angle & Minute-Detail Planning:** Prior to implementing any feature, refactor, or architectural change, create a deeply technical, comprehensive `implementation_plan.md`. The plan must be evaluated from every conceivable angle: performance & Core Web Vitals (INP, CLS, LCP), software engineering industry standards (SOLID, DRY, POLA, immutability, type safety), security, accessibility (WAI-ARIA, keyboard navigation), SEO & schema implications, SSR-client hydration parity, memory leaks, and DOM budget. Every minute detail (e.g., exact CSS token classes, DOM IDs, event handler signatures, data structures, math formulas) must be planned out upfront.
* **Deep Technical Rigor:** The `implementation_plan.md` artifact must never contain hand-waving or superficial summaries. It must be written with high technical density: specifying exact file paths, line ranges, function and type signatures, state transition flows, concrete fallback strategies, failure modes, and deterministic CLI verification commands (`composer check-all`, localized unit tests, `curl` payloads, bundle analyzers).
* **Edge Cases & Friction Identification:** Proactively identify and present all potential edge cases, UX friction points, and technical limitations (e.g., SEO cannibalization, DOM bloat, schema conflicts, mobile viewport constraints) *before* writing code.

---

### 8. Junior Model Execution Constraints & Investigation Guardrails (The Handoff Protocol)
* **Strict Adherence:** If you are executing a task based on an existing `implementation_plan.md` (likely created by a higher/senior model), you must follow it precisely. Do not rewrite the architecture, ignore standard patterns, or invent new structural patterns unless explicitly instructed.
* **The "Pause and Escalate" Rule:** If you encounter a complex error (e.g., a 500 error or a broken layout) and the fix is not immediately obvious (like a simple syntax error), DO NOT invent architectural workarounds (e.g., implicitly running database schema updates on page load). Pause, state the error clearly, and ask the user to escalate back to the Senior Model for architectural review.
* **Debugging Protocol:** Never use `echo`, `print_r()`, or `var_dump()` inside Controllers or core classes to debug. This corrupts JSON API responses and Twig rendering, leading to cascading failures. Always use `error_log()` and check the terminal output.
* **Vendor & Third-Party Library Debugging Guardrail (Zero-Rabbit-Hole Protocol):**
  1. Under NO circumstances run iterative ad-hoc text search/grep loops across minified build chunks (`dist/assets/*.js`).
  2. When diagnosing a runtime exception or crash stemming from vendor libraries (e.g. Chart.js, Vite plugins), immediately inspect the unminified original source in `node_modules/` or run an isolated minimal reproduction in a Node.js script.
  3. Clearly communicate your exact hypothesis, the specific vendor file under investigation, and the immediate next command to the user before running low-level investigative commands.
* **Browser Subagent Fail-Fast & Single-Objective Guardrail (Anti-Hang Protocol):**
  1. **Strict Single-Objective:** Browser subagents must NEVER be dispatched with open-ended or multi-step compound mandates (e.g., combining DOM JavaScript evaluations, scrolling, slider dragging, network inspection, and screenshots in a single session). Such compound tasks trigger unresolvable idle-wait loops in Playwright/Puppeteer.
  2. **Bounded Execution:** Subagents must only be used for atomic, single-shot verification (e.g., *"Navigate to URL, capture screenshot of selector X, and immediately return"*). 
  3. **Deterministic First:** Always prefer deterministic, zero-latency CLI checks (`npm run build`, `curl -s http://localhost:8080/ | grep ...`, or minimal Node.js scripts) over spawning heavy headless browser agents. Only invoke browser subagents when visual pixel-level screenshot evidence is explicitly required.
* **Vite & Twig Awareness:** Always remember that CSS/JS is bundled via Vite. If creating a new Twig layout, you *must* ensure the Vite client and `app.js` module scripts are included, otherwise styles will break.
* **Compound Review Triage Protocol (Decomposition Protocol):** When a user or review critique bundles multiple disparate feedback points (e.g., CSS layout geometry, Canvas math/rendering crash, theme styling, table row limits), NEVER enter a monolithic investigation loop. Immediately decompose and triage into 3 distinct execution streams:
  1. **Stream 1 (Fast-Path Deterministic UI):** Pure CSS layout and token adjustments (`self-start` sticky alignment, range slider accent colors, default table row milestone limits). Plan and execute without delay.
  2. **Stream 2 (Invariant Translation):** Translate any out-of-spec suggestions (e.g., dark cards) using the *Design Review Intent Translation Matrix*. Eliminate cognitive debate or hesitation.
  3. **Stream 3 (Deep Runtime Isolation):** For stateful library crashes (e.g. Chart.js fill geometry, `_metasets` collisions), isolate the problem in a minimal reproduction or typed contract inspection before planning, avoiding multi-subsystem paralysis.

---

### 9. Environment Security & Modifications
* **Zero AI Git Commits or Branch Merging Mandate:** AI agents are strictly forbidden from committing changes on any branch (`git commit`), merging branches (`git merge`), or pushing (`git push`). The human developer personally and regularly commits changes to branches and merges branches to target branches as needed.
* **No Direct Production SSH Modifications:** Under absolutely NO circumstances should you connect to a production or staging server via SSH to execute database schema alterations (e.g., `ALTER TABLE`), modify production files directly, or perform "quick fixes". All changes MUST be committed via Git and deployed through the standard CI/CD pipeline. No exceptions.
* **CLI-Only Schema Migrations:** Database schema migrations are executed strictly via CLI (`php bin/migrate`) for environment security; there are no administrative web migration endpoints.
* **SQLite WAL Mode & Concurrency:** All SQLite PDO connections must configure `PRAGMA journal_mode = WAL;`, `PRAGMA synchronous = NORMAL;`, and `PRAGMA busy_timeout = 5000;`, eliminating concurrency lock contention during traffic surges.

---

### 10. Core Software Engineering Mental Models
To ensure maintainability, scalability, and stability, all code must adhere to the following core mental models and design guidelines:

* **Robustness Principle (Postel’s Law):** "Be conservative in what you do, be liberal in what you accept from others." Be strict with data you send (e.g., API responses) and forgiving with data you receive (e.g., webhook listeners, user input).
* **The Pit of Success:** Design architectures, APIs, and primitives so that the path of least resistance is also the correct, most secure, and most performant path. It should be physically harder for a junior developer or AI model to do the wrong thing than it is to do the right thing.
* **Chesterton’s Fence:** Do not modify or destroy existing code, configuration, or infrastructure until you fully understand its original purpose. Investigate historical context before refactoring seemingly useless code.
* **Hollywood Principle:** "Don't call us, we'll call you." Use dependency injection or event-driven models instead of low-level components polling high-level ones.
* **The Boy Scout Rule:** Always leave the codebase cleaner than you found it. If you spot unused variables or bad indentation while adding a feature, clean it up in the same commit.
* **Command-Query Separation (CQS):** A function should either perform an action (Command) or return data (Query), but never both simultaneously. Avoid side effects in data retrieval functions.
* **KISS (Keep It Simple, Stupid):** Avoid over-engineering. Prefer simple functions over complex abstractions if the simple approach satisfies the requirement.
* **YAGNI (You Aren't Gonna Need It):** Build exactly what satisfies the current specifications. Do not build features or abstractions based on the assumption they might be needed in the future.
* **DRY (Don't Repeat Yourself):** Ensure business logic has a single, unambiguous representation. Abstract duplicated logic into shared helpers.

---

### 11. Sandbox Execution Protocols
* **Try Sandboxed First:** Attempt to run every command in Standard Sandbox Mode (`BypassSandbox: false`) first.
* **BypassSandbox for Socket Binding Only:** Only switch to `BypassSandbox: true` for commands that require local network socket binding (such as `IntegrationTestCase` dev server on localhost or `composer check-all`). Keep the `toolSummary` identical between runs.
* **Minimize Unsandboxed Work:** Only elevate the specific command that needs socket access. All file reading, editing, and standalone scripts must remain strictly in standard sandbox mode.

---

### 12. Clickable File Links Mandate
* Whenever referencing files in responses or documentation, you MUST create clickable links using standard markdown with the `file://` scheme (e.g., `[AGENTS.md](file:///Users/sumeetboga/projects/sipswpcalculator/.agents/AGENTS.md)` or `[AGENTS.md](file:///Users/sumeetboga/projects/sipswpcalculator/.agents/AGENTS.md#L10-L20)` to link to specific lines).
* Use basenames for readability (e.g. `[AGENTS.md](file:///...)` rather than full path in link text).
