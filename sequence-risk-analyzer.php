<?php
// This top block will be for the backend simulation logic.
// It will be triggered by an AJAX request from the frontend.
if (isset($_POST['action']) && $_POST['action'] == 'run_simulation') {
    header('Content-Type: application/json');

    // --- INPUTS ---
    $initialCorpus = (float) ($_POST['initialCorpus'] ?? 1000000);
    $monthlyWithdrawal = (float) ($_POST['monthlyWithdrawal'] ?? 5000);
    $withdrawalPeriod = (int) ($_POST['withdrawalPeriod'] ?? 30);
    $withdrawalIncrease = (float) ($_POST['withdrawalIncrease'] ?? 5);

    $avgReturn = (float) ($_POST['avgReturn'] ?? 7);
    $bearReturn = (float) ($_POST['bearReturn'] ?? -15);
    $bullReturn = (float) ($_POST['bullReturn'] ?? 20);

    // --- SIMULATION ENGINE ---
    function run_simulation($initialCorpus, $monthlyWithdrawal, $withdrawalPeriod, $withdrawalIncrease, $return_schedule)
    {
        $balance = $initialCorpus;
        $balances_over_time = [$initialCorpus];
        $current_monthly_withdrawal = $monthlyWithdrawal;

        for ($year = 1; $year <= $withdrawalPeriod; $year++) {
            $annual_return_rate = $return_schedule[$year - 1] / 100;
            // Monthly rate calculation that is more accurate for compounding
            $monthly_rate = pow(1 + $annual_return_rate, 1 / 12) - 1;

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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sequence of Returns Risk Analyzer | Retirement Calculator</title>
    <meta name="description"
        content="Visualize the impact of market timing on your retirement portfolio. Understand Sequence of Returns Risk and test different market scenarios.">
    <meta name="keywords"
        content="Sequence of Returns Risk, Retirement Planning, Market Crash Simulator, Portfolio Depletion, SWP Risk">
    <link rel="canonical" href="https://sipswpcalculator.com/sequence-risk-analyzer">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sipswpcalculator.com/sequence-risk-analyzer">
    <meta property="og:title" content="Sequence of Returns Risk Analyzer">
    <meta property="og:description"
        content="Don't let a bad market start ruin your retirement. Visualize the impact of Sequence of Returns Risk.">
    <meta property="og:image" content="https://sipswpcalculator.com/assets/og-image-risk.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://sipswpcalculator.com/sequence-risk-analyzer">
    <meta property="twitter:title" content="Sequence of Returns Risk Analyzer">
    <meta property="twitter:description"
        content="Don't let a bad market start ruin your retirement. Visualize the impact of Sequence of Returns Risk.">
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

<body class="bg-gray-50 text-gray-800 transition-colors duration-300"
    style="background-image: var(--gradient-surface); background-attachment: fixed;">

    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 animate-float">

        <header class="relative mb-8 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 border border-rose-100 mb-4">
                <span class="text-xs font-semibold text-rose-700 tracking-wide uppercase">Retirement Risk
                    Simulator</span>
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold pb-2">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-rose-600">Sequence Risk
                    Analyzer</span>
            </h1>
            <p class="text-lg sm:text-xl text-gray-500 font-medium max-w-3xl mx-auto mt-2">
                Visualize how a bad market start can impact your retirement portfolio.
            </p>
        </header>

        <main>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Form Section -->
                <div class="lg:col-span-1 glass-card p-6 rounded-xl">
                    <div class="space-y-6">
                        <fieldset class="border border-orange-200 rounded-lg p-4 bg-orange-50/30">
                            <legend class="px-2 font-semibold text-orange-700 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Retirement Plan
                            </legend>
                            <div class="space-y-4 pt-2">
                                <div>
                                    <label for="initialCorpus" class="block text-sm font-medium mb-1">Initial
                                        Corpus</label>
                                    <div class="input-group">
                                        <span class="absolute left-3 text-gray-400 pointer-events-none">₹</span>
                                        <input type="number" id="initialCorpus" value="1000000"
                                            class="input-with-icon pl-8">
                                    </div>
                                </div>
                                <div>
                                    <label for="monthlyWithdrawal" class="block text-sm font-medium mb-1">Monthly
                                        Withdrawal</label>
                                    <div class="input-group">
                                        <span class="absolute left-3 text-gray-400 pointer-events-none">₹</span>
                                        <input type="number" id="monthlyWithdrawal" value="5000"
                                            class="input-with-icon pl-8">
                                    </div>
                                </div>
                                <div>
                                    <label for="withdrawalPeriod" class="block text-sm font-medium mb-1">Duration
                                        (Years)</label>
                                    <input type="number" id="withdrawalPeriod" value="30">
                                </div>
                                <div>
                                    <label for="withdrawalIncrease" class="block text-sm font-medium mb-1">Annual Incr.
                                        (%)</label>
                                    <div class="input-group">
                                        <input type="number" id="withdrawalIncrease" value="5">
                                        <span class="input-suffix">%</span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="border border-rose-200 rounded-lg p-4 bg-rose-50/30">
                            <legend class="px-2 font-semibold text-rose-700 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                </svg>
                                Market Scenarios (Year 1-2)
                            </legend>
                            <div class="space-y-4 pt-2">
                                <div>
                                    <label for="bullReturn"
                                        class="block text-sm font-medium mb-1 text-emerald-700">"Bull" Start (%
                                        Return)</label>
                                    <input type="number" id="bullReturn" value="20" step="0.1"
                                        class="border-emerald-200 focus:border-emerald-500 focus:ring-emerald-200">
                                </div>
                                <div>
                                    <label for="bearReturn" class="block text-sm font-medium mb-1 text-rose-700">"Bear"
                                        Start (% Return)</label>
                                    <input type="number" id="bearReturn" value="-15" step="0.1"
                                        class="border-rose-200 focus:border-rose-500 focus:ring-rose-200">
                                </div>
                                <div>
                                    <label for="avgReturn" class="block text-sm font-medium mb-1">Avg Return (Later
                                        Yrs)</label>
                                    <input type="number" id="avgReturn" value="7" step="0.1">
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <button id="runSimulationBtn"
                        class="mt-8 w-full px-6 py-4 bg-gradient-to-r from-orange-600 to-rose-600 hover:from-orange-700 hover:to-rose-700 text-white font-bold rounded-xl shadow-lg transform transition-all duration-200 hover:scale-[1.02] active:scale-95">
                        Run Simulation
                    </button>
                </div>

                <!-- Chart and Insights Section -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="glass-card p-6 rounded-xl min-h-[500px]">
                        <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z">
                                </path>
                            </svg>
                            Depletion Scenarios
                        </h2>
                        <div class="h-96">
                            <canvas id="sequenceRiskChart"></canvas>
                        </div>
                    </div>

                    <div class="glass-card p-8 rounded-xl">
                        <h2 class="text-2xl font-bold mb-6">Key Takeaways & Buffer Strategies</h2>
                        <div class="prose max-w-none text-gray-600">
                            <p>The chart demonstrates the <strong>"Sequence of Returns Risk"</strong>: the order in
                                which you experience investment returns matters immensely, especially at the start of
                                retirement.</p>
                            <ul class="list-disc pl-5 space-y-2">
                                <li><strong class="text-rose-600">The Danger Zone:</strong> A bear market in the first
                                    few years of withdrawal forces you to sell more units at low prices to generate the
                                    same income. This permanently damages your portfolio's ability to recover and grow,
                                    drastically shortening its lifespan.</li>
                                <li><strong class="text-emerald-600">The Power of a Good Start:</strong> A bull market
                                    at
                                    the beginning allows your portfolio to grow substantially even while you withdraw,
                                    creating a larger cushion for future volatility.</li>
                            </ul>
                            <h3 class="text-xl font-semibold mt-6 mb-3 text-gray-800">Buffer Strategies to Mitigate
                                Risk:</h3>
                            <ol class="list-decimal pl-5 space-y-2">
                                <li><strong>Cash Bucket:</strong> Maintain 1-2 years of living expenses in cash or cash
                                    equivalents. During a market downturn, you can draw from this bucket instead of
                                    selling your stocks at a loss.</li>
                                <li><strong>Dynamic Withdrawals:</strong> Be flexible. In down years, consider reducing
                                    your withdrawal amount (e.g., forgoing a vacation or large purchase) to preserve
                                    your capital.</li>
                                <li><strong>The "Bucket" Strategy:</strong> Divide your portfolio into three buckets:
                                    (1) Short-term needs (1-3 years in cash/bonds), (2) Mid-term needs (3-10 years in a
                                    balanced fund), and (3) Long-term growth (10+ years in equities). Replenish Bucket 1
                                    from Bucket 2 during good years.</li>
                            </ol>

                            <div class="mt-8 p-6 bg-indigo-50/50 border border-indigo-100 rounded-xl">
                                <p class="text-sm text-gray-700"><strong>Pro Tip:</strong> Use our <a href="/"
                                        class="text-indigo-600 hover:text-indigo-700 font-bold underline decoration-2 decoration-indigo-200 underline-offset-2">SIP
                                        & SWP Calculator</a>
                                    to plan your accumulation phase carefully so you have a large enough corpus to
                                    withstand these market fluctuations.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-12">
                <a href="/"
                    class="inline-flex items-center text-indigo-600 hover:text-indigo-700 font-semibold transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Main SIP/SWP Calculator
                </a>
            </div>
        </main>

        <footer class="mt-16 text-sm text-center text-gray-500 glass-card py-6">
            <p class="text-xs">&copy; <?= date('Y') ?> SIP/SWP Calculator. All rights reserved.</p>
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script src="sequence-risk-analyzer.js"></script>

</body>

</html>