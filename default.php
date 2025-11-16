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
    <script>
        // Check for saved theme preference or default to light mode
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
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
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        };
    </script>
    <link rel="stylesheet" href="styles.css">
    <!-- Chart.js (modern build) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
    <!-- Chart.js Zoom plugin (adds wheel/pinch/drag zoom + pan) -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.umd.min.js"></script>
</head>

<body
    class="bg-gradient-to-br from-slate-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 text-gray-900 dark:text-gray-100 transition-colors duration-300">
    <!-- Theme toggle button - fixed position for mobile accessibility -->
    <button id="themeToggle" type="button"
        class="px-3 sm:px-4 py-2 rounded-lg bg-blue-100 dark:bg-gray-700 text-blue-900 dark:text-gray-200 hover:bg-blue-200 dark:hover:bg-gray-600 transition-colors font-medium text-xs sm:text-sm shadow-sm hover:shadow-md whitespace-nowrap"
        style="position: fixed; top: 1rem; right: 1rem; z-index: 9999; pointer-events: auto;">
        <span class="light-mode">🌙 Dark</span>
        <span class="dark-mode hidden">☀️ Light</span>
    </button>

    <div class="max-w-7xl mx-auto px-4 py-10">
        <header class="mb-8 text-center">
            <h1 class="text-4xl font-bold mb-4">Free SIP and SWP Calculator</h1>
            <p class="text-lg text-gray-400">Visualize your investment growth with our integrated tool.</p>
        </header>

        <div class="bg-gray-800 rounded-lg shadow-lg mb-6 p-6">
            <div>
                <form method="post" novalidate>
                    <fieldset class="mb-6">
                        <legend class="text-xl font-bold mb-4">SIP Details</legend>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Monthly SIP
                                    Investment</label>
                                <input type="number" step="0.01" name="sip"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500"
                                    required min="1" value="<?= htmlspecialchars((string) $sip) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Years of Investment</label>
                                <input type="number" name="years"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500"
                                    required min="1" value="<?= htmlspecialchars((string) $years) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Annual Interest Rate (%
                                    p.a.)</label>
                                <input type="number" step="0.01" name="rate"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500"
                                    required min="0" value="<?= htmlspecialchars((string) $rate) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Annual SIP Increase
                                    (%)</label>
                                <input type="number" step="0.01" name="stepup"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500"
                                    required min="0" value="<?= htmlspecialchars((string) $stepup) ?>">
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="mb-6">
                        <legend class="text-xl font-bold mb-4">SWP Details</legend>
                        <!-- SWP automatically starts the year after SIP ends -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Monthly SWP
                                    Withdrawal</label>
                                <input type="number" step="0.01" name="swp_withdrawal"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500"
                                    required min="0" value="<?= htmlspecialchars((string) $swp_withdrawal) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Annual SWP Increase
                                    (%)</label>
                                <input type="number" step="0.01" name="swp_stepup"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500"
                                    required min="0" value="<?= htmlspecialchars((string) $swp_stepup) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Number of SWP Years</label>
                                <input type="number" name="swp_years"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500"
                                    required min="1" value="<?= htmlspecialchars((string) $swp_years_input) ?>">
                            </div>
                        </div>
                        <p class="text-sm text-gray-400">Note: SWP automatically starts in the year immediately
                            following your SIP period. Monthly withdrawals are capped to available funds.</p>
                    </fieldset>
                    <div class="flex gap-2">
                        <button type="submit" name="action" value="calculate"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded font-medium">Calculate</button>
                        <button type="submit" name="action" value="download_csv"
                            class="px-6 py-2 bg-gray-700 hover:bg-gray-600 rounded font-medium">Download CSV
                            Report</button>
                        <button type="reset"
                            class="px-6 py-2 bg-gray-700 hover:bg-gray-600 rounded font-medium border border-red-600">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="bg-gray-800 rounded-lg shadow-lg mb-6 p-6">
            <div>
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Corpus, Cumulative Investment & SWP Withdrawals</h2>
                        <p class="text-sm text-gray-400">Hover to inspect. Scroll to zoom, pinch on touch, drag to zoom
                            selection. Alt+Drag to pan.</p>
                    </div>
                    <button id="resetZoomBtn" type="button"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded text-sm font-medium border border-gray-600">
                        Reset Zoom
                    </button>
                </div>
                <div class="relative" style="height: 360px;">
                    <canvas id="corpusChart"></canvas>
                </div>
            </div>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'download_csv'): ?>
            <div class="bg-gray-800 rounded-lg shadow-lg mb-6 p-6">
                <h5 class="text-lg font-bold mb-3">Note</h5>
                <p class="text-sm text-gray-400"><strong>All amounts are in USD.</strong> End-of-Year Corpus = your
                    portfolio value at the end of the year (includes all principal invested so far and interest earned). The
                    table below shows the year-by-year breakdown.</p>
            </div>

            <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-6">Yearly Report</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead class="bg-gray-900 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold"
                                    title="Financial year number of the simulation">Year</th>
                                <th class="px-4 py-3 text-left font-semibold"
                                    title="Corpus at the start of the year (carryover from previous year end)">Start-of-Year
                                    Corpus</th>
                                <th class="px-4 py-3 text-left font-semibold"
                                    title="Monthly SIP amount for that year (annual step-up applied)">Monthly SIP</th>
                                <th class="px-4 py-3 text-left font-semibold"
                                    title="Total SIP contributed during that year (Monthly SIP × 12)">Annual SIP
                                    Contribution</th>
                                <th class="px-4 py-3 text-left font-semibold"
                                    title="Total SIP contributed cumulatively up to this year">Total SIP Invested to Date
                                </th>
                                <th class="px-4 py-3 text-left font-semibold"
                                    title="Monthly SWP amount for that year (starts after SIP period)">Monthly SWP
                                    Withdrawal</th>
                                <th class="px-4 py-3 text-left font-semibold"
                                    title="Total SWP withdrawn during that year (sum of monthly withdrawals actually paid)">
                                    Annual SWP Withdrawal</th>
                                <th class="px-4 py-3 text-left font-semibold"
                                    title="Total SWP withdrawn cumulatively up to this year">Total SWP Withdrawals to Date
                                </th>
                                <th class="px-4 py-3 text-left font-semibold"
                                    title="Interest portion earned during this year (End Corpus - Start Corpus - Net Contribution)">
                                    Interest Earned This Year</th>
                                <th class="px-4 py-3 text-left font-semibold"
                                    title="Portfolio value at year end (includes principal + interest), also used by the chart">
                                    End-of-Year Corpus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($combined as $row): ?>
                                <tr class="border-b border-gray-700 hover:bg-gray-700">
                                    <td class="px-4 py-3"><?= $row['year'] ?></td>
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
                <p class="mt-4 text-sm text-gray-400 italic">Disclaimer: This tool is for illustrative purposes only and
                    does not constitute financial advice.</p>
            </div>
        <?php endif; ?>

    </div>

    <script>
        // Pass chart data to the external script
        window.chartData = {
            years: <?= json_encode($years_data) ?>,
            cumulative: <?= json_encode($cumulative_numbers) ?>,
            corpus: <?= json_encode($combined_numbers) ?>,
            swp: <?= json_encode($swp_numbers) ?>
        };
    </script>
    <script src="script.js"></script>
</body>

</html>