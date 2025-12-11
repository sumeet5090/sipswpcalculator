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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free SIP & SWP Calculator | Plan Mutual Fund Investments</title>
    <meta name="description" content="Calculate SIP returns, SWP withdrawals, and visualize wealth creation with our free, advanced financial tools. Plan your retirement with accurate projections.">
    <meta name="keywords" content="SIP Calculator, SWP Calculator, Mutual Fund Calculator, Investment Planner, Wealth Creation, Retirement Planning, SIP vs SWP">
    <link rel="canonical" href="https://sipswpcalculator.com/">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sipswpcalculator.com/">
    <meta property="og:title" content="Advanced SIP & SWP Calculator for Investors">
    <meta property="og:description" content="Plan your financial future with our comprehensive SIP & SWP calculator. Visualize growth and withdrawals effortlessly.">
    <meta property="og:image" content="https://sipswpcalculator.com/assets/og-image-main.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://sipswpcalculator.com/">
    <meta property="twitter:title" content="Advanced SIP & SWP Calculator for Investors">
    <meta property="twitter:description" content="Plan your financial future with our comprehensive SIP & SWP calculator. Visualize growth and withdrawals effortlessly.">
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
      "description": "A comprehensive tool for calculating Systematic Investment Plans (SIP) and Systematic Withdrawal Plans (SWP) with visualization."
    }
    </script>

    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/4149/4149678.png">

    <link rel="stylesheet" href="styles.css?v=<?= time() ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 text-gray-800 transition-colors duration-300">

    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <header class="relative mb-8">
            <div class="text-center">
                <h1 class="text-4xl sm:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-purple-600 pb-2">
                    Free SIP & SWP Calculator
                </h1>
                <p class="text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto">
                    The Ultimate SIP & SWP Calculator to Visualize Your Wealth Creation and Withdrawal Journey
                </p>
            </div>
            <div class="mt-8 mb-8">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Explore Our Financial Tools</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    <!-- Card 1: Main Calculator -->
                    <a href="/" class="group block p-6 bg-white rounded-xl shadow-lg border border-gray-200 hover:bg-gray-50 hover:border-indigo-500 transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-indigo-100 rounded-lg">
                                <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 0v6m0-6L9 13m4-4h2a2 2 0 012 2v2m-6 4h.01M9 17h.01M13 17h.01M17 17h.01M5 7h.01M5 11h.01M5 15h.01M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z"></path></svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900">SIP & SWP Calculator</h3>
                                <p class="mt-2 text-gray-600 text-sm">Project wealth accumulation and retirement income with our core calculator.</p>
                            </div>
                        </div>
                    </a>

                    <!-- Card 2: Goal Reverser -->
                    <a href="/goal-reverser" class="group relative block p-6 bg-white rounded-xl shadow-lg border border-gray-200 hover:bg-gray-50 hover:border-green-500 transition-all duration-300 transform hover:-translate-y-1">
                        <span class="absolute top-3 right-3 px-2 py-0.5 bg-green-500 text-white text-xs font-semibold rounded-full">NEW</span>
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-green-100 rounded-lg">
                                 <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21a9 9 0 100-18 9 9 0 000 18z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15a3 3 0 100-6 3 3 0 000 6z"></path></svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900">Goal Reverser</h3>
                                <p class="mt-2 text-gray-600 text-sm">Define your target, and we'll calculate the required annual investment increase.</p>
                            </div>
                        </div>
                    </a>

                    <!-- Card 3: SoR Analyzer -->
                    <a href="/sequence-risk-analyzer" class="group relative block p-6 bg-white rounded-xl shadow-lg border border-gray-200 hover:bg-gray-50 hover:border-orange-500 transition-all duration-300 transform hover:-translate-y-1">
                        <span class="absolute top-3 right-3 px-2 py-0.5 bg-orange-500 text-white text-xs font-semibold rounded-full">NEW</span>
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-orange-100 rounded-lg">
                                <svg class="w-10 h-10 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900">SoR Analyzer</h3>
                                <p class="mt-2 text-gray-600 text-sm">See why averages lie; simulate how market timing impacts your retirement.</p>
                            </div>
                        </div>
                    </a>

                </div>
            </div>
        </header>

        <main>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Form Section -->
                <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                    <form method="post" novalidate>
                        <fieldset class="mb-6">
                            <legend class="text-xl font-semibold mb-4 text-indigo-600">SIP Details</legend>
                            <div class="space-y-4">
                                <div>
                                    <label for="sip" class="block text-sm font-medium mb-1">Monthly Investment</label>
                                    <input type="number" id="sip" name="sip" class="w-full px-4 py-2 rounded-lg bg-gray-100 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="1" value="<?= htmlspecialchars((string) $sip) ?>">
                                </div>
                                <div>
                                    <label for="years" class="block text-sm font-medium mb-1">Investment Period (Years)</label>
                                    <input type="number" id="years" name="years" class="w-full px-4 py-2 rounded-lg bg-gray-100 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="1" value="<?= htmlspecialchars((string) $years) ?>">
                                </div>
                                <div>
                                    <label for="rate" class="block text-sm font-medium mb-1">Expected Return Rate (% p.a.)</label>
                                    <input type="number" id="rate" step="0.01" name="rate" class="w-full px-4 py-2 rounded-lg bg-gray-100 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="0" value="<?= htmlspecialchars((string) $rate) ?>">
                                </div>
                                <div>
                                    <label for="stepup" class="block text-sm font-medium mb-1">Annual Step-up (%)</label>
                                    <input type="number" id="stepup" step="0.01" name="stepup" class="w-full px-4 py-2 rounded-lg bg-gray-100 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="0" value="<?= htmlspecialchars((string) $stepup) ?>">
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="text-xl font-semibold mb-4 text-indigo-600">SWP Details</legend>
                            <div class="space-y-4">
                                <div>
                                    <label for="swp_withdrawal" class="block text-sm font-medium mb-1">Monthly Withdrawal</label>
                                    <input type="number" id="swp_withdrawal" step="0.01" name="swp_withdrawal" class="w-full px-4 py-2 rounded-lg bg-gray-100 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="0" value="<?= htmlspecialchars((string) $swp_withdrawal) ?>">
                                </div>
                                <div>
                                    <label for="swp_stepup" class="block text-sm font-medium mb-1">Annual Withdrawal Increase (%)</label>
                                    <input type="number" id="swp_stepup" step="0.01" name="swp_stepup" class="w-full px-4 py-2 rounded-lg bg-gray-100 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="0" value="<?= htmlspecialchars((string) $swp_stepup) ?>">
                                </div>
                                <div>
                                    <label for="swp_years" class="block text-sm font-medium mb-1">Withdrawal Period (Years)</label>
                                    <input type="number" id="swp_years" name="swp_years" class="w-full px-4 py-2 rounded-lg bg-gray-100 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required min="1" value="<?= htmlspecialchars((string) $swp_years_input) ?>">
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex flex-wrap gap-4 mt-8">
                            <button type="submit" name="action" value="calculate" class="flex-1 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition-all duration-300">Calculate</button>
                            <button type="submit" name="action" value="download_csv" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition-all duration-300">Download CSV</button>
                            <button type="button" id="openPdfModalBtn" class="w-full mt-4 px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg shadow-md transition-all duration-300">Generate Branded PDF Report</button>
                        </div>
                    </form>
                </div>

                <!-- Chart and Table Section -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                        <h2 class="text-2xl font-semibold mb-4">Investment Journey</h2>
                        <div class="h-96">
                            <canvas id="corpusChart"></canvas>
                        </div>
                    </div>

                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'download_csv'): ?>
                    <div id="results-table" class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                        <div class="p-6">
                            <h2 class="text-2xl font-semibold">Yearly Breakdown</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 text-xs uppercase font-semibold">
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
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($combined as $row): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium"><?= $row['year'] ?></td>
                                            <td class="px-6 py-4 text-right"><?= formatInr($row['begin_balance']) ?></td>
                                            <td class="px-6 py-4 text-right text-green-600"><?= formatInr($row['annual_contribution']) ?></td>
                                            <td class="px-6 py-4 text-right"><?= formatInr($row['cumulative_invested']) ?></td>
                                            <td class="px-6 py-4 text-right text-red-600"><?= $row['annual_withdrawal'] !== null ? formatInr($row['annual_withdrawal']) : '-' ?></td>
                                            <td class="px-6 py-4 text-right"><?= $row['cumulative_withdrawals'] ? formatInr($row['cumulative_withdrawals']) : '-' ?></td>
                                            <td class="px-6 py-4 text-right"><?= formatInr($row['interest']) ?></td>
                                            <td class="px-6 py-4 text-right font-semibold text-indigo-600"><?= formatInr($row['combined_total']) ?></td>
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
    <a href="/sip-calculator" class="text-indigo-600 hover:underline">
        Learn more about SIPs and how to use our calculator
    </a>
</div>

<div class="mt-12 bg-white rounded-xl shadow-lg border border-gray-200 p-8">
            <h2 class="text-3xl font-bold text-center mb-6">Master Your Financial Future with SIP & SWP</h2>
            <div class="prose prose-lg max-w-none text-gray-600">
                <p>Understanding the tools at your disposal is the first step toward effective financial planning. Our calculator is designed to demystify two of the most powerful tools for mutual fund investors: the Systematic Investment Plan (SIP) and the Systematic Withdrawal Plan (SWP).</p>
                
                <div class="grid md:grid-cols-2 gap-8 mt-8">
                    <div>
                        <h3 class="text-2xl font-semibold mb-3 text-indigo-600">What is a Systematic Investment Plan (SIP)?</h3>
                        <p>A SIP is a disciplined investment approach where you invest a fixed amount of money at regular intervals (usually monthly) into a mutual fund scheme. Instead of making a large one-time investment, you invest smaller amounts over time. This strategy helps in averaging out the cost of your investment and harnesses the power of compounding.</p>
                        <ul class="mt-4 space-y-2">
                            <li><span class="font-semibold text-green-600">Rupee Cost Averaging:</span> Buy more units when the market is low and fewer when it's high.</li>
                            <li><span class="font-semibold text-green-600">Power of Compounding:</span> Reinvesting your returns generates earnings on your earnings, leading to exponential growth.</li>
                            <li><span class="font-semibold text-green-600">Disciplined Investing:</span> Automates the habit of saving and investing regularly.</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-2xl font-semibold mb-3 text-purple-600">What is a Systematic Withdrawal Plan (SWP)?</h3>
                        <p>An SWP is the reverse of a SIP. It allows you to withdraw a fixed amount of money from your mutual fund investment at regular intervals. This is an ideal solution for generating a regular cash flow from your investments, especially during retirement. It provides a steady income stream while allowing the remaining investment to continue growing.</p>
                        <ul class="mt-4 space-y-2">
                            <li><span class="font-semibold text-green-600">Regular Income:</span> Create a predictable cash flow from your investments.</li>
                            <li><span class="font-semibold text-green-600">Tax-Efficient:</span> Withdrawals are structured to be tax-efficient, especially for long-term capital gains.</li>
                            <li><span class="font-semibold text-green-600">Continued Growth:</span> Your remaining corpus stays invested and continues to benefit from market growth.</li>
                        </ul>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="mt-12 border-t border-gray-200 pt-8">
                    <h2 class="text-3xl font-bold text-center mb-8">Frequently Asked Questions</h2>
                    <div class="space-y-6">
                        <div class="bg-gray-100 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-indigo-600 mb-2">Can I start an SWP immediately after my SIP ends?</h3>
                            <p>Yes, absolutely. This is a common strategy for retirement planning. You accumulate a corpus using SIP during your working years and then switch to SWP to generate a monthly pension-like income post-retirement. Our calculator specifically models this seamless transition.</p>
                        </div>
                        <div class="bg-gray-100 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-indigo-600 mb-2">Is SWP better than a fixed deposit interest?</h3>
                            <p>Generally, yes. SWP from equity or hybrid mutual funds has the potential to offer higher returns than fixed deposits over the long term. Additionally, SWP is more tax-efficient because you are only taxed on the capital gains portion of the withdrawal, whereas FD interest is fully taxable at your slab rate.</p>
                        </div>
                        <div class="bg-gray-100 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-indigo-600 mb-2">How does the "Step-up" feature work?</h3>
                            <p>A "Step-up" SIP means you increase your monthly investment amount by a certain percentage every year (e.g., as your salary increases). This significantly boosts your final corpus. Similarly, a "Step-up" SWP means you increase your withdrawal amount annually to combat inflation.</p>
                        </div>
                        <div class="bg-gray-100 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-indigo-600 mb-2">What is a safe withdrawal rate for SWP?</h3>
                            <p>Financial experts often recommend the "4% rule," suggesting you withdraw 4% of your corpus annually. However, this depends on market conditions and your lifespan. Use our <a href="/sequence-risk-analyzer" class="text-indigo-600 hover:underline">Sequence of Returns Risk Analyzer</a> to test if your withdrawal rate is sustainable during market crashes.</p>
                        </div>
                    </div>
                </div>

                <p class="text-center mt-8">Use our advanced calculator to model your SIP investments and plan your SWP withdrawals to see how you can achieve your financial goals, whether it's building a retirement corpus, funding your child's education, or creating a passive income stream.</p>
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
            "name": "What is a safe withdrawal rate for SWP?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Financial experts often recommend the 4% rule. However, this depends on market conditions. Use our Sequence of Returns Risk Analyzer to test if your withdrawal rate is sustainable."
            }
          }]
        }
        </script>

        <footer class="mt-12 text-sm text-gray-600 bg-white rounded-xl shadow-lg border border-gray-200 p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div>
                    <h3 class="font-bold text-lg mb-3 text-gray-800">About Calculator</h3>
                    <p class="leading-relaxed">A powerful, free tool designed to help investors visualize and plan their SIP and SWP strategies with precision and ease.</p>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-3 text-gray-800">Features</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Real-time calculations</li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Interactive visualization</li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> CSV export</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-3 text-gray-800">Support</h3>
                    <p class="leading-relaxed">For questions or feedback about this calculator, <a href="mailto:help@sipswpcalculator.com" class="text-indigo-600 hover:underline">reach out to us</a>. We're here to help you achieve your financial goals.</p>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <div class="bg-amber-100 p-4 rounded-lg mb-6 border border-amber-200">
                    <p class="text-amber-800"><span class="font-bold">⚠️ Disclaimer</span><br>This calculator is for educational and illustrative purposes only. It does not constitute financial, tax, or investment advice. Past performance is not indicative of future results. Please consult with a qualified financial advisor before making any investment decisions.</p>
                </div>

                <div class="text-center">
                    <p class="text-xs">&copy; <?= date('Y') ?> SIP/SWP Calculator. All rights reserved.</p>
                    <p class="text-xs mt-2">Built with care for global investors • <span class="text-indigo-600">No tracking • No ads • Free forever</span></p>
                </div>
            </div>
        </footer>

    </div>

    <!-- PDF Generation Modal -->
    <div id="pdfModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-lg border border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Create Branded PDF Report</h2>
            <form id="pdfForm" class="space-y-4">
                <div>
                    <label for="clientName" class="block text-sm font-medium mb-1 text-gray-600">Client Name</label>
                    <input type="text" id="clientName" name="clientName" placeholder="e.g., John Doe" class="w-full px-4 py-2 rounded-lg bg-gray-100 border-gray-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition">
                </div>
                <div>
                    <label for="advisorName" class="block text-sm font-medium mb-1 text-gray-600">Your Name / Company Name</label>
                    <input type="text" id="advisorName" name="advisorName" placeholder="e.g., Jane Smith Financials" class="w-full px-4 py-2 rounded-lg bg-gray-100 border-gray-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition">
                </div>
                 <div>
                    <label for="advisorLogo" class="block text-sm font-medium mb-1 text-gray-600">Your Logo</label>
                    <input type="file" id="advisorLogo" name="advisorLogo" accept="image/png, image/jpeg" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                </div>
                <div>
                    <label for="customDisclaimer" class="block text-sm font-medium mb-1 text-gray-600">Custom Disclaimer / Message</label>
                    <textarea id="customDisclaimer" name="customDisclaimer" rows="3" placeholder="e.g., This projection is based on the agreed-upon assumptions..." class="w-full px-4 py-2 rounded-lg bg-gray-100 border-gray-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition"></textarea>
                </div>
                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" id="closePdfModalBtn" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition">Cancel</button>
                    <button type="submit" id="generatePdfBtn" class="px-6 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg transition">Download PDF</button>
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