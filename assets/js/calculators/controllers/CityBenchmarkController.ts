import { DOMAdapter } from '../../adapters/DOMAdapter';
import { SliderManager } from '../SliderManager';
import { CurrencyFormatter } from '../CurrencyHelper';
import { YearResult, InvestmentInputs } from '../../types';

export interface CityBenchmarkData {
    city: string;
    baseExpense: number;
    inflation: number;
}

/**
 * CityBenchmarkController
 * Manages dynamic Indian City FIRE Benchmarks, SWR Multipliers,
 * Housing Tenure Adjustments, and live portfolio readiness telemetry.
 */
export class CityBenchmarkController {
    private dom: DOMAdapter;
    private sliderManager: SliderManager;
    private formatter: CurrencyFormatter;
    private onApply: () => void;
    private onCityChange?: (city: string) => void;

    private activeCity: string = 'mumbai';
    private baseExpense: number = 85000;
    private activeInflation: number = 7.5;
    private swrMultiplier: number = 30; // 25x, 30x, 35x
    private isHomeOwner: boolean = false; // -35% housing deduction if true

    private latestResults: YearResult[] = [];
    private currentInputs: InvestmentInputs | null = null;

    private readonly cities: Record<string, CityBenchmarkData> = {
        mumbai: { city: 'mumbai', baseExpense: 85000, inflation: 7.5 },
        bengaluru: { city: 'bengaluru', baseExpense: 75000, inflation: 7.5 },
        delhi: { city: 'delhi', baseExpense: 70000, inflation: 7.0 },
        pune: { city: 'pune', baseExpense: 60000, inflation: 6.5 },
        tier2: { city: 'tier2', baseExpense: 45000, inflation: 6.0 }
    };

    constructor(
        dom: DOMAdapter,
        sliderManager: SliderManager,
        formatter: CurrencyFormatter,
        onApply: () => void,
        onCityChange?: (city: string) => void
    ) {
        this.dom = dom;
        this.sliderManager = sliderManager;
        this.formatter = formatter;
        this.onApply = onApply;
        this.onCityChange = onCityChange;
    }

    public init(): void {
        const card = this.dom.getElement('city-fire-benchmark-card');
        if (!card) return;

        // 1. City Choice Buttons
        const cityButtons = card.querySelectorAll<HTMLButtonElement>('.city-choice-btn');
        cityButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const cityKey = btn.dataset.city || 'mumbai';
                const cityConfig = this.cities[cityKey];
                if (!cityConfig) return;

                this.activeCity = cityKey;
                this.baseExpense = cityConfig.baseExpense;
                this.activeInflation = cityConfig.inflation;

                cityButtons.forEach(b => {
                    b.classList.remove('border-emerald-500', 'border-2', 'bg-white', 'shadow-xs');
                    b.classList.add('border-slate-200/90', 'bg-slate-50/90');
                    const dot = b.querySelector('.w-2.h-2');
                    if (dot) dot.className = 'w-2 h-2 rounded-full bg-slate-300 group-hover:bg-slate-400';
                });

                btn.classList.add('border-emerald-500', 'border-2', 'bg-white', 'shadow-xs');
                btn.classList.remove('border-slate-200/90', 'bg-slate-50/90');
                const activeDot = btn.querySelector('.w-2.h-2');
                if (activeDot) activeDot.className = 'w-2 h-2 rounded-full bg-emerald-500';

                this.onCityChange?.(this.activeCity);
                this.updateCalculations();
            });
        });

        // 2. Housing Tenure Controls
        const rentedBtn = this.dom.getElement('housing-rented-btn');
        const ownedBtn = this.dom.getElement('housing-owned-btn');
        if (rentedBtn && ownedBtn) {
            rentedBtn.addEventListener('click', () => {
                this.isHomeOwner = false;
                rentedBtn.classList.add('bg-white', 'text-emerald-900', 'shadow-2xs', 'border-slate-200/60', 'font-bold');
                rentedBtn.classList.remove('text-slate-500', 'font-medium');
                ownedBtn.classList.remove('bg-white', 'text-emerald-900', 'shadow-2xs', 'border-slate-200/60', 'font-bold');
                ownedBtn.classList.add('text-slate-500', 'font-medium');
                this.updateCalculations();
            });

            ownedBtn.addEventListener('click', () => {
                this.isHomeOwner = true;
                ownedBtn.classList.add('bg-white', 'text-emerald-900', 'shadow-2xs', 'border-slate-200/60', 'font-bold');
                ownedBtn.classList.remove('text-slate-500', 'font-medium');
                rentedBtn.classList.remove('bg-white', 'text-emerald-900', 'shadow-2xs', 'border-slate-200/60', 'font-bold');
                rentedBtn.classList.add('text-slate-500', 'font-medium');
                this.updateCalculations();
            });
        }

        // 3. SWR Multiplier Selector
        const swrBtns = card.querySelectorAll<HTMLButtonElement>('.swr-multiplier-btn');
        swrBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                this.swrMultiplier = parseInt(btn.dataset.multiplier || '30', 10);
                swrBtns.forEach(b => {
                    b.classList.remove('bg-white', 'text-emerald-900', 'shadow-2xs', 'border-slate-200/60', 'font-black');
                    b.classList.add('text-slate-500');
                });
                btn.classList.add('bg-white', 'text-emerald-900', 'shadow-2xs', 'border-slate-200/60', 'font-black');
                btn.classList.remove('text-slate-500');
                this.updateCalculations();
            });
        });

        // 4. Adopt Benchmark Button
        const applyBtn = this.dom.getElement('apply-city-benchmark-btn');
        if (applyBtn) {
            applyBtn.addEventListener('click', () => {
                this.adoptCityStrategy();
            });
        }

        this.updateCalculations();
    }

    /**
     * Ingests latest calculation results from the engine
     * and re-computes readiness metrics in real-time.
     */
    public updateResults(results: YearResult[], inputs?: InvestmentInputs): void {
        this.latestResults = results;
        if (inputs) this.currentInputs = inputs;
        this.updateCalculations();
    }

    public getActiveCity(): string {
        return this.activeCity;
    }

    public getActiveMultiplier(): number {
        return this.swrMultiplier;
    }

    public getCurrentInputs(): InvestmentInputs | null {
        return this.currentInputs;
    }

    public isHomeOwnerMode(): boolean {
        return this.isHomeOwner;
    }

    public getEffectiveMonthlyExpense(): number {
        return this.isHomeOwner ? Math.round(this.baseExpense * 0.65) : this.baseExpense;
    }

    public getTargetCorpus(): number {
        const annualExpense = this.getEffectiveMonthlyExpense() * 12;
        return annualExpense * this.swrMultiplier;
    }

    private updateCalculations(): void {
        const effectiveExpense = this.getEffectiveMonthlyExpense();
        const targetCorpus = this.getTargetCorpus();

        // 1. Update UI Labels
        const expEl = this.dom.getElement('city-preview-expense');
        const corpEl = this.dom.getElement('city-preview-corpus');
        const sipEl = this.dom.getElement('city-preview-sip');
        const multTag = this.dom.getElement('fire-multiplier-tag');

        if (expEl) expEl.textContent = `${this.formatter.format(effectiveExpense)} / mo`;
        if (corpEl) corpEl.textContent = this.formatter.formatDynamic(targetCorpus);
        if (multTag) multTag.textContent = `${this.swrMultiplier}×`;

        // Update city button expense labels to reflect current housing status
        const card = this.dom.getElement('city-fire-benchmark-card');
        if (card) {
            const cityButtons = card.querySelectorAll<HTMLButtonElement>('.city-choice-btn');
            cityButtons.forEach(btn => {
                const cityKey = btn.dataset.city || '';
                const config = this.cities[cityKey];
                const label = btn.querySelector('.city-expense-label');
                if (config && label) {
                    const adjExpense = this.isHomeOwner ? Math.round(config.baseExpense * 0.65) : config.baseExpense;
                    label.textContent = `₹${Math.round(adjExpense / 1000)}k/mo`;
                }
            });
        }

        // Estimate required SIP for target corpus over 15 years @ 12% with 10% step-up
        const estimatedSip = Math.round((targetCorpus / 69.2) / 100) * 100;
        if (sipEl) sipEl.textContent = `${this.formatter.format(estimatedSip)} / mo`;

        // 2. Personal Readiness Evaluation vs. User's Live Results
        const lastRow = this.latestResults[this.latestResults.length - 1];
        const userCorpus = lastRow ? lastRow.combined_total : 0;
        const readinessPct = targetCorpus > 0 ? Math.min(200, Math.round((userCorpus / targetCorpus) * 100)) : 0;

        const pctEl = this.dom.getElement('fire-readiness-percent');
        const barEl = this.dom.getElement('fire-progress-bar');
        const barLabel = this.dom.getElement('fire-bar-pct-label');
        const statusBadge = this.dom.getElement('fire-status-badge');
        const horizonEl = this.dom.getElement('fire-horizon-date');

        if (pctEl) pctEl.textContent = `${readinessPct}%`;
        if (barLabel) barLabel.textContent = `${Math.min(100, readinessPct)}%`;
        if (barEl) barEl.style.width = `${Math.min(100, readinessPct)}%`;

        if (statusBadge) {
            const delta = userCorpus - targetCorpus;
            if (delta >= 0) {
                statusBadge.className = 'inline-flex items-center text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200/80';
                statusBadge.textContent = `Surplus: ${this.formatter.formatDynamic(delta)} (${readinessPct}% Funded)`;
            } else {
                statusBadge.className = 'inline-flex items-center text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200/80';
                statusBadge.textContent = `Deficit: ${this.formatter.formatDynamic(Math.abs(delta))}`;
            }
        }

        // Find Year of Freedom in calculation ledger
        if (horizonEl) {
            const freedomYearRow = this.latestResults.find(r => r.combined_total >= targetCorpus);
            if (freedomYearRow) {
                horizonEl.textContent = `🎯 Freedom Achieved at Year ${freedomYearRow.year}`;
                horizonEl.className = 'text-[10px] text-emerald-700 font-bold mt-0.5';
            } else {
                const totalYears = this.latestResults.length || 15;
                horizonEl.textContent = `Target Not Reached within ${totalYears} Years`;
                horizonEl.className = 'text-[10px] text-slate-500 font-bold mt-0.5';
            }
        }

        // 3. Geo-Arbitrage Delta
        const geoText = this.dom.getElement('geo-arbitrage-text');
        if (geoText) {
            const tier2Corpus = (this.isHomeOwner ? 45000 * 0.65 : 45000) * 12 * this.swrMultiplier;
            const savings = targetCorpus - tier2Corpus;
            if (this.activeCity !== 'tier2' && savings > 0) {
                geoText.innerHTML = `Moving retirement from ${this.activeCity.toUpperCase()} to a Tier-2 city reduces required corpus by <strong>${this.formatter.formatDynamic(savings)}</strong>, pulling your Freedom Date forward.`;
            } else {
                geoText.innerHTML = `Tier-2 cities offer India's most accessible retirement cost threshold with high quality of life.`;
            }
        }
    }

    private adoptCityStrategy(): void {
        const effectiveExpense = this.getEffectiveMonthlyExpense();
        const targetCorpus = this.getTargetCorpus();
        const estimatedSip = Math.round((targetCorpus / 69.2) / 100) * 100;

        this.sliderManager.updateFieldValue('sip', estimatedSip);
        this.sliderManager.updateFieldValue('inflation', this.activeInflation);

        const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
        if (swpToggle && !swpToggle.checked) {
            swpToggle.checked = true;
            swpToggle.dispatchEvent(new Event('change', { bubbles: true }));
        }

        this.sliderManager.updateFieldValue('swp_withdrawal', effectiveExpense);
        this.onApply();

        const calcSection = this.dom.getElement('calculator-section') || this.dom.getElement('calculator-app');
        if (calcSection) {
            window.scrollTo({ top: calcSection.offsetTop, behavior: 'smooth' });
        }
    }
}

