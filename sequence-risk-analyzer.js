document.addEventListener('DOMContentLoaded', function () {
    const runSimulationBtn = document.getElementById('runSimulationBtn');
    const ctx = document.getElementById('sequenceRiskChart').getContext('2d');

    let chart; // To hold the chart instance

    const chartConfig = {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Bear Market Start (-15% for 2yr)',
                    data: [],
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Flat Market Start (7% avg)',
                    data: [],
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.1,
                    borderDash: [5, 5]
                },
                 {
                    label: 'Bull Market Start (+20% for 2yr)',
                    data: [],
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Portfolio Balance Over Time',
                    color: '#cbd5e1'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                },
                 legend: {
                    labels: {
                        color: '#cbd5e1'
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Years in Retirement',
                        color: '#94a3b8'
                    },
                    ticks: {
                        color: '#94a3b8'
                    },
                    grid: {
                        color: 'rgba(148, 163, 184, 0.2)'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Portfolio Value',
                        color: '#94a3b8'
                    },
                    ticks: {
                        color: '#94a3b8',
                         callback: function(value, index, values) {
                            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', notation: 'compact' }).format(value);
                        }
                    },
                    grid: {
                        color: 'rgba(148, 163, 184, 0.2)'
                    }
                }
            }
        }
    };
    
    chart = new Chart(ctx, chartConfig);

    function runSimulation() {
        const initialCorpus = document.getElementById('initialCorpus').value;
        const monthlyWithdrawal = document.getElementById('monthlyWithdrawal').value;
        const withdrawalPeriod = document.getElementById('withdrawalPeriod').value;
        const withdrawalIncrease = document.getElementById('withdrawalIncrease').value;
        const avgReturn = document.getElementById('avgReturn').value;
        const bearReturn = document.getElementById('bearReturn').value;
        const bullReturn = document.getElementById('bullReturn').value;

        // Update chart labels dynamically based on input
        chart.data.datasets[0].label = `Bear Market Start (${bearReturn}% for 2yr)`;
        chart.data.datasets[1].label = `Flat Market Start (${avgReturn}% avg)`;
        chart.data.datasets[2].label = `Bull Market Start (${bullReturn}% for 2yr)`;

        runSimulationBtn.textContent = 'Simulating...';
        runSimulationBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'run_simulation');
        formData.append('initialCorpus', initialCorpus);
        formData.append('monthlyWithdrawal', monthlyWithdrawal);
        formData.append('withdrawalPeriod', withdrawalPeriod);
        formData.append('withdrawalIncrease', withdrawalIncrease);
        formData.append('avgReturn', avgReturn);
        formData.append('bearReturn', bearReturn);
        formData.append('bullReturn', bullReturn);

        fetch('sequence-risk-analyzer.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.bearData;
            chart.data.datasets[1].data = data.flatData;
            chart.data.datasets[2].data = data.bullData;
            chart.update();

            runSimulationBtn.textContent = 'Run Simulation';
            runSimulationBtn.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            runSimulationBtn.textContent = 'Run Simulation';
            runSimulationBtn.disabled = false;
        });
    }

    runSimulationBtn.addEventListener('click', runSimulation);

    // Run once on page load with default values
    runSimulation();
});
