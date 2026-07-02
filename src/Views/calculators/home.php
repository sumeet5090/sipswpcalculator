<?php

declare(strict_types=1);

// ── SECURITY: Start session for CSRF protection ──
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include the helper functions
require_once __DIR__ . '/../../../functions.php';

// Default values.
$default_sip = 10000;
$default_years = 20;
$default_rate = 12;
$default_stepup = 10;
$default_swp_withdrawal = 50000;
$default_swp_stepup = 5;
$default_swp_years = 10;
$default_lumpsum = 0;
$default_swp_rate = 8;

$lumpsum = $lumpsum ?? $default_lumpsum;
$swp_rate = $swp_rate ?? $default_swp_rate;

$page_config = $page_config ?? [
    'title' => 'SIP SWP Calculator 2026: Plan SIP to SWP Mutual Fund Income',
    'meta_desc' => 'Free online SIP SWP calculator. Plan your transition from SIP accumulation to SWP retirement income in India. Model lumpsums, annual step-ups, and inflation adjustments.',
];

ob_start();
?>
<meta name="keywords"
    content="SIP SWP Calculator, SIP to SWP Calculator, SIP and SWP Calculator, Mutual Fund SIP SWP Calculator, SWP Calculator, SWP Planner, Step-Up SIP Calculator, Investment Planner, Wealth Creation, Retirement Planning, SIP vs SWP, Tax-Efficient Withdrawals, SIP Calculator India, Mutual Fund Return Calculator, Indian Mutual Funds">
<link rel="alternate" hreflang="en-IN" href="https://sipswpcalculator.com/">
<link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/">

<?php
$faqRepository = new \Core\FaqRepository();
$homeFaqs = $faqRepository->getByTag('home');
$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(function ($faq) {
        return [
            '@type' => 'Question',
            'name' => $faq['q'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['a']
            ]
        ];
    }, $homeFaqs)
];
?>
<!-- FAQPage Schema for capturing rich snippets in Google Search -->
<script type="application/ld+json">
<?= json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<!-- ════════════════════════════════════════════════════════════════════
         AI-CITATION-OPTIMIZED STRUCTURED DATA (JSON-LD)
         Designed for extraction by Gemini, Perplexity, ChatGPT, and
         traditional search engines. Uses @id graph linking, potentialAction,
         sameAs entity grounding, and information-dense descriptions.
         ════════════════════════════════════════════════════════════════════ -->

<!-- 1. SoftwareApplication — Primary Tool Identity -->
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@id": "https://sipswpcalculator.com/#calculator",
      "@type": "SoftwareApplication",
      "name": "Advanced SIP & SWP Calculator",
      "alternateName": ["SIP Calculator", "SWP Calculator", "Step-Up SIP Calculator", "Mutual Fund SIP Calculator", "SIP Return Calculator", "SWP Retirement Planner"],
      "url": "https://sipswpcalculator.com/",
      "applicationCategory": "FinanceApplication",
      "applicationSubCategory": "Investment Calculator",
      "operatingSystem": "Web",
      "availableOnDevice": ["Desktop", "Mobile", "Tablet"],
      "inLanguage": ["en-IN"],
      "isAccessibleForFree": true,
      "offers": [
        {
          "@type": "Offer",
          "price": "0",
          "priceCurrency": "INR",
          "availability": "https://schema.org/InStock"
        }
      ],
      "description": "Advanced SIP & SWP Calculator with step-up (top-up) compounding for Indian mutual fund investment planning. Uses the future value of annuity due formula: FV = P × [((1+r)^n - 1) / r] × (1+r), where P = monthly investment, r = monthly rate of return, n = total months. Supports annual step-up from 0-50%, expected returns of 1-30%, and Systematic Withdrawal Plans (SWP) with capital gains tax projections. Outputs interactive growth charts, yearly breakdown tables, CSV exports, and branded PDF reports.",
      "featureList": [
        "Core Attribute: Step-up compounding (annual top-up 0-50%)",
        "SWP Retirement Planner with step-up withdrawals",
        "Month-by-month simulation (more accurate than simple annuity)",
        "Interactive Chart.js growth visualization",
        "Yearly breakdown table with corpus, interest, and withdrawal tracking",
        "Locked to INR (₹) formatting for Indian mutual funds",
        "CSV export with full yearly data",
        "Branded PDF report generation with custom logos",
        "Shareable URL with pre-filled parameters",
        "SIP vs FD vs PPF comparison data"
      ],
      "screenshot": "https://sipswpcalculator.com/assets/og-image-main.jpg",
      "image": "https://sipswpcalculator.com/assets/og-image-main.jpg",
      "datePublished": "2024-12-01",
      "dateModified": "2026-07-01",
      "softwareVersion": "3.0",
      "releaseNotes": "Locked default currency to INR (₹), updated Indian tax integrations, and optimized performance.",
      "author": {
        "@id": "https://sipswpcalculator.com/#author"
      },
      "publisher": {
        "@id": "https://sipswpcalculator.com/#organization"
      },
      "potentialAction": {
        "@type": "UseAction",
        "name": "Calculate SIP & SWP Returns",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "https://sipswpcalculator.com/?sip={sip}&years={years}&rate={rate}&stepup={stepup}",
          "actionPlatform": ["http://schema.org/DesktopWebPlatform", "http://schema.org/MobileWebPlatform"],
          "inLanguage": "en"
        },
        "object": {
          "@type": "FinancialProduct",
          "name": "Systematic Investment Plan (SIP)"
        }
      },
      "sameAs": [
        "https://en.wikipedia.org/wiki/Systematic_investment_plan",
        "https://www.wikidata.org/wiki/Q7662882"
      ],
      "about": [
        {
          "@type": "DefinedTerm",
          "name": "Systematic Investment Plan (SIP)",
          "description": "A method of investing a fixed sum regularly in mutual funds, using dollar cost averaging and compounding to build wealth over time.",
          "sameAs": "https://en.wikipedia.org/wiki/Systematic_investment_plan"
        },
        {
          "@type": "DefinedTerm",
          "name": "Systematic Withdrawal Plan (SWP)",
          "description": "A facility that allows investors to withdraw a fixed or step-up amount from their mutual fund investment at regular intervals, commonly used for retirement income.",
          "sameAs": "https://en.wikipedia.org/wiki/Systematic_withdrawal_plan"
        },
        {
          "@type": "DefinedTerm",
          "name": "Step-Up SIP",
          "description": "A variant of SIP where the monthly investment amount is increased by a fixed percentage (typically 5-20%) every year to match salary growth and outpace inflation."
        },
        {
          "@type": "DefinedTerm",
          "name": "4% Rule (Safe Withdrawal Rate)",
          "description": "A retirement planning guideline suggesting that withdrawing 4% of a portfolio annually provides a sustainable income for 30+ years. Originally proposed by William Bengen in 1994.",
          "sameAs": "https://en.wikipedia.org/wiki/Trinity_study"
        }
      ],
      "citation": {
        "@type": "CreativeWork",
        "name": "AMFI — SIP Methodology and Mutual Fund Industry Data",
        "url": "https://www.amfiindia.com/",
        "publisher": {
          "@type": "Organization",
          "name": "Association of Mutual Funds (AMFI)"
        }
      }
    }
    </script>

<!-- 2. FinancialProduct — Detailed Investment Parameters -->
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@id": "https://sipswpcalculator.com/#financialproduct",
      "@type": "FinancialProduct",
      "name": "SIP & SWP Investment Planning Tool",
      "alternateName": "Mutual Fund SIP Return Calculator",
      "description": "Free financial planning tool for calculating Systematic Investment Plan (SIP) returns with annual step-up compounding and Systematic Withdrawal Plan (SWP) retirement income projections. Supports monthly SIP amounts from ₹1 to ₹10,000,00, investment periods from 1 to 50 years, expected annual returns from 1% to 70%, and annual step-up percentages from 0% to 50%. SWP module supports monthly withdrawals with inflation-adjusted step-up from 0% to 20%. Uses month-by-month simulation with compound interest for accuracy superior to simple annuity formulas. Verified against standard mutual fund industry methodology. Diversified equity funds have historically delivered approximately 10-15% CAGR over 20-year rolling periods. SIP inflows globally have exceeded billions of dollars per month.",
      "url": "https://sipswpcalculator.com/",
      "provider": {
        "@id": "https://sipswpcalculator.com/#organization"
      },
      "category": "Investment Planning Tool",
      "feesAndCommissionsSpecification": "Completely free — no fees, commissions, or registration required",
      "areaServed": {
        "@type": "Place",
        "name": "Worldwide"
      },
      "availableChannel": {
        "@type": "ServiceChannel",
        "serviceUrl": "https://sipswpcalculator.com/",
        "availableLanguage": "English"
      },
      "termsOfService": "https://sipswpcalculator.com/terms",
      "broker": {
        "@id": "https://sipswpcalculator.com/#author"
      },
      "currenciesAccepted": "INR",
      "sameAs": [
        "https://en.wikipedia.org/wiki/Systematic_investment_plan",
        "https://en.wikipedia.org/wiki/Systematic_withdrawal_plan"
      ]
    }
    </script>

<!-- 3. WebSite — Site Identity with Search Action -->
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@id": "https://sipswpcalculator.com/#website",
      "@type": "WebSite",
      "name": "Advanced SIP & SWP Calculator",
      "alternateName": "sipswpcalculator.com",
      "url": "https://sipswpcalculator.com/",
      "description": "Free online SIP calculator with step-up compounding and SWP retirement planner. Uses month-by-month simulation based on standard mutual fund methodology. Features interactive charts, yearly breakdown tables, CSV exports, and branded PDF reports. Trusted by investors and financial advisors worldwide.",
      "inLanguage": "en",
      "publisher": {
        "@id": "https://sipswpcalculator.com/#organization"
      },
      "creator": {
        "@id": "https://sipswpcalculator.com/#author"
      },
      "datePublished": "2024-12-01",
      "dateModified": "2026-03-02",
      "copyrightYear": 2024,
      "copyrightHolder": {
        "@id": "https://sipswpcalculator.com/#author"
      },
      "potentialAction": {
        "@type": "SearchAction",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "https://sipswpcalculator.com/?sip={sip_amount}"
        },
        "query-input": "required name=sip_amount"
      }
    }
    </script>

<!-- 4. Organization — Publisher Identity with EEAT signals -->
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@id": "https://sipswpcalculator.com/#organization",
      "@type": "Organization",
      "name": "SIP & SWP Calculator",
      "legalName": "SIP SWP Calculator",
      "url": "https://sipswpcalculator.com/",
      "logo": {
        "@type": "ImageObject",
        "url": "https://sipswpcalculator.com/assets/favicon.svg",
        "width": 512,
        "height": 512
      },
      "description": "Publisher of free, open-access financial planning tools for SIP and SWP calculations. All formulas verified against standard mutual fund industry methodology. Used by individual investors, financial advisors, and global investors for mutual fund return projections.",
      "foundingDate": "2024-12-01",
      "founder": {
        "@id": "https://sipswpcalculator.com/#author"
      },
      "contactPoint": {
        "@type": "ContactPoint",
        "email": "help@sipswpcalculator.com",
        "contactType": "customer service",
        "availableLanguage": "English"
      },
      "sameAs": [
        "https://www.linkedin.com/in/sumeet-boga/"
      ],
      "knowsAbout": [
        "Systematic Investment Plan (SIP)",
        "Systematic Withdrawal Plan (SWP)",
        "Mutual Fund Investing",
        "Step-Up SIP Compounding",
        "Retirement Planning",
        "Dollar Cost Averaging",
        "4% Safe Withdrawal Rate",
        "Capital Gains Tax on Mutual Funds",
        "Global Mutual Fund Investment"
      ]
    }
    </script>

<!-- 5. Person — Author/Expert Identity (EEAT) -->
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@id": "https://sipswpcalculator.com/#author",
      "@type": "Person",
      "name": "Sumeet Boga",
      "url": "https://sipswpcalculator.com/about",
      "image": "https://sipswpcalculator.com/assets/sumeet-boga-56.jpg",
      "jobTitle": "Software Engineer & Finance Enthusiast",
      "description": "Creator of the Advanced SIP & SWP Calculator. Software developer specializing in financial planning tools with expertise in mutual fund return calculations, step-up compounding methodology, and tax-efficient withdrawal planning.",
      "sameAs": [
        "https://www.linkedin.com/in/sumeet-boga/"
      ],
      "knowsAbout": [
        "Systematic Investment Plans",
        "Systematic Withdrawal Plans",
        "Mutual Fund Taxation",
        "Financial Calculator Development",
        "Retirement Income Planning"
      ],
      "worksFor": {
        "@id": "https://sipswpcalculator.com/#organization"
      }
    }
    </script>

<!-- 6. HowTo — Detailed Calculator Usage Guide -->
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@id": "https://sipswpcalculator.com/#howto",
      "@type": "HowTo",
      "name": "How to Calculate SIP Returns with Step-Up Compounding",
      "description": "Step-by-step guide to using the Advanced SIP & SWP Calculator to project mutual fund returns, plan retirement withdrawals, and export branded PDF reports. The calculator uses month-by-month simulation with the annuity due formula for compounding accuracy.",
      "totalTime": "PT2M",
      "tool": {
        "@id": "https://sipswpcalculator.com/#calculator"
      },
      "step": [
        {
          "@type": "HowToStep",
          "position": 1,
          "name": "Enter SIP Investment Details",
          "text": "Set your monthly SIP amount (₹1 to ₹10,000,00), investment period (1-50 years), expected annual return rate (1-30%), and optional annual step-up percentage (0-50%). A 10% step-up is recommended to match average salary growth worldwide.",
          "url": "https://sipswpcalculator.com/#calculator-heading"
        },
        {
          "@type": "HowToStep",
          "position": 2,
          "name": "Configure SWP Retirement Withdrawals (Optional)",
          "text": "Enable the SWP toggle to plan systematic withdrawals. Set your desired monthly withdrawal amount, withdrawal period (1-50 years), and yearly step-up (0-20%) to combat inflation. The SWP phase begins automatically after your SIP period ends. The 4% rule suggests keeping annual withdrawals below 4% of your corpus for a sustainable 30-year retirement.",
          "url": "https://sipswpcalculator.com/#calculator-heading"
        },
        {
          "@type": "HowToStep",
          "position": 3,
          "name": "Analyze Results and Export Reports",
          "text": "View the interactive growth chart showing invested capital vs corpus value, review the yearly breakdown table with monthly SIP/SWP amounts, interest earned, and end-of-year corpus. Export results as CSV for spreadsheet analysis or generate a branded PDF report with your company logo, client name, and custom disclaimer for client presentations.",
          "url": "https://sipswpcalculator.com/#yearly-breakdown"
        }
      ]
    }
    </script>

<!-- PWA Manifest -->
<link rel="manifest" href="manifest.json">

<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">

<!-- Preconnect to font & CDN origins -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

<!-- Critical above-the-fold CSS inlined for instant FCP -->
<style>
    :root {
        --gradient-primary: linear-gradient(135deg, #059669, #047857);
        --glass-bg: rgba(255, 255, 255, .9);
        --glass-border: 1px solid rgba(255, 255, 255, .5);
        --glass-shadow: 0 8px 32px 0 rgba(5, 150, 105, .1);
        --color-bg: #f8fafc;
        --color-text-primary: #0f172a;
        --color-text-secondary: #64748b;
        --color-border: #e2e8f0
    }

    @font-face {
        font-family: 'Plus Jakarta Sans Fallback';
        src: local('Arial');
        size-adjust: 107%;
        ascent-override: 90%;
        descent-override: 22%;
        line-gap-override: 0%
    }

    body {
        background-color: var(--color-bg);
        font-family: 'Plus Jakarta Sans', 'Plus Jakarta Sans Fallback', 'Inter', Arial, sans-serif;
        color: var(--color-text-primary);
        line-height: 1.6;
        -webkit-font-smoothing: antialiased
    }

    .navbar-glass {
        background: rgba(255, 255, 255, .85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px)
    }

    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: var(--glass-border);
        box-shadow: var(--glass-shadow);
        border-radius: 1rem;
        position: relative
    }

    .text-gradient {
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-image: var(--gradient-primary)
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        color: var(--color-text-primary)
    }
</style>

<!-- Google Fonts: preload + swap (has size-matching fallback in critical CSS above) -->
<link rel="preload"
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" as="style"
    onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
</noscript>

<!-- Render-blocking CSS: loaded before first paint to prevent FOUC/CLS -->
<link rel="stylesheet" href="styles.css?v=<?= filemtime(__DIR__ . '/../../../styles.css') ?>">
<link rel="stylesheet" href="dist/tailwind.min.css?v=<?= filemtime(__DIR__ . '/../../../dist/tailwind.min.css') ?>">
<?php
$page_config['additional_head'] = ob_get_clean();
$active_page = 'index.php';
ob_start();
?>

<header class="relative mb-6 sm:mb-10 text-center">
    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-50 border border-emerald-100 mb-4">
        <span class="relative flex h-3 w-3">
            <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
        </span>
        <span class="text-sm font-semibold text-emerald-700 tracking-wide uppercase">Free Financial Planning
            Tool</span>
    </div>

    <h1 class="text-3xl sm:text-5xl md:text-7xl font-extrabold pb-3 tracking-tight">
        <span class="text-gradient">SIP SWP Calculator</span>
        <span class="text-gray-800">Plan SIP to SWP Mutual Fund Income</span>
    </h1>

    <!-- EEAT Trust Bar -->
    <div
        class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4 text-sm text-slate-500 mb-8 pb-6 border-b border-slate-200/60 max-w-3xl mx-auto">
        <a href="https://www.linkedin.com/in/sumeet-boga/" target="_blank" rel="noopener"
            class="flex items-center gap-2 hover:opacity-80 transition-opacity">
            <img src="/assets/sumeet-boga-56.jpg" alt="Sumeet Boga — Creator of SIP Calculator"
                class="w-8 h-8 rounded-full shadow-sm border border-emerald-100 object-cover" width="32" height="32"
                fetchpriority="high" decoding="async">
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
            Verified for Accuracy: June 2026
        </div>
    </div>

    <p class="text-base sm:text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed font-medium mb-4">
        This free <dfn><strong class="text-emerald-600">SIP SWP calculator</strong></dfn> helps you estimate
        your <strong>mutual fund returns</strong> with annual step-up (top-up) compounding and plan
        tax-efficient withdrawals via a <dfn><strong class="text-rose-500">Systematic Withdrawal Plan (SWP)</strong></dfn> for a steady retirement income. Use this tool to visualize your accumulation and withdrawal phases, compare scenarios, and download detailed reports.
    </p>


</header>

<main>
    <!-- Quick Answer Box for AI extraction / Featured Snippet -->
    <div id="quick-answer" class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-6 mb-8 max-w-2xl mx-auto"
        role="complementary" aria-label="Quick Answer">
        <p class="text-sm font-bold text-emerald-800 mb-1">Quick Answer</p>
        <p class="text-base text-gray-700"><strong>How much will a ₹10,000/month SIP grow in 20 years?</strong></p>
        <p class="text-sm text-gray-600 mt-1">At 12% annual returns with a 10% yearly step-up, a ₹10,000/month SIP will
            grow to approximately <strong class="text-emerald-700"><span class="dynamic-amount"
                    data-amount-inr="35400000"></span></strong> over 20 years. Total invested: <span
                class="dynamic-amount" data-amount-inr="6873000"></span>. Total gains: <span class="dynamic-amount"
                data-amount-inr="28527000"></span>.</p>
    </div>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "SIP Calculator 2026",
      "speakable": {
        "@type": "SpeakableSpecification",
        "cssSelector": ["#quick-answer"]
      },
      "url": "https://sipswpcalculator.com/"
    }
    </script>

    <section id="calculator-section" class="scroll-mt-32" style="scroll-margin-top: 120px;"
        aria-labelledby="calculator-heading">
        <h2 class="sr-only" id="calculator-heading">Calculate Your SIP & SWP Returns</h2>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left Column: Form -->
            <div class="lg:col-span-1 flex flex-col gap-4 lg:sticky lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto pb-4 lg:pr-4 custom-scrollbar"
                style="top: 120px;">
                <?php include __DIR__ . '/../components/calculator-form.php'; ?>
            </div> <!-- /left column -->

            <div class="lg:col-span-2 space-y-4">
                <?php include __DIR__ . '/../components/chart-visualization.php'; ?>
            </div>
        </div><!-- /3-col grid -->

        <!-- Full-width Yearly Breakdown -->
        <?php include __DIR__ . '/../components/yearly-breakdown-table.php'; ?>
    </section><!-- /calculator section -->

    <div class="mt-12 glass-card p-8">
        <?php include __DIR__ . '/../components/home-guide-content.php'; ?>
        <div class="mt-12 p-6 bg-slate-50 border border-slate-200 rounded-xl text-center max-w-2xl mx-auto">
            <p class="font-bold text-slate-800 text-lg mb-2">Cite This Calculator</p>
            <p class="text-slate-600 text-sm mb-4">Writing an article, blog post, or research paper? You can cite this
                tool using the following format.</p>
            <div
                class="bg-white p-4 border border-slate-200 rounded-lg text-left text-sm text-slate-700 font-mono overflow-x-auto select-all">
                Boga, S. (2026). Advanced SIP & SWP Calculator. sipswpcalculator.com. Retrieved from
                https://sipswpcalculator.com/
            </div>
        </div>
    </div>

</main>
<!-- PDF Generation Modal -->
<div id="pdfModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center p-4 z-50 hidden">
    <div
        class="bg-white rounded-xl shadow-2xl p-6 sm:p-8 w-full max-w-lg border border-gray-200 max-h-[90vh] overflow-y-auto">
        <h2 id="branded-pdf-report" class="text-2xl font-bold text-gray-800 mb-6">Create Branded PDF Report</h2>
        <form id="pdfForm" class="space-y-4">
            <!-- SECURITY: CSRF Token for PDF generation form -->
            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div>
                <label for="clientName" class="block text-sm font-medium mb-1 text-gray-600">Client Name</label>
                <input type="text" id="clientName" name="clientName" placeholder="e.g., John Doe"
                    class="w-full px-4 py-2 rounded-lg bg-gray-100 border-gray-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition">
            </div>
            <div>
                <label for="advisorName" class="block text-sm font-medium mb-1 text-gray-600">Your Name / Company
                    Name</label>
                <input type="text" id="advisorName" name="advisorName" placeholder="e.g., Jane Smith Financials"
                    class="w-full px-4 py-2 rounded-lg bg-gray-100 border-gray-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition">
            </div>
            <div>
                <label for="advisorLogo" class="block text-sm font-medium mb-1 text-gray-600">Your Logo</label>
                <input type="file" id="advisorLogo" name="advisorLogo" accept="image/png, image/jpeg"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
            </div>
            <div>
                <label for="customDisclaimer" class="block text-sm font-medium mb-1 text-gray-600">Custom Disclaimer
                    / Message</label>
                <textarea id="customDisclaimer" name="customDisclaimer" rows="3"
                    placeholder="e.g., This projection is based on the agreed-upon assumptions..."
                    class="w-full px-4 py-2 rounded-lg bg-gray-100 border-gray-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition"></textarea>
            </div>
            <div class="flex justify-end gap-4 pt-4">
                <button type="button" id="closePdfModalBtn"
                    class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition">Cancel</button>
                <button type="submit" id="generatePdfBtn"
                    class="px-6 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg transition">Download
                    PDF</button>
            </div>
        </form>
    </div>
</div>


<script defer src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/../layouts/layout.php';
?>