import { DOMAdapter } from '../adapters/DOMAdapter';
import { CurrencyFormatter } from './CurrencyHelper';
import { InputValidator } from './InputValidator';

/**
 * CompoundInterestCalculator
 * Handles calculation and DOM updates for the Compound Interest Calculator view.
 */
export class CompoundInterestCalculator {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private validator: InputValidator;

    constructor(
        dom: DOMAdapter = new DOMAdapter(),
        formatter: CurrencyFormatter = new CurrencyFormatter(),
        validator: InputValidator = new InputValidator()
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.validator = validator;
    }

    public init(): void {
        const principalInput = this.dom.getElement<HTMLInputElement>('ci-principal');
        const rateInput = this.dom.getElement<HTMLInputElement>('ci-rate');
        const yearsInput = this.dom.getElement<HTMLInputElement>('ci-years');
        const frequencySelect = this.dom.getElement<HTMLSelectElement>('ci-frequency');

        if (!principalInput || !rateInput || !yearsInput || !frequencySelect) {
            return;
        }

        const runCalc = () => this.calculate();

        ['ci-principal', 'ci-rate', 'ci-years', 'ci-frequency'].forEach(id => {
            const el = this.dom.getElement(id);
            if (el) {
                el.addEventListener('input', runCalc);
                el.addEventListener('change', runCalc);
            }
        });

        this.calculate();
    }

    public calculate(): void {
        const rawP = parseFloat(this.dom.getValue('ci-principal') || '0') || 0;
        const rawR = parseFloat(this.dom.getValue('ci-rate') || '0') || 0;
        const rawT = parseInt(this.dom.getValue('ci-years') || '0', 10) || 0;
        const rawN = parseInt(this.dom.getValue('ci-frequency') || '12', 10) || 12;

        const P = Math.max(0, this.validator.validate('lumpsum', rawP));
        const validatedRate = Math.max(0, this.validator.validate('rate', rawR));
        const r = validatedRate / 100;
        const t = Math.max(0, this.validator.validate('years', rawT));
        const n = Math.max(1, rawN);

        let A = P;
        let interest = 0;
        let effectiveRate = 0;
        let rule72Years = '∞';

        if (P > 0 && t > 0 && r > 0) {
            A = P * Math.pow(1 + r / n, n * t);
            interest = Math.max(0, A - P);
            effectiveRate = (Math.pow(1 + r / n, n) - 1) * 100;
            rule72Years = (72 / (r * 100)).toFixed(1);
        }

        const finalEl = this.dom.getElement('ci-final');
        const interestEl = this.dom.getElement('ci-interest');
        const effectiveEl = this.dom.getElement('ci-effective');
        const ruleRateEl = this.dom.getElement('ci-rule72-rate');
        const ruleYearsEl = this.dom.getElement('ci-rule72-years');

        if (finalEl) finalEl.textContent = this.formatter.format(A);
        if (interestEl) interestEl.textContent = this.formatter.format(interest);
        if (effectiveEl) effectiveEl.textContent = effectiveRate.toFixed(2) + '%';
        if (ruleRateEl) ruleRateEl.textContent = (r * 100).toFixed(1);
        if (ruleYearsEl) ruleYearsEl.textContent = rule72Years;
    }
}

// Auto-instantiate on DOM load if target calculator elements are present
if (typeof document !== 'undefined') {
    const initCI = () => {
        if (document.getElementById('ci-principal')) {
            const calc = new CompoundInterestCalculator();
            calc.init();
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCI);
    } else {
        initCI();
    }
}
