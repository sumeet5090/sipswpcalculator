import { MathEngine } from '../assets/js/calculators/MathEngine.ts';
import { CurrencyFormatter } from '../assets/js/calculators/CurrencyHelper.ts';
import { QrCodeGenerator } from '../assets/js/utils/QrCodeGenerator.ts';
import { IndianNumberParser } from '../assets/js/calculators/helpers/IndianNumberParser.ts';
import { SliderCurveHelper } from '../assets/js/calculators/helpers/SliderCurveHelper.ts';
import { NaturalLanguageQueryParser } from '../assets/js/calculators/helpers/NaturalLanguageQueryParser.ts';
import { CompoundInterestEngine } from '../assets/js/calculators/engines/CompoundInterestEngine.ts';
import { CagrEngine } from '../assets/js/calculators/engines/CagrEngine.ts';
import { EmiEngine } from '../assets/js/calculators/engines/EmiEngine.ts';
import { InflationEngine } from '../assets/js/calculators/engines/InflationEngine.ts';
import { PpfEngine } from '../assets/js/calculators/engines/PpfEngine.ts';
import { FdEngine } from '../assets/js/calculators/engines/FdEngine.ts';
import { GratuityEngine } from '../assets/js/calculators/engines/GratuityEngine.ts';
import { NpsEngine } from '../assets/js/calculators/engines/NpsEngine.ts';
import { PostRetirementEngine } from '../assets/js/calculators/engines/PostRetirementEngine.ts';
import { EpfEngine } from '../assets/js/calculators/engines/EpfEngine.ts';
import { TaxHarvestEngine } from '../assets/js/calculators/engines/TaxHarvestEngine.ts';

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
    } else if (inputArgs.action === 'safe_swp_withdrawal') {
        const res = MathEngine.calculateSafeSwpWithdrawal(inputArgs.inputs, inputArgs.starting_corpus);
        console.log(JSON.stringify({ result: res }));
    } else if (inputArgs.action === 'compound_interest') {
        const res = CompoundInterestEngine.calculate(
            inputArgs.principal,
            inputArgs.annual_rate,
            inputArgs.years,
            inputArgs.compounding_frequency
        );
        console.log(JSON.stringify(res));
    } else if (inputArgs.action === 'cagr') {
        const res = CagrEngine.calculate(
            inputArgs.beginning_value,
            inputArgs.ending_value,
            inputArgs.years
        );
        console.log(JSON.stringify(res));
    } else if (inputArgs.action === 'emi') {
        const res = EmiEngine.calculate(
            inputArgs.principal,
            inputArgs.annual_rate,
            inputArgs.tenure_years
        );
        console.log(JSON.stringify(res));
    } else if (inputArgs.action === 'inflation') {
        const res = InflationEngine.calculate(
            inputArgs.present_value,
            inputArgs.inflation_rate,
            inputArgs.years
        );
        console.log(JSON.stringify(res));
    } else if (inputArgs.action === 'ppf') {
        const res = PpfEngine.calculate(
            inputArgs.yearly_deposit,
            inputArgs.interest_rate,
            inputArgs.tenure_years,
            inputArgs.deposit_timing || 'beginning'
        );
        console.log(JSON.stringify(res));
    } else if (inputArgs.action === 'fd') {
        const res = FdEngine.calculate(
            inputArgs.principal,
            inputArgs.annual_rate,
            inputArgs.duration_years,
            inputArgs.is_senior_citizen || false,
            inputArgs.payout_frequency || 'cumulative'
        );
        console.log(JSON.stringify(res));
    } else if (inputArgs.action === 'gratuity') {
        const res = GratuityEngine.calculate(
            inputArgs.monthly_salary,
            inputArgs.tenure_years,
            inputArgs.tenure_months || 0,
            inputArgs.is_covered_by_act !== undefined ? inputArgs.is_covered_by_act : true
        );
        console.log(JSON.stringify(res));
    } else if (inputArgs.action === 'nps') {
        const res = NpsEngine.calculate(
            inputArgs.monthly_contribution,
            inputArgs.current_age,
            inputArgs.retirement_age,
            inputArgs.expected_return_rate,
            inputArgs.annuity_percent !== undefined ? inputArgs.annuity_percent : 40.0,
            inputArgs.annuity_return_rate !== undefined ? inputArgs.annuity_return_rate : 6.0
        );
        console.log(JSON.stringify(res));
    } else if (inputArgs.action === 'post_retirement') {
        const res = PostRetirementEngine.calculate(
            inputArgs.existing_corpus,
            inputArgs.gratuity_lump_sum,
            inputArgs.nps_lump_sum,
            inputArgs.guaranteed_pension,
            inputArgs.monthly_expense,
            inputArgs.expected_return,
            inputArgs.inflation_rate !== undefined ? inputArgs.inflation_rate : 6.0,
            inputArgs.current_age !== undefined ? inputArgs.current_age : 60,
            inputArgs.planning_horizon_years !== undefined ? inputArgs.planning_horizon_years : 30
        );
        console.log(JSON.stringify(res));
    } else if (inputArgs.action === 'epf') {
        const res = EpfEngine.calculate(
            inputArgs.basic_salary,
            inputArgs.current_age,
            inputArgs.retirement_age,
            inputArgs.vpf_percent !== undefined ? inputArgs.vpf_percent : 0.0,
            inputArgs.salary_hike !== undefined ? inputArgs.salary_hike : 7.0,
            inputArgs.interest_rate !== undefined ? inputArgs.interest_rate : 8.25,
            inputArgs.current_balance !== undefined ? inputArgs.current_balance : 0.0,
            inputArgs.cap_eps !== undefined ? inputArgs.cap_eps : true
        );
        console.log(JSON.stringify(res));
    } else if (inputArgs.action === 'tax_harvest') {
        const res = TaxHarvestEngine.calculate(
            inputArgs.portfolio_value,
            inputArgs.invested_cost,
            inputArgs.expected_return !== undefined ? inputArgs.expected_return : 12.0,
            inputArgs.holding_years !== undefined ? inputArgs.holding_years : 10,
            inputArgs.grandfather_fmv !== undefined ? inputArgs.grandfather_fmv : 0.0,
            inputArgs.include_cess !== undefined ? inputArgs.include_cess : true
        );
        console.log(JSON.stringify(res));
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
