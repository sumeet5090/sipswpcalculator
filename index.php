<?php
declare(strict_types=1);

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

// Retrieve POST values or use defaults.
$sip = isset($_POST['sip']) ? (float) $_POST['sip'] : $default_sip;
$years = isset($_POST['years']) ? (int) $_POST['years'] : $default_years;
$rate = isset($_POST['rate']) ? (float) $_POST['rate'] : $default_rate;
$stepup = isset($_POST['stepup']) ? (float) $_POST['stepup'] : $default_stepup;
$enable_swp = isset($_POST['enable_swp']) ? (bool) $_POST['enable_swp'] : false;
$swp_withdrawal = isset($_POST['swp_withdrawal']) ? (float) $_POST['swp_withdrawal'] : $default_swp_withdrawal;
$swp_stepup = isset($_POST['swp_stepup']) ? (float) $_POST['swp_stepup'] : $default_swp_stepup;
$swp_years_input = isset($_POST['swp_years']) ? (int) $_POST['swp_years'] : $default_swp_years;
$action = $_POST['action'] ?? '';

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

    <!-- Structured Data: SoftwareApplication -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "SIP & SWP Calculator",
      "url": "https://sipswpcalculator.com/",
      "applicationCategory": "FinanceApplication",
      "operatingSystem": "Web",
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
      },
      "description": "Free online SIP calculator with step-up compounding and SWP retirement planner. Visualize mutual fund growth, yearly breakdown, and tax-efficient withdrawals.",
      "featureList": ["SIP Calculator", "SWP Calculator", "Step-Up SIP", "Visual Charts", "CSV Export", "Branded PDF Reports"],
      "screenshot": "https://sipswpcalculator.com/assets/og-image-main.jpg",
      "image": "https://sipswpcalculator.com/assets/og-image-main.jpg",
      "datePublished": "2024-12-01",
      "dateModified": "2026-02-25",
      "author": {
          "@type": "Person",
          "name": "Sumeet Boga",
          "url": "https://sipswpcalculator.com/"
      }
    }
    </script>
    <!-- Structured Data: FinancialProduct -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FinancialProduct",
      "name": "SIP & SWP Investment Calculator",
      "description": "Free online calculator for planning Systematic Investment Plans and Systematic Withdrawal Plans with step-up compounding, visual charts, and retirement income projections.",
      "url": "https://sipswpcalculator.com/",
      "provider": {
        "@type": "Person",
        "name": "Sumeet Boga",
        "url": "https://sipswpcalculator.com/"
      },
      "category": "Investment Planning Tool",
      "feesAndCommissionsSpecification": "Free — no fees or commissions",
      "areaServed": "Worldwide"
    }
    </script>
    <!-- Structured Data: WebSite (for sitelinks search box) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "SIP & SWP Calculator",
      "url": "https://sipswpcalculator.com/",
      "description": "Free online SIP and SWP calculator with step-up compounding, visual charts, and retirement income projections.",
      "publisher": {
        "@type": "Person",
        "name": "Sumeet Boga",
        "url": "https://sipswpcalculator.com/about"
      }
    }
    </script>

    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/favicon.png">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="styles.css?v=<?= filemtime(__DIR__ . '/styles.css') ?>">
    <!-- Tailwind CSS (production build, purged) -->
    <link rel="stylesheet" href="dist/tailwind.min.css?v=<?= filemtime(__DIR__ . '/dist/tailwind.min.css') ?>">
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
                    <img src="https://ui-avatars.com/api/?name=Sumeet+Boga&background=10b981&color=fff&rounded=true"
                        alt="Sumeet Boga" class="w-7 h-7 rounded-full shadow-sm border border-emerald-100">
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
            <div class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-6 mb-8 max-w-2xl mx-auto"
                role="complementary" aria-label="Quick Answer">
                <p class="text-sm font-bold text-emerald-800 mb-1">Quick Answer</p>
                <p class="text-base text-gray-700"><strong>How much will a ₹10,000/month SIP grow in 20 years?</strong>
                </p>
                <p class="text-sm text-gray-600 mt-1">At 12% annual returns with a 10% yearly step-up, a ₹10,000/month
                    SIP will grow to approximately <strong class="text-emerald-700">₹3.54 Crore</strong> over 20 years.
                    Total invested: ₹68.73 Lakh. Total gains: ₹2.85 Crore.</p>
            </div>

            <section aria-labelledby="calculator-heading">
                <h2 class="sr-only" id="calculator-heading">Calculate Your SIP & SWP Returns</h2>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <!-- Left Column: Currency + Form -->
                    <div
                        class="lg:col-span-1 flex flex-col gap-4 lg:sticky lg:top-4 lg:max-h-[calc(100vh-2rem)] lg:overflow-y-auto pb-4 lg:pr-4 custom-scrollbar">



                        <!-- Form Section -->
                        <div class="glass-card p-4">
                            <form method="post" novalidate id="calculator-form">

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
                                            class="currency-btn px-3 py-3 sm:py-1.5 text-xs font-semibold cursor-pointer transition-colors bg-white text-slate-500 hover:bg-slate-50"
                                            onclick="updateCurrency('EUR')">
                                            € EUR
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
                        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
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
        <h2 class="text-3xl font-bold text-center mb-6">Master Your Financial Future with SIP & SWP</h2>
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
                        As per <a href="https://www.amfiindia.com/" target="_blank" rel="noopener"
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
                <h2 class="text-2xl font-bold text-gray-800 mb-4">How to Use This SIP & SWP Calculator</h2>
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
                <h2 class="text-2xl font-bold text-gray-800 mb-4">SIP Calculator Formula</h2>
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
                    Source: <a href="https://www.amfiindia.com/" target="_blank" rel="noopener"
                        class="text-indigo-600 hover:underline">AMFI India</a> standard methodology.</p>
            </div>

            <!-- Worked Examples -->
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">SIP Return Examples: How Much Can You Earn?</h2>
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
                </div>
                <p class="text-xs text-gray-400 mt-4 text-center">Note: These are illustrative projections. Actual
                    returns depend on market conditions. Mutual fund investments are subject to market risks. Read all
                    scheme-related documents carefully.</p>
            </div>

            <!-- Risks Section -->
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Risks of SIP & SWP Investments</h2>
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
                            consult a <a href="https://www.sebi.gov.in/" target="_blank" rel="noopener"
                                class="text-indigo-600 hover:underline">SEBI</a>-registered financial advisor.</li>
                    </ul>
                </div>
            </div>

            <!-- Mr. Sharma story (promoted from H3 to H2) -->
            <div class="mt-12 bg-indigo-50/50 p-8 rounded-xl border border-indigo-100/50 backdrop-blur-sm">
                <h2 class="text-2xl font-bold text-indigo-700 mb-4">Real-Life Success Story: The "Mr. Sharma" Strategy
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
                <h2 class="text-3xl font-bold text-center mb-6">SIP vs RD vs FD vs PPF: A Comparison</h2>
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
                <h2 class="text-3xl font-bold text-center mb-8">Frequently Asked Questions</h2>
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

    <!-- FAQ Schema -->
    <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "FAQPage",
          "mainEntity": [{
            "@type": "Question",
            "name": "Can I start an SWP immediately after my SIP ends?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Yes, absolutely. This is a common strategy for retirement planning. You accumulate a corpus using SIP during your working years and then switch to SWP to generate a monthly pension-like income post-retirement."
            }
          }, {
            "@type": "Question",
            "name": "Is SWP better than a fixed deposit interest?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Generally, yes. SWP from equity or hybrid mutual funds has the potential to offer higher returns than fixed deposits over the long term. Additionally, SWP is more tax-efficient."
            }
          }, {
            "@type": "Question",
            "name": "How does the Step-up feature work?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "A Step-up SIP means you increase your monthly investment amount by a certain percentage every year. Similarly, a Step-up SWP means you increase your withdrawal amount annually to combat inflation."
            }
          }, {
            "@type": "Question",
            "name": "What is a safe withdrawal rate for SWP?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Financial experts often recommend the 4% rule, suggesting you withdraw 4% of your corpus annually. However, this depends on market conditions, your expected lifespan, and the sequence of investment returns."
            }
          }, {
            "@type": "Question",
            "name": "Which is better: SIP or Lump Sum?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "In a rising market, Lump Sum often wins. However, SIP is safer for volatile markets as it benefits from Dollar Cost Averaging, reducing the risk of investing at a market peak."
            }
          }, {
            "@type": "Question",
            "name": "Can I lose money in SIP?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Yes, since SIPs in equity mutual funds are market-linked, the value can fluctuate. However, over the long term (7-10+ years), the probability of negative returns decreases significantly."
            }
          }, {
            "@type": "Question",
            "name": "What is the minimum amount to start a SIP in India?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Most mutual fund houses in India allow SIPs starting from as low as ₹500/month. Some AMCs like SBI MF and HDFC MF offer micro-SIPs at ₹100/month."
            }
          }, {
            "@type": "Question",
            "name": "How do I choose the right mutual fund for my SIP?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Consider: (1) Risk profile — large-cap for stability, small-cap for growth; (2) Expense ratio — lower is better; (3) Track record — check 5-7 year consistency; (4) Fund manager experience."
            }
          }, {
            "@type": "Question",
            "name": "How are SWP withdrawals taxed in India (2026)?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "SWP withdrawals are treated as partial redemptions. For equity funds: STCG taxed at 20%, LTCG taxed at 12.5% on gains above ₹1.25 Lakh/year. Only the capital gains portion is taxable."
            }
          }, {
            "@type": "Question",
            "name": "How long should I continue my SIP for best results?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "For equity mutual funds, 7-10 years minimum is recommended. Historical data shows that Nifty 50 SIPs held for 10+ years have never delivered negative returns."
            }
          }, {
            "@type": "Question",
            "name": "Is SIP better than a Recurring Deposit (RD)?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "For long-term goals (5+ years), equity SIPs have historically outperformed RDs by 5-8% annually. RDs offer guaranteed returns but are fully taxable."
            }
          }, {
            "@type": "Question",
            "name": "What step-up percentage should I use for my SIP?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "A 10% annual step-up is the most common recommendation, roughly matching average salary increments in India. Conservative investors can use 5-7%, aggressive savers 15-20%."
            }
          }, {
            "@type": "Question",
            "name": "Can I stop or pause my SIP anytime?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Yes, SIPs are completely flexible. You can pause, stop, or modify your SIP at any time without penalties. Your existing invested units remain in the fund."
            }
          }, {
            "@type": "Question",
            "name": "Will my SWP deplete my corpus completely?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "It depends on your withdrawal rate vs. investment return. If you withdraw less than what your corpus earns, it can last indefinitely. The 4% rule suggests withdrawing 4% annually for a 30-year retirement."
            }
          }, {
            "@type": "Question",
            "name": "Can NRIs invest in SIP in India?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Yes, NRIs can invest in mutual fund SIPs in India through NRE or NRO accounts. Most AMCs accept NRI investments, though some may have restrictions due to FATCA regulations."
            }
          }, {
            "@type": "Question",
            "name": "How does inflation affect my SIP and SWP planning?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Inflation (5-6% in India) erodes purchasing power. At 6% inflation, ₹1 Lakh today is worth only ₹31,000 in 20 years. Step-up SIPs and step-up SWPs help outpace inflation."
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
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Create Branded PDF Report</h2>
            <form id="pdfForm" class="space-y-4">
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