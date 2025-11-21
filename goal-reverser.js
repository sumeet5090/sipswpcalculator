document.addEventListener('DOMContentLoaded', function () {
    const calculateBtn = document.getElementById('calculateBtn');
    const resultDiv = document.getElementById('result');
    const stepUpRateSpan = document.getElementById('stepUpRate');
    const resultMessageSpan = document.getElementById('result-message');

    calculateBtn.addEventListener('click', function () {
        const targetAmount = parseFloat(document.getElementById('targetAmount').value);
        const initialInvestment = parseFloat(document.getElementById('initialInvestment').value);
        const investmentPeriod = parseFloat(document.getElementById('investmentPeriod').value);
        const returnRate = parseFloat(document.getElementById('returnRate').value);

        if (isNaN(targetAmount) || isNaN(initialInvestment) || isNaN(investmentPeriod) || isNaN(returnRate)) {
            alert('Please fill in all fields with valid numbers.');
            return;
        }

        if (targetAmount <= 0 || initialInvestment <= 0 || investmentPeriod <= 0 || returnRate <= 0) {
            alert('All values must be positive.');
            return;
        }

        resultDiv.style.display = 'block';
        stepUpRateSpan.textContent = 'Calculating...';
        resultMessageSpan.textContent = '';


        const formData = new FormData();
        formData.append('action', 'calculate_step_up');
        formData.append('targetAmount', targetAmount);
        formData.append('initialInvestment', initialInvestment);
        formData.append('investmentPeriod', investmentPeriod);
        formData.append('returnRate', returnRate);

        fetch('goal-reverser.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                stepUpRateSpan.textContent = 'Error';
                resultMessageSpan.textContent = data.error;
            } else {
                stepUpRateSpan.textContent = data.stepUpRate.toFixed(2);
                if (data.stepUpRate > 50) {
                     resultMessageSpan.textContent = "The required step-up rate is very high. You may want to consider increasing your initial investment or investment duration.";
                } else {
                    resultMessageSpan.textContent = `You will need to increase your monthly investment by ${data.stepUpRate.toFixed(2)}% each year to reach your goal of ${targetAmount.toLocaleString()}.`;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.style.display = 'block';
            stepUpRateSpan.textContent = 'Error';
            resultMessageSpan.textContent = 'An error occurred during calculation. Please check the console.';
        });
    });
});
