document.addEventListener('DOMContentLoaded', () => {
    const targetAmountEl = document.getElementById('targetAmount');
    const investmentPeriodEl = document.getElementById('investmentPeriod');
    const returnRateEl = document.getElementById('returnRate');
    const stepUpRateEl = document.getElementById('stepUpRate');
    const requiredSipEl = document.getElementById('requiredSip');
    const staircaseContainer = document.getElementById('staircase');

    // --- Debounce utility ---
    let debounceTimer;
    function debounce(func, delay) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(func, delay);
    }

    // --- Core Calculation and Rendering Function ---
    function recalculateAndRender() {
        requiredSipEl.textContent = '...';

        const formData = new FormData();
        formData.append('action', 'calculate_initial_sip');
        formData.append('targetAmount', targetAmountEl.value);
        formData.append('investmentPeriod', investmentPeriodEl.value);
        formData.append('returnRate', returnRateEl.value);
        formData.append('stepUpRate', stepUpRateEl.value);

        fetch('goal-reverser.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data) {
                animateSipAmount(data.initialSip);
                renderStaircase(data.staircaseData);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            requiredSipEl.textContent = 'Error';
        });
    }

    // --- Animate the resulting SIP amount ---
    function animateSipAmount(finalAmount) {
        let currentAmount = parseInt(requiredSipEl.textContent.replace(/[^0-9]/g, '')) || 0;
        const duration = 500;
        const startTime = performance.now();

        function updateAmount(currentTime) {
            const elapsedTime = currentTime - startTime;
            if (elapsedTime < duration) {
                const progress = elapsedTime / duration;
                const animatedValue = Math.floor(currentAmount + (finalAmount - currentAmount) * progress);
                requiredSipEl.textContent = `₹${animatedValue.toLocaleString('en-IN')}`;
                requestAnimationFrame(updateAmount);
            } else {
                requiredSipEl.textContent = `₹${finalAmount.toLocaleString('en-IN')}`;
            }
        }
        requestAnimationFrame(updateAmount);
    }

    // --- Render the staircase visualization ---
    function renderStaircase(staircaseData) {
        staircaseContainer.innerHTML = '';
        if (!staircaseData || staircaseData.length === 0) return;

        const maxAmount = Math.max(...staircaseData.map(d => d.amount));
        const maxStairHeight = 220; // in pixels, container is 250px high

        staircaseData.forEach(data => {
            const step = document.createElement('div');
            step.className = 'stair-step';
            
            const height = maxAmount > 0 ? (data.amount / maxAmount) * maxStairHeight : 0;
            step.style.height = `${Math.max(height, 5)}px`; // min height of 5px

            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = `Year ${data.year}: ₹${data.amount.toLocaleString('en-IN')}`;
            
            step.appendChild(tooltip);
            staircaseContainer.appendChild(step);
        });
    }


    // --- Event Listeners ---
    [targetAmountEl, investmentPeriodEl, returnRateEl, stepUpRateEl].forEach(el => {
        el.addEventListener('input', () => {
            debounce(recalculateAndRender, 400);
        });
    });

    // --- Initial Calculation on Page Load ---
    recalculateAndRender();
});