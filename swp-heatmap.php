<?php
// --- CONFIGURATION ---
$withdrawal_rates = [3, 4, 5, 6, 7]; // in Percent
$durations = [10, 15, 20, 25, 30]; // in Years

// --- USER INPUTS (with defaults) ---
$initial_corpus = isset($_GET['corpus']) ? (float)$_GET['corpus'] : 10000000; // 1 Crore
$expected_return = isset($_GET['return']) ? (float)$_GET['return'] : 8; // 8% p.a.
$inflation_rate = isset($_GET['inflation']) ? (float)$_GET['inflation'] : 6; // 6% p.a.

/**
 * Simulates portfolio survival for a given scenario.
 *
 * @param float $corpus Initial investment.
 * @param int $years Duration of withdrawal.
 * @param float $withdrawal_rate Annual withdrawal rate (%).
 * @param float $return_rate Expected annual portfolio return (%).
 * @param float $inflation Annual inflation rate (%).
 * @return float Final portfolio balance.
 */
function simulate_survival($corpus, $years, $withdrawal_rate, $return_rate, $inflation) {
    $balance = $corpus;
    $annual_withdrawal = $corpus * ($withdrawal_rate / 100);

    for ($i = 0; $i < $years; $i++) {
        if ($balance <= 0) return 0;

        // 1. Withdraw amount for the year (adjusted for inflation)
        $current_withdrawal = $annual_withdrawal * pow(1 + ($inflation / 100), $i);
        $balance -= $current_withdrawal;

        // 2. Portfolio grows for the rest of the year
        if ($balance > 0) {
            $balance *= (1 + ($return_rate / 100));
        }
    }
    return $balance;
}

/**
 * Renders the survival heatmap grid.
 *
 * @param float $corpus
 * @param float $return_rate
 * @param float $inflation
 * @param array $withdrawal_rates
 * @param array $durations
 * @return string HTML for the grid.
 */
function render_survival_table($corpus, $return_rate, $inflation, $withdrawal_rates, $durations) {
    $html = '<div class="heatmap-grid" style="--cols: ' . (count($durations) + 1) . ';">';

    // Headers
    $html .= '<div class="header-cell">Rate ↓ | Years →</div>';
    foreach ($durations as $year) {
        $html .= '<div class="header-cell">' . $year . ' Yrs</div>';
    }

    // Body
    foreach ($withdrawal_rates as $w_rate) {
        $html .= '<div class="header-cell">' . $w_rate . '%</div>';
        foreach ($durations as $year) {
            $final_balance = simulate_survival($corpus, $year, $w_rate, $return_rate, $inflation);
            $is_safe = $final_balance > 0;
            
            // Opacity logic:
            // Safe: closer to 1 means more money left. 0.3 is minimum.
            // Depleted: closer to 1 means it depleted faster/earlier. 0.3 is min.
            // This is a simple representation.
            $opacity = $is_safe ? min(0.3 + ($final_balance / $corpus) * 0.7, 1.0) : 0.7;

            $status_class = $is_safe ? 'safe' : 'depleted';
            $formatted_balance = '₹' . number_format($final_balance > 0 ? $final_balance : 0);
            
            $html .= '<div class="cell ' . $status_class . '" style="--opacity: ' . $opacity . ';">';
            $html .= '<div class="cell-status">' . ($is_safe ? 'Survives' : 'Depleted') . '</div>';
            $html .= '<div class="cell-balance">Bal: ' . $formatted_balance . '</div>';
            $html .= '</div>';
        }
    }

    $html .= '</div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe Withdrawal Rate (SWR) Heatmap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Specific styles for the heatmap */
        .heatmap-grid {
            display: grid;
            grid-template-columns: repeat(var(--cols, 6), minmax(0, 1fr));
            gap: 8px;
            margin-top: 2rem;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid #dee2e6;
        }
        .header-cell {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: center;
            padding: 0.75rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
            color: #495057;
        }
        .cell {
            border-radius: 6px;
            padding: 0.75rem;
            text-align: center;
            transition: all 0.2s ease-in-out;
            color: white;
            position: relative;
        }
        .cell.safe {
             background-color: rgba(40, 167, 69, var(--opacity, 0.7)); /* Green */
        }
        .cell.depleted {
             background-color: rgba(220, 53, 69, var(--opacity, 0.7)); /* Red */
        }
        .cell:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            z-index: 10;
        }
        .cell-status {
            font-weight: bold;
            font-size: 1rem;
        }
        .cell-balance {
            font-size: 0.8rem;
            margin-top: 4px;
            opacity: 0.8;
            font-family: monospace;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 flex items-center justify-center min-h-screen p-4">

    <div class="max-w-5xl w-full">
        <header class="text-center mb-8">
            <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-sky-500 to-blue-600 pb-2">
                Safe Withdrawal Rate (SWR) Heatmap
            </h1>
            <p class="text-gray-600">Find the sweet spot for your retirement withdrawals.</p>
        </header>

        <main class="bg-white p-6 sm:p-8 rounded-2xl shadow-2xl border border-gray-200">
            <!-- Input Form -->
            <form method="GET" action="swp-heatmap.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="corpus" class="block text-sm font-medium text-gray-700 mb-1">Initial Corpus</label>
                    <input type="number" name="corpus" id="corpus" class="w-full" value="<?= htmlspecialchars($initial_corpus) ?>">
                </div>
                <div>
                    <label for="return" class="block text-sm font-medium text-gray-700 mb-1">Expected Return (% p.a.)</label>
                    <input type="number" name="return" id="return" class="w-full" value="<?= htmlspecialchars($expected_return) ?>">
                </div>
                <div>
                    <label for="inflation" class="block text-sm font-medium text-gray-700 mb-1">Inflation Rate (% p.a.)</label>
                    <input type="number" name="inflation" id="inflation" class="w-full" value="<?= htmlspecialchars($inflation_rate) ?>">
                </div>
                <button type="submit" class="w-full md:w-auto justify-self-start h-10 px-6">Generate Heatmap</button>
            </form>

            <!-- Heatmap -->
            <?= render_survival_table($initial_corpus, $expected_return, $inflation_rate, $withdrawal_rates, $durations) ?>
            
             <div class="mt-6 text-xs text-gray-500 text-center">
                <p>Each cell shows if your portfolio would survive for a given duration and withdrawal rate. 'Bal' is the final balance.</p>
            </div>
        </main>
        
        <footer class="mt-8 text-sm text-center text-gray-500">
             <a href="default.php" class="text-sky-500 hover:underline">
                &larr; Back to Main SIP/SWP Calculator
            </a>
             <p class="mt-4 text-xs">&copy; <?= date('Y') ?> SIP/SWP Calculator. All rights reserved.</p>
        </footer>
    </div>

</body>
</html>