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
<?php
// Function to get the theme from the cookie or system preference
function get_theme() {
    if (isset($_COOKIE['theme'])) {
        return $_COOKIE['theme'];
    }
    // Fallback for server-side rendering if you can't access client headers easily
    return 'light'; 
}
$theme = get_theme();
?>
<!DOCTYPE html>
<html lang="en" class="<?= $theme === 'dark' ? 'dark' : '' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced SIP & SWP Calculator for Investors | Plan Your Mutual Fund Investments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        // This script now only handles the initial state for clients without cookies
        // and ensures the toggle button icon is correct on first load.
        document.addEventListener('DOMContentLoaded', () => {
            const theme = localStorage.getItem('theme');
            const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
            const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                themeToggleLightIcon.classList.remove('hidden');
                themeToggleDarkIcon.classList.add('hidden');
            } else {
                themeToggleDarkIcon.classList.remove('hidden');
                themeToggleLightIcon.classList.add('hidden');
            }
        });
    </script>
</head>

<body class="bg-slate-100 dark:bg-gradient-to-br dark:from-slate-900 dark:to-slate-800 text-slate-800 dark:text-slate-200 transition-colors duration-300">

    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <header class="relative mb-8">
            <div class="text-center">
                <h1 class="text-4xl sm:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-purple-500 dark:from-indigo-400 dark:to-purple-400 pb-2">
                    Future-Proof Your Finances
                </h1>
                <p class="text-lg sm:text-xl text-slate-600 dark:text-slate-300 max-w-2xl mx-auto">
                    The Ultimate SIP & SWP Calculator to Visualize Your Wealth Creation and Withdrawal Journey
                </p>
            </div>
            <button id="theme-toggle" class="absolute top-0 right-0 p-2 rounded-full text-slate-500 dark:text-slate-400 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg id="theme-toggle-dark-icon" class="hidden h-6 w-6" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                <svg id="theme-toggle-light-icon" class="hidden h-6 w-6" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 01-1 1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM10 18a1 1 0 01-1-1v-1a1 1 0 112 0v1a1 1 0 01-1 1zM5.05 14.95a1 1 0 010-1.414l.707-.707a1 1 0 011.414 1.414l-.707.707a1 1 0 01-1.414 0zM1.707 4.293a1 1 0 00-1.414 1.414l.707.707a1 1 0 001.414-1.414l-.707-.707z"></path></svg>
            </button>
        </header>

        <main>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Form Section -->
                <div class="lg:col-span-1 bg-white dark:bg-slate-800/50 dark:backdrop-blur-sm p-6 rounded-xl shadow-lg border dark:border-slate-700">
                    <form method="post" novalidate>
                        <fieldset class="mb-6">
                            <legend class="text-xl font-semibold mb-4 text-indigo-600 dark:text-indigo-400">SIP Details</legend>
                            <div class="space-y-4">
                                <div>
                                    <label for="sip" class="block text-sm font-medium mb-1">Monthly Investment</label>
                                    <input type="number" id="sip" name="sip" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="1" value="<?= htmlspecialchars((string) $sip) ?>">
                                </div>
                                <div>
                                    <label for="years" class="block text-sm font-medium mb-1">Investment Period (Years)</label>
                                    <input type="number" id="years" name="years" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="1" value="<?= htmlspecialchars((string) $years) ?>">
                                </div>
                                <div>
                                    <label for="rate" class="block text-sm font-medium mb-1">Expected Return Rate (% p.a.)</label>
                                    <input type="number" id="rate" step="0.01" name="rate" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="0" value="<?= htmlspecialchars((string) $rate) ?>">
                                </div>
                                <div>
                                    <label for="stepup" class="block text-sm font-medium mb-1">Annual Step-up (%)</label>
                                    <input type="number" id="stepup" step="0.01" name="stepup" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="0" value="<?= htmlspecialchars((string) $stepup) ?>">
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="text-xl font-semibold mb-4 text-indigo-600 dark:text-indigo-400">SWP Details</legend>
                            <div class="space-y-4">
                                <div>
                                    <label for="swp_withdrawal" class="block text-sm font-medium mb-1">Monthly Withdrawal</label>
                                    <input type="number" id="swp_withdrawal" step="0.01" name="swp_withdrawal" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="0" value="<?= htmlspecialchars((string) $swp_withdrawal) ?>">
                                </div>
                                <div>
                                    <label for="swp_stepup" class="block text-sm font-medium mb-1">Annual Withdrawal Increase (%)</label>
                                    <input type="number" id="swp_stepup" step="0.01" name="swp_stepup" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="0" value="<?= htmlspecialchars((string) $swp_stepup) ?>">
                                </div>
                                <div>
                                    <label for="swp_years" class="block text-sm font-medium mb-1">Withdrawal Period (Years)</label>
                                    <input type="number" id="swp_years" name="swp_years" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="1" value="<?= htmlspecialchars((string) $swp_years_input) ?>">
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex flex-wrap gap-4 mt-8">
                            <button type="submit" name="action" value="calculate" class="flex-1 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition-all duration-300">Calculate</button>
                            <button type="submit" name="action" value="download_csv" class="flex-1 px-6 py-3 bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded-lg shadow-md transition-all duration-300">Download CSV</button>
                        </div>
                    </form>
                </div>

                <!-- Chart and Table Section -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white dark:bg-slate-800/50 dark:backdrop-blur-sm p-6 rounded-xl shadow-lg border dark:border-slate-700">
                        <h2 class="text-2xl font-semibold mb-4">Investment Journey</h2>
                        <div class="h-96">
                            <canvas id="corpusChart"></canvas>
                        </div>
                    </div>

                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'download_csv'): ?>
                    <div class="bg-white dark:bg-slate-800/50 dark:backdrop-blur-sm rounded-xl shadow-lg overflow-hidden border dark:border-slate-700">
                        <div class="p-6">
                            <h2 class="text-2xl font-semibold">Yearly Breakdown</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs uppercase font-semibold">
                                    <tr>
                                        <th class="px-6 py-3">Year</th>
                                        <th class="px-6 py-3 text-right">Start Corpus</th>
                                        <th class="px-6 py-3 text-right">Annual SIP</th>
                                        <th class="px-6 py-3 text-right">Total Invested</th>
                                        <th class="px-6 py-3 text-right">Annual SWP</th>
                                        <th class="px-6 py-3 text-right">Total Withdrawn</th>
                                        <th class="px-6 py-3 text-right">Interest</th>
                                        <th class="px-6 py-3 text-right">End Corpus</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <?php foreach ($combined as $row): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                            <td class="px-6 py-4 font-medium"><?= $row['year'] ?></td>
                                            <td class="px-6 py-4 text-right"><?= formatInr($row['begin_balance']) ?></td>
                                            <td class="px-6 py-4 text-right text-green-600 dark:text-green-400"><?= formatInr($row['annual_contribution']) ?></td>
                                            <td class="px-6 py-4 text-right"><?= formatInr($row['cumulative_invested']) ?></td>
                                            <td class="px-6 py-4 text-right text-red-600 dark:text-red-400"><?= $row['annual_withdrawal'] !== null ? formatInr($row['annual_withdrawal']) : '-' ?></td>
                                            <td class="px-6 py-4 text-right"><?= $row['cumulative_withdrawals'] ? formatInr($row['cumulative_withdrawals']) : '-' ?></td>
                                            <td class="px-6 py-4 text-right"><?= formatInr($row['interest']) ?></td>
                                            <td class="px-6 py-4 text-right font-semibold text-indigo-600 dark:text-indigo-400"><?= formatInr($row['combined_total']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <div class="text-center mt-8">
    <a href="sip-calculator.php" class="text-indigo-600 dark:text-indigo-400 hover:underline">
        Learn more about SIPs and how to use our calculator
    </a>
</div>

<div class="mt-12 bg-white dark:bg-slate-800/50 dark:backdrop-blur-sm rounded-xl shadow-lg border dark:border-slate-700 p-8">
            <h2 class="text-3xl font-bold text-center mb-6">Master Your Financial Future with SIP & SWP</h2>
            <div class="prose prose-lg max-w-none text-slate-600 dark:text-slate-300">
                <p>Understanding the tools at your disposal is the first step toward effective financial planning. Our calculator is designed to demystify two of the most powerful tools for mutual fund investors: the Systematic Investment Plan (SIP) and the Systematic Withdrawal Plan (SWP).</p>
                
                <div class="grid md:grid-cols-2 gap-8 mt-8">
                    <div>
                        <h3 class="text-2xl font-semibold mb-3 text-indigo-600 dark:text-indigo-400">What is a Systematic Investment Plan (SIP)?</h3>
                        <p>A SIP is a disciplined investment approach where you invest a fixed amount of money at regular intervals (usually monthly) into a mutual fund scheme. Instead of making a large one-time investment, you invest smaller amounts over time. This strategy helps in averaging out the cost of your investment and harnesses the power of compounding.</p>
                        <ul class="mt-4 space-y-2">
                            <li><span class="font-semibold text-green-500">Rupee Cost Averaging:</span> Buy more units when the market is low and fewer when it's high.</li>
                            <li><span class="font-semibold text-green-500">Power of Compounding:</span> Reinvesting your returns generates earnings on your earnings, leading to exponential growth.</li>
                            <li><span class="font-semibold text-green-500">Disciplined Investing:</span> Automates the habit of saving and investing regularly.</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-2xl font-semibold mb-3 text-purple-600 dark:text-purple-400">What is a Systematic Withdrawal Plan (SWP)?</h3>
                        <p>An SWP is the reverse of a SIP. It allows you to withdraw a fixed amount of money from your mutual fund investment at regular intervals. This is an ideal solution for generating a regular cash flow from your investments, especially during retirement. It provides a steady income stream while allowing the remaining investment to continue growing.</p>
                        <ul class="mt-4 space-y-2">
                            <li><span class="font-semibold text-green-500">Regular Income:</span> Create a predictable cash flow from your investments.</li>
                            <li><span class="font-semibold text-green-500">Tax-Efficient:</span> Withdrawals are structured to be tax-efficient, especially for long-term capital gains.</li>
                            <li><span class="font-semibold text-green-500">Continued Growth:</span> Your remaining corpus stays invested and continues to benefit from market growth.</li>
                        </ul>
                    </div>
                </div>
                <p class="text-center mt-8">Use our advanced calculator to model your SIP investments and plan your SWP withdrawals to see how you can achieve your financial goals, whether it's building a retirement corpus, funding your child's education, or creating a passive income stream.</p>
            </div>
        </div>

        <footer class="mt-12 text-sm text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-800/50 dark:backdrop-blur-sm rounded-xl shadow-lg border dark:border-slate-700 p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div>
                    <h3 class="font-bold text-lg mb-3 text-slate-800 dark:text-slate-200">About Calculator</h3>
                    <p class="leading-relaxed">A powerful, free tool designed to help investors visualize and plan their SIP and SWP strategies with precision and ease.</p>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-3 text-slate-800 dark:text-slate-200">Features</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Real-time calculations</li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Interactive visualization</li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> CSV export</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-3 text-slate-800 dark:text-slate-200">Support</h3>
                    <p class="leading-relaxed">For questions or feedback about this calculator, <a href="mailto:help@sipswpcalculator.com" class="text-indigo-600 dark:text-indigo-400 hover:underline">reach out to us</a>. We're here to help you achieve your financial goals.</p>
                </div>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                <div class="bg-amber-100/50 dark:bg-amber-900/20 p-4 rounded-lg mb-6 border border-amber-200/50 dark:border-amber-800/30">
                    <p class="text-amber-800 dark:text-amber-200"><span class="font-bold">⚠️ Disclaimer</span><br>This calculator is for educational and illustrative purposes only. It does not constitute financial, tax, or investment advice. Past performance is not indicative of future results. Please consult with a qualified financial advisor before making any investment decisions.</p>
                </div>

                <div class="text-center">
                    <p class="text-xs">&copy; <?= date('Y') ?> SIP/SWP Calculator. All rights reserved.</p>
                    <p class="text-xs mt-2">Built with care for global investors • <span class="text-indigo-600 dark:text-indigo-400">No tracking • No ads • Free forever</span></p>
                </div>
            </div>
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
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
