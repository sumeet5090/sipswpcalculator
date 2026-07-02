<?php
/**
 * chart-visualization.php
 * Component for summary cards and Chart.js canvas wrapper.
 */
declare(strict_types=1);

$lastRow = end($combined) ?: [
    'cumulative_invested' => 0,
    'combined_total' => 0,
    'cumulative_withdrawals' => 0
];
$totalGains = ($lastRow['combined_total'] + ($lastRow['cumulative_withdrawals'] ?? 0)) - ($lastRow['cumulative_invested'] ?? 0);

// Safe mapping of values
$years_data_val = isset($years_data) ? array_values($years_data) : [];
$cumulative_numbers_val = isset($cumulative_numbers) ? array_values($cumulative_numbers) : [];
$combined_numbers_val = isset($combined_numbers) ? array_values($combined_numbers) : [];
$swp_numbers_val = isset($swp_numbers) ? array_values($swp_numbers) : [];
?>
<div class="space-y-4">
    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="glass-card p-3 sm:p-4 text-center">
            <div class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                Total Invested
            </div>
            <div id="summary-invested" class="text-lg sm:text-xl font-extrabold text-emerald-600 font-mono transition-numbers">
                <?= formatInr($lastRow['cumulative_invested']) ?>
            </div>
        </div>
        <div class="glass-card p-3 sm:p-4 text-center">
            <div class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                Total Gains
            </div>
            <div id="summary-interest" class="text-lg sm:text-xl font-extrabold text-emerald-600 font-mono transition-numbers">
                <?= formatInr($totalGains) ?>
            </div>
        </div>
        <div class="glass-card p-3 sm:p-4 text-center">
            <div class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                Total Withdrawn
            </div>
            <div id="summary-withdrawn" class="text-lg sm:text-xl font-extrabold text-rose-500 font-mono transition-numbers">
                <?= formatInr($lastRow['cumulative_withdrawals'] ?? 0) ?>
            </div>
        </div>
        <div class="glass-card p-3 sm:p-4 text-center border-2 border-emerald-100">
            <div class="text-[10px] sm:text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">
                Final Corpus
            </div>
            <div id="summary-corpus" class="text-lg sm:text-xl font-extrabold text-slate-800 font-mono transition-numbers">
                <?= formatInr($lastRow['combined_total']) ?>
            </div>
        </div>
    </div>

    <!-- Chart Card -->
    <div class="relative z-10 bg-[var(--glass-bg)] rounded-3xl border border-[var(--glass-border)] shadow-2xl backdrop-blur-xl overflow-hidden transition-all duration-300 hover:shadow-emerald-500/10 p-4 sm:p-6">
        <div class="h-[280px] sm:h-[350px] lg:h-[450px] w-full relative z-10">
            <canvas id="corpusChart" width="800" height="450"
                data-years="<?= htmlspecialchars(json_encode($years_data_val), ENT_QUOTES, 'UTF-8') ?>"
                data-cumulative="<?= htmlspecialchars(json_encode($cumulative_numbers_val), ENT_QUOTES, 'UTF-8') ?>"
                data-corpus="<?= htmlspecialchars(json_encode($combined_numbers_val), ENT_QUOTES, 'UTF-8') ?>"
                data-swp="<?= htmlspecialchars(json_encode($swp_numbers_val), ENT_QUOTES, 'UTF-8') ?>"></canvas>
        </div>
    </div>
</div>
