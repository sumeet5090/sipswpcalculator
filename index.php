<?php
declare(strict_types=1);

// Include the helper functions
require_once __DIR__ . '/functions.php';

// Default values.
$default_sip = 1000;
$default_years = 10;
$default_rate = 12;
$default_stepup = 10;
$default_swp_withdrawal = 10000;
$default_swp_stepup = 10;
$default_swp_years = 20;

// Retrieve POST values or use defaults.
$sip = isset($_POST['sip']) ? (float) $_POST['sip'] : $default_sip;
$years = isset($_POST['years']) ? (int) $_POST['years'] : $default_years;
$rate = isset($_POST['rate']) ? (float) $_POST['rate'] : $default_rate;
$stepup = isset($_POST['stepup']) ? (float) $_POST['stepup'] : $default_stepup;
$enable_swp = isset($_POST['enable_swp']) ? (bool) $_POST['enable_swp'] : true;
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
    <title>Free SIP & SWP Calculator | Plan Mutual Fund Investments</title>
    <meta name="description"
        content="Calculate SIP returns, SWP withdrawals, and visualize wealth creation with our free, advanced financial tools. Plan your retirement with accurate projections.">
    <meta name="keywords"
        content="SIP Calculator, SWP Calculator, Mutual Fund Calculator, Investment Planner, Wealth Creation, Retirement Planning, SIP vs SWP">
    <link rel="canonical" href="https://sipswpcalculator.com/">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sipswpcalculator.com/">
    <meta property="og:title" content="Advanced SIP & SWP Calculator for Investors">
    <meta property="og:description"
        content="Plan your financial future with our comprehensive SIP & SWP calculator. Visualize growth and withdrawals effortlessly.">
    <meta property="og:image" content="https://sipswpcalculator.com/assets/og-image-main.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://sipswpcalculator.com/">
    <meta property="twitter:title" content="Advanced SIP & SWP Calculator for Investors">
    <meta property="twitter:description"
        content="Plan your financial future with our comprehensive SIP & SWP calculator. Visualize growth and withdrawals effortlessly.">
    <meta property="twitter:image" content="https://sipswpcalculator.com/assets/og-image-main.jpg">

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "SIP & SWP Calculator",
      "applicationCategory": "FinanceApplication",
      "operatingSystem": "Web",
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
      },
      "description": "A comprehensive tool for calculating Systematic Investment Plans (SIP) and Systematic Withdrawal Plans (SWP) with visualization.",
      "featureList": "SIP Calculator, SWP Calculator, Inflation Adjustment, Visual Charts, CSV Export",
      "screenshot": "https://sipswpcalculator.com/assets/og-image-main.jpg",
      "author": {
          "@type": "Person",
          "name": "Finance Expert"
      }
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Table",
      "about": "Investment Comparison"
    }
    </script>
    </script>

    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/4149/4149678.png">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="styles.css?v=<?= time() ?>">
    <!-- Tailwind CSS (via CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        indigo: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="font-sans antialiased text-slate-200">
    <?php include 'navbar.php'; ?>
    <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">

        <header class="relative mb-16 text-center">
            <div
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 border border-indigo-100 mb-6">
                <span class="relative flex h-3 w-3">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                </span>
                <span class="text-sm font-semibold text-indigo-700 tracking-wide uppercase">Free Financial Planning
                    Tool</span>
            </div>

            <h1 class="text-3xl sm:text-5xl md:text-7xl font-extrabold pb-4 tracking-tight">
                <span class="text-gradient">Visualise Your</span> <br>
                <span class="text-gray-800">Wealth Journey</span>
            </h1>

            <p
                class="text-base sm:text-lg md:text-xl text-gray-500 max-w-2xl mx-auto leading-relaxed font-medium px-2 sm:px-0">
                The most advanced <strong class="text-indigo-600">SIP & SWP Calculator</strong> on the web.
                Seamlessly plan your investments and retirement income with vivid clarity.
                Make informed decisions about your financial future with our advanced, real-time calculator.
            </p>
        </header>

        <main>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Left Column: Currency + Form -->
                <div
                    class="lg:col-span-1 flex flex-col gap-4 sticky top-4 lg:max-h-[calc(100vh-2rem)] lg:overflow-y-auto pb-4 lg:pr-4 custom-scrollbar">

                    <!-- Currency Selector -->
                    <div class="flex justify-end relative z-20">
                        <div class="inline-flex rounded-md shadow-sm" role="group">
                            <input type="radio" name="currency" id="curr_inr" value="INR" class="peer/inr hidden"
                                checked onchange="updateCurrency('INR')">
                            <label for="curr_inr"
                                class="px-3 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg cursor-pointer hover:bg-gray-100 peer-checked/inr:bg-indigo-600 peer-checked/inr:text-white border-r-0 transition-colors">
                                ₹ INR
                            </label>

                            <input type="radio" name="currency" id="curr_usd" value="USD" class="peer/usd hidden"
                                onchange="updateCurrency('USD')">
                            <label for="curr_usd"
                                class="px-3 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 cursor-pointer hover:bg-gray-100 peer-checked/usd:bg-indigo-600 peer-checked/usd:text-white border-r-0 transition-colors">
                                $ USD
                            </label>

                            <input type="radio" name="currency" id="curr_eur" value="EUR" class="peer/eur hidden"
                                onchange="updateCurrency('EUR')">
                            <label for="curr_eur"
                                class="px-3 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-lg cursor-pointer hover:bg-gray-100 peer-checked/eur:bg-indigo-600 peer-checked/eur:text-white transition-colors">
                                € EUR
                            </label>
                        </div>
                    </div>

                    <!-- Form Section -->
                    <div class="glass-card p-4">
                        <form method="post" novalidate id="calculator-form">

                            <!-- Tab Bar -->
                            <div class="flex rounded-xl overflow-hidden border border-slate-200 mb-4" role="tablist">
                                <button type="button" id="tab-sip" role="tab" aria-selected="true"
                                    onclick="switchFormTab('sip')"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all duration-200 bg-emerald-500 text-white">
                                    <span
                                        class="flex items-center justify-center w-4 h-4 rounded-full bg-white/20 text-[9px]">1</span>
                                    SIP Details
                                </button>
                                <button type="button" id="tab-swp" role="tab" aria-selected="false"
                                    onclick="switchFormTab('swp')"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all duration-200 bg-white text-slate-400 hover:text-rose-400">
                                    <span
                                        class="flex items-center justify-center w-4 h-4 rounded-full bg-slate-100 text-[9px]">2</span>
                                    SWP Details
                                </button>
                            </div>

                            <!-- SIP Panel -->
                            <div id="panel-sip" role="tabpanel">
                                <div class="relative">
                                    <fieldset
                                        class="mb-4 relative z-10 bg-[var(--glass-bg)] p-5 rounded-3xl border border-[var(--glass-border)] shadow-xl backdrop-blur-xl">
                                        <legend
                                            class="flex items-center gap-2 text-xs font-bold text-emerald-500 mb-4 w-full tracking-widest uppercase">
                                            <div
                                                class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500/15 text-emerald-500 ring-1 ring-emerald-500/30 text-[10px]">
                                                1</div>
                                            SIP Details
                                        </legend>

                                        <div class="space-y-3">
                                            <!-- Row 1: Monthly + Duration -->
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <!-- Monthly Investment -->
                                                <div class="group">
                                                    <label for="sip"
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1 mb-1.5">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5"
                                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                        </svg>
                                                        Monthly
                                                    </label>
                                                    <div class="relative">
                                                        <span
                                                            class="currency-symbol absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">₹</span>
                                                        <input type="number" id="sip" name="sip"
                                                            class="w-full bg-white border border-slate-200 rounded-lg pl-6 pr-2.5 py-1.5 text-sm font-bold text-emerald-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
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
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1 mb-1.5">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5"
                                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Duration
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" id="years" name="years"
                                                            class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 pr-8 text-sm font-bold text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
                                                            required min="1" max="50"
                                                            value="<?= htmlspecialchars((string) $years) ?>">
                                                        <span
                                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">Yrs</span>
                                                    </div>
                                                    <input type="range" id="years_range" min="1" max="50" step="1"
                                                        value="<?= htmlspecialchars((string) $years) ?>"
                                                        class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-emerald-500 mt-2">
                                                </div>
                                            </div>

                                            <!-- Row 2: Return % + Yearly Step-up -->
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <!-- Expected Return -->
                                                <div class="group">
                                                    <label for="rate"
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1 mb-1.5">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                        </svg>
                                                        Return %
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" id="rate" step="0.1" name="rate"
                                                            class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 pr-6 text-sm font-bold text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
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
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1 mb-1.5">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                        </svg>
                                                        Step-up
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" id="stepup" step="1" name="stepup"
                                                            class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 pr-6 text-sm font-bold text-emerald-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
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
                                    </fieldset>
                                </div>
                            </div><!-- /panel-sip -->

                            <!-- SWP Panel -->
                            <div id="panel-swp" role="tabpanel" class="hidden">
                                <div class="relative">
                                    <fieldset
                                        class="relative z-10 bg-[var(--glass-bg)] p-5 rounded-3xl border border-[var(--glass-border)] shadow-xl backdrop-blur-xl">
                                        <legend
                                            class="flex items-center justify-between w-full mb-4 text-xs font-bold text-rose-400 tracking-widest uppercase">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="flex items-center justify-center w-5 h-5 rounded-full bg-rose-500/15 text-rose-400 ring-1 ring-rose-500/30 text-[10px]">
                                                    2</div>
                                                SWP Details
                                            </div>
                                            <label
                                                class="toggle-switch relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" id="enable_swp" name="enable_swp" checked
                                                    onchange="toggleSwpFields()" class="sr-only peer">
                                                <div
                                                    class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-rose-500">
                                                </div>
                                            </label>
                                        </legend>

                                        <div id="swp-fields" class="space-y-4 transition-all duration-300">
                                            <!-- Monthly Withdrawal -->
                                            <div class="group">
                                                <label for="swp_withdrawal"
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1 mb-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2.5"
                                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                    Monthly
                                                </label>
                                                <div class="relative">
                                                    <span
                                                        class="currency-symbol absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">₹</span>
                                                    <input type="number" id="swp_withdrawal" step="500"
                                                        name="swp_withdrawal"
                                                        class="w-full bg-white border border-slate-200 rounded-lg pl-6 pr-2.5 py-1.5 text-sm font-bold text-rose-500 focus:outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400/30 transition-colors"
                                                        required min="0" max="1000000"
                                                        value="<?= htmlspecialchars((string) $swp_withdrawal) ?>">
                                                </div>
                                                <input type="range" id="swp_withdrawal_range" min="1000" max="200000"
                                                    step="500" value="<?= htmlspecialchars((string) $swp_withdrawal) ?>"
                                                    class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-rose-500 mt-2">
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <!-- Withdrawal Duration -->
                                                <div class="group">
                                                    <label for="swp_years"
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1 mb-1.5">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5"
                                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Duration
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" id="swp_years" name="swp_years"
                                                            class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 pr-8 text-sm font-bold text-slate-700 focus:outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400/30 transition-colors"
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
                                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1 mb-1.5">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                        </svg>
                                                        Hike %
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" id="swp_stepup" step="0.1"
                                                            name="swp_stepup"
                                                            class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 pr-6 text-sm font-bold text-slate-700 focus:outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400/30 transition-colors"
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
                                    </fieldset>
                                </div>
                            </div><!-- /panel-swp -->


                        </form>
                    </div>
                </div> <!-- /left column -->

                <div class="lg:col-span-2 space-y-6">
                    <!-- Hero Result & Chart Card -->
                    <div
                        class="relative z-10 bg-[var(--glass-bg)] rounded-3xl border border-[var(--glass-border)] shadow-2xl backdrop-blur-xl overflow-hidden transition-all duration-300 hover:shadow-emerald-500/10 p-6">
                        <!-- Chart -->
                        <div class="h-[280px] sm:h-[350px] lg:h-[450px] w-full relative z-10">
                            <canvas id="corpusChart"></canvas>
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
                            class="text-sm px-4 py-2 flex items-center gap-2 rounded-lg font-semibold bg-slate-800 text-slate-300 border border-slate-700 hover:bg-slate-700 hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            CSV
                        </button>
                        <button type="button" id="openPdfModalBtn"
                            class="text-sm px-4 py-2 flex items-center gap-2 rounded-lg font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            PDF
                        </button>
                    </div>
                </div>

                <div class="glass-card overflow-hidden border border-slate-200 shadow-sm">
                    <div class="overflow-x-auto max-h-[600px] overflow-y-auto custom-scrollbar">
                        <table class="w-full text-sm text-left relative">
                            <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-4 bg-slate-50/95 border-b border-slate-200">Year</th>
                                    <th class="px-6 py-4 bg-slate-50/95 border-b border-slate-200 whitespace-nowrap">
                                        Start Corpus</th>
                                    <th
                                        class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-emerald-600 whitespace-nowrap">
                                        Annual SIP</th>
                                    <th
                                        class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 whitespace-nowrap">
                                        Total Invested</th>
                                    <?php if ($enable_swp): ?>
                                        <th
                                            class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-rose-500 whitespace-nowrap">
                                            Annual SWP</th>
                                        <th
                                            class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-slate-500 whitespace-nowrap">
                                            Total Withdrawn</th>
                                    <?php endif; ?>
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
                                        <td class="px-6 py-4 text-right font-mono"><?= formatInr($row['begin_balance']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono">
                                            <?= formatInr($row['annual_contribution']) ?></td>
                                        <td class="px-6 py-4 text-right text-slate-500 font-mono">
                                            <?= formatInr($row['cumulative_invested']) ?></td>
                                        <?php if ($enable_swp): ?>
                                            <td class="px-6 py-4 text-right text-rose-500 font-medium font-mono">
                                                <?= $row['annual_withdrawal'] !== null ? formatInr($row['annual_withdrawal']) : '-' ?>
                                            </td>
                                            <td class="px-6 py-4 text-right text-slate-500 font-mono">
                                                <?= $row['cumulative_withdrawals'] ? formatInr($row['cumulative_withdrawals']) : '-' ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono">
                                            <?= formatInr($row['interest']) ?></td>
                                        <td class="px-6 py-4 text-right font-bold text-slate-800 font-mono">
                                            <?= formatInr($row['combined_total']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


    </div>
    </main>

    <div class="text-center mt-8">
        <a href="/sip-calculator" class="text-indigo-600 hover:underline">
            Learn more about SIPs and how to use our calculator
        </a>
    </div>

    <div class="mt-12 glass-card p-8">
        <h2 class="text-3xl font-bold text-center mb-6">Master Your Financial Future with SIP & SWP</h2>
        <div class="prose prose-lg max-w-none text-gray-600">
            <p>Understanding the tools at your disposal is the first step toward effective financial planning. Our
                calculator is designed to demystify two of the most powerful tools for mutual fund investors: the
                Systematic Investment Plan (SIP) and the Systematic Withdrawal Plan (SWP).</p>

            <div class="grid md:grid-cols-2 gap-8 mt-8">
                <div>
                    <h3 class="text-2xl font-semibold mb-3 text-indigo-600">What is a Systematic Investment Plan
                        (SIP)?</h3>
                    <p>A SIP is a disciplined investment approach where you invest a fixed amount of money at
                        regular intervals (usually monthly) into a mutual fund scheme. Instead of making a large
                        one-time investment, you invest smaller amounts over time. This strategy helps in averaging
                        out the cost of your investment and harnesses the power of compounding.</p>
                    <ul class="mt-4 space-y-2">
                        <li><span class="font-semibold text-green-600">Dollar Cost Averaging:</span> Buy more units
                            when the market is low and fewer when it's high.</li>
                        <li><span class="font-semibold text-green-600">Power of Compounding:</span> Reinvesting your
                            returns generates earnings on your earnings, leading to exponential growth.</li>
                        <li><span class="font-semibold text-green-600">Disciplined Investing:</span> Automates the
                            habit of saving and investing regularly.</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-2xl font-semibold mb-3 text-purple-600">What is a Systematic Withdrawal Plan
                        (SWP)?</h3>
                    <p>An SWP is the reverse of a SIP. It allows you to withdraw a fixed amount of money from your
                        mutual fund investment at regular intervals. This is an ideal solution for generating a
                        regular cash flow from your investments, especially during retirement. It provides a steady
                        income stream while allowing the remaining investment to continue growing.</p>
                    <ul class="mt-4 space-y-2">
                        <li><span class="font-semibold text-green-600">Regular Income:</span> Create a predictable
                            cash flow from your investments.</li>
                        <li><span class="font-semibold text-green-600">Tax-Efficient:</span> Withdrawals are
                            structured to be tax-efficient, especially for long-term capital gains.</li>
                        <li><span class="font-semibold text-green-600">Continued Growth:</span> Your remaining
                            corpus stays invested and continues to benefit from market growth.</li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 bg-indigo-50/50 p-8 rounded-xl border border-indigo-100/50 backdrop-blur-sm">
                <h3 class="text-2xl font-bold text-indigo-700 mb-4">Real-Life Success Story: The "Mr. Sharma"
                    Strategy</h3>
                <p class="mb-4">Meet Mr. Sharma (30). He decides to invest <strong>₹10,000/month</strong> in an
                    Equity Mutual Fund via SIP for his retirement at age 60.</p>
                <ul class="list-disc pl-5 space-y-2 mb-4">
                    <li><strong>Goal:</strong> Retire with ₹5 Crores.</li>
                    <li><strong>Strategy:</strong> Step-up SIP. Increase investment by 10% every year as his salary
                        grows.</li>
                    <li><strong>Result:</strong> By age 60, avoiding the urge to stop during market lows, his corpus
                        grows exponentially due to compounding.</li>
                </ul>
                <p class="font-semibold">Moral: It's not just about starting early; it's about increasing your
                    investment as you grow.</p>
            </div>

            <div class="mt-12">
                <h2 class="text-3xl font-bold text-center mb-6">SIP vs RD vs FD vs PPF: A Comparison</h2>
                <div class="glass-card overflow-hidden">
                    <table class="min-w-full">
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
                <div class="space-y-6">
                    <div class="bg-gray-100 p-6 rounded-lg">
                        <h3 class="text-xl font-bold text-indigo-600 mb-2">Can I start an SWP immediately after my
                            SIP ends?</h3>
                        <p>Yes, absolutely. This is a common strategy for retirement planning. You accumulate a
                            corpus using SIP during your working years and then switch to SWP to generate a monthly
                            pension-like income post-retirement. Our calculator specifically models this seamless
                            transition.</p>
                    </div>
                    <div class="bg-gray-100 p-6 rounded-lg">
                        <h3 class="text-xl font-bold text-indigo-600 mb-2">Is SWP better than a fixed deposit
                            interest?</h3>
                        <p>Generally, yes. SWP from equity or hybrid mutual funds has the potential to offer higher
                            returns than fixed deposits over the long term. Additionally, SWP is more tax-efficient
                            because you are only taxed on the capital gains portion of the withdrawal, whereas FD
                            interest is fully taxable at your slab rate.</p>
                    </div>
                    <div class="bg-gray-100 p-6 rounded-lg">
                        <h3 class="text-xl font-bold text-indigo-600 mb-2">How does the "Step-up" feature work?</h3>
                        <p>A "Step-up" SIP means you increase your monthly investment amount by a certain percentage
                            every year (e.g., as your salary increases). This significantly boosts your final
                            corpus. Similarly, a "Step-up" SWP means you increase your withdrawal amount annually to
                            combat inflation.</p>
                    </div>
                    <div class="bg-gray-100 p-6 rounded-lg">
                        <h3 class="text-xl font-bold text-indigo-600 mb-2">What is a safe withdrawal rate for SWP?
                        </h3>
                        <p>Financial experts often recommend the "4% rule," suggesting you withdraw 4% of your
                            corpus annually. However, this depends on market conditions and your lifespan. Use our
                            <a href="/sequence-risk-analyzer" class="text-indigo-600 hover:underline">Sequence of
                                Returns Risk Analyzer</a> to test if your withdrawal rate is sustainable during
                            market crashes.
                        </p>
                    </div>
                    <div class="bg-gray-100 p-6 rounded-lg">
                        <h3 class="text-xl font-bold text-indigo-600 mb-2">Which is better: SIP or Lump Sum?</h3>
                        <p>In a rising market, Lump Sum often wins mathematically. However, SIP is psychologically
                            easier and safer for volatile markets as it benefits from <strong>Dollar Cost
                                Averaging</strong>, reducing the risk of investing a large amount at a market peak.
                        </p>
                    </div>
                    <div class="bg-gray-100 p-6 rounded-lg">
                        <h3 class="text-xl font-bold text-indigo-600 mb-2">Can I lose money in SIP?</h3>
                        <p>Yes, in the short term. Since SIPs in equity mutual funds are market-linked, the value
                            can fluctuate. However, historical data shows that over the long term (7-10+ years), the
                            probability of negative returns in a diversified fund is negligible.</p>
                    </div>
                </div>
            </div>

            <p class="text-center mt-8">Use our advanced calculator to model your SIP investments and plan your SWP
                withdrawals to see how you can achieve your financial goals, whether it's building a retirement
                corpus, funding your child's education, or creating a passive income stream.</p>
        </div>
    </div>

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
              "text": "Yes, since SIPs in equity mutual funds are market-linked, the value can fluctuate. However, over the long term (5-10+ years), the probability of negative returns decreases significantly."
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


    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Pass chart data to the external script
        window.chartData = {
            years: <?php echo json_encode(array_values($years_data)); ?>,
            cumulative: <?php echo json_encode(array_values($cumulative_numbers)); ?>,
            corpus: <?php echo json_encode(array_values($combined_numbers)); ?>,
            swp: <?php echo json_encode(array_values($swp_numbers)); ?>
        };
        console.log('Chart Data Loaded:', window.chartData);
    </script>
    <script src="script.js?v=<?= time() ?>"></script>

</body>

</html>