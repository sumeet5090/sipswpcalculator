import { MathEngine } from '../assets/js/calculators/MathEngine.ts';
import { CurrencyFormatter } from '../assets/js/calculators/CurrencyHelper.ts';
import { QrCodeGenerator } from '../assets/js/utils/QrCodeGenerator.ts';
import { IndianNumberParser } from '../assets/js/calculators/helpers/IndianNumberParser.ts';
import { SliderCurveHelper } from '../assets/js/calculators/helpers/SliderCurveHelper.ts';
import { NaturalLanguageQueryParser } from '../assets/js/calculators/helpers/NaturalLanguageQueryParser.ts';

try {
    // Read input parameters JSON from command line arguments
    const inputArgs = JSON.parse(process.argv[2]);

    if (inputArgs.action === 'calculate_delay_cost') {
        const delayCost = MathEngine.calculateDelayCost(inputArgs.inputs);
        const formatter = new CurrencyFormatter('INR', 'en-IN');
        const formatted = formatter.format(delayCost);
        console.log(JSON.stringify({ success: true, delayCost, formatted }));
    } else if (inputArgs.action === 'format_stepup_subtext') {
        const formatter = new CurrencyFormatter('INR', 'en-IN');
        const subtext = formatter.formatSubtext('stepup', inputArgs.stepup, { sip: inputArgs.sip });
        console.log(JSON.stringify({ success: true, subtext }));
    } else if (inputArgs.action === 'format_rate_subtext') {
        const formatter = new CurrencyFormatter('INR', 'en-IN');
        const subtext = formatter.formatSubtext('rate', inputArgs.rate);
        console.log(JSON.stringify({ success: true, subtext }));
    } else if (inputArgs.action === 'calculate_tax_harvesting') {
        const results = MathEngine.calculate(inputArgs.inputs);
        const harvest = MathEngine.calculateTaxHarvestingSavings(inputArgs.inputs, results);
        console.log(JSON.stringify({ success: true, harvest }));
    } else if (inputArgs.action === 'parse_nlp_query') {
        const result = NaturalLanguageQueryParser.parse(inputArgs.query);
        console.log(JSON.stringify({ success: true, result }));
    } else if (inputArgs.action === 'slider_curve_test') {
        const { min, max, step, curve } = inputArgs;
        const posToVal0 = SliderCurveHelper.positionToValue(0, min, max, step, curve);
        const posToVal50 = SliderCurveHelper.positionToValue(50, min, max, step, curve);
        const posToVal100 = SliderCurveHelper.positionToValue(100, min, max, step, curve);
        const valToPosMin = SliderCurveHelper.valueToPosition(min, min, max, curve);
        const valToPosMax = SliderCurveHelper.valueToPosition(max, min, max, curve);
        
        let monotonic = true;
        let lastVal = -Infinity;
        for (let p = 0; p <= 100; p += 5) {
            const v = SliderCurveHelper.positionToValue(p, min, max, step, curve);
            if (v < lastVal) {
                monotonic = false;
                break;
            }
            lastVal = v;
        }

        console.log(JSON.stringify({
            success: true,
            posToVal0,
            posToVal50,
            posToVal100,
            valToPosMin,
            valToPosMax,
            monotonic
        }));
    } else if (inputArgs.action === 'parse_indian_number') {
        const results = {};
        for (const [key, val] of Object.entries(inputArgs.inputs)) {
            results[key] = IndianNumberParser.parse(val);
        }
        console.log(JSON.stringify({ success: true, results }));
    } else if (inputArgs.action === 'format_currency_test') {
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
    } else if (inputArgs.action === 'generate_qr_test') {
        const matrix = QrCodeGenerator.generateMatrix(inputArgs.text);
        console.log(JSON.stringify({
            success: true,
            size: matrix.length,
            isSquare: matrix.every(row => row.length === matrix.length),
            hasTopLeftFinder: matrix[0][0] && matrix[0][6] && matrix[6][0] && matrix[6][6] && !matrix[1][1]
        }));
    } else {
        const results = MathEngine.calculate(inputArgs);
        console.log(JSON.stringify(results));
    }
} catch (err) {
    console.error("Node.js JS Calculator Runner Error:", err);
    process.exit(1);
}
