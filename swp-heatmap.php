<?php
// --- CONFIGURATION ---
$withdrawal_rates = [3, 4, 5, 6, 7]; // in Percent
$durations = [10, 15, 20, 25, 30]; // in Years

// --- USER INPUTS (with defaults) ---
$initial_corpus = isset($_GET['corpus']) ? (float) $_GET['corpus'] : 10000000; // 1 Crore
$expected_return = isset($_GET['return']) ? (float) $_GET['return'] : 8; // 8% p.a.
$inflation_rate = isset($_GET['inflation']) ? (float) $_GET['inflation'] : 6; // 6% p.a.

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
function simulate_survival($corpus, $years, $withdrawal_rate, $return_rate, $inflation)
{
    $balance = $corpus;
    $annual_withdrawal = $corpus * ($withdrawal_rate / 100);

    for ($i = 0; $i < $years; $i++) {
        if ($balance <= 0)
            return 0;

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
function render_survival_table($corpus, $return_rate, $inflation, $withdrawal_rates, $durations)
{
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
    <link rel="stylesheet" href="styles.css">
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
    <style>
        /* Specific styles for the heatmap */
        .heatmap-grid {
            display: grid;
            grid-template-columns: repeat(var(--cols, 6), minmax(0, 1fr));
            gap: 8px;
            margin-top: 2rem;
            padding: 1rem;
            border-radius: 12px;
        }

        .header-cell {
            background-color: rgba(255, 255, 255, 0.5);
            font-weight: 800;
            text-align: center;
            padding: 1rem 0.5rem;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cell {
            border-radius: 8px;
            padding: 1rem 0.5rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: white;
            position: relative;
            backdrop-filter: blur(4px);
        }

        .cell.safe {
            background-color: rgba(16, 185, 129, var(--opacity, 0.7));
            /* Emerald */
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2);
        }

        .cell.depleted {
            background-color: rgba(244, 63, 94, var(--opacity, 0.7));
            /* Rose */
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2);
        }

        .cell:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
            z-index: 20;
        }

        .cell-status {
            font-weight: 800;
            font-size: 0.9rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .cell-balance {
            font-size: 0.75rem;
            margin-top: 4px;
            opacity: 0.9;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased"
    style="background-image: var(--gradient-surface); background-attachment: fixed;">
    <?php include 'navbar.php'; ?>

    <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8 animate-float">
        <header class="text-center mb-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-100 mb-4">
                <span class="text-xs font-semibold text-blue-700 tracking-wide uppercase">Safe Withdrawal Rate
                    Tool</span>
            </div>
            <h1 class="text-4xl font-extrabold pb-2">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-500 to-blue-600">SWR Heatmap</span>
            </h1>
            <p class="text-lg text-gray-500 font-medium">Find your retirement sweet spot.</p>
        </header>

        <main class="glass-card p-6 sm:p-8">
            <!-- Input Form -->
            <form method="GET" action="swp-heatmap.php"
                class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end mb-8 border-b border-gray-100 pb-8">
                <div>
                    <label for="corpus" class="block text-sm font-medium text-gray-700 mb-2">Initial Corpus</label>
                    <div class="input-group">
                        <span class="absolute left-3 text-gray-400 pointer-events-none">₹</span>
                        <input type="number" name="corpus" id="corpus" class="input-with-icon pl-8 w-full"
                            value="<?= htmlspecialchars((string) $initial_corpus) ?>">
                    </div>
                </div>
                <div>
                    <label for="return" class="block text-sm font-medium text-gray-700 mb-2">Expected Return</label>
                    <div class="input-group">
                        <input type="number" name="return" id="return" class="w-full"
                            value="<?= htmlspecialchars((string) $expected_return) ?>">
                        <span class="input-suffix">%</span>
                    </div>
                </div>
                <div>
                    <label for="inflation" class="block text-sm font-medium text-gray-700 mb-2">Inflation Rate</label>
                    <div class="input-group">
                        <input type="number" name="inflation" id="inflation" class="w-full"
                            value="<?= htmlspecialchars((string) $inflation_rate) ?>">
                        <span class="input-suffix">%</span>
                    </div>
                </div>
                <div>
                    <button type="submit"
                        class="w-full h-[46px] btn-primary bg-gradient-to-r from-sky-500 to-blue-600 shadow-lg shadow-sky-200">
                        Generate Heatmap
                    </button>
                </div>
            </form>

            <!-- Heatmap -->
            <div class="overflow-x-auto">
                <?= render_survival_table($initial_corpus, $expected_return, $inflation_rate, $withdrawal_rates, $durations) ?>
            </div>

            <div class="mt-6 flex justify-center gap-6 text-xs text-gray-500">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span> Safe
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500"></span> Depleted
                </div>
                <span class="text-gray-400">|</span>
                <p>Brighter colors indicate higher confidence (more balance remaining).</p>
            </div>
        </main>

        <footer class="mt-12 text-center text-sm text-gray-500 glass-card py-6">
            <p>&copy; <?= date('Y') ?> SIP/SWP Calculator. All rights reserved.</p>
        </footer>
    </div>

</body>

</html>