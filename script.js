document.addEventListener('DOMContentLoaded', function () {
	// Register the zoom plugin (needed for CDN UMD build)
	if (window['chartjs-plugin-zoom']) {
		Chart.register(window['chartjs-plugin-zoom']);
	}

	// Chart data from PHP
	const years = window.chartData.years;
	const cumulative = window.chartData.cumulative;
	const corpus = window.chartData.corpus;
	const swp = window.chartData.swp;

	function inrCompact(num) {
		if (num >= 1000000) return (num / 1000000).toFixed(2) + 'M';
		return num.toLocaleString('en-US');
	}

	const ctx = document.getElementById('corpusChart').getContext('2d');

	const chart = new Chart(ctx, {
		type: 'line',
		data: {
			labels: years,
			datasets: [
				{
					label: 'Total SIP Invested',
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
					label: 'End-of-Year Corpus',
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
					label: 'Annual SWP Withdrawals',
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
					title: { display: true, text: 'Amount' },
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
