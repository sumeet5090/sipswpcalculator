<?php declare(strict_types=1);
require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | SIP & SWP Calculator</title>
    <meta name="description"
        content="Learn about the team behind SIP & SWP Calculator — a free step-up SIP planner and retirement income tool. Developed by Sumeet Boga, Software Developer & Finance Specialist.">
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
      "jobTitle": "Software Developer & Finance Specialist",
      "description": "Creator of SIP & SWP Calculator. Passionate about making financial planning accessible through technology.",
      "knowsAbout": ["Systematic Investment Plans", "Systematic Withdrawal Plans", "Mutual Fund Investing", "Financial Planning", "Tax-Efficient Investing", "Web Development"],
      "sameAs": ["https://www.linkedin.com/in/sumeetboga/"]
    }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased"
    style="background-image: var(--gradient-surface); background-attachment: fixed;">
    <?php include 'navbar.php'; ?>

    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <header class="mb-12 text-center">
            <h1 class="text-4xl font-extrabold pb-2">
                <span class="text-gradient">About SIP Calculator</span>
            </h1>
            <p class="text-lg text-gray-500 font-medium mt-2">Making financial planning accessible to everyone</p>
        </header>

        <main class="glass-card p-8 sm:p-12">
            <article class="prose prose-lg max-w-none text-gray-600 prose-headings:text-indigo-900">

                <h2>Our Mission</h2>
                <p>We believe that <strong>everyone deserves access to professional-grade financial planning
                        tools</strong> — without paywalls, account requirements, or hidden agendas. SIP & SWP Calculator
                    was built to help investors worldwide — including India, USA, UK, and beyond — visualize the power
                    of systematic investing
                    and plan their retirement income with confidence.</p>

                <h2>About the Creator</h2>
                <div
                    class="flex flex-col sm:flex-row items-start gap-6 not-prose bg-indigo-50/50 p-6 rounded-xl border border-indigo-100">
                    <div class="flex-shrink-0">
                        <div
                            class="w-20 h-20 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                            SB</div>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800 mb-1">Sumeet Boga</h3>
                        <p class="text-sm text-indigo-600 font-medium mb-3">Software Developer & Finance Specialist</p>
                        <p class="text-gray-600 text-sm leading-relaxed">Sumeet combines software engineering expertise
                            with a deep passion for personal finance. Having experienced firsthand the lack of
                            transparent, free financial planning tools in India, he built SIP Calculator to bridge the
                            gap between complex financial mathematics and accessible, visual planning.</p>
                        <p class="text-gray-600 text-sm leading-relaxed mt-2">His focus areas include mutual fund
                            investing, tax-efficient retirement planning, and building tools that automate financial
                            decision-making.</p>
                    </div>
                </div>

                <h2>What Makes Our Calculator Different</h2>
                <ul>
                    <li><strong>Step-Up SIP</strong> — We're one of the few calculators that model annual step-up
                        (top-up) SIPs, which is how real investors actually invest as their income grows.</li>
                    <li><strong>SIP + SWP in One Tool</strong> — Seamlessly model both the accumulation phase (SIP) and
                        the distribution phase (SWP retirement income) in a single simulation.</li>
                    <li><strong>Step-Up SWP</strong> — Adjust your withdrawals for inflation with annual step-up,
                        something most calculators don't support.</li>
                    <li><strong>Branded PDF Reports</strong> — Financial advisors can generate professional PDF reports
                        with their name and client details.</li>
                    <li><strong>100% Free, No Sign-Up</strong> — No account required, no data collection, no ads. Your
                        financial data stays in your browser.</li>
                </ul>

                <h2>Our Commitment to Accuracy</h2>
                <p>All financial data, tax rates, and formulas on this site are verified against official sources:</p>
                <ul>
                    <li><a href="https://www.amfiindia.com/" target="_blank" rel="noopener"
                            class="text-indigo-600 hover:underline">AMFI India</a> — Mutual fund industry data and SIP
                        methodology</li>
                    <li><a href="https://www.sebi.gov.in/" target="_blank" rel="noopener"
                            class="text-indigo-600 hover:underline">SEBI</a> — Regulatory framework for mutual funds
                    </li>
                    <li><a href="https://incometaxindia.gov.in/" target="_blank" rel="noopener"
                            class="text-indigo-600 hover:underline">Income Tax India</a> — Capital gains tax rates
                        (LTCG/STCG)</li>
                </ul>
                <p><strong>Last verified:</strong> <time datetime="2026-02-25">February 2026</time></p>

                <h2>Disclaimer</h2>
                <p>This calculator is for <strong>educational and illustrative purposes only</strong>. The projections
                    do not account for exit loads, expense ratios, or extreme market volatility. We are not registered
                    financial advisors. Please consult a SEBI-registered advisor before making investment decisions.</p>

                <h2>Contact Us</h2>
                <p>Have questions, feedback, or found an error? Reach us at <a href="mailto:help@sipswpcalculator.com"
                        class="text-indigo-600 hover:underline">help@sipswpcalculator.com</a>.</p>

                <div class="bg-emerald-50 p-6 rounded-xl border border-emerald-200 not-prose mt-8">
                    <p class="text-lg font-bold text-emerald-800 mb-2">Start Planning Your Financial Future</p>
                    <p class="text-gray-600 mb-4">Our free SIP & SWP calculator helps you visualize exactly how your
                        investments grow and plan for a comfortable retirement.</p>
                    <a href="/"
                        class="inline-block bg-emerald-600 text-white font-bold py-3 px-6 rounded-xl hover:bg-emerald-700 transition-colors">Launch
                        Calculator →</a>
                </div>
            </article>
        </main>

        <?php include 'footer.php'; ?>
    </div>
</body>

</html>