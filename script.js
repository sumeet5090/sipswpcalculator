document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Chart
    const ctx = document.getElementById('corpusChart');
    let chartInstance = null;

    if (ctx) {
        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Total Invested',
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        data: [],
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Corpus Value',
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        data: [],
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Annual Withdrawal',
                        borderColor: '#dc3545',
                        borderDash: [5, 5],
                        data: [],
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                if (value >= 10000000) return '₹' + (value / 10000000).toFixed(2) + ' Cr';
                                if (value >= 100000) return '₹' + (value / 100000).toFixed(2) + ' L';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Sync Sliders & Inputs
    const ids = ['sip', 'years', 'rate', 'stepup', 'swp_withdrawal', 'swp_stepup', 'swp_years'];
    ids.forEach(id => {
        const numInput = document.getElementById(id);
        const rangeInput = document.getElementById(id + '_range');

        if (numInput && rangeInput) {
            // Range -> Number
            rangeInput.addEventListener('input', () => {
                numInput.value = rangeInput.value;
                calculateAndRender();
            });
            // Number -> Range
            numInput.addEventListener('input', () => {
                rangeInput.value = numInput.value;
                calculateAndRender();
            });
        }
    });

    // 3. Calculation Logic (Ported from PHP)
    function calculateAndRender() {
        // Get values
        const sip = parseFloat(document.getElementById('sip').value) || 0;
        const years = parseInt(document.getElementById('years').value) || 1;
        const rate = parseFloat(document.getElementById('rate').value) || 0;
        const stepup = parseFloat(document.getElementById('stepup').value) || 0;

        const swp_withdrawal = parseFloat(document.getElementById('swp_withdrawal').value) || 0;
        const swp_stepup = parseFloat(document.getElementById('swp_stepup').value) || 0;
        const swp_years = parseInt(document.getElementById('swp_years').value) || 1;

        // Logic
        const monthly_rate = rate / 100 / 12;
        const simulation_years = years + swp_years;
        const swp_start_year = years + 1;

        let net_balance = 0.0;
        let cumulative_invested = 0.0;

        const labels = [];
        const dataInvested = [];
        const dataCorpus = [];
        const dataWithdrawal = [];

        let swp_amount = swp_withdrawal; // Monthly SWP amount for the year

        for (let y = 1; y <= simulation_years; y++) {
            // Determine monthly SIP for this year
            let monthly_sip = 0;
            if (y <= years) {
                monthly_sip = sip * Math.pow(1 + stepup / 100, y - 1);
            }

            // Determine monthly SWP for this year
            let current_monthly_swp = 0;
            if (y >= swp_start_year) {
                // Determine SWP amount based on step-up
                current_monthly_swp = swp_withdrawal * Math.pow(1 + swp_stepup / 100, y - swp_start_year);
            }

            let annual_invested = 0;
            let annual_withdrawn = 0;

            // Monthly Simulation
            for (let m = 1; m <= 12; m++) {
                // SIP Contribution
                let contrib = (y <= years) ? monthly_sip : 0;
                annual_invested += contrib;

                // SWP Withdrawal
                let withdraw = 0;
                let potential_balance = net_balance + contrib;

                if (y >= swp_start_year && current_monthly_swp > 0) {
                    // Withdraw at end of month (or beginning, consistent with PHP logic)
                    // PHP logic: "contribution arrives before withdrawal"
                    let desired = current_monthly_swp;
                    withdraw = (desired > potential_balance) ? potential_balance : desired;
                }
                annual_withdrawn += withdraw;

                // Update Balance
                net_balance = (net_balance + contrib - withdraw) * (1 + monthly_rate);
                if (net_balance < 0) net_balance = 0;
            }

            // Update Cumulative
            if (y <= years) {
                cumulative_invested += annual_invested;
            }

            // Store Data
            labels.push(y);
            dataInvested.push(Math.round(cumulative_invested));
            dataCorpus.push(Math.round(net_balance));
            dataWithdrawal.push(Math.round(annual_withdrawn));
        }

        // 4. Update UI
        // Update Chart
        if (chartInstance) {
            chartInstance.data.labels = labels;
            chartInstance.data.datasets[0].data = dataInvested;
            chartInstance.data.datasets[1].data = dataCorpus;
            chartInstance.data.datasets[2].data = dataWithdrawal;
            chartInstance.update();
        }

        // Update Summary Cards
        const finalCorpus = dataCorpus[years - 1] || 0; // Corpus at end of investment phase
        const totalInvested = dataInvested[years - 1] || 0;
        const wealthGained = finalCorpus - totalInvested;

        // Find max SWP possible without depletion (simple approximation or just show first year SWP)
        // Let's just show the calculated monthly SWP for the first withdrawal year
        const firstYearSWP = swp_withdrawal;
        // Or if we want to show 'Max Monthly SWP' that is sustainable, that's complex.
        // Let's stick to showing the inputs/outputs. 
        // Logic: The card says "Max Monthly SWP", which implies advice. 
        // But let's show the *actual* SWP user selected or maybe the corpus.
        // Actually, let's update the cards to match the user's simulation results at the END of the investment phase.

        document.getElementById('val-invested').innerText = formatCurrency(totalInvested);
        document.getElementById('val-corpus').innerText = formatCurrency(finalCorpus);
        document.getElementById('val-gain').innerText = formatCurrency(wealthGained);

        // For the 4th card, let's show the "Monthly Income Limit" (Sustainable 6% of corpus / 12)
        const sustainableSWP = (finalCorpus * 0.06) / 12; // 6% rule
        document.getElementById('val-swp-income').innerText = formatCurrency(sustainableSWP);
        document.querySelector('#val-swp-income').previousElementSibling.innerText = "Safe Monthly Withdraw (6%)";
    }

    function formatCurrency(num) {
        if (num >= 10000000) return '₹' + (num / 10000000).toFixed(2) + ' Cr';
        if (num >= 100000) return '₹' + (num / 100000).toFixed(2) + ' L';
        return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(num);
    }

    // Initial Run
    calculateAndRender();
});
