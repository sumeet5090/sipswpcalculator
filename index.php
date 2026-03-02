<?php
declare(strict_types=1);

// ── SECURITY: Start session for CSRF protection ──
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include the helper functions
require_once __DIR__ . '/functions.php';

// Default values.
$default_sip = 10000;
$default_years = 20;
$default_rate = 12;
$default_stepup = 10;
$default_swp_withdrawal = 50000;
$default_swp_stepup = 5;
$default_swp_years = 10;

// ── SECURITY: Helper to clamp numeric values to safe ranges ──
function clamp(float $val, float $min, float $max): float
{
    return max($min, min($max, $val));
}

// ── SECURITY: Validate POST requests (CSRF & Honeypot) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Honeypot check: If the hidden 'website_url' field is filled, it's a bot.
    if (!empty($_POST['website_url'])) {
        http_response_code(403);
        die('Forbidden: Automated request detected.');
    }
    // 2. CSRF Token check
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Forbidden: Invalid security token. Please reload the page and try again.');
    }
}

// Retrieve POST values with server-side validation & range clamping
$sip = isset($_POST['sip']) ? clamp((float) $_POST['sip'], 500, 1000000) : (float) $default_sip;
$years = isset($_POST['years']) ? (int) clamp((float) $_POST['years'], 1, 50) : $default_years;
$rate = isset($_POST['rate']) ? clamp((float) $_POST['rate'], 1, 30) : (float) $default_rate;
$stepup = isset($_POST['stepup']) ? clamp((float) $_POST['stepup'], 0, 50) : (float) $default_stepup;
$enable_swp = isset($_POST['enable_swp']) ? (bool) $_POST['enable_swp'] : false;
$swp_withdrawal = isset($_POST['swp_withdrawal']) ? clamp((float) $_POST['swp_withdrawal'], 0, 1000000) : (float) $default_swp_withdrawal;
$swp_stepup = isset($_POST['swp_stepup']) ? clamp((float) $_POST['swp_stepup'], 0, 20) : (float) $default_swp_stepup;
$swp_years_input = isset($_POST['swp_years']) ? (int) clamp((float) $_POST['swp_years'], 0, 50) : $default_swp_years;

// ── SECURITY: Whitelist allowed actions ──
$action = $_POST['action'] ?? '';
if (!in_array($action, ['', 'download_csv'], true)) {
    $action = '';
}

// SWP automatically starts the year immediately following the SIP period.
$swp_start = $years + 1;

$monthly_rate = $rate / 100 / 12;

// Simulation period: SIP years + user-defined SWP years.
$simulation_years = $years + $swp_years_input;

$net_balance = 0.0;
$cumulative_invested = 0.0;
$cumulative_withdrawals = 0.0;
$combined = [];

// Main loop
for ($y = 1; $y <= $simulation_years; $y++) {
    // Determine monthly SIP (if within SIP period).
    $monthly_sip = ($y <= $years) ? round($sip * pow(1 + $stepup / 100, $y - 1), 2) : 0.0;
    $annual_contribution = $monthly_sip * 12.0;

    // Determine monthly SWP (if SWP has started).
    $monthly_swp = ($y >= $swp_start) ? round($swp_withdrawal * pow(1 + $swp_stepup / 100, $y - $swp_start), 2) : 0.0;
    // Instead of precomputing annual SWP, we'll sum actual withdrawals.
    $actual_year_withdrawn = 0.0;

    $year_begin = $net_balance;

    // Simulate month-by-month for the year.
    for ($m = 1; $m <= 12; $m++) {
        $contrib = ($y <= $years) ? $monthly_sip : 0.0;
        // contribution arrives before withdrawal in that month
        $potential_balance = $net_balance + $contrib;
        if ($y >= $swp_start && $monthly_swp > 0.0) {
            // Cap the withdrawal to available funds (never go negative)
            $desired_withdraw = $monthly_swp;
            $withdraw = ($desired_withdraw > $potential_balance) ? $potential_balance : $desired_withdraw;
            $withdraw = max(0.0, $withdraw);
        } else {
            $withdraw = 0.0;
        }
        $actual_year_withdrawn += $withdraw;
        // update net balance and apply monthly interest
        $net_balance = ($net_balance + $contrib - $withdraw) * (1 + $monthly_rate);
        // guard tiny negative rounding
        if ($net_balance < 0 && $net_balance > -0.0001) {
            $net_balance = 0.0;
        }
    }

    $annual_withdrawal = $actual_year_withdrawn;
    // interest earned during the year = final - (start + contributions - withdrawals)
    $interest_earned = $net_balance - ($year_begin + $annual_contribution - $annual_withdrawal);
    $cumulative_invested += $annual_contribution;
    if ($y >= $swp_start) {
        $cumulative_withdrawals += $annual_withdrawal;
    }

    $combined[$y] = [
        'year' => $y,
        'begin_balance' => round($year_begin),
        'sip_monthly' => ($y <= $years) ? $monthly_sip : null,
        'annual_contribution' => $annual_contribution,
        'cumulative_invested' => $cumulative_invested,
        'swp_monthly' => ($y >= $swp_start && $monthly_swp > 0.0) ? $monthly_swp : null,
        'annual_withdrawal' => ($y >= $swp_start) ? $annual_withdrawal : null,
        'cumulative_withdrawals' => ($y >= $swp_start) ? $cumulative_withdrawals : 0.0,
        'interest' => round($interest_earned),
        'combined_total' => round($net_balance)
    ];
}

if ($action === 'download_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="SIP_SWP_Report.csv"');
    echo "\xEF\xBB\xBF"; // BOM for UTF-8 Excel
    $csv = new SplTempFileObject();
    $csv->setCsvControl(',', '"', "\\");
    $csv->fputcsv(['Financial Projection Report']);
    $csv->fputcsv(['Source:', 'https://sipswpcalculator.com/ (Advanced SIP & SWP Calculator)']);
    $csv->fputcsv(['Date Generated:', date('Y-m-d')]);
    $csv->fputcsv([]);
    $csv->fputcsv(['Note: All amounts are in USD']);
    $csv->fputcsv([]);
    $headers = [
        'Year',
        'Start-of-Year Corpus',
        'Monthly SIP',
        'Annual SIP Contribution',
        'Total SIP Invested to Date'
    ];
    if ($enable_swp) {
        $headers[] = 'Monthly SWP Withdrawal';
        $headers[] = 'Annual SWP Withdrawal';
        $headers[] = 'Total SWP Withdrawals to Date';
    }
    $headers[] = 'Interest Earned This Year';
    $headers[] = 'End-of-Year Corpus';

    $csv->fputcsv($headers);
    for ($y = 1; $y <= $simulation_years; $y++) {
        $row = $combined[$y];
        $csvRow = [
            $row['year'],
            formatInr($row['begin_balance']),
            $row['sip_monthly'] !== null ? formatInr($row['sip_monthly']) : '-',
            formatInr($row['annual_contribution']),
            formatInr($row['cumulative_invested'])
        ];
        if ($enable_swp) {
            $csvRow[] = $row['swp_monthly'] !== null ? formatInr($row['swp_monthly']) : '-';
            $csvRow[] = $row['annual_withdrawal'] !== null ? formatInr($row['annual_withdrawal']) : '-';
            $csvRow[] = $row['cumulative_withdrawals'] ? formatInr($row['cumulative_withdrawals']) : '-';
        }
        $csvRow[] = formatInr($row['interest']);
        $csvRow[] = formatInr($row['combined_total']);

        $csv->fputcsv($csvRow);
    }
    $csv->rewind();
    while (!$csv->eof()) {
        echo $csv->fgets();
    }
    exit;
}

// Prepare chart data for the line graph.
$years_data = array();
$cumulative_numbers = array();
$combined_numbers = array();
$swp_numbers = array();
foreach ($combined as $row) {
    $years_data[] = $row['year'];
    $cumulative_numbers[] = $row['cumulative_invested'];
    $combined_numbers[] = $row['combined_total'];
    $swp_numbers[] = $row['annual_withdrawal'] ?? 0.0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIP Calculator 2026 — Free Step-Up SIP & SWP Planner Online</title>
    <meta name="description"
        content="Free SIP calculator with step-up compounding & SWP retirement planner. Visual charts, yearly breakdown, CSV & PDF export — trusted by investors worldwide.">
    <meta name="keywords"
        content="SIP Calculator, SIP Calculator Online, SIP Return Calculator, Mutual Fund SIP Calculator, SWP Calculator, SWP Planner, Step-Up SIP Calculator, Investment Planner, Wealth Creation, Retirement Planning, SIP vs SWP, Tax-Efficient Withdrawals, SIP Calculator India, SIP Calculator USA, SIP Calculator UK, Mutual Fund Return Calculator">
    <link rel="canonical" href="https://sipswpcalculator.com/">
    <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/">
    <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sipswpcalculator.com/">
    <meta property="og:title" content="SIP Calculator 2026 — Free Step-Up SIP & SWP Planner">
    <meta property="og:description"
        content="Free SIP calculator with step-up compounding & SWP retirement planner. Visual charts, yearly breakdown, CSV & PDF export.">
    <meta property="og:image" content="https://sipswpcalculator.com/assets/og-image-main.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://sipswpcalculator.com/">
    <meta property="twitter:title" content="SIP Calculator 2026 — Free Step-Up SIP & SWP Planner">
    <meta property="twitter:description"
        content="Free SIP calculator with step-up compounding & SWP retirement planner. Visual charts, yearly breakdown, CSV & PDF export.">
    <meta property="twitter:image" content="https://sipswpcalculator.com/assets/og-image-main.jpg">

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
      "inLanguage": ["en"],
      "isAccessibleForFree": true,
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD",
        "availability": "https://schema.org/InStock"
      },
      "description": "Advanced SIP & SWP Calculator with step-up (top-up) compounding for mutual fund investment planning. Uses the future value of annuity due formula: FV = P × [((1+r)^n - 1) / r] × (1+r), where P = monthly investment, r = monthly rate of return, n = total months. Supports annual step-up from 0-50%, investment periods of 1-50 years, expected returns of 1-30%, and Systematic Withdrawal Plans (SWP) with the 4% safe withdrawal rule. Calculates month-by-month compounding with rupee cost averaging. Outputs interactive growth charts, yearly breakdown tables, CSV exports, and branded PDF reports. Trusted by investors for SIP calculations in INR (₹), USD ($), EUR (€), and GBP (£). Based on AMFI India standard methodology for mutual fund return projections.",
      "featureList": [
        "SIP Calculator with step-up compounding (annual top-up 0-50%)",
        "SWP Retirement Planner with step-up withdrawals",
        "Month-by-month simulation (more accurate than simple annuity)",
        "Interactive Chart.js growth visualization",
        "Yearly breakdown table with corpus, interest, and withdrawal tracking",
        "Multi-currency support: INR, USD, EUR, GBP",
        "CSV export with full yearly data",
        "Branded PDF report generation with custom logos",
        "Shareable URL with pre-filled parameters",
        "SIP vs FD vs PPF comparison data"
      ],
      "screenshot": "https://sipswpcalculator.com/assets/og-image-main.jpg",
      "image": "https://sipswpcalculator.com/assets/og-image-main.jpg",
      "datePublished": "2024-12-01",
      "dateModified": "2026-03-02",
      "softwareVersion": "3.0",
      "releaseNotes": "Added step-up SWP withdrawals, multi-currency support (INR/USD/EUR/GBP), branded PDF reports, and SIP vs RD vs FD vs PPF comparison.",
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
          "description": "A method of investing a fixed sum regularly in mutual funds, using rupee cost averaging and compounding to build wealth over time.",
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
        "name": "AMFI India — SIP Methodology and Mutual Fund Industry Data",
        "url": "https://www.amfiindia.com/",
        "publisher": {
          "@type": "Organization",
          "name": "Association of Mutual Funds in India (AMFI)"
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
      "description": "Free financial planning tool for calculating Systematic Investment Plan (SIP) returns with annual step-up compounding and Systematic Withdrawal Plan (SWP) retirement income projections. Supports monthly SIP amounts from ₹500 to ₹10,00,000 (or equivalent in USD/EUR/GBP), investment periods from 1 to 50 years, expected annual returns from 1% to 30%, and annual step-up percentages from 0% to 50%. SWP module supports monthly withdrawals with inflation-adjusted step-up from 0% to 20%. Uses month-by-month simulation with compound interest for accuracy superior to simple annuity formulas. Verified against AMFI India standard methodology. Historical context: Nifty 50 has delivered approximately 12-15% CAGR over 20-year rolling periods. SIP inflows in India exceeded ₹21,000 Crore per month in 2025 (AMFI data).",
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
      "currenciesAccepted": "INR, USD, EUR, GBP",
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
      "description": "Free online SIP calculator with step-up compounding and SWP retirement planner. Uses month-by-month simulation based on AMFI India methodology. Features interactive charts, yearly breakdown tables, multi-currency support (INR/USD/EUR/GBP), CSV exports, and branded PDF reports. Trusted by investors and financial advisors worldwide.",
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
        "url": "https://sipswpcalculator.com/assets/favicon.png",
        "width": 512,
        "height": 512
      },
      "description": "Publisher of free, open-access financial planning tools for SIP and SWP calculations. All formulas verified against AMFI India methodology and SEBI regulatory guidelines. Used by individual investors, financial advisors, and NRIs worldwide for mutual fund return projections.",
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
        "https://www.linkedin.com/in/sumeetboga/"
      ],
      "knowsAbout": [
        "Systematic Investment Plan (SIP)",
        "Systematic Withdrawal Plan (SWP)",
        "Mutual Fund Investing",
        "Step-Up SIP Compounding",
        "Retirement Planning",
        "Rupee Cost Averaging",
        "4% Safe Withdrawal Rate",
        "Capital Gains Tax on Mutual Funds in India",
        "LTCG Tax 2026",
        "STCG Tax 2026",
        "NRI Mutual Fund Investment"
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
      "jobTitle": "Software Developer & Finance Specialist",
      "description": "Creator of the Advanced SIP & SWP Calculator. Software developer specializing in financial planning tools with expertise in mutual fund return calculations, step-up compounding methodology, and tax-efficient withdrawal planning.",
      "sameAs": [
        "https://www.linkedin.com/in/sumeetboga/"
      ],
      "knowsAbout": [
        "Systematic Investment Plans",
        "Systematic Withdrawal Plans",
        "Mutual Fund Taxation India",
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
          "text": "Set your monthly SIP amount (₹500 to ₹10 Lakh, or equivalent in USD/EUR/GBP), investment period (1-50 years), expected annual return rate (1-30%), and optional annual step-up percentage (0-50%). A 10% step-up is recommended to match average salary growth in India.",
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
    <link rel="icon" type="image/png" href="/assets/favicon.png">

    <!-- Preconnect to font & CDN origins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <!-- Critical above-the-fold CSS inlined for instant FCP -->
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #4f46e5, #4338ca);
            --glass-bg: rgba(255, 255, 255, .9);
            --glass-border: 1px solid rgba(255, 255, 255, .5);
            --glass-shadow: 0 8px 32px 0 rgba(79, 81, 93, .1);
            --color-bg: #f8fafc;
            --color-text-primary: #0f172a;
            --color-text-secondary: #64748b;
            --color-border: #e2e8f0
        }

        body {
            background-color: var(--color-bg);
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
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

    <!-- Non-blocking CSS: Google Fonts -->
    <link rel="preload"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    </noscript>

    <!-- Non-blocking CSS: App styles -->
    <link rel="preload" href="styles.css?v=<?= filemtime(__DIR__ . '/styles.css') ?>" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="styles.css?v=<?= filemtime(__DIR__ . '/styles.css') ?>">
    </noscript>

    <!-- Non-blocking CSS: Tailwind -->
    <link rel="preload" href="dist/tailwind.min.css?v=<?= filemtime(__DIR__ . '/dist/tailwind.min.css') ?>" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="dist/tailwind.min.css?v=<?= filemtime(__DIR__ . '/dist/tailwind.min.css') ?>">
    </noscript>
    <script src="https://analytics.ahrefs.com/analytics.js" data-key="WiDGDiqV9F0xelXDCYFUfw" async></script>
</head>

<body class="font-sans antialiased text-slate-800">
    <?php include 'navbar.php'; ?>
    <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">

        <header class="relative mb-6 sm:mb-10 text-center">
            <div
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 border border-indigo-100 mb-4">
                <span class="relative flex h-3 w-3">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                </span>
                <span class="text-sm font-semibold text-indigo-700 tracking-wide uppercase">Free Financial Planning
                    Tool</span>
            </div>

            <h1 class="text-3xl sm:text-5xl md:text-7xl font-extrabold pb-3 tracking-tight">
                <span class="text-gradient">SIP & SWP Calculator</span> <br>
                <span class="text-gray-800">Visualise Your Wealth Journey</span>
            </h1>

            <!-- EEAT Trust Bar -->
            <div
                class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4 text-sm text-slate-500 mb-8 pb-6 border-b border-slate-200/60 max-w-3xl mx-auto">
                <div class="flex items-center gap-2">
                    <img src="/assets/sumeet-boga-56.jpg" alt="Sumeet Boga — Creator of SIP Calculator"
                        class="w-7 h-7 rounded-full shadow-sm border border-emerald-100 object-cover" width="28"
                        height="28" fetchpriority="high" decoding="async">
                    <span>Developed by <strong class="text-slate-700">Sumeet Boga</strong>, Software Developer & Finance
                        Specialist</span>
                </div>
                <span class="hidden sm:inline text-slate-300">|</span>
                <div
                    class="flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold border border-emerald-100 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Verified for Accuracy: February 2026
                </div>
            </div>

            <p class="text-base sm:text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed font-medium mb-4">
                This free <dfn><strong class="text-indigo-600">SIP calculator</strong></dfn> helps you estimate
                your <strong>mutual fund SIP returns</strong> with annual step-up (top-up) compounding.
                A <dfn><strong class="text-rose-500">Systematic Withdrawal Plan (SWP)</strong></dfn> lets you plan
                tax-efficient withdrawals for a steady retirement income. Use this <strong>SIP return
                    calculator</strong>
                to visualize growth, compare scenarios, and download detailed PDF reports — free for investors
                worldwide.
            </p>


        </header>

        <main>
            <!-- Quick Answer Box for AI extraction / Featured Snippet -->
            <div id="quick-answer"
                class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-6 mb-8 max-w-2xl mx-auto"
                role="complementary" aria-label="Quick Answer">
                <p class="text-sm font-bold text-emerald-800 mb-1">Quick Answer</p>
                <p class="text-base text-gray-700"><strong>How much will a ₹10,000/month SIP grow in 20 years?</strong>
                </p>
                <p class="text-sm text-gray-600 mt-1">At 12% annual returns with a 10% yearly step-up, a ₹10,000/month
                    SIP will grow to approximately <strong class="text-emerald-700">₹3.54 Crore</strong> over 20 years.
                    Total invested: ₹68.73 Lakh. Total gains: ₹2.85 Crore.</p>
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

            <section aria-labelledby="calculator-heading">
                <h2 class="sr-only" id="calculator-heading">Calculate Your SIP & SWP Returns</h2>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <!-- Left Column: Currency + Form -->
                    <div
                        class="lg:col-span-1 flex flex-col gap-4 lg:sticky lg:top-4 lg:max-h-[calc(100vh-2rem)] lg:overflow-y-auto pb-4 lg:pr-4 custom-scrollbar">



                        <!-- Form Section -->
                        <div class="glass-card p-4">
                            <form method="post" novalidate id="calculator-form">
                                <!-- SECURITY: CSRF Token for main form -->
                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                                <!-- SECURITY: Honeypot field (hidden from real users, catches bots) -->
                                <div style="position: absolute; left: -9999px; top: -9999px;" aria-hidden="true">
                                    <label for="website_url">Leave this field empty</label>
                                    <input type="text" id="website_url" name="website_url" tabindex="-1"
                                        autocomplete="off">
                                </div>

                                <!-- Currency Selector -->
                                <div class="flex justify-center mb-3">
                                    <div class="inline-flex rounded-lg overflow-hidden border border-slate-200"
                                        role="group" id="currency-group">
                                        <button type="button" data-currency="INR"
                                            class="currency-btn px-3 py-3 sm:py-1.5 text-xs font-semibold cursor-pointer transition-colors bg-indigo-600 text-white"
                                            onclick="updateCurrency('INR')">
                                            ₹ INR
                                        </button>
                                        <button type="button" data-currency="USD"
                                            class="currency-btn px-3 py-3 sm:py-1.5 text-xs font-semibold cursor-pointer transition-colors bg-white text-slate-500 hover:bg-slate-50 border-x border-slate-200"
                                            onclick="updateCurrency('USD')">
                                            $ USD
                                        </button>
                                        <button type="button" data-currency="EUR"
                                            class="currency-btn px-3 py-3 sm:py-1.5 text-xs font-semibold cursor-pointer transition-colors bg-white text-slate-500 hover:bg-slate-50 border-r border-slate-200"
                                            onclick="updateCurrency('EUR')">
                                            € EUR
                                        </button>
                                        <button type="button" data-currency="GBP"
                                            class="currency-btn px-3 py-3 sm:py-1.5 text-xs font-semibold cursor-pointer transition-colors bg-white text-slate-500 hover:bg-slate-50"
                                            onclick="updateCurrency('GBP')">
                                            £ GBP
                                        </button>
                                    </div>
                                </div>

                                <!-- Tab Bar -->
                                <div class="flex rounded-xl overflow-hidden border border-slate-200 mb-4"
                                    role="tablist">
                                    <button type="button" id="tab-sip" role="tab" aria-selected="true"
                                        onclick="switchFormTab('sip')"
                                        class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all duration-200 bg-emerald-500 text-white">
                                        <span
                                            class="flex items-center justify-center w-4 h-4 rounded-full bg-white/20 text-[9px]">1</span>
                                        SIP Details
                                    </button>
                                    <button type="button" id="tab-swp" role="tab" aria-selected="false"
                                        onclick="switchFormTab('swp')"
                                        class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all duration-200 bg-white text-slate-400 hover:bg-rose-50 hover:text-rose-500">
                                        <span
                                            class="flex items-center justify-center w-4 h-4 rounded-full bg-slate-100 text-[9px]">2</span>
                                        SWP Details
                                    </button>
                                </div>

                                <!-- SIP Panel -->
                                <div id="panel-sip" role="tabpanel">
                                    <div class="relative">
                                        <div
                                            class="mb-4 relative z-10 bg-[var(--glass-bg)] p-4 sm:p-5 rounded-3xl border border-[var(--glass-border)] shadow-xl backdrop-blur-xl">

                                            <div class="space-y-3">
                                                <!-- Monthly + Duration -->
                                                <!-- Monthly Investment -->
                                                <div class="group">
                                                    <label for="sip"
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                                        Monthly SIP
                                                    </label>
                                                    <div class="relative">
                                                        <span
                                                            class="currency-symbol absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">₹</span>
                                                        <input type="number" id="sip" name="sip"
                                                            class="w-full bg-white border border-slate-200 rounded-lg pl-6 pr-2.5 py-3 sm:py-1.5 text-sm font-bold text-emerald-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
                                                            required min="500" step="500" max="1000000"
                                                            value="<?= htmlspecialchars((string) $sip) ?>">
                                                    </div>
                                                    <input type="range" id="sip_range" min="500" max="100000" step="500"
                                                        value="<?= htmlspecialchars((string) $sip) ?>"
                                                        class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-emerald-500 mt-2">
                                                </div>

                                                <!-- Duration -->
                                                <div class="group">
                                                    <label for="years"
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                                        Period (Yrs)
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" id="years" name="years"
                                                            class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-3 sm:py-1.5 pr-8 text-sm font-bold text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
                                                            required min="1" max="50"
                                                            value="<?= htmlspecialchars((string) $years) ?>">
                                                        <span
                                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">Yrs</span>
                                                    </div>
                                                    <input type="range" id="years_range" min="1" max="50" step="1"
                                                        value="<?= htmlspecialchars((string) $years) ?>"
                                                        class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-emerald-500 mt-2">
                                                </div>
                                                <!-- Expected Return -->
                                                <div class="group">
                                                    <label for="rate"
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                                        Expected Return
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" id="rate" step="0.1" name="rate"
                                                            class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-3 sm:py-1.5 pr-6 text-sm font-bold text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
                                                            required min="0" max="30"
                                                            value="<?= htmlspecialchars((string) $rate) ?>">
                                                        <span
                                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">%</span>
                                                    </div>
                                                    <input type="range" id="rate_range" min="1" max="30" step="0.1"
                                                        value="<?= htmlspecialchars((string) $rate) ?>"
                                                        class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-emerald-500 mt-2">
                                                </div>

                                                <!-- Yearly Step-up -->
                                                <div class="group">
                                                    <label for="stepup"
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                                        Annual Step-up
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" id="stepup" step="1" name="stepup"
                                                            class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-3 sm:py-1.5 pr-6 text-sm font-bold text-emerald-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
                                                            required min="0" max="100"
                                                            value="<?= htmlspecialchars((string) $stepup) ?>">
                                                        <span
                                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">%</span>
                                                    </div>
                                                    <input type="range" id="stepup_range" min="0" max="50" step="1"
                                                        value="<?= htmlspecialchars((string) $stepup) ?>"
                                                        class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-emerald-500 mt-2">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div><!-- /panel-sip -->

                                <!-- SWP Panel -->
                                <div id="panel-swp" role="tabpanel" class="hidden">
                                    <div class="relative">
                                        <div
                                            class="relative z-10 bg-[var(--glass-bg)] p-4 sm:p-5 rounded-3xl border border-[var(--glass-border)] shadow-xl backdrop-blur-xl">
                                            <div class="flex items-center justify-between mb-4">
                                                <span
                                                    class="text-xs font-bold text-rose-400 tracking-widest uppercase">SWP
                                                    Config</span>
                                                <label
                                                    class="toggle-switch relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" id="enable_swp" name="enable_swp"
                                                        onchange="toggleSwpFields()" class="sr-only peer">
                                                    <div
                                                        class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-rose-500">
                                                    </div>
                                                </label>
                                            </div>

                                            <div id="swp-fields" class="space-y-4 transition-all duration-300">
                                                <!-- Monthly Withdrawal -->
                                                <div class="group">
                                                    <label for="swp_withdrawal"
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                                        Monthly SWP
                                                    </label>
                                                    <div class="relative">
                                                        <span
                                                            class="currency-symbol absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">₹</span>
                                                        <input type="number" id="swp_withdrawal" step="500"
                                                            name="swp_withdrawal"
                                                            class="w-full bg-white border border-slate-200 rounded-lg pl-6 pr-2.5 py-3 sm:py-1.5 text-sm font-bold text-rose-500 focus:outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400/30 transition-colors"
                                                            required min="0" max="1000000"
                                                            value="<?= htmlspecialchars((string) $swp_withdrawal) ?>">
                                                    </div>
                                                    <input type="range" id="swp_withdrawal_range" min="1000"
                                                        max="200000" step="500"
                                                        value="<?= htmlspecialchars((string) $swp_withdrawal) ?>"
                                                        class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-rose-500 mt-2">
                                                </div>


                                                <!-- Withdrawal Duration -->
                                                <div class="group">
                                                    <label for="swp_years"
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                                        SWP Period
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" id="swp_years" name="swp_years"
                                                            class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-3 sm:py-1.5 pr-8 text-sm font-bold text-slate-700 focus:outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400/30 transition-colors"
                                                            required min="1" max="50"
                                                            value="<?= htmlspecialchars((string) $swp_years_input) ?>">
                                                        <span
                                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">Yrs</span>
                                                    </div>
                                                    <input type="range" id="swp_years_range" min="1" max="50" step="1"
                                                        value="<?= htmlspecialchars((string) $swp_years_input) ?>"
                                                        class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-rose-500 mt-2">
                                                </div>

                                                <!-- Withdrawal Hike -->
                                                <div class="group">
                                                    <label for="swp_stepup"
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                                                        Yearly Hike
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" id="swp_stepup" step="0.1"
                                                            name="swp_stepup"
                                                            class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-3 sm:py-1.5 pr-6 text-sm font-bold text-slate-700 focus:outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400/30 transition-colors"
                                                            required min="0" max="20"
                                                            value="<?= htmlspecialchars((string) $swp_stepup) ?>">
                                                        <span
                                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">%</span>
                                                    </div>
                                                    <input type="range" id="swp_stepup_range" min="0" max="20"
                                                        step="0.5" value="<?= htmlspecialchars((string) $swp_stepup) ?>"
                                                        class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-rose-500 mt-2">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div><!-- /panel-swp -->


                            </form>
                        </div>
                    </div> <!-- /left column -->

                    <div class="lg:col-span-2 space-y-4">
                        <!-- Summary Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="glass-card p-3 sm:p-4 text-center">
                                <div
                                    class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">
                                    Total Invested</div>
                                <div id="summary-invested"
                                    class="text-lg sm:text-xl font-extrabold text-indigo-600 font-mono transition-numbers">
                                    <?= formatInr(end($combined)['cumulative_invested'] ?? 0) ?>
                                </div>
                            </div>
                            <div class="glass-card p-3 sm:p-4 text-center">
                                <div
                                    class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">
                                    Total Gains</div>
                                <div id="summary-interest"
                                    class="text-lg sm:text-xl font-extrabold text-emerald-600 font-mono transition-numbers">
                                    <?php
                                    $lastRow = end($combined);
                                    $totalGains = ($lastRow['combined_total'] + ($lastRow['cumulative_withdrawals'] ?? 0)) - ($lastRow['cumulative_invested'] ?? 0);
                                    echo formatInr($totalGains);
                                    ?>
                                </div>
                            </div>
                            <div class="glass-card p-3 sm:p-4 text-center">
                                <div
                                    class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">
                                    Total Withdrawn</div>
                                <div id="summary-withdrawn"
                                    class="text-lg sm:text-xl font-extrabold text-rose-500 font-mono transition-numbers">
                                    <?= formatInr(end($combined)['cumulative_withdrawals'] ?? 0) ?>
                                </div>
                            </div>
                            <div class="glass-card p-3 sm:p-4 text-center border-2 border-indigo-100">
                                <div
                                    class="text-[10px] sm:text-xs font-bold text-indigo-400 uppercase tracking-widest mb-1">
                                    Final Corpus</div>
                                <div id="summary-corpus"
                                    class="text-lg sm:text-xl font-extrabold text-slate-800 font-mono transition-numbers">
                                    <?= formatInr(end($combined)['combined_total'] ?? 0) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Chart Card -->
                        <div
                            class="relative z-10 bg-[var(--glass-bg)] rounded-3xl border border-[var(--glass-border)] shadow-2xl backdrop-blur-xl overflow-hidden transition-all duration-300 hover:shadow-emerald-500/10 p-4 sm:p-6">
                            <div class="h-[280px] sm:h-[350px] lg:h-[450px] w-full relative z-10">
                                <canvas id="corpusChart" width="800" height="450"></canvas>
                            </div>
                        </div>
                    </div>
                </div><!-- /3-col grid -->



                <!-- Full-width Yearly Breakdown -->
                <div class="mt-8 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h2 id="yearly-breakdown" class="text-xl font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Yearly Breakdown
                        </h2>
                        <div class="flex gap-2">
                            <button type="submit" name="action" value="download_csv" form="calculator-form"
                                class="text-sm px-4 py-3 sm:py-2 flex items-center gap-2 rounded-lg font-semibold bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                CSV
                            </button>
                            <button type="button" id="openPdfModalBtn"
                                class="text-sm px-4 py-3 sm:py-2 flex items-center gap-2 rounded-lg font-semibold bg-white text-emerald-600 border border-emerald-200 hover:bg-emerald-50 hover:border-emerald-300 transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                PDF
                            </button>
                            <button type="button" id="shareCalcBtn"
                                class="text-sm px-4 py-3 sm:py-2 flex items-center gap-2 rounded-lg font-semibold bg-white text-indigo-600 border border-indigo-200 hover:bg-indigo-50 hover:border-indigo-300 transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                                <span id="shareBtnText">Share</span>
                            </button>
                        </div>
                    </div>

                    <div class="glass-card overflow-hidden border border-slate-200 shadow-sm">
                        <div id="table-scroll-wrapper"
                            class="max-h-[600px] overflow-y-auto overflow-x-auto custom-scrollbar">
                            <table class="w-full text-sm text-left relative">
                                <thead id="breakdown-head"
                                    class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-6 py-4 bg-slate-50/95 border-b border-slate-200">Year</th>
                                        <th
                                            class="px-6 py-4 bg-slate-50/95 border-b border-slate-200 whitespace-nowrap">
                                            Start Corpus</th>
                                        <th
                                            class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-emerald-600 whitespace-nowrap">
                                            Annual SIP</th>
                                        <th
                                            class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 whitespace-nowrap">
                                            Total Invested</th>
                                        <th class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-rose-500 whitespace-nowrap swp-col"
                                            <?= !$enable_swp ? 'style="display:none"' : '' ?>>
                                            Annual SWP</th>
                                        <th class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-slate-500 whitespace-nowrap swp-col"
                                            <?= !$enable_swp ? 'style="display:none"' : '' ?>>
                                            Total Withdrawn</th>
                                        <th
                                            class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-emerald-600 whitespace-nowrap">
                                            Interest</th>
                                        <th
                                            class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 font-bold text-slate-800 whitespace-nowrap">
                                            End Corpus</th>
                                    </tr>
                                </thead>
                                <tbody id="breakdown-body" class="divide-y divide-slate-100 text-slate-600">
                                    <?php foreach ($combined as $row): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-6 py-4 font-medium text-slate-700"><?= $row['year'] ?></td>
                                            <td class="px-6 py-4 text-right font-mono">
                                                <?= formatInr($row['begin_balance']) ?>
                                            </td>
                                            <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono">
                                                <?= formatInr($row['annual_contribution']) ?>
                                            </td>
                                            <td class="px-6 py-4 text-right text-slate-500 font-mono">
                                                <?= formatInr($row['cumulative_invested']) ?>
                                            </td>
                                            <td class="px-6 py-4 text-right text-rose-500 font-medium font-mono swp-col"
                                                <?= !$enable_swp ? 'style="display:none"' : '' ?>>
                                                <?= $row['annual_withdrawal'] !== null ? formatInr($row['annual_withdrawal']) : '-' ?>
                                            </td>
                                            <td class="px-6 py-4 text-right text-slate-500 font-mono swp-col"
                                                <?= !$enable_swp ? 'style="display:none"' : '' ?>>
                                                <?= $row['cumulative_withdrawals'] ? formatInr($row['cumulative_withdrawals']) : '-' ?>
                                            </td>
                                            <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono">
                                                <?= formatInr($row['interest']) ?>
                                            </td>
                                            <td class="px-6 py-4 text-right font-bold text-slate-800 font-mono">
                                                <?= formatInr($row['combined_total']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


    </div>
    </section><!-- /calculator section -->

    <div class="text-center mt-8">
        <a href="/sip-calculator" class="text-indigo-600 hover:underline">
            Learn more about SIPs and how to use our calculator
        </a>
    </div>

    <div class="mt-12 glass-card p-8">
        <h2 id="master-financial-future" class="text-3xl font-bold text-center mb-6">Master Your Financial Future with
            SIP & SWP</h2>
        <div class="prose prose-lg max-w-none text-gray-600">
            <p>Understanding the tools at your disposal is the first step toward effective financial planning. Our
                <strong>mutual fund SIP calculator</strong> is designed to demystify two of the most powerful tools
                for Indian and global investors: the Systematic Investment Plan (SIP) and the Systematic Withdrawal Plan
                (SWP).
            </p>

            <!-- Standalone SIP Definition -->
            <div class="grid md:grid-cols-2 gap-8 mt-8">
                <div itemscope itemtype="https://schema.org/DefinedTerm">
                    <h3 class="text-2xl font-semibold mb-3 text-indigo-600" id="what-is-sip">What is a Systematic
                        Investment Plan (SIP)?</h3>
                    <p itemprop="description">A <dfn><strong>Systematic Investment Plan (SIP)</strong></dfn> is a method
                        of
                        investing a fixed amount of money at regular intervals (monthly, quarterly) into mutual funds.
                        SIPs use <strong>rupee cost averaging</strong> and <strong>compounding</strong> to build wealth
                        over time, making them ideal for long-term goals like retirement, education, or wealth creation.
                        As per <a href="https://www.amfiindia.com/" target="_blank" rel="noopener noreferrer"
                            class="text-indigo-600 hover:underline">AMFI</a> data, SIP inflows in India crossed ₹21,000
                        Crore/month in 2025.
                        <a href="/sip-calculator" class="text-indigo-600 hover:underline font-medium">Read our complete
                            SIP guide →</a>
                    </p>
                    <ul class="mt-4 space-y-2">
                        <li><span class="font-semibold text-green-600">Rupee Cost Averaging:</span> Buy more units when
                            NAV is low, fewer when it's high — reducing average cost automatically.</li>
                        <li><span class="font-semibold text-green-600">Power of Compounding:</span> Reinvesting returns
                            generates earnings on earnings, leading to exponential growth over 10-20+ years.</li>
                        <li><span class="font-semibold text-green-600">Disciplined Investing:</span> Automates saving
                            and removes emotional decision-making from investing.</li>
                    </ul>
                </div>

                <!-- Standalone SWP Definition -->
                <div itemscope itemtype="https://schema.org/DefinedTerm">
                    <h3 class="text-2xl font-semibold mb-3 text-purple-600" id="what-is-swp">What is a Systematic
                        Withdrawal Plan (SWP)?</h3>
                    <p itemprop="description">A <dfn><strong>Systematic Withdrawal Plan (SWP)</strong></dfn> allows
                        investors
                        to withdraw a fixed amount from their mutual fund corpus at regular intervals.
                        SWP provides a steady, <strong>tax-efficient income stream</strong> during retirement while
                        allowing the remaining investment to continue growing. Unlike FD interest (taxed at slab rate),
                        SWP withdrawals are taxed only on the capital gains portion — making them significantly more
                        efficient.
                        <a href="/#panel-swp" class="text-purple-600 hover:underline font-medium">Try the SWP calculator
                            →</a>
                    </p>
                    <ul class="mt-4 space-y-2">
                        <li><span class="font-semibold text-green-600">Regular Income:</span> Create a predictable
                            pension-like cash flow from your mutual fund investments.</li>
                        <li><span class="font-semibold text-green-600">Tax-Efficient Withdrawals:</span> Only the
                            capital gains portion is taxed (LTCG at 12.5% above ₹1.25 Lakh for equity funds).</li>
                        <li><span class="font-semibold text-green-600">Continued Growth:</span> Remaining corpus stays
                            invested and benefits from market growth, potentially outliving you.</li>
                    </ul>
                </div>
            </div>

            <!-- How to Use This Calculator -->
            <div class="mt-12">
                <h2 id="how-to-use-calculator" class="text-2xl font-bold text-gray-800 mb-4">How to Use This SIP & SWP
                    Calculator</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-emerald-50/50 p-6 rounded-xl border border-emerald-100">
                        <div class="text-emerald-600 font-bold text-lg mb-2">Step 1: Enter SIP Details</div>
                        <p class="text-sm">Set your <strong>monthly SIP amount</strong> (₹500 to ₹10 Lakh), investment
                            period (1-50 years), expected annual return rate, and optional <strong>annual step-up
                                percentage</strong>.</p>
                    </div>
                    <div class="bg-rose-50/50 p-6 rounded-xl border border-rose-100">
                        <div class="text-rose-600 font-bold text-lg mb-2">Step 2: Configure SWP (Optional)</div>
                        <p class="text-sm">Switch to the SWP tab, enable it, and set your <strong>monthly withdrawal
                                amount</strong>, withdrawal period, and yearly hike. The SWP phase begins automatically
                            after your SIP period ends.</p>
                    </div>
                    <div class="bg-indigo-50/50 p-6 rounded-xl border border-indigo-100">
                        <div class="text-indigo-600 font-bold text-lg mb-2">Step 3: Analyze Results</div>
                        <p class="text-sm">View the interactive <strong>growth chart</strong>, yearly breakdown table,
                            and summary cards. Export results as <strong>CSV</strong> or generate a branded <strong>PDF
                                report</strong> for clients.</p>
                    </div>
                </div>
            </div>

            <!-- SIP Formula -->
            <div class="mt-12">
                <h2 id="sip-calculator-formula" class="text-2xl font-bold text-gray-800 mb-4">SIP Calculator Formula
                </h2>
                <div
                    class="bg-gray-50 p-6 rounded-xl border border-gray-200 font-mono text-sm sm:text-base overflow-x-auto">
                    <p class="font-bold text-indigo-700 mb-2">Future Value of SIP (Annuity Due):</p>
                    <p class="text-lg mb-4">FV = P × [ { (1 + i)<sup>n</sup> - 1 } / i ] × (1 + i)</p>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                        <div>
                            <dt class="inline font-bold">FV</dt>
                            <dd class="inline">= Future Value (Maturity Amount)</dd>
                        </div>
                        <div>
                            <dt class="inline font-bold">P</dt>
                            <dd class="inline">= Monthly Investment Amount</dd>
                        </div>
                        <div>
                            <dt class="inline font-bold">i</dt>
                            <dd class="inline">= Monthly Rate (Annual Rate ÷ 12 ÷ 100)</dd>
                        </div>
                        <div>
                            <dt class="inline font-bold">n</dt>
                            <dd class="inline">= Total Payments (Years × 12)</dd>
                        </div>
                    </dl>
                </div>
                <p class="mt-4 text-sm text-gray-500">Our calculator uses month-by-month simulation with step-up
                    compounding, which is more accurate than the simple annuity formula for long-term projections.
                    Source: <a href="https://www.amfiindia.com/" target="_blank" rel="noopener noreferrer"
                        class="text-indigo-600 hover:underline">AMFI India</a> standard methodology.</p>
            </div>

            <!-- Worked Examples -->
            <div class="mt-12">
                <h2 id="sip-return-examples" class="text-2xl font-bold text-gray-800 mb-6">SIP Return Examples: How Much
                    Can You Earn?</h2>
                <div class="grid md:grid-cols-3 gap-6 not-prose">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h4 class="text-lg font-bold text-emerald-700 mb-2">₹5,000/month for 15 Years</h4>
                        <p class="text-xs text-gray-500 mb-3">@ 12% return, 10% annual step-up</p>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex justify-between"><span>Total Invested:</span> <span
                                    class="font-bold">₹19.09L</span></li>
                            <li class="flex justify-between"><span>Wealth Gained:</span> <span
                                    class="font-bold text-green-600">+₹21.41L</span></li>
                            <li class="flex justify-between border-t border-gray-100 pt-2 text-base"><span>Maturity
                                    Value:</span> <span class="font-bold text-indigo-700">₹40.50L</span></li>
                        </ul>
                        <p class="text-xs text-gray-400 mt-3">Money multiplied ~2.1×</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-indigo-100 ring-2 ring-indigo-100">
                        <div class="text-xs font-bold text-indigo-600 mb-1">MOST POPULAR</div>
                        <h4 class="text-lg font-bold text-indigo-700 mb-2">₹10,000/month for 20 Years</h4>
                        <p class="text-xs text-gray-500 mb-3">@ 12% return, 10% annual step-up</p>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex justify-between"><span>Total Invested:</span> <span
                                    class="font-bold">₹68.73L</span></li>
                            <li class="flex justify-between"><span>Wealth Gained:</span> <span
                                    class="font-bold text-green-600">+₹2.85Cr</span></li>
                            <li class="flex justify-between border-t border-gray-100 pt-2 text-base"><span>Maturity
                                    Value:</span> <span class="font-bold text-indigo-700">₹3.54Cr</span></li>
                        </ul>
                        <p class="text-xs text-gray-400 mt-3">Money multiplied ~5.1×</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h4 class="text-lg font-bold text-rose-700 mb-2">₹25,000/month for 30 Years</h4>
                        <p class="text-xs text-gray-500 mb-3">@ 12% return, 10% annual step-up</p>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex justify-between"><span>Total Invested:</span> <span
                                    class="font-bold">₹4.94Cr</span></li>
                            <li class="flex justify-between"><span>Wealth Gained:</span> <span
                                    class="font-bold text-green-600">+₹36.91Cr</span></li>
                            <li class="flex justify-between border-t border-gray-100 pt-2 text-base"><span>Maturity
                                    Value:</span> <span class="font-bold text-rose-700">₹41.85Cr</span></li>
                        </ul>
                        <p class="text-xs text-gray-400 mt-3">Money multiplied ~8.5×</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-teal-100">
                        <div class="text-xs font-bold text-teal-600 mb-1">🌍 GLOBAL EXAMPLE</div>
                        <h4 class="text-lg font-bold text-teal-700 mb-2">$500/month for 20 Years</h4>
                        <p class="text-xs text-gray-500 mb-3">@ 10% return, 5% annual step-up</p>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex justify-between"><span>Total Invested:</span> <span
                                    class="font-bold">$198,396</span></li>
                            <li class="flex justify-between"><span>Wealth Gained:</span> <span
                                    class="font-bold text-green-600">+$218,104</span></li>
                            <li class="flex justify-between border-t border-gray-100 pt-2 text-base"><span>Maturity
                                    Value:</span> <span class="font-bold text-teal-700">$416,500</span></li>
                        </ul>
                        <p class="text-xs text-gray-400 mt-3">Money multiplied ~2.1×</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-4 text-center">Note: These are illustrative projections. Actual
                    returns depend on market conditions. Mutual fund investments are subject to market risks. Read all
                    scheme-related documents carefully.</p>
            </div>

            <!-- Risks Section -->
            <div class="mt-12">
                <h2 id="investment-risks" class="text-2xl font-bold text-gray-800 mb-4">Risks of SIP & SWP Investments
                </h2>
                <div class="bg-amber-50/50 p-6 rounded-xl border border-amber-200">
                    <ul class="space-y-3 text-gray-700">
                        <li><strong class="text-amber-700">Market Risk:</strong> Returns depend on stock/bond market
                            performance. Equity SIPs can show negative returns in the short term (1-3 years). However,
                            over 7-10+ years, diversified equity funds have historically delivered positive returns.
                        </li>
                        <li><strong class="text-amber-700">Sequence-of-Returns Risk (SWP):</strong> If markets crash
                            early in your SWP phase, your corpus depletes faster. Stress-test your withdrawal rate
                            against downturns.</li>
                        <li><strong class="text-amber-700">Inflation Risk:</strong> A 6-7% return on debt funds may not
                            beat inflation (5-6%). Equity SIPs historically outpace inflation over the long term.</li>
                        <li><strong class="text-amber-700">No Guaranteed Returns:</strong> Unlike PPF or FDs, mutual
                            fund returns are not guaranteed. Past performance does not guarantee future results. Always
                            consult a <a href="https://www.sebi.gov.in/" target="_blank" rel="noopener noreferrer"
                                class="text-indigo-600 hover:underline">SEBI</a>-registered financial advisor.</li>
                    </ul>
                </div>
            </div>

            <!-- Mr. Sharma story (promoted from H3 to H2) -->
            <div class="mt-12 bg-indigo-50/50 p-8 rounded-xl border border-indigo-100/50 backdrop-blur-sm">
                <h2 id="real-life-success-story" class="text-2xl font-bold text-indigo-700 mb-4">Real-Life Success
                    Story: The "Mr. Sharma" Strategy
                </h2>
                <p class="mb-4">Meet Mr. Sharma (30). He decides to invest <strong>₹10,000/month</strong> in an
                    Equity Mutual Fund via SIP for his retirement at age 60.</p>
                <ul class="list-disc pl-5 space-y-2 mb-4">
                    <li><strong>Goal:</strong> Retire with ₹5 Crores.</li>
                    <li><strong>Strategy:</strong> Step-up SIP. Increase investment by 10% every year as his salary
                        grows.</li>
                    <li><strong>Result:</strong> By age 60, avoiding the urge to stop during market lows, his corpus
                        grows to ₹3.54 Crore — and with SWP at ₹50,000/month, he earns a steady retirement income while
                        the corpus continues to grow.</li>
                </ul>
                <p class="font-semibold">Moral: It's not just about starting early — it's about increasing your
                    investment as you grow. <a href="/sip-step-up-calculator"
                        class="text-indigo-600 hover:underline">Learn more about Step-Up SIP →</a></p>
            </div>

            <div class="mt-12">
                <h2 id="investment-comparison" class="text-3xl font-bold text-center mb-6">SIP vs RD vs FD vs PPF: A
                    Comparison</h2>
                <div class="glass-card overflow-hidden">
                    <table class="min-w-full">
                        <caption class="sr-only">SIP vs PPF vs Fixed Deposit: Investment Comparison for Indian Investors
                            (2026)</caption>
                        <thead>
                            <tr class="bg-gray-50 text-gray-700 text-left">
                                <th class="py-4 px-6 font-bold border-b text-xs uppercase tracking-wider">Feature
                                </th>
                                <th
                                    class="py-4 px-6 font-bold border-b text-xs uppercase tracking-wider text-indigo-600">
                                    SIP (Equity MF)</th>
                                <th class="py-4 px-6 font-bold border-b text-xs uppercase tracking-wider">PPF</th>
                                <th class="py-4 px-6 font-bold border-b text-xs uppercase tracking-wider">Fixed
                                    Deposit (FD)</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr class="border-b hover:bg-indigo-50/30 transition-colors">
                                <td class="py-4 px-6 font-medium text-gray-900">Expected Returns</td>
                                <td class="py-4 px-6 font-bold text-green-600">12% - 15% (High)</td>
                                <td class="py-4 px-6 text-gray-600">7.1% (Moderate)</td>
                                <td class="py-4 px-6 text-gray-600">6% - 7% (Low)</td>
                            </tr>
                            <tr class="border-b hover:bg-indigo-50/30 transition-colors">
                                <td class="py-4 px-6 font-medium text-gray-900">Risk Profile</td>
                                <td class="py-4 px-6 text-rose-500 font-medium">High (Market Linked)</td>
                                <td class="py-4 px-6 text-emerald-600 font-medium">Risk-Free (Govt Backed)</td>
                                <td class="py-4 px-6 text-emerald-600 font-medium">Low Risk</td>
                            </tr>
                            <tr class="border-b hover:bg-indigo-50/30 transition-colors">
                                <td class="py-4 px-6 font-medium text-gray-900">Liquidity</td>
                                <td class="py-4 px-6">High (Exit Load < 1 yr)</td>
                                <td class="py-4 px-6">Low (15 Year Lock-in)</td>
                                <td class="py-4 px-6">High (Penalty applies)</td>
                            </tr>
                            <tr class="hover:bg-indigo-50/30 transition-colors">
                                <td class="py-4 px-6 font-medium text-gray-900">Taxation</td>
                                <td class="py-4 px-6">LTCG > ₹1.25L taxed @ 12.5%</td>
                                <td class="py-4 px-6 font-bold text-emerald-600">Exempt (EEE)</td>
                                <td class="py-4 px-6">Taxed as Income</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="mt-12 border-t border-gray-200 pt-8">
                <h2 id="faq" class="text-3xl font-bold text-center mb-8">Frequently Asked Questions</h2>
                <div class="space-y-3">
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            Can I start an SWP immediately after my SIP ends?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            Yes, absolutely. This is a common strategy for retirement planning. You accumulate a corpus
                            using SIP during your working years and then switch to SWP to generate a monthly
                            pension-like income post-retirement. Our calculator specifically models this seamless
                            transition.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            Is SWP better than a fixed deposit interest?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            Generally, yes. SWP from equity or hybrid mutual funds has the potential to offer higher
                            returns than fixed deposits over the long term. Additionally, SWP is more tax-efficient
                            because you are only taxed on the capital gains portion of the withdrawal, whereas FD
                            interest is fully taxable at your slab rate.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            How does the "Step-up" feature work?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            A "Step-up" SIP means you increase your monthly investment amount by a certain percentage
                            every year (e.g., as your salary increases). This significantly boosts your final corpus.
                            Similarly, a "Step-up" SWP means you increase your withdrawal amount annually to combat
                            inflation.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            What is a safe withdrawal rate for SWP?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            Financial experts often recommend the "4% rule," suggesting you withdraw 4% of your corpus
                            annually. However, this depends on market conditions, your expected lifespan, and the
                            sequence of investment returns you experience. A conservative approach is to stress-test
                            your withdrawal rate against historical market downturns.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            Which is better: SIP or Lump Sum?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            In a rising market, Lump Sum often wins mathematically. However, SIP is psychologically
                            easier and safer for volatile markets as it benefits from <strong>Dollar Cost
                                Averaging</strong>, reducing the risk of investing a large amount at a market peak.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            Can I lose money in SIP?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            Yes, in the short term. Since SIPs in equity mutual funds are market-linked, the value can
                            fluctuate. However, historical data shows that over the long term (7-10+ years), the
                            probability of negative returns in a diversified fund is negligible.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            What is the minimum amount to start a SIP in India?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            Most mutual fund houses in India allow SIPs starting from as low as
                            <strong>₹500/month</strong>. Some AMCs like SBI MF and HDFC MF offer micro-SIPs at
                            ₹100/month. The key is to start early — even ₹500/month over 20 years at 12% can grow to ₹5+
                            Lakhs.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            How do I choose the right mutual fund for my SIP?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            Consider: (1) <strong>Risk profile</strong> — large-cap for stability, small-cap for
                            aggressive growth; (2) <strong>Expense ratio</strong> — lower is better, prefer direct
                            plans; (3) <strong>Track record</strong> — check 5-7 year consistency, not just 1-year
                            returns; (4) <strong>Fund manager experience</strong>. Use AMFI's mutual fund comparison
                            tools for data.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            How are SWP withdrawals taxed in India (2026)?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition=" transform
                                duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            SWP withdrawals are treated as partial redemptions. For <strong>equity funds</strong>: STCG
                            (held &lt;1 year) taxed at 20%, LTCG taxed at 12.5% on gains above ₹1.25 Lakh/year. For
                            <strong>debt funds</strong> (purchased after Apr 2023): taxed at your income slab rate. Only
                            the <em>capital gains portion</em> of each withdrawal is taxable — the principal component
                            is tax-free.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            How long should I continue my SIP for best results?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            For equity mutual funds, <strong>7-10 years minimum</strong> is recommended to ride out
                            market cycles and benefit from compounding. Historical data shows that Nifty 50 SIPs held
                            for 10+ years have never delivered negative returns. For retirement goals, 20-30 year SIPs
                            yield the best compounding effect.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            Is SIP better than a Recurring Deposit (RD)?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            For <strong>long-term goals (5+ years)</strong>, equity SIPs have historically outperformed
                            RDs by 5-8% annually. RDs offer guaranteed returns (~6-7%) but are fully taxable. SIPs in
                            equity funds offer higher growth potential with LTCG tax benefits, but carry market risk.
                            For short-term goals (1-3 years), RDs or debt fund SIPs may be safer.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            What step-up percentage should I use for my SIP?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            A <strong>10% annual step-up</strong> is the most common recommendation — roughly matching
                            average salary increments in India. Conservative investors can use 5-7%, while aggressive
                            savers might go up to 15-20%. Even a 5% step-up dramatically outperforms a flat SIP over 20+
                            years. Use our calculator above to compare different step-up rates.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            Can I stop or pause my SIP anytime?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            Yes, SIPs are completely flexible. You can <strong>pause, stop, or modify</strong> your SIP
                            at any time without penalties. Your existing invested units remain in the fund and continue
                            growing. However, stopping during market downturns is the most common mistake — it means you
                            miss buying units at lower prices, which is exactly when SIPs are most beneficial.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            Will my SWP deplete my corpus completely?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            It depends on your <strong>withdrawal rate vs. investment return</strong>. If you withdraw
                            less than what your corpus earns, it can last indefinitely. The "4% rule" suggests
                            withdrawing 4% annually to sustain a 30-year retirement. Use our calculator to stress-test
                            different withdrawal amounts and see exactly when your corpus would run out.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            Can NRIs invest in SIP in India?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            Yes, NRIs can invest in mutual fund SIPs in India through their <strong>NRE or NRO
                                accounts</strong>. Most AMCs accept NRI investments, though some sectoral/thematic funds
                            may have restrictions for US/Canada-based NRIs due to FATCA regulations. Tax treatment
                            follows India's DTAA provisions with the NRI's country of residence.
                        </div>
                    </details>
                    <details class="group bg-white rounded-xl border border-slate-200 shadow-sm">
                        <summary
                            class="flex items-center justify-between cursor-pointer px-6 py-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                            How does inflation affect my SIP and SWP planning?
                            <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed">
                            Inflation (typically 5-6% in India) erodes purchasing power over time. At 6% inflation, ₹1
                            Lakh today is worth only ₹31,000 in 20 years. This is why <strong>step-up SIPs</strong> are
                            critical — they increase your investment to outpace inflation. For SWP, use the step-up
                            withdrawal feature to increase monthly withdrawals by 5-7% annually to maintain your
                            lifestyle.
                        </div>
                    </details>
                </div>
            </div>

            <p class="text-center mt-8">Use our <a href="/" class="text-indigo-600 hover:underline font-medium">advanced
                    SIP & SWP calculator</a> to model your investments and plan your
                withdrawals to see how you can achieve your financial goals, whether it's building a retirement
                corpus, funding your child's education, or creating a passive income stream.
                <a href="/sip-calculator" class="text-indigo-600 hover:underline font-medium">Learn more about how SIPs
                    work →</a>
            </p>
        </div>
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
    </div>
    </main>

    <!-- AI-CITATION-OPTIMIZED FAQ Schema — Answers designed for LLM extraction -->
    <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@id": "https://sipswpcalculator.com/#faq",
          "@type": "FAQPage",
          "mainEntity": [{
            "@type": "Question",
            "name": "How to calculate SWP (Systematic Withdrawal Plan) returns?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "To calculate SWP returns: (1) Start with your accumulated corpus after the SIP phase, (2) Each month, deduct your withdrawal amount before applying monthly returns, (3) Formula: Month-end balance = (Previous balance + SIP contribution - SWP withdrawal) x (1 + monthly rate), where monthly rate = annual return / 12 / 100. For example, a Rs 1 Crore corpus at 10% annual return with Rs 50,000/month SWP: monthly return = Rs 83,333. Net monthly change = Rs 83,333 - Rs 50,000 = Rs 33,333 growth. The 4% rule (William Bengen, 1994) suggests keeping annual withdrawals below 4% of corpus for a sustainable 30-year retirement. Use sipswpcalculator.com for exact month-by-month SWP calculations with step-up adjustments."
            }
          }, {
            "@type": "Question",
            "name": "How does inflation affect my SIP and SWP planning?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Inflation (historically 5-6% in India, 2-3% in the US) erodes purchasing power exponentially. At 6% inflation, Rs 1 Lakh today is worth only Rs 55,839 in 10 years and Rs 31,180 in 20 years. Impact on SIP: A flat Rs 10,000/month SIP loses real value every year. After 20 years, your Rs 10,000 has the purchasing power of only Rs 3,118 in today's terms. Solution: Use step-up SIP (10% annual increase) to grow contributions faster than inflation, which can increase your final corpus by 2-3x compared to a flat SIP. Impact on SWP: A fixed Rs 50,000/month withdrawal will buy significantly less over time. Solution: Use step-up SWP (5-7% annual increase) to maintain lifestyle. The calculator at sipswpcalculator.com models both step-up SIP and step-up SWP to help investors outpace inflation."
            }
          }, {
            "@type": "Question",
            "name": "Can I start an SWP immediately after my SIP ends?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Yes, absolutely. This is a common strategy for retirement planning called the SIP-to-SWP transition. You accumulate a corpus using SIP during your working years (typically 20-30 years) and then switch to SWP to generate a monthly pension-like income post-retirement. The Advanced SIP and SWP Calculator at sipswpcalculator.com specifically models this seamless transition, showing the exact corpus at the point of switch and projecting SWP sustainability over time."
            }
          }, {
            "@type": "Question",
            "name": "Is SWP better than fixed deposit interest for retirement income?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Generally, yes. SWP from equity or hybrid mutual funds has historically offered 10-12% returns vs FD rates of 6-7%. Additionally, SWP is more tax-efficient: only the capital gains portion of each withdrawal is taxable (LTCG at 12.5% above Rs 1.25 Lakh/year for equity funds in India), whereas FD interest is fully taxable at your income slab rate (up to 30%). A Rs 1 Crore FD at 7% gives Rs 58,333/month (fully taxable), while SWP from equity MF at 10% return with Rs 50,000/month withdrawal preserves and grows the corpus over time."
            }
          }, {
            "@type": "Question",
            "name": "How does the Step-up feature work in SIP Calculator?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "A Step-up (or Top-up) SIP increases your monthly investment by a fixed percentage every year. Formula: Year N monthly SIP = Base SIP x (1 + step-up percentage / 100) raised to the power of (N-1). For example, Rs 10,000/month with 10% step-up becomes Rs 11,000 in Year 2, Rs 12,100 in Year 3, and so on. Impact: A Rs 10,000/month flat SIP at 12% for 20 years yields Rs 1 Crore, but with a 10% step-up, the same SIP yields Rs 3.54 Crore, a 3.5x increase. Similarly, Step-up SWP increases withdrawals annually (typically 5-7%) to combat inflation during retirement."
            }
          }, {
            "@type": "Question",
            "name": "What is a safe withdrawal rate for SWP?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "The widely referenced 4% Rule (from William Bengen's 1994 research and the Trinity Study) suggests withdrawing 4% of your initial corpus annually, adjusted for inflation, to sustain a 30-year retirement with a 95% success rate. For Indian investors in equity mutual funds averaging 12% returns, a 5-6% withdrawal rate may be sustainable. However, this depends on: (1) sequence of returns risk, (2) actual market performance, (3) inflation rate, and (4) retirement duration. Use the Advanced SIP and SWP Calculator at sipswpcalculator.com to stress-test different withdrawal rates against your expected returns."
            }
          }, {
            "@type": "Question",
            "name": "Which is better: SIP or Lump Sum investment?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "In a consistently rising market, Lump Sum mathematically outperforms SIP because money is invested longer. However, SIP is safer and more practical for volatile markets because it uses Rupee Cost Averaging (RCA), buying more units when prices are low and fewer when prices are high. Historical data: SIP in Nifty 50 over 10+ year periods has never delivered negative returns. For most salaried investors, SIP is recommended because it matches monthly income flows, removes timing risk, enforces discipline, and provides psychological ease during market crashes."
            }
          }, {
            "@type": "Question",
            "name": "Can I lose money in SIP?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Yes, in the short term. Since SIPs in equity mutual funds are market-linked, the NAV can fluctuate. However, historical data from AMFI India shows that over the long term (7-10+ years), the probability of negative returns in a diversified equity fund SIP is negligible. Nifty 50 rolling SIP returns: 1-year SIPs have seen negative returns in approximately 30% of periods, 5-year SIPs in approximately 10%, and 10-year SIPs in 0% of historical periods."
            }
          }, {
            "@type": "Question",
            "name": "What is the minimum amount to start a SIP in India?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Most mutual fund houses in India allow SIPs starting from as low as Rs 500/month. Some AMCs like SBI MF and HDFC MF offer micro-SIPs at Rs 100/month. The key insight: even Rs 500/month at 12% returns with 10% annual step-up grows to over Rs 5 Lakh in 15 years and Rs 17 Lakh in 20 years, demonstrating the power of compounding over time."
            }
          }, {
            "@type": "Question",
            "name": "How do I choose the right mutual fund for my SIP?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Consider these four factors: (1) Risk profile — large-cap funds for stability, mid-cap for balanced growth, small-cap for aggressive growth; (2) Expense ratio — lower is better, prefer direct plans over regular plans; (3) Track record — check 5-7 year consistency, not just 1-year returns; (4) Fund manager experience and AUM (Assets Under Management). Use AMFI India's mutual fund comparison tools for data-driven decisions."
            }
          }, {
            "@type": "Question",
            "name": "How are SWP withdrawals taxed in India (2026)?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "SWP withdrawals are treated as partial redemptions under Indian tax law. For equity mutual funds (2026 rules): Short-Term Capital Gains (STCG, held less than 1 year) are taxed at 20%. Long-Term Capital Gains (LTCG, held over 1 year) are taxed at 12.5% on gains exceeding Rs 1.25 Lakh per financial year. For debt mutual funds (purchased after April 2023): gains are taxed at your income slab rate regardless of holding period. Crucially, only the capital gains portion of each SWP withdrawal is taxable — the principal component is tax-free, making SWP significantly more tax-efficient than FD interest income."
            }
          }, {
            "@type": "Question",
            "name": "How long should I continue my SIP for best results?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "For equity mutual funds, a minimum of 7-10 years is recommended to ride out market cycles and benefit from compounding. Historical data shows that Nifty 50 SIPs held for 10+ years have never delivered negative returns, with average annualized returns of 12-15%. For retirement goals, 20-30 year SIPs with annual step-up yield the best compounding effect: a Rs 10,000/month SIP with 10% step-up at 12% returns grows to approximately Rs 3.54 Crore in 20 years."
            }
          }, {
            "@type": "Question",
            "name": "What step-up percentage should I use for my SIP?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "A 10% annual step-up is the most common recommendation, roughly matching average salary increments in India (8-12% annually). Conservative investors can use 5-7%, while aggressive savers might use 15-20%. Impact comparison for Rs 10,000/month base SIP at 12% for 20 years: 0% step-up = Rs 1 Crore, 5% step-up = Rs 1.73 Crore, 10% step-up = Rs 3.54 Crore, 15% step-up = Rs 5.7 Crore. Even a 5% step-up nearly doubles the final corpus compared to a flat SIP."
            }
          }, {
            "@type": "Question",
            "name": "Can NRIs invest in SIP in India?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Yes, NRIs can invest in mutual fund SIPs in India through their NRE (repatriable) or NRO (non-repatriable) bank accounts. Most AMCs accept NRI investments, though some sectoral or thematic funds may have restrictions for US/Canada-based NRIs due to FATCA (Foreign Account Tax Compliance Act) regulations. Tax treatment follows India's DTAA (Double Taxation Avoidance Agreement) provisions with the NRI's country of residence, with TDS deducted at source on redemptions."
            }
          }, {
            "@type": "Question",
            "name": "Is SIP better than a Recurring Deposit (RD)?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "For long-term goals (5+ years), equity SIPs have historically outperformed RDs by 5-8% annually. Comparison: RDs offer guaranteed returns of 6-7% but are fully taxable at income slab rate. Equity SIPs offer potential returns of 12-15% with favorable LTCG taxation (12.5% above Rs 1.25 Lakh). Example: Rs 10,000/month for 10 years — RD at 7% yields Rs 17.3 Lakh, equity SIP at 12% yields Rs 23.2 Lakh (34% more). For short-term goals (1-3 years), RDs or debt fund SIPs may be safer due to lower volatility."
            }
          }, {
            "@type": "Question",
            "name": "Can I stop or pause my SIP anytime?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Yes, SIPs are completely flexible. You can pause, stop, or modify your SIP amount at any time without penalties or exit loads on existing investments (exit loads may apply only on redemption of units held less than 1 year). Your existing invested units remain in the fund and continue to grow with market returns. However, stopping during market downturns is the most common investor mistake — it means you miss buying units at lower prices through rupee cost averaging, which is exactly when SIPs are most beneficial."
            }
          }, {
            "@type": "Question",
            "name": "Will my SWP deplete my corpus completely?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "It depends on your withdrawal rate versus your investment return. If you withdraw less than what your corpus earns, it can last indefinitely. The 4% Rule suggests withdrawing 4% of corpus annually for a sustainable 30-year retirement. Example: Rs 1 Crore corpus at 10% returns with Rs 40,000/month withdrawal — the corpus actually grows because annual returns (Rs 10 Lakh) exceed annual withdrawals (Rs 4.8 Lakh). At Rs 1 Lakh/month withdrawal, the corpus depletes in approximately 12 years. Use the calculator at sipswpcalculator.com to stress-test exactly when your corpus would be exhausted under different scenarios."
            }
          }]
        }
        </script>

    <?php include 'footer.php'; ?>

    </div>

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
    <script>
        // Pass chart data to the external script
        window.chartData = {
            years: <?php echo json_encode(array_values($years_data)); ?>,
            cumulative: <?php echo json_encode(array_values($cumulative_numbers)); ?>,
            corpus: <?php echo json_encode(array_values($combined_numbers)); ?>,
            swp: <?php echo json_encode(array_values($swp_numbers)); ?>
        };
    </script>
    <script defer src="script.js?v=<?= filemtime(__DIR__ . '/script.js') ?>"></script>

</body>

</html>