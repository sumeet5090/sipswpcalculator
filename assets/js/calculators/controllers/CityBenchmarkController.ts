import { DOMAdapter } from '../../adapters/DOMAdapter';
import { SliderManager } from '../SliderManager';
import { CurrencyFormatter } from '../CurrencyHelper';

export interface CityData {
    city: string;
    expense: number;
    corpus: number;
    sip: number;
}

export class CityBenchmarkController {
    private dom: DOMAdapter;
    private sliderManager: SliderManager;
    private formatter: CurrencyFormatter;
    private onApply: () => void;
    private activeInflation: number = 7.5;
    private activeData: CityData = {
        city: 'mumbai',
        expense: 85000,
        corpus: 25500000,
        sip: 36800
    };

    constructor(
        dom: DOMAdapter,
        sliderManager: SliderManager,
        formatter: CurrencyFormatter,
        onApply: () => void
    ) {
        this.dom = dom;
        this.sliderManager = sliderManager;
        this.formatter = formatter;
        this.onApply = onApply;
    }

    init(): void {
        const card = this.dom.getElement('city-fire-benchmark-card');
        if (!card) return;

        const buttons = card.querySelectorAll<HTMLButtonElement>('.city-choice-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const expense = parseFloat(btn.dataset.expense || '0');
                const corpus = parseFloat(btn.dataset.corpus || '0');
                const sip = parseFloat(btn.dataset.sip || '0');
                const city = btn.dataset.city || 'mumbai';

                this.activeData = { city, expense, corpus, sip };

                buttons.forEach(b => {
                    b.classList.remove('border-emerald-500', 'border-2', 'bg-white', 'shadow-sm');
                    b.classList.add('border-slate-200', 'bg-slate-50/90');
                });
                btn.classList.add('border-emerald-500', 'border-2', 'bg-white', 'shadow-sm');
                btn.classList.remove('border-slate-200', 'bg-slate-50/90');

                // Auto-sync inflation preference based on metro vs tier-2
                if (city === 'tier2' || city === 'pune') {
                    this.setInflation(6);
                } else {
                    this.setInflation(7.5);
                }

                this.updatePreviews();
            });
        });

        const inflationBtns = card.querySelectorAll<HTMLButtonElement>('.city-inflation-btn');
        inflationBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const inf = parseFloat(btn.dataset.inflation || '6');
                this.setInflation(inf);
            });
        });

        const applyBtn = this.dom.getElement('apply-city-benchmark-btn');
        if (applyBtn) {
            applyBtn.addEventListener('click', () => {
                this.applyStrategy();
            });
        }

        this.updatePreviews();
    }

    private setInflation(inf: number): void {
        this.activeInflation = inf;
        const standardBtn = this.dom.getElement('city-inflation-standard-btn');
        const metroBtn = this.dom.getElement('city-inflation-metro-btn');

        if (inf === 6) {
            standardBtn?.classList.add('bg-white', 'text-emerald-800', 'shadow-2xs');
            standardBtn?.classList.remove('text-slate-600');
            metroBtn?.classList.remove('bg-white', 'text-emerald-800', 'shadow-2xs');
            metroBtn?.classList.add('text-slate-600');
        } else {
            metroBtn?.classList.add('bg-white', 'text-emerald-800', 'shadow-2xs');
            metroBtn?.classList.remove('text-slate-600');
            standardBtn?.classList.remove('bg-white', 'text-emerald-800', 'shadow-2xs');
            standardBtn?.classList.add('text-slate-600');
        }
    }

    private updatePreviews(): void {
        const expEl = this.dom.getElement('city-preview-expense');
        const corpEl = this.dom.getElement('city-preview-corpus');
        const sipEl = this.dom.getElement('city-preview-sip');

        if (expEl) expEl.textContent = `${this.formatter.format(this.activeData.expense)} / mo`;
        if (corpEl) corpEl.textContent = this.formatter.format(this.activeData.corpus);
        if (sipEl) sipEl.textContent = `${this.formatter.format(this.activeData.sip)} / mo`;
    }

    private applyStrategy(): void {
        this.sliderManager.updateFieldValue('sip', this.activeData.sip);
        this.sliderManager.updateFieldValue('years', 15);
        this.sliderManager.updateFieldValue('rate', 12);
        this.sliderManager.updateFieldValue('stepup', 10);
        this.sliderManager.updateFieldValue('inflation', this.activeInflation);

        const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
        if (swpToggle) {
            swpToggle.checked = true;
            swpToggle.dispatchEvent(new Event('change', { bubbles: true }));
        }

        this.sliderManager.updateFieldValue('swp_years', 25);
        this.sliderManager.updateFieldValue('swp_rate', 9);
        this.sliderManager.updateFieldValue('swp_withdrawal', this.activeData.expense);

        this.onApply();

        const calcSection = this.dom.getElement('calculator-section') || this.dom.getElement('calculator-app');
        if (calcSection) {
            window.scrollTo({ top: calcSection.offsetTop, behavior: 'smooth' });
        }
    }
}
