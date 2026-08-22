import { MathEngine } from '../assets/js/calculators/MathEngine.ts';
import { CurrencyFormatter } from '../assets/js/calculators/CurrencyHelper.ts';

try {
    // Read input parameters JSON from command line arguments
    const inputArgs = JSON.parse(process.argv[2]);

    if (inputArgs.action === 'format_currency_test') {
        const formatter = new CurrencyFormatter();
        let allMatched = true;
        for (const [val, expected] of Object.entries(inputArgs.values)) {
            const formatted = formatter.formatDynamic(Number(val));
            if (formatted !== expected) {
                allMatched = false;
                break;
            }
        }
        console.log(JSON.stringify({ success: allMatched }));
    } else if (inputArgs.action === 'calculate_sip_schedule') {
        const results = MathEngine.calculate({
            sip: inputArgs.sip,
            years: inputArgs.years,
            rate: inputArgs.rate,
            stepup: inputArgs.stepup || 0,
            lumpsum: inputArgs.lumpsum || 0,
            enable_swp: false,
            swp_withdrawal: 0,
            swp_years: 0,
            swp_rate: 0,
            swp_stepup: 0
        });
        console.log(JSON.stringify({
            success: true,
            schedule: results.map(r => ({
                year: r.year,
                combined_invested: r.cumulative_invested,
                combined_total: r.combined_total,
                combined_interest: r.combined_total - r.cumulative_invested,
                interest: r.interest
            }))
        }));
    } else if (inputArgs.action === 'calculate_summary_metrics') {
        const results = MathEngine.calculate({
            sip: inputArgs.sip,
            years: inputArgs.years,
            rate: inputArgs.rate,
            stepup: inputArgs.stepup || 0,
            lumpsum: inputArgs.lumpsum || 0,
            enable_swp: false,
            swp_withdrawal: 0,
            swp_years: 0,
            swp_rate: 0,
            swp_stepup: 0
        });
        const lastRow = results[results.length - 1];
        const invested = lastRow.cumulative_invested;
        const total = lastRow.combined_total;
        const gains = total - invested;
        console.log(JSON.stringify({
            success: true,
            total,
            invested,
            gains
        }));
    } else if (inputArgs.action === 'required_sip') {
        const res = MathEngine.calculateRequiredSip(inputArgs.inputs, inputArgs.target_corpus);
        console.log(JSON.stringify({ result: res }));
    } else if (inputArgs.action === 'swp_required_corpus') {
        const res = MathEngine.calculateRequiredStartingCorpusForSwp(inputArgs.inputs);
        console.log(JSON.stringify({ result: res }));
    } else if (inputArgs.action === 'inflation_discount') {
        const res = MathEngine.calculateInflationDiscount(inputArgs.corpus, inputArgs.years, inputArgs.inflation);
        console.log(JSON.stringify({ result: res }));
    } else if (inputArgs.action === 'delay_cost') {
        const res = MathEngine.calculateDelayCost(inputArgs.inputs);
        console.log(JSON.stringify({ result: res }));
    } else {
        const results = MathEngine.calculate(inputArgs);
        console.log(JSON.stringify(results));
    }
} catch (err) {
    console.error("Node.js JS Calculator Runner Error:", err);
    process.exit(1);
}
