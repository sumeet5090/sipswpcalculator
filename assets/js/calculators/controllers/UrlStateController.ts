import { DOMAdapter } from '../../adapters/DOMAdapter';

export class UrlStateController {
    private dom: DOMAdapter;
    private syncSwpToggleState: () => void;
    private setGoalMode?: (mode: string) => void;

    constructor(dom: DOMAdapter, syncSwpToggleState: () => void, setGoalMode?: (mode: string) => void) {
        this.dom = dom;
        this.syncSwpToggleState = syncSwpToggleState;
        this.setGoalMode = setGoalMode;
    }

    init(): void {
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.toString()) return;

        const appEl = this.dom.getElement('calculator-app');
        const isSwpMode = (appEl?.dataset?.mode === 'swp');

        const paramMap: Record<string, string> = {
            'sip': 'sip',
            'years': 'years',
            'rate': 'rate',
            'stepup': 'stepup',
            'lumpsum': isSwpMode ? 'corpus' : 'lumpsum',
            'corpus': 'corpus',
            'inflation': 'inflation',
            'target_corpus': 'target_corpus',
            'swp': 'swp_withdrawal',
            'swp_withdrawal': 'swp_withdrawal',
            'swp_years': 'swp_years',
            'swp_stepup': 'swp_stepup',
            'swp_rate': 'swp_rate'
        };

        for (const [key, id] of Object.entries(paramMap)) {
            if (urlParams.has(key)) {
                const rawVal = urlParams.get(key) || '';
                const numVal = parseFloat(rawVal);
                if (!isNaN(numVal)) {
                    const inputEl = this.dom.getElement<HTMLInputElement>(id);
                    const rangeEl = this.dom.getElement<HTMLInputElement>(id + '_range');

                    let clampedVal = numVal;
                    if (inputEl) {
                        const min = parseFloat(inputEl.getAttribute('min') || '0');
                        const max = parseFloat(inputEl.getAttribute('max') || String(Number.MAX_SAFE_INTEGER));
                        clampedVal = Math.min(Math.max(numVal, min), max);
                        inputEl.value = String(clampedVal);
                        inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (rangeEl) {
                        rangeEl.value = String(clampedVal);
                        rangeEl.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            }
        }

        if (urlParams.has('cur')) {
            const curVal = urlParams.get('cur') || 'INR';
            const curEl = this.dom.getElement<HTMLSelectElement>('currency');
            if (curEl) {
                curEl.value = curVal;
                curEl.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        if (urlParams.has('goal_mode') && this.setGoalMode) {
            const mode = urlParams.get('goal_mode') || 'grow';
            this.setGoalMode(mode);
        }

        if (urlParams.has('post_tax') && urlParams.get('post_tax') === '1') {
            const postTaxToggle = this.dom.getElement<HTMLInputElement>('show_post_tax');
            if (postTaxToggle) {
                postTaxToggle.checked = true;
                const taxCols = this.dom.getElements<HTMLElement>('tax-col');
                taxCols.forEach(el => {
                    el.style.display = '';
                });
            }
        }

        if (urlParams.has('wealth_map') && urlParams.get('wealth_map') === '1') {
            const wealthMapToggle = this.dom.getElement<HTMLInputElement>('show_wealth_map');
            if (wealthMapToggle) {
                wealthMapToggle.checked = true;
            }
        }

        if (urlParams.has('swp_on') && urlParams.get('swp_on') === '1') {
            const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
            if (swpToggle) {
                swpToggle.checked = true;
                this.syncSwpToggleState();
            }
        }
    }
}
