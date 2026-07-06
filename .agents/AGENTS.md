# Workspace Instructions

Adhere to the following protocols for all development, design, and implementation tasks.

### 1. Development & Quality Assurance
* **Implementation Workflow (Planning Mode):** Always start by generating an `implementation_plan.md` detailing the architecture, files touched, and rationale. Wait for explicit user approval before executing any code changes. Retrospect code against global rules immediately after execution. Run a single browser validation test upon task completion if necessary.
* **Engineering Standards:** Write code that is Object-Oriented, modular, scalable, and robust. Strictly follow **SOLID** principles. Avoid hardcoding and workarounds.
* **Validation Protocol:** Before implementation, assess if the request aligns with industry best practices. If a request deviates from best practices, flag it to the user with a brief explanation before proceeding.
* **Hook Compliance:** Before finalizing any task, ensure the implementation passes the local composer check-all suite. If a build fails due to linting or test errors, prioritize fixing the issue before submitting the code.
* **Avoid Hallucinations:** Ensure code is clean, structured, and strictly functional.

### 2. Design & Styling
* **Premium Aesthetic:** Maintain a premium, professional look and feel. Use **Tailwind CSS** for all styling, ensuring high consistency in fonts, layouts, and component design across the entire application.
* **Consistency:** All new components must align with existing premium design themes.

### 3. SEO & Content Strategy
* **Strategic Optimization:** Maintain strict SEO friendliness. Use semantic HTML, descriptive page titles, and accurate meta tags.
* **Localization:** Content must be tailored for the **Indian audience**. Use terminology and language conventions standard in the Indian finance industry.
* **Authenticity:** Ensure content is professional and avoids sounding "AI-generated." It should be precise, clear, and authoritative.

### 4. Product Mission & Vision
* **Core Mission:** Provide a reliable, high-accuracy SIP and SWP calculator backed by precise data. Supplement this with high-value, actionable resources on Indian finance.
* **Product Vision:** Establish this platform as the leading free Indian mutual fund investment planner. The calculators are the anchor; all peripheral features must support or enhance this core utility.

### 5. Workflow & Context Governance
* **Approval Gate:** Always outline your implementation plan and obtain explicit user approval before beginning any coding or design task.
* **Context Management:** Monitor the volume of data and the length of the conversation trail. If the conversation reaches a critical threshold or if the code complexity exceeds what a smaller model can reliably parse, immediately flag this to the user.
* **Model-Centric Modularity:** Prioritize high modularity and extreme componentization. If a task requires complexity that risks model failure, suggest a refactor into smaller, isolated components first.
* **Documentation Maintenance:** At the end of every successful task or implementation, update the `README.md` file to reflect any changes, new features, or architectural adjustments made. Keep the documentation as clean and current as the code itself.
* **"Trace the Data Flow" Protocol:** Before modifying any routing or rendering strategy, strictly trace the parameters back to their origin (e.g., `routes.php`) to understand their actual runtime values. Do not assume their intent based solely on variable names.

### 6. Communication & Efficiency Protocol
* **Zero-Padding Policy:** Avoid conversational filler, apologies, or clarifying your understanding of the context. Provide technical output directly.
* **Explicit Context Anchoring:** The user should provide explicit file targets and constraints in the prompt. If a requirement is still ambiguous, the agent must not guess, but instead state: "Constraint check: [Topic]. Please confirm my assumption."
* **State Compression:** If the conversation exceeds 20 turns, stop and summarize the project state into a concise context block before proceeding.
* **Local Curl Verification:** Before declaring a task complete, mandate running a `curl` command on the local PHP server for any specific routes modified. Grep for expected DOM elements or JSON payloads to definitively verify the rendering success.

### 7. Architectural & Technical Decisions
* **Calculator Computation Paradigm:** Never replace the web frontend's native `MathEngine.js` computations with AJAX/API calls. The instant, zero-latency feedback loop on slider manipulation is a core product experience. The calculation parity between PHP and JS is strictly enforced via `tests/parity_check.php`, mitigating dual-maintenance risks. API endpoints for calculations should only be developed as headless services for external consumption (e.g., mobile apps).
* **Principle of Least Astonishment (POLA):** Ensure components and variables behave in a way that least surprises the next developer (or AI). If a variable's natural assumption is structural (e.g., `category = calculator`), do not overload it to mean semantic domains (e.g., `category = growth`). Refactor the codebase to match the natural assumption rather than writing workarounds.
* **Explicit is Better Than Implicit:** Avoid magic behavior like conditionally building URLs based on the presence of a slash, or inferring types from loose string matching. Data structures and configurations must be explicit and decoupled.
### 8. Architectural Planning & Pre-Implementation Discussions
* **Exhaustive Planning:** Prior to implementing any non-trivial feature or architecture change, engage the user in a rigorous pre-implementation discussion within the `implementation_plan.md` artifact.
* **Edge Cases & Friction Identification:** Proactively identify and present all potential edge cases, UX friction points, and technical limitations (e.g., SEO implications, DOM bloat, schema conflicts) *before* writing code.
* **Modern Industry Standards Alignment:** Always compare the proposed solution against the latest modern software industry standards. Advise the user if a proposed path deviates from these standards and explain the optimal architectural pattern to avoid future tech debt or hurdles.

### 9. Junior Model Execution Constraints (The Handoff Protocol)
* **Strict Adherence:** If you are executing a task based on an existing `implementation_plan.md` (likely created by a higher/senior model), you must follow it precisely. Do not rewrite the architecture, ignore standard patterns, or invent new structural patterns unless explicitly instructed.
* **The "Pause and Escalate" Rule:** If you encounter a complex error (e.g., a 500 error or a broken layout) and the fix is not immediately obvious (like a simple syntax error), DO NOT invent architectural workarounds (e.g., implicitly running database schema updates on page load). Pause, state the error clearly, and ask the user to escalate back to the Senior Model for architectural review.
* **Debugging Protocol:** Never use `echo`, `print_r()`, or `var_dump()` inside Controllers or core classes to debug. This corrupts JSON API responses and Twig rendering, leading to cascading failures. Always use `error_log()` and check the terminal output.
* **Vite & Twig Awareness:** Always remember that CSS/JS is bundled via Vite. If creating a new Twig layout, you *must* ensure the Vite client and `app.js` module scripts are included, otherwise styles will break.
