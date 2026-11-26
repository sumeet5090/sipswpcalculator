import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { InvestmentInputs, YearResult } from '../../types';
import { OdometerController } from './OdometerController';
import { PurchasingPowerController } from './PurchasingPowerController';
import { ContextualInsightsController } from './ContextualInsightsController';

export class SummaryMetricsController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputs: () => InvestmentInputs;
    private odometer: OdometerController;
    private purchasingPower: PurchasingPowerController;
    private insightsController: ContextualInsightsController;

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        getInputs: () => InvestmentInputs
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.getInputs = getInputs;
        this.odometer = new OdometerController(dom, formatter);
        this.purchasingPower = new PurchasingPowerController(dom, formatter);
        this.insightsController = new ContextualInsightsController(dom, formatter);
        this.initDrawer();
        this.initCardClickHandlers();
    }

    /**
     * Navigate to and highlight a specific milestone year in the Cashflow Ledger.
     */
    public navigateToMilestoneYear(targetYear: number): void {
        const tabBtn = document.getElementById('tab-yearly-breakdown');
        if (tabBtn && tabBtn.getAttribute('aria-selected') !== 'true') {
            tabBtn.click();
        }

        // If in 5Y density mode and target is not a milestone, expand to all years
        const isOddMilestone = targetYear > 1 && targetYear % 5 !== 0;
        if (isOddMilestone) {
            const densityAllBtn = document.getElementById('table-density-all');
            if (densityAllBtn && densityAllBtn.getAttribute('aria-selected') !== 'true') {
                densityAllBtn.click();
            }
        }

        const isMobile = typeof window !== 'undefined' && window.innerWidth < 640;
        if (isMobile) {
            const mobileCard = document.querySelector<HTMLElement>(`#mobile-breakdown-cards [data-card-year="${targetYear}"]`);
            if (mobileCard) {
                mobileCard.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                mobileCard.classList.add('ring-2', 'ring-emerald-500', 'bg-emerald-50/90');
                setTimeout(() => {
                    mobileCard.classList.remove('ring-2', 'ring-emerald-500', 'bg-emerald-50/90');
                }, 1400);
                return;
            }
        }

        // Desktop table row
        const row = document.querySelector<HTMLElement>(`#breakdown-body tr[data-year="${targetYear}"]`);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('bg-emerald-100/90', 'ring-2', 'ring-emerald-500/50');
            setTimeout(() => {
                row.classList.remove('bg-emerald-100/90', 'ring-2', 'ring-emerald-500/50');
            }, 1400);
            return;
        }

        // Fallback to table container
        const tableTarget = document.getElementById('yearly-breakdown') || 
                            document.getElementById('yearly-breakdown-section') || 
                            document.getElementById('panel-yearly-breakdown');
        if (tableTarget) {
            tableTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    /**
     * Bind summary cards to smoothly scroll down and highlight specific milestone years in the Cashflow Ledger.
     */
    private initCardClickHandlers(): void {
        const grid = this.dom.getElement('summary-cards-grid');
        if (!grid) return;

        const bindCard = (id: string, indexFallback: number, getTargetYear: () => number, label: string) => {
            const card = this.dom.getElement(id) || (grid.children[indexFallback] as HTMLElement | undefined);
            if (!card) return;

            card.classList.add('cursor-pointer');
            card.setAttribute('role', 'button');
            card.setAttribute('tabindex', '0');
            card.setAttribute('aria-label', label);

            const handler = () => {
                const targetYear = getTargetYear();
                this.navigateToMilestoneYear(targetYear);
            };

            card.addEventListener('click', handler);
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    handler();
                }
            });
        };

        bindCard('card-invested', 0, () => 1, 'View initial investment row in Cashflow Ledger');
        bindCard('card-maturity', 1, () => {
            const inputs = this.getInputs();
            return inputs.years > 0 ? inputs.years : this.getFinalTableYear();
        }, 'View Phase 1 Maturity row in Cashflow Ledger');
        bindCard('card-withdrawn', 2, () => {
            const inputs = this.getInputs();
            return inputs.enable_swp && inputs.years > 0 ? inputs.years + 1 : 1;
        }, 'View retirement withdrawal cashflows in Cashflow Ledger');
        bindCard('card-corpus', 3, () => this.getFinalTableYear(), 'View final wealth corpus in Cashflow Ledger');
    }

    private getFinalTableYear(): number {
        const inputs = this.getInputs();
        const sipYears = inputs.years || 0;
        const swpYears = inputs.swp_years || 0;
        if (inputs.enable_swp && (sipYears > 0 || swpYears > 0)) {
            return Math.max(1, sipYears + swpYears);
        }
        if (sipYears > 0) {
            return sipYears;
        }
        const rows = this.dom.getElements<HTMLTableRowElement>('#breakdown-body tr[data-year]');
        const lastRow = rows[rows.length - 1];
        const parsed = lastRow ? parseInt(lastRow.dataset.year || '1', 10) : 1;
        return Number.isFinite(parsed) && parsed > 0 ? parsed : 1;
    }

    /**
     * Bind Alpha Beacon secondary insights expandable drawer toggle.
     */
    private initDrawer(): void {
        const toggleBtn = this.dom.getElement<HTMLButtonElement>('alpha-drawer-toggle');
        const drawer = this.dom.getElement('alpha-secondary-insights');
        if (toggleBtn && drawer) {
            toggleBtn.addEventListener('click', () => {
                const isOpen = !drawer.classList.contains('hidden');
                drawer.classList.toggle('hidden', isOpen);
                drawer.classList.toggle('flex', !isOpen);
                toggleBtn.setAttribute('aria-expanded', String(!isOpen));
                const svg = toggleBtn.querySelector('svg');
                if (svg) {
                    svg.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
                }
            });
        }
    }

    /**
     * Force immediate non-animated settling (used for initial hydration and headless snapshots).
     */
    public setInstant(instant: boolean): void {
        this.odometer.setInstant(instant);
    }

    /**
     * Adapt text font size inside metrics tiles on screen resize.
     */
    fitSummaryCards(): void {
        const ids = ['summary-invested', 'summary-interest', 'summary-withdrawn', 'summary-corpus'];
        const cardElms = ids
            .map(id => this.dom.getElement(id))
            .filter((el): el is HTMLElement => el !== null);

        if (cardElms.length === 0) return;

        // 1. Reset Phase (subline elements use native compact typography)
        cardElms.forEach(el => {
            el.style.whiteSpace = 'nowrap';
            el.style.fontSize = '';
            el.style.letterSpacing = '';
        });

        // 2. Query Phase (batch DOM measurements)
        const measurements = cardElms.map(el => {
            const parent = el.parentElement;
            if (!parent) return null;
            const cs = getComputedStyle(parent);
            const availableW = parent.clientWidth - parseFloat(cs.paddingLeft || '0') - parseFloat(cs.paddingRight || '0');
            const textW = el.scrollWidth;
            const currentFontSize = parseFloat(getComputedStyle(el).fontSize || '16');
            return { el, currentFontSize, availableW, textW };
        }).filter((item): item is NonNullable<typeof item> => item !== null);

        // 3. Command Phase (batch exact downscaling mutations)
        measurements.forEach(({ el, currentFontSize, availableW, textW }) => {
            if (textW > availableW && availableW > 0) {
                const scaled = Math.floor((availableW / textW) * currentFontSize * 0.96);
                el.style.fontSize = Math.max(scaled, 9) + 'px';
                el.style.letterSpacing = '-0.04em';
            }
        });
    }

    /**
     * Reset cached base font sizes before re-fitting summary cards.
     */
    resetBaseFontCache(): void {
        const ids = ['summary-invested', 'summary-interest', 'summary-withdrawn', 'summary-corpus'];
        ids.forEach(id => {
            const el = this.dom.getElement(id);
            if (el) delete el.dataset.baseFont;
        });
    }

    /**
     * Update top-level summary metrics with smooth odometer numeric roll physics.
     * Supports Tier 1 instant text updates during active dragging, deferring
     * layout-thrashing DOM measurements and multi-frame physics to settled state.
     */
    updateSummaryMetrics(data: YearResult[], isDragging: boolean = false): void {
        if (!data || data.length === 0) return;

        const lastRow = data[data.length - 1];
        const totalInvested = lastRow.cumulative_invested;
        const preTaxCorpus = lastRow.combined_total;
        const totalWithdrawn = lastRow.cumulative_withdrawals || 0;
        const preTaxGains = (preTaxCorpus + totalWithdrawn) - totalInvested;

        const postTaxToggle = this.dom.getElement<HTMLInputElement>('show_post_tax');
        const showPostTax = postTaxToggle?.checked || false;

        let finalCorpus = preTaxCorpus;
        let finalGains = preTaxGains;

        const inputs = this.getInputs();

        const interestTitle = this.dom.getElement('title-interest');
        const corpusTitle = this.dom.getElement('title-corpus');

        let interestLabel = 'Total Gains';
        let corpusLabel = 'Final Corpus';

        if (showPostTax && inputs.inflation > 0) {
            const ltcgTax = lastRow.ltcg_tax ?? 0;
            finalCorpus = lastRow.post_tax_total ?? Math.max(0, preTaxCorpus - ltcgTax);
            finalGains = Math.max(0, preTaxGains - ltcgTax);
            const totalYears = inputs.enable_swp ? (inputs.years + inputs.swp_years) : inputs.years;
            finalCorpus = MathEngine.calculateInflationDiscount(finalCorpus, totalYears, inputs.inflation);
            finalGains = MathEngine.calculateInflationDiscount(finalGains, totalYears, inputs.inflation);
            interestLabel = 'Post-Tax Real Gains';
            corpusLabel = 'Post-Tax Real Corpus';
        } else if (showPostTax) {
            const ltcgTax = lastRow.ltcg_tax ?? 0;
            finalCorpus = lastRow.post_tax_total ?? Math.max(0, preTaxCorpus - ltcgTax);
            finalGains = Math.max(0, preTaxGains - ltcgTax);
            interestLabel = 'Post-Tax Gains';
            corpusLabel = 'Post-Tax Corpus';
        } else if (inputs.inflation > 0) {
            const totalYears = inputs.enable_swp ? (inputs.years + inputs.swp_years) : inputs.years;
            finalCorpus = MathEngine.calculateInflationDiscount(finalCorpus, totalYears, inputs.inflation);
            finalGains = MathEngine.calculateInflationDiscount(finalGains, totalYears, inputs.inflation);
            interestLabel = 'Real Gains';
            corpusLabel = 'Real Corpus';
        }

        if (interestTitle) interestTitle.textContent = interestLabel;
        if (corpusTitle) corpusTitle.textContent = corpusLabel;

        const isDualMode = Boolean(inputs.enable_swp && inputs.years > 0);
        const sipYears = inputs.years || 0;
        const swpYears = inputs.swp_years || 0;
        const totalYears = isDualMode ? (sipYears + swpYears) : (inputs.enable_swp ? swpYears : sipYears);

        let maturedCorpus = 0;
        let maturedGains = 0;
        let maturedGainPct = 0;

        if (isDualMode && sipYears > 0) {
            const maturedIndex = Math.min(sipYears - 1, data.length - 1);
            const maturedRow = data[maturedIndex];
            const maturedInvested = maturedRow.cumulative_invested;
            let mCorpus = maturedRow.combined_total;
            let mGains = Math.max(0, mCorpus - maturedInvested);

            if (showPostTax && inputs.inflation > 0) {
                const maturedLtcg = maturedRow.ltcg_tax ?? 0;
                mCorpus = maturedRow.post_tax_total ?? Math.max(0, mCorpus - maturedLtcg);
                mGains = Math.max(0, mGains - maturedLtcg);
                mCorpus = MathEngine.calculateInflationDiscount(mCorpus, sipYears, inputs.inflation);
                mGains = MathEngine.calculateInflationDiscount(mGains, sipYears, inputs.inflation);
            } else if (showPostTax) {
                const maturedLtcg = maturedRow.ltcg_tax ?? 0;
                mCorpus = maturedRow.post_tax_total ?? Math.max(0, mCorpus - maturedLtcg);
                mGains = Math.max(0, mGains - maturedLtcg);
            } else if (inputs.inflation > 0) {
                mCorpus = MathEngine.calculateInflationDiscount(mCorpus, sipYears, inputs.inflation);
                mGains = MathEngine.calculateInflationDiscount(mGains, sipYears, inputs.inflation);
            }

            maturedCorpus = mCorpus;
            maturedGains = mGains;
            maturedGainPct = maturedInvested > 0 ? Math.round((maturedGains / maturedInvested) * 100) : 0;

            const bridgeVal = this.dom.getElement('bridge-matured-corpus-val');
            if (bridgeVal) {
                bridgeVal.textContent = this.formatter.formatCompact(maturedRow.combined_total);
            }
        }

        const card2DisplayValue = isDualMode ? maturedCorpus : finalGains;

        const titleInvested = this.dom.getElement('title-invested');
        const subtitleInvested = this.dom.getElement('subtitle-invested');
        const titleInterest = this.dom.getElement('title-interest');
        const subtitleInterest = this.dom.getElement('subtitle-interest');
        const titleWithdrawn = this.dom.getElement('title-withdrawn');
        const subtitleWithdrawn = this.dom.getElement('subtitle-withdrawn');
        const titleCorpusText = this.dom.getElement('title-corpus-text');
        const subtitleCorpus = this.dom.getElement('subtitle-corpus');

        if (isDualMode) {
            if (titleInvested) titleInvested.textContent = 'Total SIP Invested';
            if (subtitleInvested) subtitleInvested.textContent = `${sipYears} Yrs Accumulation`;

            let card2Title = 'Phase 1 SIP Maturity';
            if (showPostTax && inputs.inflation > 0) card2Title = 'Phase 1 Post-Tax Real Maturity';
            else if (showPostTax) card2Title = 'Phase 1 Post-Tax Maturity';
            else if (inputs.inflation > 0) card2Title = 'Phase 1 Real Maturity';
            if (titleInterest) titleInterest.textContent = card2Title;
            if (subtitleInterest) subtitleInterest.textContent = `+${this.formatter.formatCompact(maturedGains)} Gains @ Yr ${sipYears}`;

            if (titleWithdrawn) titleWithdrawn.textContent = 'Phase 2 Total SWP Paid';
            if (subtitleWithdrawn) subtitleWithdrawn.textContent = `${swpYears} Yrs Retirement`;

            let card4Title = `Final Corpus @ Yr ${totalYears}`;
            if (showPostTax && inputs.inflation > 0) card4Title = `Post-Tax Real Corpus @ Yr ${totalYears}`;
            else if (showPostTax) card4Title = `Post-Tax Corpus @ Yr ${totalYears}`;
            else if (inputs.inflation > 0) card4Title = `Real Corpus @ Yr ${totalYears}`;
            if (titleCorpusText) titleCorpusText.textContent = card4Title;
            if (subtitleCorpus) subtitleCorpus.textContent = `+${this.formatter.formatCompact(finalGains)} Lifetime Gains`;
        } else {
            if (titleInvested) titleInvested.textContent = 'Total Invested';
            if (subtitleInvested) subtitleInvested.textContent = 'Principal Capital';
            if (titleInterest) titleInterest.textContent = interestLabel;
            if (subtitleInterest) subtitleInterest.textContent = 'Compound Growth';
            if (titleWithdrawn) titleWithdrawn.textContent = 'Total Payouts';
            if (subtitleWithdrawn) subtitleWithdrawn.textContent = 'Systematic Cashflow';
            if (titleCorpusText) titleCorpusText.textContent = corpusLabel;
            if (subtitleCorpus) subtitleCorpus.textContent = '';
        }

        // Dynamic Morphological Summary Cards Grid (SIP: 3-column, SWP: 4-column)
        const summaryGrid = this.dom.getElement('summary-cards-grid');
        const cardWithdrawn = this.dom.getElement('card-withdrawn');
        const cardCorpus = this.dom.getElement('card-corpus');
        if (summaryGrid && cardWithdrawn) {
            if (inputs.enable_swp) {
                summaryGrid.className = 'grid grid-cols-2 xl:grid-cols-4 gap-2 sm:gap-2.5 transition-all duration-300';
                cardWithdrawn.classList.remove('hidden');
                if (cardCorpus) {
                    cardCorpus.classList.remove('col-span-2', 'sm:col-span-1', 'xl:col-span-1', 'order-first');
                    cardCorpus.classList.add('col-span-1');
                }
            } else {
                summaryGrid.className = 'grid grid-cols-2 xl:grid-cols-3 gap-2 sm:gap-2.5 transition-all duration-300';
                cardWithdrawn.classList.add('hidden');
                if (cardCorpus) {
                    cardCorpus.classList.remove('col-span-1', 'sm:col-span-1', 'order-first');
                    cardCorpus.classList.add('col-span-2', 'xl:col-span-1');
                }
            }
        }

        // Statutory Budget 2024 Post-Tax Indicator on Corpus Card
        const postTaxPill = this.dom.getElement('post-tax-statutory-pill');
        if (postTaxPill) {
            postTaxPill.style.display = showPostTax ? 'block' : 'none';
        }

        // Update Compact Indian Denomination Headlines & Badges (e.g. ₹1.20 Cr, ₹40.74 L)
        const updateMetricsDisplays = (baseId: string, val: number) => {
            const compactText = val > 0 ? this.formatter.formatCompact(val) : this.formatter.format(0);

            const headlineEl = this.dom.getElement(`${baseId}-headline`);
            if (headlineEl) {
                headlineEl.textContent = compactText;
            }

            const badgeEl = this.dom.getElement(`${baseId}-compact`);
            if (badgeEl) {
                if (val > 0) {
                    badgeEl.textContent = compactText;
                    badgeEl.style.display = '';
                } else {
                    badgeEl.style.display = 'none';
                }
            }
        };

        updateMetricsDisplays('summary-invested', totalInvested);
        updateMetricsDisplays('summary-interest', card2DisplayValue);
        updateMetricsDisplays('summary-withdrawn', totalWithdrawn);
        updateMetricsDisplays('summary-corpus', finalCorpus);

        // Update Gain Ratio Badge
        const gainBadge = this.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            const activeGainPct = isDualMode ? maturedGainPct : (totalInvested > 0 ? Math.round((finalGains / totalInvested) * 100) : 0);
            const sign = activeGainPct >= 0 ? '+' : '';
            gainBadge.textContent = `${sign}${activeGainPct}%`;
            gainBadge.style.display = '';
        }

        // ── TIER 1 FAST-PATH BAILOUT DURING ACTIVE POINTER DRAG ──
        // Bypasses multi-frame odometer physics, layout font measurement loops, and forced DOM reflows
        if (isDragging) {
            return;
        }

        // Odometer spring animation for KPI numbers (executed once dragging pauses or completes)
        this.odometer.animateValue('summary-invested', totalInvested);
        this.odometer.animateValue('summary-interest', card2DisplayValue);
        this.odometer.animateValue('summary-withdrawn', totalWithdrawn);
        this.odometer.animateValue('summary-corpus', finalCorpus);
        this.fitSummaryCards();

        // Update real purchasing power and peace-of-mind telemetry
        this.purchasingPower.updateDisplay(preTaxCorpus, inputs);
        this.updateNarrative(inputs);

        // Flash ambient recalculation indicator on primary corpus card
        const corpusCard = this.dom.getElement('summary-corpus')?.closest('.fintech-glass-card, [class*="rounded-2xl"]');
        if (corpusCard) {
            corpusCard.classList.remove('metric-pulse-active');
            void (corpusCard as HTMLElement).offsetWidth; // Force DOM reflow
            corpusCard.classList.add('metric-pulse-active');
        }

        // Live Cost of 1-Year Delay (SIP mode)
        const delayBadge = this.dom.getElement('summary-delay-cost-badge');
        if (delayBadge) {
            if (inputs.sip > 0 && !inputs.enable_swp && inputs.years > 1) {
                const delayCost = MathEngine.calculateDelayCost(inputs);
                if (delayCost > 0) {
                    delayBadge.textContent = `⏳ Cost of 1-Yr Delay: -${this.formatter.format(delayCost)}`;
                    delayBadge.style.display = 'inline-flex';
                } else {
                    delayBadge.style.display = 'none';
                }
            } else {
                delayBadge.style.display = 'none';
            }
        }

        // SWP Retirement Longevity Feasibility
        const longevityBadge = this.dom.getElement('summary-longevity-badge');
        if (longevityBadge) {
            if (inputs.enable_swp && inputs.swp_withdrawal > 0) {
                const startingRetirementCorpus = inputs.years > 0
                    ? (data[inputs.years - 1]?.combined_total || 0)
                    : (inputs.lumpsum || 0);
                const initialAnnualSwp = inputs.swp_withdrawal * 12;
                const swrRate = startingRetirementCorpus > 0 ? (initialAnnualSwp / startingRetirementCorpus) * 100 : 99;

                const depletedRow = data.find(r => r.year > inputs.years && r.combined_total <= 0);
                const safeMonthlySwp = startingRetirementCorpus > 0 ? Math.round((startingRetirementCorpus * 0.04) / 12) : 0;

                if (depletedRow) {
                    longevityBadge.textContent = `🚨 Depletes in Year ${depletedRow.year} • Safe SWP: ${this.formatter.format(safeMonthlySwp)}/mo (4% SWR)`;
                    longevityBadge.className = 'inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 border border-rose-200';
                } else if (swrRate <= 4.0) {
                    longevityBadge.textContent = '🛡️ Highly Sustainable (Safe 4% Rule)';
                    longevityBadge.className = 'inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200';
                } else if (swrRate <= 6.0) {
                    longevityBadge.textContent = '⚠️ Moderate Depletion Risk (4-6% SWR)';
                    longevityBadge.className = 'inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-200';
                } else {
                    longevityBadge.textContent = '🚨 High Depletion Risk (>6% SWR)';
                    longevityBadge.className = 'inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 border border-rose-200';
                }
                longevityBadge.style.display = 'inline-flex';
            } else {
                longevityBadge.style.display = 'none';
            }
        }

        this.insightsController.updateInsights(data, inputs, totalInvested, finalGains, preTaxCorpus, preTaxGains);
        this.fitSummaryCards();
    }

    initTaxWaterfallModal(onOpen?: () => void): void {
        this.insightsController.initTaxWaterfallModal(onOpen);
    }

    updateNarrative(inputs: InvestmentInputs): void {
        this.insightsController.updateNarrative(inputs);
    }
}

