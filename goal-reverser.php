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
</head>

<body class="dark bg-gradient-to-br from-slate-900 to-slate-800 text-slate-200 transition-colors duration-300">

    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <header class="relative mb-8 text-center">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-teal-400 pb-2">
                Smart "Step-Up" Goal Reverser
            </h1>
            <p class="text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto">
                Define your financial target, and we'll calculate the annual investment increase required to reach it.
            </p>
        </header>

        <main>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <!-- Form Section -->
                <div class="md:col-span-1 bg-white dark:bg-slate-800/50 dark:backdrop-blur-sm p-6 rounded-xl shadow-lg border dark:border-slate-700">
                    <fieldset>
                        <legend class="text-xl font-semibold mb-4 text-green-400">Your Goal</legend>
                        <div class="space-y-4">
                            <div>
                                <label for="targetAmount" class="block text-sm font-medium mb-1">I need to reach a target of</label>
                                <input type="number" id="targetAmount" placeholder="e.g., 1,000,000" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label for="initialInvestment" class="block text-sm font-medium mb-1">Starting with an initial monthly investment of</label>
                                <input type="number" id="initialInvestment" placeholder="e.g., 5,000" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label for="investmentPeriod" class="block text-sm font-medium mb-1">Over a period of</label>
                                <input type="number" id="investmentPeriod" placeholder="e.g., 10" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <span class="text-sm text-slate-400">years</span>
                            </div>
                            <div>
                                <label for="returnRate" class="block text-sm font-medium mb-1">With an expected annual return of</label>
                                <input type="number" id="returnRate" placeholder="e.g., 12" class="w-full px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700/50 border-transparent focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                 <span class="text-sm text-slate-400">%</span>
                            </div>
                        </div>
                    </fieldset>
                    <button id="calculateBtn" class="mt-8 w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition-all duration-300">Calculate Required Step-Up</button>
                </div>

                <!-- Result Section -->
                <div class="md:col-span-1 flex items-center justify-center">
                    <div id="result" style="display: none;" class="text-center bg-slate-800/50 backdrop-blur-sm p-8 rounded-xl shadow-lg border border-slate-700 w-full">
                        <h2 class="text-xl font-semibold text-slate-300 mb-2">Required Annual Step-Up</h2>
                        <p class="text-6xl font-bold text-green-400 mb-4"><span id="stepUpRate"></span><span class="text-4xl">%</span></p>
                        <p id="result-message" class="text-slate-400"></p>
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
    <script src="goal-reverser.js"></script>
</body>
</html>
