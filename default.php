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
    $csv->fputcsv([
        'Year',
        'Start-of-Year Corpus',
        'Monthly SIP',
        'Annual SIP Contribution',
        'Total SIP Invested to Date',
        'Monthly SWP Withdrawal',
        'Annual SWP Withdrawal',
        'Total SWP Withdrawals to Date',
        'Interest Earned This Year',
        'End-of-Year Corpus'
    ]);
    for ($y = 1; $y <= $simulation_years; $y++) {
        $row = $combined[$y];
        $csv->fputcsv([
            $row['year'],
            formatInr($row['begin_balance']),
            $row['sip_monthly'] !== null ? formatInr($row['sip_monthly']) : '-',
            formatInr($row['annual_contribution']),
            formatInr($row['cumulative_invested']),
            $row['swp_monthly'] !== null ? formatInr($row['swp_monthly']) : '-',
            $row['annual_withdrawal'] !== null ? formatInr($row['annual_withdrawal']) : '-',
            $row['cumulative_withdrawals'] ? formatInr($row['cumulative_withdrawals']) : '-',
            formatInr($row['interest']),
            formatInr($row['combined_total'])
        ]);
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIP SWP Calculator</title>
    
    <!-- Preload critical CSS and fonts -->
    <link rel="preload" href="styles.css" as="style">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.tailwindcss.com">
    
    <script>
        // Initialize theme on page load (runs before rendering) - Critical path
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            if (savedTheme === 'light') {
                document.documentElement.classList.add('light');
            }
        })();
    </script>
    
    <meta name="description" content="Use our free SIP & SWP calculator to plan your investments. A simple, accurate tool designed for global investors.">
    <link rel="canonical" href="https://sipswpcalculator.com">
    <meta name="robots" content="index,follow">
    
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebApplication",
            "name": "SIP SWP Calculator",
            "url": "https://sipswpcalculator.com",
            "applicationCategory": "Finance",
            "description": "A free, easy-to-use SIP SWP calculator for Global investors."
        }
    </script>
    
    <!-- Load custom CSS first (synchronous, critical) -->
    <link rel="stylesheet" href="styles.css">
    
    <!-- Defer Tailwind CSS (non-critical, can load async) -->
    <script defer src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwindcss.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        accent: '#8b5cf6',
                    }
                }
            }
        }
    </script>
</head>

<body class="light:bg-gradient-to-br light:from-slate-50 light:via-white light:to-slate-100 dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 light:text-slate-900 dark:text-gray-100 min-h-screen transition-colors duration-300">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-50 focus:bg-indigo-600 focus:text-white focus:p-2">Skip to main content</a>
    
    <!-- Theme Toggle Button -->
    <button id="themeToggle" type="button" aria-label="Toggle between dark and light theme"
        class="fixed top-6 right-6 z-50 p-3 rounded-full backdrop-blur-md transition-all duration-300 shadow-lg hover:shadow-xl
        light:bg-slate-900/10 light:hover:bg-slate-900/20 light:text-slate-700 light:border light:border-slate-300
        dark:bg-slate-100/10 dark:hover:bg-slate-100/20 dark:text-slate-200 dark:border dark:border-slate-700">
        <!-- Moon icon (for dark mode) -->
        <span id="moonIcon" class="inline-block text-2xl transition-transform duration-300">
            🌙
        </span>
        <!-- Sun icon (for light mode) -->
        <span id="sunIcon" class="hidden inline-block text-2xl transition-transform duration-300">
            ☀️
        </span>
    </button>
    
    <header role="banner" class="relative overflow-hidden py-16 px-4 sm:px-6 lg:px-8">
        <!-- Decorative background elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-80 h-80 light:bg-blue-200/30 dark:bg-indigo-600/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 light:bg-purple-200/30 dark:bg-purple-600/20 rounded-full blur-3xl"></div>
        </div>
        
        <!-- Content -->
        <div class="relative max-w-7xl mx-auto text-center">
            <h1 class="text-5xl sm:text-6xl font-bold light:text-slate-900 dark:text-white mb-4 drop-shadow-lg">
                Free SIP & SWP Calculator
            </h1>
            <p class="text-xl light:text-slate-700 dark:text-slate-300 max-w-2xl mx-auto">
                Plan, visualize, and optimize your investment strategy with our powerful financial calculator
            </p>
        </div>
    </header>

    <main id="main-content" role="main" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">

        <div class="card-gradient mb-8 p-8" style="margin-top: -2rem;">
            <form method="post" novalidate>
                    <fieldset class="mb-6">
                        <legend class="text-xl font-bold mb-6 light:text-purple-900 dark:text-white">SIP Details</legend>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium mb-2">Monthly SIP Investment</label>
                                <input type="number" step="0.01" name="sip"
                                    class="w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    required min="1" value="<?= htmlspecialchars((string) $sip) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Years of Investment</label>
                                <input type="number" name="years"
                                    class="w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    required min="1" value="<?= htmlspecialchars((string) $years) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Annual Interest Rate (% p.a.)</label>
                                <input type="number" step="0.01" name="rate"
                                    class="w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    required min="0" value="<?= htmlspecialchars((string) $rate) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Annual SIP Increase (%)</label>
                                <input type="number" step="0.01" name="stepup"
                                    class="w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    required min="0" value="<?= htmlspecialchars((string) $stepup) ?>">
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="mt-10">
                        <legend class="text-xl font-bold mb-6 light:text-purple-900 dark:text-white">SWP Details</legend>
                        <p class="text-sm light:text-slate-600 dark:text-slate-400 mb-6">SWP automatically starts the year after SIP ends. Monthly withdrawals are capped to available funds.</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium mb-2">Monthly SWP Withdrawal</label>
                                <input type="number" step="0.01" name="swp_withdrawal"
                                    class="w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    required min="0" value="<?= htmlspecialchars((string) $swp_withdrawal) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Annual SWP Increase (%)</label>
                                <input type="number" step="0.01" name="swp_stepup"
                                    class="w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    required min="0" value="<?= htmlspecialchars((string) $swp_stepup) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Number of SWP Years</label>
                                <input type="number" name="swp_years"
                                    class="w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    required min="1" value="<?= htmlspecialchars((string) $swp_years_input) ?>">
                            </div>
                        </div>
                    </fieldset>

                    <div class="flex flex-wrap gap-4 mt-10">
                        <button type="submit" name="action" value="calculate"
                            class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 text-white font-semibold rounded-lg shadow-lg transition-all duration-300">Calculate Results</button>
                        <button type="submit" name="action" value="download_csv"
                            class="px-8 py-3 bg-slate-700 hover:bg-slate-600 text-slate-100 font-semibold rounded-lg border border-slate-600 transition-all duration-300">Download CSV Report</button>
                        <button type="reset"
                            class="px-8 py-3 bg-slate-700 hover:bg-slate-600 text-slate-100 font-semibold rounded-lg border border-slate-600 transition-all duration-300">Reset Form</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Chart Section -->
        <section class="card-gradient mb-8 p-8 mt-6" aria-label="Investment growth visualization">
            <div class="mb-8">
                <h2 class="text-2xl font-bold mb-3 light:text-purple-900 dark:text-white">Investment Growth Visualization</h2>
                <p class="text-sm light:text-slate-600 dark:text-slate-400">Interactive chart: Use mouse scroll to zoom, pinch on touch to zoom, drag to select zoom area. Hold Alt and drag to pan.</p>
            </div>
            <div class="flex justify-end mb-6">
                <button id="resetZoomBtn" type="button" aria-label="Reset chart zoom to default view"
                    class="px-6 py-2.5 light:bg-purple-600 light:hover:bg-purple-700 light:text-white dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-100 font-semibold rounded-lg light:border-0 dark:border dark:border-slate-600 transition-all duration-300 shadow-sm">
                    Reset Zoom
                </button>
            </div>
            <div class="relative rounded-lg overflow-hidden" style="height: 420px; background: rgba(0,0,0,0.02);" role="img" aria-label="Line chart showing corpus, cumulative investment, and SWP withdrawals over years">
                <canvas id="corpusChart" role="img" aria-label="Investment growth chart"></canvas>
            </div>
        </section>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'download_csv'): ?>
            <section class="card-gradient mb-8 p-8 mt-6" aria-label="Results explanation">
                <h5 class="text-lg font-bold mb-4 mt-0 light:text-purple-900 dark:text-white">Summary</h5>
                <div class="space-y-3 light:text-slate-700 dark:text-slate-300">
                    <p class="flex items-start gap-3">
                        <span class="text-lg light:text-purple-600 dark:text-indigo-400 font-bold flex-shrink-0">✓</span>
                        <span><strong class="light:text-purple-900 dark:text-slate-100">All amounts are in USD.</strong> The End-of-Year Corpus represents your portfolio value at year-end (all principal invested plus interest earned).</span>
                    </p>
                    <p class="flex items-start gap-3">
                        <span class="text-lg light:text-purple-600 dark:text-indigo-400 font-bold flex-shrink-0">✓</span>
                        <span>The table below provides a detailed year-by-year breakdown of your SIP contributions, SWP withdrawals, and compound growth.</span>
                    </p>
                </div>
            </section>

            <section class="card-gradient p-8 mt-6" aria-label="Detailed yearly report">
                <h2 class="text-2xl font-bold mb-6 mt-0">Detailed Yearly Report</h2>
                <div class="overflow-x-auto rounded-lg" style="box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                    <table class="w-full text-sm border-collapse">
                        <thead class="table-header-gradient sticky top-0">
                            <tr>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider" title="Financial year number">Year</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider" title="Corpus at year start">Start Corpus</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider" title="Monthly SIP amount">Monthly SIP</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider" title="Annual SIP contribution">Annual SIP</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider" title="Cumulative SIP">Total SIP Invested</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider" title="Monthly withdrawal">Monthly SWP</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider" title="Annual withdrawal">Annual SWP</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider" title="Cumulative withdrawals">Total SWP</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider" title="Interest earned">Interest</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider" title="End of year corpus">End Corpus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($combined as $row): ?>
                                <tr class="border-b border-gray-700 hover:bg-gray-700">
                                    <td scope="row" class="px-4 py-3"><?= $row['year'] ?></td>
                                    <td class="px-4 py-3"><?= formatInr($row['begin_balance']) ?></td>
                                    <td class="px-4 py-3">
                                        <?= $row['sip_monthly'] !== null ? formatInr($row['sip_monthly']) : '-' ?>
                                    </td>
                                    <td class="px-4 py-3"><?= formatInr($row['annual_contribution']) ?></td>
                                    <td class="px-4 py-3"><?= formatInr($row['cumulative_invested']) ?></td>
                                    <td class="px-4 py-3">
                                        <?= $row['swp_monthly'] !== null ? formatInr($row['swp_monthly']) : '-' ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?= $row['annual_withdrawal'] !== null ? formatInr($row['annual_withdrawal']) : '-' ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?= $row['cumulative_withdrawals'] ? formatInr($row['cumulative_withdrawals']) : '-' ?>
                                    </td>
                                    <td class="px-4 py-3"><?= formatInr($row['interest']) ?></td>
                                    <td class="px-4 py-3"><?= formatInr($row['combined_total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <!-- Footer Section -->
    <footer role="contentinfo" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 mb-8">
        <div class="card-gradient p-8 md:p-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8 mt-0">
                <!-- About Section -->
                <div>
                    <h3 class="text-lg font-bold mb-3 mt-0 light:text-purple-900 dark:text-indigo-300">About Calculator</h3>
                    <p class="text-sm light:text-slate-700 dark:text-slate-400 leading-relaxed">
                        A powerful, free tool designed to help investors visualize and plan their SIP and SWP strategies with precision and ease.
                    </p>
                </div>
                
                <!-- Quick Links Section -->
                <div>
                    <h3 class="text-lg font-bold mb-3 mt-0 light:text-purple-900 dark:text-indigo-300">Features</h3>
                    <ul class="text-sm space-y-2 light:text-slate-700 dark:text-slate-400">
                        <li class="flex items-center">
                            <span class="mr-2 light:text-purple-600 dark:text-indigo-400">✓</span>
                            <span>Real-time calculations</span>
                        </li>
                        <li class="flex items-center">
                            <span class="mr-2 light:text-purple-600 dark:text-indigo-400">✓</span>
                            <span>Interactive visualization</span>
                        </li>
                        <li class="flex items-center">
                            <span class="mr-2 light:text-purple-600 dark:text-indigo-400">✓</span>
                            <span>CSV export</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Support Section -->
                <div>
                    <h3 class="text-lg font-bold mb-3 mt-0 light:text-purple-900 dark:text-indigo-300">Support</h3>
                    <p class="text-sm light:text-slate-700 dark:text-slate-400 leading-relaxed">
                        For questions or feedback about this calculator, 
                        <a href="mailto:help@sipswpcalculator.com" class="font-semibold light:text-purple-600 dark:text-indigo-400 hover:light:text-purple-700 hover:dark:text-indigo-300 underline transition-colors">reach out to us</a>. 
                        We're here to help you achieve your financial goals.
                    </p>
                </div>
            </div>

            <!-- Disclaimer Section -->
            <div class="border-t light:border-purple-200 dark:border-slate-700 pt-6 mt-0">
                <div class="mb-6 p-4 light:bg-amber-50/50 dark:bg-amber-950/20 rounded-lg light:border light:border-amber-200/50 dark:border-amber-900/30">
                    <p class="text-sm light:text-amber-900 dark:text-amber-200">
                        <span class="font-bold block mb-2">⚠️ Disclaimer</span>
                        <span>This calculator is for educational and illustrative purposes only. It does not constitute financial, tax, or investment advice. Past performance is not indicative of future results. Please consult with a qualified financial advisor before making any investment decisions.</span>
                    </p>
                </div>

                <!-- Copyright Section -->
                <div class="text-center border-t light:border-purple-200 dark:border-slate-700 pt-6">
                    <p class="text-xs light:text-slate-600 dark:text-slate-500 font-medium">
                        © 2024 SIP/SWP Calculator. All rights reserved.
                    </p>
                    <p class="text-xs light:text-slate-500 dark:text-slate-600 mt-2">
                        Built with care for global investors • <span class="light:text-purple-600 dark:text-indigo-400">No tracking • No ads • Free forever</span>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Defer non-critical Chart.js and custom scripts to end of body -->
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.umd.min.js"></script>
    
    <script defer>
        // Pass chart data to the external script
        window.chartData = {
            years: <?= json_encode($years_data) ?>,
            cumulative: <?= json_encode($cumulative_numbers) ?>,
            corpus: <?= json_encode($combined_numbers) ?>,
            swp: <?= json_encode($swp_numbers) ?>
        };
    </script>
    
    <script defer src="script.js"></script>

</body>

</html>