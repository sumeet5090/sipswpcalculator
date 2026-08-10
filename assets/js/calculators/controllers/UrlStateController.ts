import { DOMAdapter } from '../../adapters/DOMAdapter';

export class UrlStateController {
    private dom: DOMAdapter;
    private syncSwpToggleState: () => void;

    constructor(dom: DOMAdapter, syncSwpToggleState: () => void) {
        this.dom = dom;
        this.syncSwpToggleState = syncSwpToggleState;
    }

    init(): void {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('sip') || urlParams.has('lumpsum')) {
            const paramMap: Record<string, string> = {
                'sip': 'sip',
                'years': 'years',
                'rate': 'rate',
                'stepup': 'stepup',
                'lumpsum': 'lumpsum',
                'swp': 'swp_withdrawal',
                'swp_years': 'swp_years',
                'swp_stepup': 'swp_stepup',
                'swp_rate': 'swp_rate'
            };
            for (const [key, id] of Object.entries(paramMap)) {
                if (urlParams.has(key)) {
                    const val = urlParams.get(key) || '';
                    this.dom.setValue(id, val);
                    this.dom.setValue(id + '_range', val);
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
}
