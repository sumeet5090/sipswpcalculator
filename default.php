<?php
declare(strict_types=1);

// Format numbers in Indian numbering system with Lakh/Cr suffix for large numbers
function formatInr(float|int $num): string
{
	$absNum = abs($num);
	if ($absNum >= 10000000) { // 1 Crore
		return '₹' . round($num / 10000000, 2) . ' Cr';
	} elseif ($absNum >= 100000) { // 1 Lakh
		return '₹' . round($num / 100000, 2) . ' L';
	} else {
		$numStr = (string) round($num);
		if (strlen($numStr) > 3) {
			$last3 = substr($numStr, -3);
			$rest = substr($numStr, 0, -3);
			$rest = preg_replace("/\\B(?=(\\d{2})+(?!\\d))/", ",", $rest);
			return '₹' . $rest . ',' . $last3;
		}
		return '₹' . $numStr;
	}
}

// Default values.
$default_sip = 1000;
$default_years = 10;
$default_rate = 12;
$default_stepup = 10;
$default_swp_withdrawal = 10000;
$default_swp_stepup = 10;
$default_swp_years = 20;

// Retrieve POST values or use defaults.
$sip = isset($_POST['sip']) ? (float) $_POST['sip'] : $default_sip;
$years = isset($_POST['years']) ? (int) $_POST['years'] : $default_years;
$rate = isset($_POST['rate']) ? (float) $_POST['rate'] : $default_rate;
$stepup = isset($_POST['stepup']) ? (float) $_POST['stepup'] : $default_stepup;
$swp_withdrawal = isset($_POST['swp_withdrawal']) ? (float) $_POST['swp_withdrawal'] : $default_swp_withdrawal;
$swp_stepup = isset($_POST['swp_stepup']) ? (float) $_POST['swp_stepup'] : $default_swp_stepup;
$swp_years_input = isset($_POST['swp_years']) ? (int) $_POST['swp_years'] : $default_swp_years;
$action = $_POST['action'] ?? '';

// SWP automatically starts the year immediately following the SIP period.
$swp_start = $years + 1;

$monthly_rate = $rate / 100 / 12;

// Simulation period: SIP years + user-defined SWP years.
$simulation_years = $years + $swp_years_input;

$net_balance = 0.0;
$cumulative_invested = 0.0;
$cumulative_withdrawals = 0.0;
$combined = [];

// Main loop
for ($y = 1; $y <= $simulation_years; $y++) {
	// Determine monthly SIP (if within SIP period).
	$monthly_sip = ($y <= $years) ? round($sip * pow(1 + $stepup / 100, $y - 1), 2) : 0.0;
	$annual_contribution = $monthly_sip * 12.0;

	// Determine monthly SWP (if SWP has started).
	$monthly_swp = ($y >= $swp_start) ? round($swp_withdrawal * pow(1 + $swp_stepup / 100, $y - $swp_start), 2) : 0.0;
	// Instead of precomputing annual SWP, we'll sum actual withdrawals.
	$actual_year_withdrawn = 0.0;

	$year_begin = $net_balance;

	// Simulate month-by-month for the year.
	for ($m = 1; $m <= 12; $m++) {
		$contrib = ($y <= $years) ? $monthly_sip : 0.0;
		// contribution arrives before withdrawal in that month
		$potential_balance = $net_balance + $contrib;
		if ($y >= $swp_start && $monthly_swp > 0.0) {
			// Cap the withdrawal to available funds (never go negative)
			$desired_withdraw = $monthly_swp;
			$withdraw = ($desired_withdraw > $potential_balance) ? $potential_balance : $desired_withdraw;
			$withdraw = max(0.0, $withdraw);
		} else {
			$withdraw = 0.0;
		}
		$actual_year_withdrawn += $withdraw;
		// update net balance and apply monthly interest
		$net_balance = ($net_balance + $contrib - $withdraw) * (1 + $monthly_rate);
		// guard tiny negative rounding
		if ($net_balance < 0 && $net_balance > -0.0001) {
			$net_balance = 0.0;
		}
	}

	$annual_withdrawal = $actual_year_withdrawn;
	// interest earned during the year = final - (start + contributions - withdrawals)
	$interest_earned = $net_balance - ($year_begin + $annual_contribution - $annual_withdrawal);
	$cumulative_invested += $annual_contribution;
	if ($y >= $swp_start) {
		$cumulative_withdrawals += $annual_withdrawal;
	}

	$combined[$y] = [
		'year' => $y,
		'begin_balance' => round($year_begin),
		'sip_monthly' => ($y <= $years) ? $monthly_sip : null,
		'annual_contribution' => $annual_contribution,
		'cumulative_invested' => $cumulative_invested,
		'swp_monthly' => ($y >= $swp_start && $monthly_swp > 0.0) ? $monthly_swp : null,
		'annual_withdrawal' => ($y >= $swp_start) ? $annual_withdrawal : null,
		'cumulative_withdrawals' => ($y >= $swp_start) ? $cumulative_withdrawals : 0.0,
		'interest' => round($interest_earned),
		'combined_total' => round($net_balance)
	];
}

if ($action === 'download_csv') {
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="SIP_SWP_Report.csv"');
	echo "\xEF\xBB\xBF"; // BOM for UTF-8 Excel
	$csv = new SplTempFileObject();
	$csv->setCsvControl(',', '"', "\\");
	$csv->fputcsv([
		'Year',
		'Start-of-Year Corpus (₹)',
		'Monthly SIP (₹)',
		'Annual SIP Contribution (₹)',
		'Total SIP Invested to Date (₹)',
		'Monthly SWP Withdrawal (₹)',
		'Annual SWP Withdrawal (₹)',
		'Total SWP Withdrawals to Date (₹)',
		'Interest Earned This Year (₹)',
		'End-of-Year Corpus (₹)'
	]);
	for ($y = 1; $y <= $simulation_years; $y++) {
		$row = $combined[$y];
		$csv->fputcsv([
			$row['year'],
			formatInr($row['begin_balance']),
			$row['sip_monthly'] !== null ? formatInr($row['sip_monthly']) : '-',
			formatInr($row['annual_contribution']),
			formatInr($row['cumulative_invested']),
			$row['swp_monthly'] !== null ? formatInr($row['swp_monthly']) : '-',
			$row['annual_withdrawal'] !== null ? formatInr($row['annual_withdrawal']) : '-',
			$row['cumulative_withdrawals'] ? formatInr($row['cumulative_withdrawals']) : '-',
			formatInr($row['interest']),
			formatInr($row['combined_total'])
		]);
	}
	$csv->rewind();
	while (!$csv->eof()) {
		echo $csv->fgets();
	}
	exit;
}

// Prepare chart data for the line graph.
$years_data = array();
$cumulative_numbers = array();
$combined_numbers = array();
$swp_numbers = array();
foreach ($combined as $row) {
	$years_data[] = $row['year'];
	$cumulative_numbers[] = $row['cumulative_invested'];
	$combined_numbers[] = $row['combined_total'];
	$swp_numbers[] = $row['annual_withdrawal'] ?? 0.0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Free SIP Calculator</title>
	<meta name="description"
		content="Use our free SIP & SWP calculator to plan your investments. A simple, accurate tool designed for Indian investors.">
	<link rel="canonical" href="http://sip-calculator.sumeet-boga.lovestoblog.com/?i=1">
	<meta name="robots" content="index,follow">
	<script type="application/ld+json">
  {
	"@context": "https://schema.org",
	"@type": "WebApplication",
	"name": "Free SIP Calculator",
	"url": "http://sip-calculator.sumeet-boga.lovestoblog.com/?i=1",
	"applicationCategory": "Finance",
	"description": "A free, easy-to-use SIP calculator for Indian investors."
  }
  </script>
	<!-- Cyborg dark theme from Bootswatch -->
	<link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.7/dist/cyborg/bootstrap.min.css" rel="stylesheet">
	<!-- Chart.js (modern build) -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
	<!-- Chart.js Zoom plugin (adds wheel/pinch/drag zoom + pan) -->
	<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.umd.min.js"></script>

	<style>
		/* Ensure touch gestures (pinch/drag) are captured by the canvas, not the page */
		#corpusChart {
			touch-action: none;
			/* modern browsers */
			-ms-touch-action: none;
			/* old Edge */
			user-select: none;
			-webkit-user-select: none;
			min-height: 280px;
			/* give some breathing room for gesture UI */
			width: 100%;
			display: block;
		}
	</style>
</head>

<body>
	<div class="container my-5">
		<header class="mb-4 text-center">
			<h1 class="mb-3">Free SIP & SWP Calculator</h1>
			<p class="lead">Visualize your investment growth with our integrated tool.</p>
		</header>

		<div class="card mb-4 shadow">
			<div class="card-body">
				<form method="post" novalidate>
					<fieldset class="mb-4">
						<legend class="mb-3">SIP Details</legend>
						<div class="row g-3">
							<div class="col-md-3">
								<label class="form-label">Monthly SIP Investment (₹)</label>
								<input type="number" step="0.01" name="sip" class="form-control" required min="1"
									value="<?= htmlspecialchars((string) $sip) ?>">
							</div>
							<div class="col-md-3">
								<label class="form-label">Years of Investment</label>
								<input type="number" name="years" class="form-control" required min="1"
									value="<?= htmlspecialchars((string) $years) ?>">
							</div>
							<div class="col-md-3">
								<label class="form-label">Annual Interest Rate (% p.a.)</label>
								<input type="number" step="0.01" name="rate" class="form-control" required min="0"
									value="<?= htmlspecialchars((string) $rate) ?>">
							</div>
							<div class="col-md-3">
								<label class="form-label">Annual SIP Increase (%)</label>
								<input type="number" step="0.01" name="stepup" class="form-control" required min="0"
									value="<?= htmlspecialchars((string) $stepup) ?>">
							</div>
						</div>
					</fieldset>
					<br>
					<fieldset class="mb-4">
						<legend class="mb-3">SWP Details</legend>
						<!-- SWP automatically starts the year after SIP ends -->
						<div class="row g-3">
							<div class="col-md-3">
								<label class="form-label">Monthly SWP Withdrawal (₹)</label>
								<input type="number" step="0.01" name="swp_withdrawal" class="form-control" required
									min="0" value="<?= htmlspecialchars((string) $swp_withdrawal) ?>">
							</div>
							<div class="col-md-3">
								<label class="form-label">Annual SWP Increase (%)</label>
								<input type="number" step="0.01" name="swp_stepup" class="form-control" required min="0"
									value="<?= htmlspecialchars((string) $swp_stepup) ?>">
							</div>
							<div class="col-md-3">
								<label class="form-label">Number of SWP Years</label>
								<input type="number" name="swp_years" class="form-control" required min="1"
									value="<?= htmlspecialchars((string) $swp_years_input) ?>">
							</div>
						</div>
						<p class="mt-2 small text-muted">Note: SWP automatically starts in the year immediately
							following your SIP
							period. Monthly withdrawals are capped to available funds.</p>
					</fieldset>
					<div class="mb-3">
						<button type="submit" name="action" value="calculate"
							class="btn btn-primary me-2">Calculate</button>
						<button type="submit" name="action" value="download_csv" class="btn btn-secondary me-2">Download
							CSV
							Report</button>
						<button type="reset" class="btn btn-outline-danger">Reset</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Chart Section -->
		<div class="card mb-4 shadow">
			<div class="card-body">
				<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
					<div>
						<h2 class="card-title mb-1">Corpus, Cumulative Investment & SWP Withdrawals</h2>
						<p class="small text-muted mb-0">Hover to inspect. Scroll to zoom, pinch on touch, drag to zoom
							selection.
							Alt+Drag to pan.</p>
					</div>
					<button id="resetZoomBtn" type="button" class="btn btn-outline-light btn-sm">
						Reset Zoom
					</button>
				</div>
				<div class="mt-3 position-relative" style="height: 360px;">
					<canvas id="corpusChart"></canvas>
				</div>
			</div>
		</div>

		<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'download_csv'): ?>
			<div class="card shadow mb-3">
				<div class="card-body">
					<h5 class="mb-2">Note</h5>
					<p class="small text-muted mb-0">End-of-Year Corpus = your portfolio value at the end of the year
						(includes all
						principal invested so far and interest earned). The table below shows the year-by-year breakdown.
					</p>
				</div>
			</div>

			<div class="card shadow">
				<div class="card-body">
					<h2 class="card-title mb-4">Yearly Report</h2>
					<div class="table-responsive">
						<table class="table table-bordered table-striped">
							<thead class="table-dark">
								<tr>
									<th data-bs-toggle="tooltip" title="Financial year number of the simulation">Year</th>
									<th data-bs-toggle="tooltip"
										title="Corpus at the start of the year (carryover from previous year end)">
										Start-of-Year Corpus (₹)</th>
									<th data-bs-toggle="tooltip"
										title="Monthly SIP amount for that year (annual step-up applied)">Monthly
										SIP (₹)</th>
									<th data-bs-toggle="tooltip"
										title="Total SIP contributed during that year (Monthly SIP × 12)">Annual
										SIP Contribution (₹)</th>
									<th data-bs-toggle="tooltip" title="Total SIP contributed cumulatively up to this year">
										Total SIP
										Invested to Date (₹)</th>
									<th data-bs-toggle="tooltip"
										title="Monthly SWP amount for that year (starts after SIP period)">Monthly
										SWP Withdrawal (₹)</th>
									<th data-bs-toggle="tooltip"
										title="Total SWP withdrawn during that year (sum of monthly withdrawals actually paid)">
										Annual SWP
										Withdrawal (₹)</th>
									<th data-bs-toggle="tooltip" title="Total SWP withdrawn cumulatively up to this year">
										Total SWP
										Withdrawals to Date (₹)</th>
									<th data-bs-toggle="tooltip"
										title="Interest portion earned during this year (End Corpus - Start Corpus - Net Contribution)">
										Interest Earned This Year (₹)</th>
									<th data-bs-toggle="tooltip"
										title="Portfolio value at year end (includes principal + interest), also used by the chart">
										End-of-Year Corpus (₹)</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($combined as $row): ?>
									<tr>
										<td><?= $row['year'] ?></td>
										<td><?= formatInr($row['begin_balance']) ?></td>
										<td><?= $row['sip_monthly'] !== null ? formatInr($row['sip_monthly']) : '-' ?></td>
										<td><?= formatInr($row['annual_contribution']) ?></td>
										<td><?= formatInr($row['cumulative_invested']) ?></td>
										<td><?= $row['swp_monthly'] !== null ? formatInr($row['swp_monthly']) : '-' ?></td>
										<td><?= $row['annual_withdrawal'] !== null ? formatInr($row['annual_withdrawal']) : '-' ?>
										</td>
										<td><?= $row['cumulative_withdrawals'] ? formatInr($row['cumulative_withdrawals']) : '-' ?>
										</td>
										<td><?= formatInr($row['interest']) ?></td>
										<td><?= formatInr($row['combined_total']) ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<p class="mt-3 text-muted fst-italic">Disclaimer: This tool is for illustrative purposes only and does not constitute Financial Advice.</p>
				</div>
			</div>
		<?php endif; ?>

	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// enable Bootstrap tooltips
			var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
			tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

			// Register the zoom plugin (needed for CDN UMD build)
			if (window['chartjs-plugin-zoom']) {
				Chart.register(window['chartjs-plugin-zoom']);
			}

			// Chart data from PHP
			const years = <?= json_encode($years_data) ?>;
			const cumulative = <?= json_encode($cumulative_numbers) ?>;
			const corpus = <?= json_encode($combined_numbers) ?>;
			const swp = <?= json_encode($swp_numbers) ?>;

			function inrCompact(num) {
				if (num >= 10000000) return '₹' + (num / 10000000).toFixed(2) + ' Cr';
				if (num >= 100000) return '₹' + (num / 100000).toFixed(2) + ' L';
				return '₹' + num.toLocaleString('en-IN');
			}

			const ctx = document.getElementById('corpusChart').getContext('2d');

			const chart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: years,
					datasets: [
						{
							label: 'Total SIP Invested (₹)',
							data: cumulative,
							borderWidth: 2,
							tension: 0.35,
							pointRadius: 3,
							pointHoverRadius: 6,
							borderColor: 'rgba(0, 200, 150, 1)',
							backgroundColor: 'rgba(0, 200, 150, 0.08)',
							fill: true,
						},
						{
							label: 'End-of-Year Corpus (₹)',
							data: corpus,
							borderWidth: 2.5,
							tension: 0.4,
							pointRadius: 3,
							pointHoverRadius: 6,
							borderColor: 'rgba(102, 102, 255, 1)',
							backgroundColor: 'rgba(102, 102, 255, 0.06)',
							fill: true,
						},
						{
							label: 'Annual SWP Withdrawals (₹)',
							data: swp,
							borderWidth: 2,
							tension: 0.35,
							pointRadius: 3,
							pointHoverRadius: 6,
							borderColor: 'rgba(255, 99, 132, 1)',
							backgroundColor: 'rgba(255, 99, 132, 0.04)',
							borderDash: [6, 4],
							fill: false,
						}
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					interaction: { intersect: false, mode: 'index' },
					plugins: {
						legend: { position: 'top' },
						tooltip: {
							callbacks: {
								label: ctx => `${ctx.dataset.label}: ${inrCompact(ctx.raw)}`
							}
						},
						zoom: {
							limits: {
								x: { min: Math.min(...years), max: Math.max(...years) },
								y: { min: 0 } // amounts shouldn't go negative
							},
							pan: {
								enabled: true,
								mode: 'xy',
								modifierKey: 'alt' // hold Alt to pan (prevents accidental pans)
							},
							zoom: {
								wheel: { enabled: true },     // scroll to zoom
								pinch: { enabled: true },     // pinch to zoom (touch)
								drag: { enabled: true },     // drag selection to zoom
								pan: { enabled: true, mode: 'xy' }
							}
						}
					},
					scales: {
						x: {
							title: { display: true, text: 'Year' }
						},
						y: {
							title: { display: true, text: 'Amount (₹)' },
							ticks: {
								callback: val => inrCompact(val)
							}
						}
					},
					animations: {
						tension: { duration: 1000, easing: 'easeOutQuart' }
					}
				}
			});

			// Reset zoom handlers
			const resetBtn = document.getElementById('resetZoomBtn');
			if (resetBtn) resetBtn.addEventListener('click', () => chart.resetZoom());
			// double-click canvas to reset zoom
			document.getElementById('corpusChart').addEventListener('dblclick', () => chart.resetZoom());
		});
	</script>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>