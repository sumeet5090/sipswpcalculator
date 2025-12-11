<?php
// AJAX Endpoint for real-time calculation
if (isset($_POST['action']) && $_POST['action'] == 'calculate_initial_sip') {
    header('Content-Type: application/json');

    // --- INPUTS ---
    $targetAmount = (float) ($_POST['targetAmount'] ?? 1000000);
    $investmentPeriod = (int) ($_POST['investmentPeriod'] ?? 10);
    $returnRate = (float) ($_POST['returnRate'] ?? 12);
    $stepUpRate = (float) ($_POST['stepUpRate'] ?? 10);

    // This function calculates the future value factor for a step-up SIP with an initial investment of 1.
    function calculate_fv_factor($years, $return_rate, $step_up_rate)
    {
        if ($years <= 0 || $return_rate <= -100)
            return 0;

        $monthly_rate = $return_rate / 12 / 100;
        $annual_step_up = 1 + ($step_up_rate / 100);
        $total_factor = 0;

        for ($y = 0; $y < $years; $y++) {
            $year_factor = 0;
            // Calculate future value of 12 monthly payments of 1 unit at the end of the year
            for ($m = 0; $m < 12; $m++) {
                $year_factor = ($year_factor + 1) * (1 + $monthly_rate);
            }

            // Compound this year's total amount to the end of the investment term
            $compounding_for_future = pow(1 + (12 * $monthly_rate + $return_rate / 100), $years - ($y + 1));
            if (phpversion() >= 7.1) {
                $compounding_for_future = pow(1 + $return_rate / 100, $years - ($y + 1));
            }


            // The contribution amount for this year is multiplied by the step-up factor
            $step_up_multiplier = pow($annual_step_up, $y);

            $total_factor += $year_factor * $compounding_for_future * $step_up_multiplier;
        }
        return $total_factor;
    }

    $fv_factor = calculate_fv_factor($investmentPeriod, $returnRate, $stepUpRate);

    if ($fv_factor == 0) {
        $initialSip = 0;
    } else {
        $initialSip = $targetAmount / $fv_factor;
    }

    // Generate data for the staircase visualization
    $staircaseData = [];
    $currentSip = $initialSip;
    for ($year = 1; $year <= $investmentPeriod; $year++) {
        $staircaseData[] = [
            'year' => $year,
            'amount' => round($currentSip)
        ];
        $currentSip *= (1 + ($stepUpRate / 100));
    }

    echo json_encode([
        'initialSip' => round($initialSip),
        'staircaseData' => $staircaseData
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sip Goal Calculator | Backward SIP Calculator</title>
    <meta name="description"
        content="Calculate the required monthly SIP to achieve your financial goals with our Target-First Step-Up Planner. Reverse engineer your investments.">
    <meta name="keywords"
        content="Goal Planner, SIP Calculator, Target Amount Calculator, Step-up SIP, Financial Goal Setting">
    <link rel="canonical" href="https://sipswpcalculator.com/goal-reverser">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sipswpcalculator.com/goal-reverser">
    <meta property="og:title" content="Target-First Step-Up SIP Planner">
    <meta property="og:description"
        content="Start with your financial goal and find out how much to invest. Reverse engineer your wealth creation journey.">
    <meta property="og:image" content="https://sipswpcalculator.com/assets/og-image-goal.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://sipswpcalculator.com/goal-reverser">
    <meta property="twitter:title" content="Target-First Step-Up SIP Planner">
    <meta property="twitter:description"
        content="Start with your financial goal and find out how much to invest. Reverse engineer your wealth creation journey.">
    <meta property="twitter:image" content="https://sipswpcalculator.com/assets/og-image-goal.jpg">

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FinancialProduct",
      "name": "Target-First Step-Up Planner",
      "description": "A tool to calculate required SIP investments based on a target financial goal.",
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

<body class="bg-gray-50 text-gray-800 flex items-center justify-center min-h-screen p-4"
    style="background-image: var(--gradient-surface); background-attachment: fixed;">

    <div class="max-w-4xl w-full animate-float">
        <header class="text-center mb-8">
            <div
                class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 mb-4">
                <span class="text-xs font-semibold text-emerald-700 tracking-wide uppercase">Target Based
                    Planning</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold pb-2">
                <span class="text-gradient">SIP Goal Calculator</span>
            </h1>
            <p class="text-lg text-gray-500 font-medium">Start with your target, and we'll reveal the path.</p>
        </header>

        <main class="glass-card p-6 sm:p-10">
            <!-- Target-First Input -->
            <div id="target-first-calculator" class="text-center">
                <label class="text-gray-600 text-lg">I want to accumulate</label>
                <input type="number" id="targetAmount" value="10000000" class="hero-input font-mono font-bold">

                <div class="flex justify-center items-center flex-wrap gap-4 mt-6">
                    <div class="pill-input">
                        <label for="investmentPeriod">in</label>
                        <input type="number" id="investmentPeriod" value="15">
                        <span>Years</span>
                    </div>
                    <div class="pill-input">
                        <label for="returnRate">at</label>
                        <input type="number" id="returnRate" value="12">
                        <span>% p.a.</span>
                    </div>
                    <div class="pill-input">
                        <label for="stepUpRate">with an annual step-up of</label>
                        <input type="number" id="stepUpRate" value="10">
                        <span>%</span>
                    </div>
                </div>

                <div class="mt-8 bg-gray-50 p-6 rounded-xl">
                    <p class="text-gray-600 text-lg">You need to start with a monthly SIP of</p>
                    <p id="requiredSip"
                        class="font-mono font-bold text-5xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-purple-600">
                        ₹0
                    </p>
                </div>

                <button id="downloadReportBtn"
                    class="btn-secondary mt-4 text-indigo-600 border-indigo-200 hover:bg-indigo-50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="btn-icon mr-2">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <path d="M16 13H8"></path>
                        <path d="M16 17H8"></path>
                        <path d="M10 9H8"></path>
                    </svg>
                    Download Summary PDF
                </button>

            </div>

            <!-- Staircase Visualization -->
            <div class="mt-8">
                <h3 class="text-center text-xl font-bold text-gray-800 mb-4">Your SIP Increases Each Year</h3>
                <div id="staircase" class="staircase-container">
                    <!-- Steps will be generated here by JavaScript -->
                </div>
            </div>

            <footer class="text-center mt-8">
                <a href="/" class="text-indigo-600 hover:underline">&larr; Back to Main Calculator</a>
            </footer>

        </main>

        <footer class="text-center text-sm text-gray-500 mt-6">
            &copy; 2023 SIP/SWP Calculator
        </footer>

    </div>

    <script src="goal-reverser.js"></script>
</body>

</html>