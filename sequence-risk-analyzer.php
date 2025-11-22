<?php
// This top block will be for the backend simulation logic.
// It will be triggered by an AJAX request from the frontend.
if (isset($_POST['action']) && $_POST['action'] == 'run_simulation') {
    header('Content-Type: application/json');

    // --- INPUTS ---
    $initialCorpus = (float)($_POST['initialCorpus'] ?? 1000000);
    $monthlyWithdrawal = (float)($_POST['monthlyWithdrawal'] ?? 5000);
    $withdrawalPeriod = (int)($_POST['withdrawalPeriod'] ?? 30);
    $withdrawalIncrease = (float)($_POST['withdrawalIncrease'] ?? 5);
    
    $avgReturn = (float)($_POST['avgReturn'] ?? 7);
    $bearReturn = (float)($_POST['bearReturn'] ?? -15);
    $bullReturn = (float)($_POST['bullReturn'] ?? 20);

    // --- SIMULATION ENGINE ---
    function run_simulation($initialCorpus, $monthlyWithdrawal, $withdrawalPeriod, $withdrawalIncrease, $return_schedule) {
        $balance = $initialCorpus;
        $balances_over_time = [$initialCorpus];
        $current_monthly_withdrawal = $monthlyWithdrawal;

        for ($year = 1; $year <= $withdrawalPeriod; $year++) {
            $annual_return_rate = $return_schedule[$year - 1] / 100;
            // Monthly rate calculation that is more accurate for compounding
            $monthly_rate = pow(1 + $annual_return_rate, 1/12) - 1;

            for ($month = 1; $month <= 12; $month++) {
                // Withdraw at the start of the month
                $balance -= $current_monthly_withdrawal;
                if ($balance < 0) {
                    $balance = 0;
                }
                
                // Remaining balance grows
                $balance *= (1 + $monthly_rate);

                if ($balance < 0) {
                    $balance = 0;
                }
            }
            
            // Increase withdrawal amount for the next year
            $current_monthly_withdrawal *= (1 + $withdrawalIncrease / 100);

            // Record the balance at the end of the year
            array_push($balances_over_time, round($balance));
            
            if ($balance == 0) {
                // If corpus is depleted, fill rest of the years with 0
                for ($y = $year + 1; $y <= $withdrawalPeriod; $y++) {
                    array_push($balances_over_time, 0);
                }
                break;
            }
        }
        return $balances_over_time;
    }

    // --- SCENARIO DEFINITIONS ---
    $return_schedule_flat = array_fill(0, $withdrawalPeriod, $avgReturn);
    
    $return_schedule_bear = array_fill(0, $withdrawalPeriod, $avgReturn);
    $return_schedule_bear[0] = $bearReturn;
    $return_schedule_bear[1] = $bearReturn;

    $return_schedule_bull = array_fill(0, $withdrawalPeriod, $avgReturn);
    $return_schedule_bull[0] = $bullReturn;
    $return_schedule_bull[1] = $bullReturn;

    // --- RUN SIMULATIONS ---
    $flat_scenario_data = run_simulation($initialCorpus, $monthlyWithdrawal, $withdrawalPeriod, $withdrawalIncrease, $return_schedule_flat);
    $bear_scenario_data = run_simulation($initialCorpus, $monthlyWithdrawal, $withdrawalPeriod, $withdrawalIncrease, $return_schedule_bear);
    $bull_scenario_data = run_simulation($initialCorpus, $monthlyWithdrawal, $withdrawalPeriod, $withdrawalIncrease, $return_schedule_bull);

    $labels = range(0, $withdrawalPeriod);

    // --- OUTPUT ---
    echo json_encode([
        'labels' => $labels,
        'flatData' => $flat_scenario_data,
        'bearData' => $bear_scenario_data,
        'bullData' => $bull_scenario_data
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sequence of Returns Risk Analyzer | SIP & SWP Calculator</title>
    <meta name="description" content="Visualize the impact of market timing on your retirement portfolio. Understand Sequence of Returns Risk and test different market scenarios.">
    <meta name="keywords" content="Sequence of Returns Risk, Retirement Planning, Market Crash Simulator, Portfolio Depletion, SWP Risk">
    <link rel="canonical" href="https://sipswpcalculator.com/sequence-risk-analyzer.php">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sipswpcalculator.com/sequence-risk-analyzer.php">
    <meta property="og:title" content="Sequence of Returns Risk Analyzer">
    <meta property="og:description" content="Don't let a bad market start ruin your retirement. Visualize the impact of Sequence of Returns Risk.">
    <meta property="og:image" content="https://sipswpcalculator.com/assets/og-image-risk.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://sipswpcalculator.com/sequence-risk-analyzer.php">
    <meta property="twitter:title" content="Sequence of Returns Risk Analyzer">
    <meta property="twitter:description" content="Don't let a bad market start ruin your retirement. Visualize the impact of Sequence of Returns Risk.">
    <meta property="twitter:image" content="https://sipswpcalculator.com/assets/og-image-risk.jpg">

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FinancialProduct",
      "name": "Sequence of Returns Risk Analyzer",
      "description": "A simulation tool to visualize the impact of market returns sequence on retirement portfolio longevity.",
      "brand": {
        "@type": "Brand",
        "name": "SIP/SWP Calculator"
      },
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
      }
    }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="dark bg-gradient-to-br from-slate-900 to-slate-800 text-slate-200 transition-colors duration-300">

    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <header class="relative mb-8 text-center">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-red-500 pb-2">
                Sequence of Returns Risk Analyzer
            </h1>
            <p class="text-lg sm:text-xl text-slate-300 max-w-3xl mx-auto">
                Visualize how a market crash early in retirement can impact your portfolio's longevity compared to other market conditions.
            </p>
        </header>

        <main>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Form Section -->
                <div class="lg:col-span-1 bg-white dark:bg-slate-800/50 dark:backdrop-blur-sm p-6 rounded-xl shadow-lg border dark:border-slate-700">
                    <div class="space-y-6">
                        <fieldset>
                            <legend class="text-xl font-semibold mb-4 text-orange-400">Retirement Plan</legend>
                            <div class="space-y-4">
                                <div>
                                    <label for="initialCorpus" class="block text-sm font-medium mb-1">Initial Retirement Corpus</label>
                                    <input type="number" id="initialCorpus" value="1000000" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="monthlyWithdrawal" class="block text-sm font-medium mb-1">Initial Monthly Withdrawal</label>
                                    <input type="number" id="monthlyWithdrawal" value="5000" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="withdrawalPeriod" class="block text-sm font-medium mb-1">Withdrawal Period (Years)</label>
                                    <input type="number" id="withdrawalPeriod" value="30" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                </div>
                                 <div>
                                    <label for="withdrawalIncrease" class="block text-sm font-medium mb-1">Annual Withdrawal Increase (%)</label>
                                    <input type="number" id="withdrawalIncrease" value="5" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="text-xl font-semibold mb-4 text-orange-400">Market Scenarios (1st 2 Years)</legend>
                            <div class="space-y-4">
                                <div>
                                    <label for="bullReturn" class="block text-sm font-medium mb-1">"Bull Market" Start (% Annual Return)</label>
                                    <input type="number" id="bullReturn" value="20" step="0.1" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                 <div>
                                    <label for="bearReturn" class="block text-sm font-medium mb-1">"Bear Market" Start (% Annual Return)</label>
                                    <input type="number" id="bearReturn" value="-15" step="0.1" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="avgReturn" class="block text-sm font-medium mb-1">Average Return (All other years)</label>
                                    <input type="number" id="avgReturn" value="7" step="0.1" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                </div>
                            </div>
                        </fieldset>
                    </div>
                     <button id="runSimulationBtn" class="mt-8 w-full px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg shadow-md transition-all duration-300">Run Simulation</button>
                </div>

                <!-- Chart and Insights Section -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-slate-800/50 backdrop-blur-sm p-6 rounded-xl shadow-lg border border-slate-700 min-h-[500px]">
                        <h2 class="text-2xl font-semibold mb-4">Portfolio Depletion Scenarios</h2>
                        <div class="h-96">
                            <canvas id="sequenceRiskChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-slate-800/50 backdrop-blur-sm p-6 rounded-xl shadow-lg border border-slate-700">
                        <h2 class="text-2xl font-semibold mb-4">Key Takeaways & Buffer Strategies</h2>
                        <div class="prose prose-invert max-w-none">
                            <p>The chart demonstrates the <strong>"Sequence of Returns Risk"</strong>: the order in which you experience investment returns matters immensely, especially at the start of retirement.</p>
                            <ul>
                                <li><strong class="text-red-400">The Danger Zone:</strong> A bear market in the first few years of withdrawal forces you to sell more units at low prices to generate the same income. This permanently damages your portfolio's ability to recover and grow, drastically shortening its lifespan.</li>
                                <li><strong class="text-green-400">The Power of a Good Start:</strong> A bull market at the beginning allows your portfolio to grow substantially even while you withdraw, creating a larger cushion for future volatility.</li>
                            </ul>
                            <h3 class="text-xl font-semibold mt-6">Buffer Strategies to Mitigate Risk:</h3>
                            <ol>
                                <li><strong>Cash Bucket:</strong> Maintain 1-2 years of living expenses in cash or cash equivalents. During a market downturn, you can draw from this bucket instead of selling your stocks at a loss.</li>
                                <li><strong>Dynamic Withdrawals:</strong> Be flexible. In down years, consider reducing your withdrawal amount (e.g., forgoing a vacation or large purchase) to preserve your capital.</li>
                                <li><strong>The "Bucket" Strategy:</strong> Divide your portfolio into three buckets: (1) Short-term needs (1-3 years in cash/bonds), (2) Mid-term needs (3-10 years in a balanced fund), and (3) Long-term growth (10+ years in equities). Replenish Bucket 1 from Bucket 2 during good years.</li>
                            </ol>
                            
                            <div class="mt-6 p-4 bg-slate-700/30 rounded-lg border-l-4 border-indigo-500">
                                <p class="text-sm text-slate-300"><strong>Pro Tip:</strong> Use our <a href="default.php" class="text-indigo-400 hover:underline font-semibold">SIP & SWP Calculator</a> to plan your accumulation phase carefully so you have a large enough corpus to withstand these market fluctuations.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
             <div class="text-center mt-8">
                <a href="default.php" class="text-indigo-400 hover:underline">
                    &larr; Back to Main SIP/SWP Calculator
                </a>
            </div>
        </main>
        
        <footer class="mt-12 text-sm text-center text-slate-400">
             <p class="text-xs">&copy; <?= date('Y') ?> SIP/SWP Calculator. All rights reserved.</p>
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script src="sequence-risk-analyzer.js"></script>

</body>
</html>
