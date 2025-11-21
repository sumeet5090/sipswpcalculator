<?php
if (isset($_POST['action']) && $_POST['action'] == 'calculate_step_up') {
    header('Content-Type: application/json');

    $targetAmount = (float)$_POST['targetAmount'];
    $initialInvestment = (float)$_POST['initialInvestment'];
    $investmentPeriod = (int)$_POST['investmentPeriod'];
    $returnRate = (float)$_POST['returnRate'];

    // This function calculates the future value of a step-up SIP.
    function calculate_future_value($initialInvestment, $investmentPeriod, $returnRate, $stepUpRate) {
        $monthlyRate = $returnRate / 12 / 100;
        $futureValue = 0;
        $monthlyInvestment = $initialInvestment;

        for ($year = 1; $year <= $investmentPeriod; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $futureValue = ($futureValue + $monthlyInvestment) * (1 + $monthlyRate);
            }
            $monthlyInvestment *= (1 + ($stepUpRate / 100));
        }
        return $futureValue;
    }

    $low = 0;
    $high = 100; // Assuming a max step-up of 100%
    $precision = 0.01;
    $maxIterations = 100;
    $iterations = 0;

    // First, check if the goal is achievable at all, even with a massive step-up
    if (calculate_future_value($initialInvestment, $investmentPeriod, $returnRate, $high) < $targetAmount) {
        echo json_encode(['error' => 'Goal is not achievable even with a 100% annual step-up. Consider increasing initial investment or duration.']);
        exit;
    }
    
    // Check if goal is already met with no step-up
    if (calculate_future_value($initialInvestment, $investmentPeriod, $returnRate, 0) > $targetAmount) {
        echo json_encode(['stepUpRate' => 0]);
        exit;
    }


    while ($high - $low > $precision && $iterations < $maxIterations) {
        $mid = ($low + $high) / 2;
        $calculatedValue = calculate_future_value($initialInvestment, $investmentPeriod, $returnRate, $mid);

        if ($calculatedValue < $targetAmount) {
            $low = $mid;
        } else {
            $high = $mid;
        }
        $iterations++;
    }

    $resultRate = ($low + $high) / 2;

    echo json_encode(['stepUpRate' => $resultRate]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart "Step-Up" Goal Reverser | SIP & SWP Calculator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in-out forwards;
        }
    </style>
</head>

<body class="dark bg-gradient-to-br from-slate-900 to-slate-800 text-slate-200 transition-colors duration-300 flex items-center justify-center min-h-screen p-4">

    <div class="max-w-2xl w-full">
        
        <header class="relative mb-8 text-center">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400 pb-2">
                Smart "Step-Up" Goal Reverser
            </h1>
            <p class="text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto">
                Define your financial target, and we'll find the path to get you there.
            </p>
        </header>

        <main class="bg-slate-800/50 backdrop-blur-sm p-6 sm:p-8 rounded-2xl shadow-2xl border border-slate-700">
            <div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="targetAmount" class="block text-sm font-medium mb-2">Target Amount</label>
                        <input type="number" id="targetAmount" placeholder="1,000,000" class="w-full px-4 py-2 rounded-lg bg-slate-700/50 border-slate-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                    <div>
                        <label for="initialInvestment" class="block text-sm font-medium mb-2">Initial Monthly Investment</label>
                        <input type="number" id="initialInvestment" placeholder="5,000" class="w-full px-4 py-2 rounded-lg bg-slate-700/50 border-slate-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                    <div>
                        <label for="investmentPeriod" class="block text-sm font-medium mb-2">Investment Period (Years)</label>
                        <input type="number" id="investmentPeriod" placeholder="10" class="w-full px-4 py-2 rounded-lg bg-slate-700/50 border-slate-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                    <div>
                        <label for="returnRate" class="block text-sm font-medium mb-2">Expected Return Rate (% p.a.)</label>
                        <input type="number" id="returnRate" placeholder="12" class="w-full px-4 py-2 rounded-lg bg-slate-700/50 border-slate-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                </div>

                <button id="calculateBtn" class="mt-8 w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-lg transition-all duration-300 transform hover:scale-105">
                    Calculate Required Step-Up
                </button>
            </div>

            <!-- Result Section -->
            <div id="result" class="mt-8 pt-6 border-t border-slate-700" style="display: none;">
                 <h2 class="text-lg font-semibold text-slate-300 mb-2 text-center">Your Path to Success Requires an Annual Step-Up of:</h2>
                 <p class="text-7xl font-bold text-center text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400 mb-4">
                    <span id="stepUpRate"></span><span class="text-5xl">%</span>
                 </p>
                 <p id="result-message" class="text-slate-400 text-center"></p>
            </div>
        </main>
        
        <footer class="mt-8 text-sm text-center text-slate-400">
             <a href="default.php" class="text-indigo-400 hover:underline">
                &larr; Back to Main SIP/SWP Calculator
            </a>
             <p class="mt-4 text-xs">&copy; <?= date('Y') ?> SIP/SWP Calculator. All rights reserved.</p>
        </footer>

    </div>
    <script>
        // Minor JS adjustment to handle the new result display animation
        document.addEventListener('DOMContentLoaded', function () {
            const resultDiv = document.getElementById('result');
            const originalDisplay = resultDiv.style.display;

            // Override the default show/hide to add the animation class
            Object.defineProperty(resultDiv.style, 'display', {
                set: function(value) {
                    if (value === 'block' && this.getPropertyValue('display') === 'none') {
                        resultDiv.classList.remove('fade-in');
                        // Timeout to allow the browser to apply the class removal
                        setTimeout(() => {
                            resultDiv.classList.add('fade-in');
                        }, 10);
                    } else if (value === 'none') {
                         resultDiv.classList.remove('fade-in');
                    }
                    this.setProperty('display', value, '');
                },
                get: function() {
                    return this.getPropertyValue('display');
                }
            });
        });
    </script>
    <script src="goal-reverser.js"></script>
</body>
</html>
