<?php
// AJAX Endpoint for real-time calculation
if (isset($_POST['action']) && $_POST['action'] == 'calculate_initial_sip') {
    header('Content-Type: application/json');

    // --- INPUTS ---
    $targetAmount = (float)($_POST['targetAmount'] ?? 1000000);
    $investmentPeriod = (int)($_POST['investmentPeriod'] ?? 10);
    $returnRate = (float)($_POST['returnRate'] ?? 12);
    $stepUpRate = (float)($_POST['stepUpRate'] ?? 10);

    // This function calculates the future value factor for a step-up SIP with an initial investment of 1.
    function calculate_fv_factor($years, $return_rate, $step_up_rate) {
        if ($years <= 0 || $return_rate <= -100) return 0;
        
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
            $compounding_for_future = pow(1 + (12 * $monthly_rate + $return_rate/100), $years - ($y + 1));
            if (phpversion() >= 7.1) {
                 $compounding_for_future = pow(1 + $return_rate/100, $years - ($y + 1));
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
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>"Target-First" Step-Up SIP Planner | Goal Based Investing</title>
    <meta name="description" content="Calculate the required monthly SIP to achieve your financial goals with our Target-First Step-Up Planner. Reverse engineer your investments.">
    <meta name="keywords" content="Goal Planner, SIP Calculator, Target Amount Calculator, Step-up SIP, Financial Goal Setting">
    <link rel="canonical" href="https://sipswpcalculator.com/goal-reverser.php">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sipswpcalculator.com/goal-reverser.php">
    <meta property="og:title" content="Target-First Step-Up SIP Planner">
    <meta property="og:description" content="Start with your financial goal and find out how much to invest. Reverse engineer your wealth creation journey.">
    <meta property="og:image" content="https://sipswpcalculator.com/assets/og-image-goal.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://sipswpcalculator.com/goal-reverser.php">
    <meta property="twitter:title" content="Target-First Step-Up SIP Planner">
    <meta property="twitter:description" content="Start with your financial goal and find out how much to invest. Reverse engineer your wealth creation journey.">
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

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'Roboto Mono', monospace; }
        .hero-input {
            background: transparent;
            border: none;
            border-bottom: 2px solid #4f46e5;
            text-align: center;
            color: white;
            font-size: 3rem;
            width: 100%;
            padding: 10px 0;
        }
        .hero-input:focus { outline: none; border-bottom-color: #818cf8; }
        .pill-input {
            display: inline-flex;
            align-items: center;
            background-color: #374151;
            border-radius: 9999px;
            padding: 8px 16px;
            border: 1px solid #4b5563;
        }
        .pill-input label { margin-right: 8px; font-size: 0.875rem; color: #d1d5db; }
        .pill-input input {
            background: transparent;
            border: none;
            color: white;
            width: 50px;
            text-align: right;
        }
        .pill-input input:focus { outline: none; }
        
        /* Staircase */
        .staircase-container {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            height: 250px;
            padding: 20px;
            gap: 4px;
            background: #1f2937;
            border-radius: 12px;
            overflow-x: auto;
        }
        .stair-step {
            flex-grow: 1;
            background-image: linear-gradient(to top, #4f46e5, #818cf8);
            border-radius: 4px 4px 0 0;
            transition: all 0.2s ease-in-out;
            position: relative;
            cursor: pointer;
        }
        .stair-step:hover {
            transform: scale(1.05);
            background-image: linear-gradient(to top, #6366f1, #a5b4fc);
        }
        .stair-step .tooltip {
            visibility: hidden;
            width: 160px;
            background-color: #111827;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px 0;
            position: absolute;
            z-index: 1;
            bottom: 105%;
            left: 50%;
            margin-left: -80px;
            opacity: 0;
            transition: opacity 0.3s;
        }
                .stair-step:hover .tooltip {
                    visibility: visible;
                    opacity: 1;
                }
                .btn-ghost {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.5rem;
                    padding: 0.75rem 1.5rem;
                    margin-top: 1rem;
                    border: 2px solid #4f46e5; /* slate gray/blue */
                    color: #818cf8; /* slate gray/blue */
                    background-color: transparent;
                    border-radius: 0.5rem;
                    font-weight: 600;
                    transition: all 0.2s ease-in-out;
                    cursor: pointer;
                }
        
                .btn-ghost:hover {
                    background-color: rgba(79, 70, 229, 0.1); /* Light wash of the border color */
                    color: #a5b4fc;
                    border-color: #6366f1;
                }
        
                .btn-ghost svg {
                    transition: all 0.2s ease-in-out;
                }
            </style>
        </head>
        <body class="dark bg-gradient-to-br from-slate-900 to-slate-800 text-slate-200 flex items-center justify-center min-h-screen p-4">
        
            <div class="max-w-4xl w-full">
                <header class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400 pb-2">
                        "Target-First" Step-Up Planner
                    </h1>
                    <p class="text-slate-400">Start with your goal, and we'll tell you how to begin.</p>
                </header>
        
                <main class="bg-slate-800/50 backdrop-blur-sm p-6 sm:p-8 rounded-2xl shadow-2xl border border-slate-700">
                    <!-- Target-First Input -->
                    <div id="target-first-calculator" class="text-center">
                        <label class="text-slate-400 text-lg">I want to accumulate</label>
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
        
                        <div class="mt-8 bg-slate-900/50 p-6 rounded-xl">
                            <p class="text-slate-400 text-lg">You need to start with a monthly SIP of</p>
                            <p id="requiredSip" class="font-mono font-bold text-5xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">
                                ₹0
                            </p>
                        </div>
                        
                        <button id="downloadReportBtn" class="btn-ghost">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>
                            <span>Download Report</span>
                        </button>
        
                    </div>
        
                     <!-- Staircase Visualization -->
                    <div class="mt-8">
                         <h2 class="text-xl font-semibold text-center mb-4">Your Annual Step-Up Journey</h2>
                        <div id="staircase" class="staircase-container">
                            <!-- Steps will be generated by JavaScript -->
                        </div>
                    </div>
                </main>
                
                <footer class="mt-8 text-sm text-center text-slate-400">
                     <a href="default.php" class="text-indigo-400 hover:underline">
                        &larr; Back to Main SIP/SWP Calculator
                    </a>
                     <p class="mt-4 text-xs">&copy; <?= date('Y') ?> SIP/SWP Calculator. All rights reserved.</p>
                </footer>
        
            </div>
        
            <script src="goal-reverser.js"></script>
        </body>
        </html>