import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { InvestmentInputs, YearResult } from '../../types';
import { OdometerController } from './OdometerController';
import { ModalScrollLockHelper } from '../helpers/ModalScrollLockHelper';
import { PurchasingPowerController } from './PurchasingPowerController';

export class SummaryMetricsController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputs: () => InvestmentInputs;
    private odometer: OdometerController;
    private purchasingPower: PurchasingPowerController;

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
        this.initDrawer();
        this.initCardClickHandlers();
    }

    /**
     * Bind summary cards to smoothly scroll down to the yearly breakdown table on click.
     */
    private initCardClickHandlers(): void {
        const grid = this.dom.getElement('summary-cards-grid');
        if (!grid) return;
        const cards = grid.querySelectorAll<HTMLElement>(':scope > div');
        cards.forEach(card => {
            card.classList.add('cursor-pointer');
            card.setAttribute('role', 'button');
            card.setAttribute('tabindex', '0');
            card.setAttribute('aria-label', 'View detailed yearly breakdown');
            const handler = () => {
                const tabBtn = document.getElementById('tab-yearly-breakdown');
                if (tabBtn && tabBtn.getAttribute('aria-selected') !== 'true') {
                    tabBtn.click();
                }
                const target = document.getElementById('yearly-breakdown') || 
                               document.getElementById('yearly-breakdown-section') || 
                               document.getElementById('panel-yearly-breakdown');
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            };
            card.addEventListener('click', handler);
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    handler();
                }
            });
        });
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
     */
    updateSummaryMetrics(data: YearResult[]): void {
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
        if (summaryGrid && cardWithdrawn) {
            if (inputs.enable_swp) {
                summaryGrid.className = 'grid grid-cols-2 sm:grid-cols-4 gap-3 transition-all duration-300';
                cardWithdrawn.classList.remove('hidden');
            } else {
                summaryGrid.className = 'grid grid-cols-2 sm:grid-cols-3 gap-3 transition-all duration-300';
                cardWithdrawn.classList.add('hidden');
            }
        }

        // Statutory Budget 2024 Post-Tax Indicator on Corpus Card
        const postTaxPill = this.dom.getElement('post-tax-statutory-pill');
        if (postTaxPill) {
            postTaxPill.style.display = showPostTax ? 'block' : 'none';
        }

        // Odometer spring animation for KPI numbers
        this.odometer.animateValue('summary-invested', totalInvested);
        this.odometer.animateValue('summary-interest', card2DisplayValue);
        this.odometer.animateValue('summary-withdrawn', totalWithdrawn);
        this.odometer.animateValue('summary-corpus', finalCorpus);
        this.fitSummaryCards();

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

        // Update real purchasing power and peace-of-mind telemetry
        this.purchasingPower.updateDisplay(preTaxCorpus, inputs);
        this.updateNarrative(inputs);

        // Flash ambient recalculation indicator on primary corpus card
        const corpusCard = this.dom.getElement('summary-corpus')?.closest('.glass-card, [class*="rounded-2xl"]');
        if (corpusCard) {
            corpusCard.classList.remove('metric-pulse-active');
            void (corpusCard as HTMLElement).offsetWidth; // Force DOM reflow
            corpusCard.classList.add('metric-pulse-active');
        }

        // Update Gain Ratio Badge
        const gainBadge = this.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            const activeGainPct = isDualMode ? maturedGainPct : (totalInvested > 0 ? Math.round((finalGains / totalInvested) * 100) : 0);
            const sign = activeGainPct >= 0 ? '+' : '';
            gainBadge.textContent = `${sign}${activeGainPct}%`;
            gainBadge.style.display = '';
        }

        // Compounding Crossover Point (Year when annual return > annual investment)
        const crossoverYear = data.find(r => r.annual_contribution > 0 && r.interest > r.annual_contribution)?.year;

        const crossoverBadge = this.dom.getElement('summary-crossover-badge');
        if (crossoverBadge) {
            if (crossoverYear) {
                crossoverBadge.textContent = `🚀 Crossover in Year ${crossoverYear} (Annual Gains > Annual SIP)`;
                crossoverBadge.style.display = 'inline-flex';
            } else {
                crossoverBadge.style.display = 'none';
            }
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

        // Principal vs Profit Ratio Pill
        const ratioPill = this.dom.getElement('summary-ratio-pill');
        if (ratioPill) {
            const totalSum = totalInvested + finalGains;
            if (totalSum > 0) {
                const invPct = Math.round((totalInvested / totalSum) * 100);
                const gainPct = 100 - invPct;
                ratioPill.textContent = `${invPct}% Invested • ${gainPct}% Compounded Profit`;
                ratioPill.style.display = 'inline-flex';
            } else {
                ratioPill.style.display = 'none';
            }
        }

        // Rule of 72 Doubling Time Indicator
        const doublingBadge = this.dom.getElement('summary-doubling-badge');
        if (doublingBadge) {
            const effRate = inputs.years > 0 ? inputs.rate : (inputs.enable_swp ? inputs.swp_rate : inputs.rate);
            if (effRate > 0) {
                const doublingYrs = (72 / effRate).toFixed(1);
                doublingBadge.textContent = `⏳ Capital doubles every ${doublingYrs} yrs (@${effRate}%)`;
                doublingBadge.style.display = 'inline-flex';
            } else {
                doublingBadge.style.display = 'none';
            }
        }

        // Daily Wealth Accrual at Maturity
        const dailyAccrual = this.dom.getElement('summary-daily-accrual');
        if (dailyAccrual) {
            const lastInterest = lastRow.interest || 0;
            if (lastInterest > 0) {
                const daily = Math.round(lastInterest / 365);
                dailyAccrual.textContent = `⚡ Compounding at ${this.formatter.format(daily)}/day at maturity`;
                dailyAccrual.style.display = 'inline-flex';
            } else {
                dailyAccrual.style.display = 'none';
            }
        }

        // Contextual Alpha Radar (Priority-Ranked Single Insight)
        this._updateContextualRadar(data, inputs, totalInvested, finalGains);

        // Populate Tax Waterfall Modal values
        const taxGross = this.dom.getElement('tax-modal-gross-gains');
        const taxTaxable = this.dom.getElement('tax-modal-taxable-gains');
        const taxAmount = this.dom.getElement('tax-modal-tax-amount');
        const taxNet = this.dom.getElement('tax-modal-net-corpus');
        const taxHarvestSavings = this.dom.getElement('tax-modal-harvest-savings');
        const taxHarvestEffective = this.dom.getElement('tax-modal-harvest-effective');

        const ltcgExemption = inputs.ltcg_exemption ?? 125000;
        if (taxGross) taxGross.textContent = this.formatter.format(preTaxGains);
        if (taxTaxable) taxTaxable.textContent = this.formatter.format(Math.max(0, preTaxGains - ltcgExemption));
        if (taxAmount) taxAmount.textContent = `- ${this.formatter.format(lastRow.ltcg_tax ?? 0)}`;
        if (taxNet) taxNet.textContent = this.formatter.format(lastRow.post_tax_total ?? preTaxCorpus);

        const harvestAlpha = MathEngine.calculateTaxHarvestingSavings(inputs, data);
        if (taxHarvestSavings) taxHarvestSavings.textContent = `Save ${this.formatter.format(harvestAlpha.cumulativeSavings)}`;
        if (taxHarvestEffective) taxHarvestEffective.textContent = this.formatter.format(harvestAlpha.harvestedTax);

        this.fitSummaryCards();
    }

    /**
     * Determine single highest-priority contextual insight for progressive disclosure.
     */
    private _updateContextualRadar(data: YearResult[], inputs: InvestmentInputs, totalInvested: number, finalGains: number): void {
        const radarContainer = this.dom.getElement('contextual-alpha-radar');
        const radarIcon = this.dom.getElement('contextual-radar-icon');
        const radarText = this.dom.getElement('contextual-radar-text');
        if (!radarContainer || !radarText) return;

        // 1. Priority 1: Depletion Risk in SWP Mode
        if (inputs.enable_swp && inputs.swp_withdrawal > 0) {
            const depletedRow = data.find(r => r.year > inputs.years && r.combined_total <= 0);
            if (depletedRow) {
                radarContainer.className = 'flex items-center justify-between p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 shadow-2xs mb-3 transition-all';
                if (radarIcon) radarIcon.textContent = '🚨';
                radarText.innerHTML = `<strong>Capital Exhaustion Warning:</strong> Portfolio depletes in Year ${depletedRow.year}. Consider safe-rate calibration.`;
                radarContainer.style.display = 'flex';
                return;
            }
        }

        // 2. Priority 2: Wealth Crossover Point
        const crossoverYear = data.find(r => r.annual_contribution > 0 && r.interest > r.annual_contribution)?.year;
        if (crossoverYear) {
            radarContainer.className = 'flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 text-emerald-900 shadow-2xs mb-3 transition-all';
            if (radarIcon) radarIcon.textContent = '🚀';
            radarText.innerHTML = `<strong>Wealth Crossover:</strong> In Year ${crossoverYear}, annual compounding gains surpass your annual invested principal.`;
            radarContainer.style.display = 'flex';
            return;
        }

        // 3. Priority 3: Milestone Doubling / Velocity
        const effRate = inputs.years > 0 ? inputs.rate : (inputs.enable_swp ? inputs.swp_rate : inputs.rate);
        if (effRate > 0 && finalGains > totalInvested) {
            radarContainer.className = 'flex items-center justify-between p-3 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-900 shadow-2xs mb-3 transition-all';
            if (radarIcon) radarIcon.textContent = '💎';
            const doublingYrs = (72 / effRate).toFixed(1);
            radarText.innerHTML = `<strong>Compounding Multiplier:</strong> Capital doubles every ${doublingYrs} years with ${Math.round((finalGains / totalInvested) * 100)}% net wealth expansion.`;
            radarContainer.style.display = 'flex';
            return;
        }

        // 4. Default: Daily Compounding Velocity
        const lastRow = data[data.length - 1];
        const dailyRate = Math.round((lastRow?.interest || 0) / 365);
        if (dailyRate > 0) {
            radarContainer.className = 'flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-200/90 text-slate-800 shadow-2xs mb-3 transition-all';
            if (radarIcon) radarIcon.textContent = '⚡';
            radarText.innerHTML = `<strong>Compounding Velocity:</strong> Generating <span class="font-mono font-bold text-emerald-700">${this.formatter.format(dailyRate)} / day</span> in passive interest at maturity.`;
            radarContainer.style.display = 'flex';
            return;
        }

        radarContainer.style.display = 'none';
    }

    initTaxWaterfallModal(onOpen?: () => void): void {
        const modal = this.dom.getElement<HTMLDialogElement>('tax-waterfall-modal');
        const openBtn = this.dom.getElement('open-tax-waterfall-btn');
        const closeBtn = this.dom.getElement('close-tax-waterfall-btn');
        const footerCloseBtn = this.dom.getElement('close-tax-waterfall-footer-btn');

        if (!modal) return;

        let cleanupFocusTrap: (() => void) | null = null;
        const closeModal = () => {
            modal.close();
            if (cleanupFocusTrap) {
                cleanupFocusTrap();
                cleanupFocusTrap = null;
            }
            ModalScrollLockHelper.unlock();
        };

        if (openBtn) {
            openBtn.addEventListener('click', () => {
                modal.showModal();
                ModalScrollLockHelper.lock(openBtn);
                cleanupFocusTrap = ModalScrollLockHelper.bindFocusTrap(modal);
                onOpen?.();
            });
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
        if (footerCloseBtn) {
            footerCloseBtn.addEventListener('click', closeModal);
        }
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        modal.addEventListener('cancel', () => {
            if (cleanupFocusTrap) {
                cleanupFocusTrap();
                cleanupFocusTrap = null;
            }
            ModalScrollLockHelper.unlock();
        });
    }

    /**
     * Update conversational live narrative strip tokens.
     */
    updateNarrative(inputs: InvestmentInputs): void {
        const narrativeSip = this.dom.getElement('narrative-sip');
        const narrativeYears = this.dom.getElement('narrative-years');
        const narrativeRate = this.dom.getElement('narrative-rate');

        if (narrativeSip) {
            narrativeSip.textContent = this.formatter.format(inputs.sip);
        }
        if (narrativeYears) {
            narrativeYears.textContent = `${inputs.years} yrs`;
        }
        if (narrativeRate) {
            narrativeRate.textContent = `${inputs.rate}% CAGR`;
        }
    }
}

