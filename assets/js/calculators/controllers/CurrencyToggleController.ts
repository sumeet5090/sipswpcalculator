import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';

export interface CurrencyConfig {
    code: string;
    locale: string;
    symbol: string;
    label: string;
}

export const CURRENCY_CONFIGS: Record<string, CurrencyConfig> = {
    INR: { code: 'INR', locale: 'en-IN', symbol: '₹', label: '🇮🇳 INR (₹)' },
    USD: { code: 'USD', locale: 'en-US', symbol: '$', label: '🇺🇸 USD ($)' },
    AED: { code: 'AED', locale: 'en-AE', symbol: 'AED ', label: '🇦🇪 AED (د.إ)' },
    GBP: { code: 'GBP', locale: 'en-GB', symbol: '£', label: '🇬🇧 GBP (£)' }
};

export class CurrencyToggleController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private onCurrencyChange: () => void;
    private activeCode: string = 'INR';

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter, onCurrencyChange: () => void) {
        this.dom = dom;
        this.formatter = formatter;
        this.onCurrencyChange = onCurrencyChange;
    }

    init(): void {
        const saved = localStorage.getItem('sip_currency');
        if (saved && CURRENCY_CONFIGS[saved]) {
            this.activeCode = saved;
        }

        const select = this.dom.getElement<HTMLSelectElement>('currency-select-dropdown');
        if (select) {
            select.value = this.activeCode;
            select.addEventListener('change', () => {
                this.setCurrency(select.value);
            });
        }

        this.applyConfig();
    }

    setCurrency(code: string): void {
        if (!CURRENCY_CONFIGS[code]) return;
        this.activeCode = code;
        localStorage.setItem('sip_currency', code);
        this.applyConfig();
        this.onCurrencyChange();
    }

    private applyConfig(): void {
        const cfg = CURRENCY_CONFIGS[this.activeCode] || CURRENCY_CONFIGS.INR;
        this.formatter.setCurrency(cfg.locale, cfg.code, cfg.symbol);

        const select = this.dom.getElement<HTMLSelectElement>('currency-select-dropdown');
        if (select && select.value !== this.activeCode) {
            select.value = this.activeCode;
        }
    }
}
