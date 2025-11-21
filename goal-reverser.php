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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step-Up Goal Reverser</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Smart "Step-Up" Goal Reverser</h1>
        <p>Instead of guessing SIP amounts, input your target, and we'll calculate the annual contribution increase (Step-Up) you need.</p>
        
        <div class="calculator">
            <div class="input-group">
                <label for="targetAmount">I need:</label>
                <input type="number" id="targetAmount" placeholder="e.g., 1,000,000">
            </div>
            <div class="input-group">
                <label for="initialInvestment">My initial monthly investment is:</label>
                <input type="number" id="initialInvestment" placeholder="e.g., 5,000">
            </div>
            <div class="input-group">
                <label for="investmentPeriod">in:</label>
                <input type="number" id="investmentPeriod" placeholder="e.g., 10">
                <span>years</span>
            </div>
            <div class="input-group">
                <label for="returnRate">With an expected annual return of:</label>
                <input type="number" id="returnRate" placeholder="e.g., 12">
                <span>%</span>
            </div>
            <button id="calculateBtn">Calculate Required Step-Up</button>
        </div>

        <div id="result" class="result" style="display: none;">
            <h2>Required Annual Step-Up: <span id="stepUpRate"></span>%</h2>
            <p id="result-message"></p>
        </div>
         <div class="back-link">
            <a href="default.php">Back to Main Calculator</a>
        </div>
    </div>
    <script src="goal-reverser.js"></script>
</body>
</html>
