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

    constructor() {
        this.dom = new DOMAdapter();
        this.formatter = new CurrencyFormatter();
        this.validator = new InputValidator();
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

        const P = this.validator.validate('lumpsum', rawP);
        const r = (this.validator.validate('rate', rawR)) / 100;
        const t = this.validator.validate('years', rawT);
        const n = rawN > 0 ? rawN : 12;

        const A = P * Math.pow(1 + r / n, n * t);
        const interest = A - P;
        const effectiveRate = (Math.pow(1 + r / n, n) - 1) * 100;
        const rule72Years = r > 0 ? (72 / (r * 100)).toFixed(1) : '∞';

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

// Auto-instantiate on DOM load
if (typeof document !== 'undefined') {
    const initCI = () => {
        const calc = new CompoundInterestCalculator();
        calc.init();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCI);
    } else {
        initCI();
    }
}
