// Initialize theme on page load (before content renders)
(function() {
	const savedTheme = localStorage.getItem('theme') || 'dark';
	document.documentElement.setAttribute('data-theme', savedTheme);
	if (savedTheme === 'light') {
		document.documentElement.classList.add('light');
	}
})();

// Wait for both DOM and Tailwind to be ready
Promise.all([
	new Promise(resolve => {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', resolve);
		} else {
			resolve();
		}
	}),
	window.tailwindReady || Promise.resolve()
]).then(() => {
	// Theme toggle management
	const themeToggle = document.getElementById('themeToggle');
	const moonIcon = document.getElementById('moonIcon');
	const sunIcon = document.getElementById('sunIcon');

	function setTheme(theme) {
		localStorage.setItem('theme', theme);
		document.documentElement.setAttribute('data-theme', theme);
		
		// Update classes for Tailwind dark: mode and custom CSS
		if (theme === 'light') {
			document.documentElement.classList.add('light');
			document.body.classList.add('light-mode');
			document.body.classList.remove('dark-mode');
			if (moonIcon) moonIcon.style.display = 'none';
			if (sunIcon) sunIcon.style.display = 'inline-block';
		} else {
			document.documentElement.classList.remove('light');
			document.body.classList.remove('light-mode');
			document.body.classList.add('dark-mode');
			if (moonIcon) moonIcon.style.display = 'inline-block';
			if (sunIcon) sunIcon.style.display = 'none';
		}
		console.log('Theme set to:', theme);
	}

	// Initialize theme on DOM ready
	const savedTheme = localStorage.getItem('theme') || 'dark';
	setTheme(savedTheme);

	// Toggle on click
	if (themeToggle) {
		themeToggle.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			const currentTheme = localStorage.getItem('theme') || 'dark';
			const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
			setTheme(newTheme);
		});

		// Also handle touchend for mobile reliability
		themeToggle.addEventListener('touchend', function(e) {
			e.preventDefault();
			e.stopPropagation();
			const currentTheme = localStorage.getItem('theme') || 'dark';
			const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
			setTheme(newTheme);
		});
	} else {
		console.warn('Theme toggle button not found!');
	}
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
