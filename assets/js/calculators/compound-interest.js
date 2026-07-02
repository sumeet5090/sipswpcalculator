/**
 * compound-interest.js
 * Isolated scripting logic for the Compound Interest Calculator view.
 */
(function() {
    'use strict';

    function calculateCI() {
        const principalInput = document.getElementById('ci-principal');
        const rateInput = document.getElementById('ci-rate');
        const yearsInput = document.getElementById('ci-years');
        const frequencySelect = document.getElementById('ci-frequency');

        if (!principalInput || !rateInput || !yearsInput || !frequencySelect) {
            return;
        }

        const P = parseFloat(principalInput.value) || 0;
        const r = (parseFloat(rateInput.value) || 0) / 100;
        const t = parseInt(yearsInput.value) || 0;
        const n = parseInt(frequencySelect.value) || 12;

        const A = P * Math.pow(1 + r / n, n * t);
        const interest = A - P;
        const effectiveRate = (Math.pow(1 + r / n, n) - 1) * 100;
        const rule72Years = r > 0 ? (72 / (r * 100)).toFixed(1) : '∞';

        const fmt = (num) => {
            return new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                maximumFractionDigits: 0
            }).format(num);
        };

        const finalEl = document.getElementById('ci-final');
        const interestEl = document.getElementById('ci-interest');
        const effectiveEl = document.getElementById('ci-effective');
        const ruleRateEl = document.getElementById('ci-rule72-rate');
        const ruleYearsEl = document.getElementById('ci-rule72-years');

        if (finalEl) finalEl.textContent = fmt(A);
        if (interestEl) interestEl.textContent = fmt(interest);
        if (effectiveEl) effectiveEl.textContent = effectiveRate.toFixed(2) + '%';
        if (ruleRateEl) ruleRateEl.textContent = (r * 100).toFixed(1);
        if (ruleYearsEl) ruleYearsEl.textContent = rule72Years;
    }

    // Attach listeners
    document.querySelectorAll('#ci-principal, #ci-rate, #ci-years, #ci-frequency').forEach(el => {
        el.addEventListener('input', calculateCI);
        el.addEventListener('change', calculateCI);
    });

    // Run initial calculation when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', calculateCI);
    } else {
        calculateCI();
    }
})();
