<?php declare(strict_types=1);
require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | SIP & SWP Calculator</title>
    <meta name="description"
        content="Learn about the team behind SIP & SWP Calculator — a free step-up SIP planner and retirement income tool. Built by Sumeet Boga, Software Engineer & Finance Enthusiast.">
    <link rel="canonical" href="https://sipswpcalculator.com/about">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sipswpcalculator.com/about">
    <meta property="og:title" content="About Us | SIP & SWP Calculator">
    <meta property="og:description"
        content="Meet the team behind SIP & SWP Calculator — a free step-up SIP planner and retirement income tool built by Sumeet Boga.">
    <meta property="og:image" content="https://sipswpcalculator.com/assets/og-image-main.jpg">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="styles.css?v=<?= filemtime(__DIR__ . '/styles.css') ?>">
    <link rel="stylesheet" href="dist/tailwind.min.css?v=<?= filemtime(__DIR__ . '/dist/tailwind.min.css') ?>">
    <script src="https://analytics.ahrefs.com/analytics.js" data-key="WiDGDiqV9F0xelXDCYFUfw" async></script>
    <link rel="icon" type="image/png" href="/assets/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

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
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased"
    style="background-image: var(--gradient-surface); background-attachment: fixed;">
    <?php include 'navbar.php'; ?>

    <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">
        <header class="relative mb-6 sm:mb-10 text-center">
            <div
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 border border-indigo-100 mb-4">
                <span class="relative flex h-3 w-3">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                </span>
                <span class="text-sm font-semibold text-indigo-700 tracking-wide uppercase">Our Story</span>
            </div>

            <h1 class="text-3xl sm:text-5xl md:text-7xl font-extrabold pb-3 tracking-tight">
                <span class="text-gradient">About SIP Calculator</span> <br>
                <span class="text-gray-800">Making Finance Accessible</span>
            </h1>

            <!-- EEAT Trust Bar -->
            <div
                class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4 text-sm text-slate-500 mb-8 pb-6 border-b border-slate-200/60 max-w-3xl mx-auto">
                <a href="https://www.linkedin.com/in/sumeet-boga/" target="_blank" rel="noopener"
                    class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                    <img src="/assets/sumeet-boga-56.jpg" alt="Sumeet Boga — Creator of SIP Calculator"
                        class="w-8 h-8 rounded-full shadow-sm border border-emerald-100 object-cover" width="32"
                        height="32" fetchpriority="high" decoding="async">
                    <span>By <strong class="text-slate-700">Sumeet Boga</strong>, Software Engineer &amp; Finance
                        Enthusiast</span>
                </a>
                <span class="hidden sm:inline text-slate-300">|</span>
                <div
                    class="flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold border border-emerald-100 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Updated: March 2026
                </div>
            </div>

            <p class="text-base sm:text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed font-medium mb-4">
                We believe that <strong class="text-indigo-600">everyone deserves access to professional-grade financial
                    planning tools</strong> — without paywalls, account requirements, or hidden agendas.
            </p>
        </header>

        <main class="space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8">
                    <!-- Our Mission Card -->
                    <div
                        class="relative z-10 bg-[var(--glass-bg)] p-6 sm:p-8 rounded-3xl border border-[var(--glass-border)] shadow-xl backdrop-blur-xl">
                        <h2 class="text-2xl font-bold text-indigo-900 mb-4">Our Mission</h2>
                        <p class="text-gray-600 leading-relaxed text-lg">
                            SIP & SWP Calculator was built to help investors worldwide — including India, USA, UK, and
                            beyond — visualize the power of systematic investing and plan their retirement income with
                            confidence.
                        </p>
                    </div>

                    <!-- What Makes Us Different Grid -->
                    <div
                        class="relative z-10 bg-[var(--glass-bg)] p-6 sm:p-8 rounded-3xl border border-[var(--glass-border)] shadow-xl backdrop-blur-xl">
                        <h2 class="text-2xl font-bold text-indigo-900 mb-6">What Makes Our Calculator Different</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="bg-indigo-50/50 p-5 rounded-2xl border border-indigo-100">
                                <h3 class="font-bold text-indigo-900 mb-2 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                    Step-Up SIP
                                </h3>
                                <p class="text-sm text-gray-600">We're one of the few calculators that model annual
                                    step-up (top-up) SIPs, which is how real investors actually invest as their income
                                    grows.</p>
                            </div>
                            <div class="bg-indigo-50/50 p-5 rounded-2xl border border-indigo-100">
                                <h3 class="font-bold text-indigo-900 mb-2 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2">
                                        </path>
                                    </svg>
                                    SIP + SWP in One
                                </h3>
                                <p class="text-sm text-gray-600">Seamlessly model both the accumulation phase (SIP) and
                                    the distribution phase (SWP retirement income) in a single simulation.</p>
                            </div>
                            <div class="bg-indigo-50/50 p-5 rounded-2xl border border-indigo-100">
                                <h3 class="font-bold text-indigo-900 mb-2 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Step-Up SWP
                                </h3>
                                <p class="text-sm text-gray-600">Adjust your withdrawals for inflation with annual
                                    step-up, something most calculators don't support.</p>
                            </div>
                            <div class="bg-indigo-50/50 p-5 rounded-2xl border border-indigo-100">
                                <h3 class="font-bold text-indigo-900 mb-2 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                        </path>
                                    </svg>
                                    100% Free
                                </h3>
                                <p class="text-sm text-gray-600">No account required, no data collection, no ads. Your
                                    financial data stays securely in your browser.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Commitment to Accuracy & Disclaimer -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div
                            class="relative z-10 bg-[var(--glass-bg)] p-6 rounded-3xl border border-[var(--glass-border)] shadow-xl backdrop-blur-xl">
                            <div class="flex items-center gap-2 mb-3">
                                <span
                                    class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                                <h3 class="font-bold text-emerald-800">Commitment to Accuracy</h3>
                            </div>
                            <p class="text-sm text-gray-600 mb-3">All financial data, tax rates, and formulas are
                                verified against official sources:</p>
                            <ul class="text-sm text-gray-500 space-y-2 mb-4 font-medium">
                                <li>• <a href="https://www.amfiindia.com/" target="_blank" rel="noopener noreferrer"
                                        class="text-indigo-600 hover:text-indigo-500 hover:underline">AMFI India</a>
                                </li>
                                <li>• <a href="https://www.sebi.gov.in/" target="_blank" rel="noopener noreferrer"
                                        class="text-indigo-600 hover:text-indigo-500 hover:underline">SEBI</a></li>
                                <li>• <a href="https://incometaxindia.gov.in/" target="_blank" rel="noopener noreferrer"
                                        class="text-indigo-600 hover:text-indigo-500 hover:underline">Income Tax
                                        India</a></li>
                            </ul>
                            <p
                                class="text-xs text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg inline-block font-bold">
                                Last verified: March 2026</p>
                        </div>

                        <div
                            class="relative z-10 bg-[var(--glass-bg)] p-6 rounded-3xl border border-[var(--glass-border)] shadow-xl backdrop-blur-xl">
                            <div class="flex items-center gap-2 mb-3">
                                <span
                                    class="flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                                <h3 class="font-bold text-slate-800">Disclaimer</h3>
                            </div>
                            <p class="text-sm text-slate-600">
                                This calculator is for <strong>educational and illustrative purposes only</strong>. The
                                projections do not account for exit loads, expense ratios, or extreme market volatility.
                                We are not registered financial advisors. Please consult a SEBI-registered advisor
                                before making investment decisions.
                            </p>
                            <div class="mt-4 pt-4 border-t border-slate-100">
                                <p class="text-sm text-slate-600">Contact: <a href="mailto:help@sipswpcalculator.com"
                                        class="text-indigo-600 hover:underline font-medium">help@sipswpcalculator.com</a>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: About the Creator -->
                <div class="lg:col-span-1">
                    <div
                        class="relative z-10 bg-[var(--glass-bg)] p-6 sm:p-8 rounded-3xl border border-indigo-100/50 shadow-xl backdrop-blur-xl lg:sticky lg:top-8 bg-gradient-to-b from-indigo-50/30 to-white/80">
                        <div class="text-center mb-6">
                            <a href="https://www.linkedin.com/in/sumeet-boga/" target="_blank" rel="noopener"
                                class="inline-block hover:scale-105 transition-transform duration-300">
                                <img src="/assets/sumeet-boga.jpg" alt="Sumeet Boga"
                                    class="w-32 h-32 rounded-full shadow-lg border-[3px] border-white object-cover mx-auto mb-4"
                                    width="128" height="128">
                            </a>
                            <h2 class="text-2xl font-bold text-slate-800 mb-1">Sumeet Boga</h2>
                            <p class="text-indigo-600 font-bold text-sm track-wide">Software Engineer & Finance
                                Enthusiast</p>
                        </div>

                        <div class="space-y-4 text-sm text-gray-600">
                            <p class="leading-relaxed">
                                Sumeet combines software engineering expertise with a deep passion for personal finance.
                                Having experienced firsthand the lack of transparent, free financial planning tools in
                                India, he built SIP Calculator to bridge the gap between complex financial mathematics
                                and accessible, visual planning.
                            </p>
                            <p class="leading-relaxed">
                                His focus areas include mutual fund investing, tax-efficient retirement planning, and
                                building tools that automate financial decision-making.
                            </p>
                        </div>

                        <a href="https://www.linkedin.com/in/sumeet-boga/" target="_blank" rel="noopener"
                            class="mt-8 flex items-center justify-center gap-2 w-full py-3.5 px-4 bg-[#0077b5] text-white font-bold rounded-xl hover:bg-[#005e93] transition-colors shadow-md">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path
                                    d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" />
                            </svg>
                            Connect on LinkedIn
                        </a>
                    </div>
                </div>

            </div>

            <!-- CTA -->
            <div
                class="mt-8 p-8 sm:p-12 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-3xl text-white shadow-xl text-center">
                <h2 class="text-3xl font-bold mb-4 text-white">Start Planning Your Financial Future</h2>
                <p class="mb-8 text-indigo-100 max-w-2xl mx-auto text-lg">Our free SIP & SWP calculator helps you
                    visualize exactly how your investments grow and plan for a comfortable retirement.</p>
                <a href="/"
                    class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 font-bold rounded-xl shadow-lg hover:bg-gray-50 transform hover:-translate-y-1 transition-all duration-200 text-lg">
                    Launch Calculator
                    <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        </main>

        <?php include 'footer.php'; ?>
    </div>
</body>

</html>