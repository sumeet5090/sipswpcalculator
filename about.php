<?php declare(strict_types=1);
require_once __DIR__ . '/functions.php'; ?>
<?php
$page_config = [
    'title' => 'About Us | SIP & SWP Calculator',
    'meta_desc' => 'Learn about the team behind SIP & SWP Calculator — a free step-up SIP planner and retirement income tool built by Sumeet Boga.',
];

ob_start();
?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Person",
      "name": "Sumeet Boga",
      "url": "https://sipswpcalculator.com/about",
      "jobTitle": "Software Engineer & Finance Enthusiast",
      "description": "Creator of SIP & SWP Calculator. Passionate about making financial planning accessible through technology.",
      "knowsAbout": ["Systematic Investment Plans", "Systematic Withdrawal Plans", "Mutual Fund Investing", "Financial Planning", "Tax-Efficient Investing", "Web Development"],
      "sameAs": ["https://www.linkedin.com/in/sumeet-boga/"]
    }
    </script>
    <style>
        /* Fallback Styles because Tailwind compilation is not active */
        .profile-wrapper { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1.5rem; }
        @media (min-width: 640px) { .profile-wrapper { flex-direction: row; text-align: left; } }
        
        .profile-img { width: 6rem; height: 6rem; flex-shrink: 0; object-fit: cover; border-radius: 9999px; }
        @media (min-width: 640px) { .profile-img { width: 7rem; height: 7rem; } }
        
        .feature-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        @media (min-width: 640px) { .feature-grid { grid-template-columns: repeat(2, 1fr); } }

        .btn-calculator { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; background-color: #059669; color: white; border-radius: 0.75rem; font-weight: 600; font-size: 0.875rem; transition: background-color 0.2s; text-decoration: none; }
        .btn-calculator:hover { background-color: #047857; }

        .hero-title { font-size: 2.25rem; line-height: 1.2; }
        @media (min-width: 640px) { .hero-title { font-size: 3rem; } }
        @media (min-width: 768px) { .hero-title { font-size: 3.75rem; } }
    </style>
<?php
$page_config['additional_head'] = ob_get_clean();
$active_page = 'about.php';
require_once __DIR__ . '/includes/layout-top.php';
?>

<!-- Breadcrumbs -->
<nav class="max-w-3xl mx-auto px-4 pt-6 pb-2 text-sm text-slate-500">
    <a href="/" class="hover:text-emerald-600 transition-colors">Home</a>
    <span class="mx-2">›</span>
    <span class="text-slate-800 font-medium">About</span>
</nav>

<!-- Page Header -->
<header class="text-center mb-10 sm:mb-14">
    <h1 class="hero-title font-extrabold tracking-tight pb-2">
        About <span class="text-emerald-600">Us</span>
    </h1>
    <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed">
        The story behind SIP &amp; SWP Calculator — who built it, why, and what we stand for.
    </p>
</header>

<div class="max-w-3xl mx-auto pb-20 px-4">

    <!-- Creator Card -->
    <div class="profile-wrapper p-6 bg-white rounded-2xl border border-slate-200 mb-10">
        <a href="https://www.linkedin.com/in/sumeet-boga/" target="_blank" rel="noopener" class="flex-shrink-0">
            <img src="/assets/sumeet-boga.jpg" alt="Sumeet Boga — Creator of SIP & SWP Calculator"
                class="profile-img rounded-full object-cover border-2 border-emerald-100 shadow-sm"
                width="112" height="112">
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800">Sumeet Boga</h2>
            <p class="text-sm text-emerald-600 font-semibold mb-2">Software Engineer &amp; Finance Enthusiast</p>
            <p class="text-sm text-slate-500 leading-relaxed">
                Sumeet combines software engineering expertise with a deep passion for personal finance.
                Having experienced firsthand the lack of transparent, free financial planning tools, he built
                this calculator to bridge the gap between complex financial mathematics and accessible planning.
            </p>
            <a href="https://www.linkedin.com/in/sumeet-boga/" target="_blank" rel="noopener"
                class="inline-flex items-center gap-1.5 mt-3 text-xs font-semibold text-[#0077b5] hover:underline">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                    <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                </svg>
                Connect on LinkedIn
            </a>
        </div>
    </div>

    <!-- Divider -->
    <div class="border-t border-slate-100 mb-10"></div>

    <!-- Our Mission -->
    <section class="mb-10">
        <h2 class="text-xl font-bold text-slate-800 mb-3">Our Mission</h2>
        <p class="text-slate-600 leading-relaxed">
            SIP &amp; SWP Calculator was built to help investors worldwide — including India, USA, UK, and beyond —
            visualize the power of systematic investing and plan their retirement income with confidence.
            We believe that <strong class="text-slate-700">everyone deserves access to professional-grade financial
            planning tools</strong> — without paywalls, account requirements, or hidden agendas.
        </p>
    </section>

    <!-- What Makes Us Different -->
    <section class="mb-10">
        <h2 class="text-xl font-bold text-slate-800 mb-5">What Makes Our Calculator Different</h2>
        <div class="feature-grid">

            <div class="flex gap-4 p-4 bg-white rounded-xl border border-slate-200">
                <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 text-sm mb-1">Step-Up SIP</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">One of the few calculators that model annual step-up SIPs — how real investors actually invest as their income grows.</p>
                </div>
            </div>

            <div class="flex gap-4 p-4 bg-white rounded-xl border border-slate-200">
                <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 text-sm mb-1">SIP + SWP in One</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Seamlessly model both the accumulation phase (SIP) and the distribution phase (SWP) in a single simulation.</p>
                </div>
            </div>

            <div class="flex gap-4 p-4 bg-white rounded-xl border border-slate-200">
                <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 text-sm mb-1">Step-Up SWP</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Adjust withdrawals for inflation with annual step-up — something most calculators don't support.</p>
                </div>
            </div>

            <div class="flex gap-4 p-4 bg-white rounded-xl border border-slate-200">
                <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 text-sm mb-1">100% Free &amp; Private</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">No account required, no data collection, no ads. Your financial data stays securely in your browser.</p>
                </div>
            </div>

        </div>
    </section>

    <!-- Divider -->
    <div class="border-t border-slate-100 mb-10"></div>

    <!-- Commitment to Accuracy + Disclaimer -->
    <section class="mb-10">
        <h2 class="text-xl font-bold text-slate-800 mb-5">Accuracy &amp; Disclaimer</h2>
        <div class="feature-grid">

            <div class="p-5 bg-white rounded-xl border border-slate-200">
                <div class="flex items-center gap-2 mb-3">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <h3 class="font-semibold text-slate-800 text-sm">Commitment to Accuracy</h3>
                </div>
                <p class="text-xs text-slate-500 mb-3">All financial data, tax rates, and formulas are verified against official sources:</p>
                <ul class="text-xs text-slate-500 space-y-1.5 font-medium">
                    <li>• <a href="https://www.amfiindia.com/" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:underline">AMFI India</a></li>
                    <li>• <a href="https://www.sebi.gov.in/" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:underline">SEBI</a></li>
                    <li>• <a href="https://incometaxindia.gov.in/" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:underline">Income Tax India</a></li>
                </ul>
                <p class="text-xs text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg inline-block font-semibold mt-3">Last verified: March 2026</p>
            </div>

            <div class="p-5 bg-white rounded-xl border border-slate-200">
                <div class="flex items-center gap-2 mb-3">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <h3 class="font-semibold text-slate-800 text-sm">Disclaimer</h3>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    This calculator is for <strong class="text-slate-700">educational and illustrative purposes only</strong>.
                    Projections do not account for exit loads, expense ratios, or extreme market volatility.
                    We are not registered financial advisors. Please consult a SEBI-registered advisor before making investment decisions.
                </p>
                <div class="mt-4 pt-3 border-t border-slate-100">
                    <p class="text-xs text-slate-500">Contact: <a href="mailto:help@sipswpcalculator.com" class="text-emerald-600 hover:underline font-medium">help@sipswpcalculator.com</a></p>
                </div>
            </div>

        </div>
    </section>

    <!-- Quick Links -->
    <div class="flex flex-wrap gap-3">
        <a href="/#calculator-section" class="btn-calculator">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Open Calculator
        </a>
        <a href="/faq.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 hover:border-emerald-500 hover:text-emerald-600 transition-colors">
            View FAQ
        </a>
        <a href="/glossary.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 hover:border-emerald-500 hover:text-emerald-600 transition-colors">
            Glossary
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
    </div>
</body>

</html>