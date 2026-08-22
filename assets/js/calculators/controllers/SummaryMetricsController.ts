import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { InvestmentInputs, YearResult } from '../../types';
import { OdometerController } from './OdometerController';
import { ModalScrollLockHelper } from '../helpers/ModalScrollLockHelper';

export class SummaryMetricsController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputs: () => InvestmentInputs;
    private odometer: OdometerController;

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        getInputs: () => InvestmentInputs
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.getInputs = getInputs;
        this.odometer = new OdometerController(dom, formatter);
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

        // 1. Reset Phase
        cardElms.forEach(el => {
            el.style.whiteSpace = 'nowrap';
            el.style.overflow = 'hidden';
            if (!el.dataset.baseFont) {
                el.dataset.baseFont = getComputedStyle(el).fontSize;
            }
            const basePx = parseFloat(el.dataset.baseFont);
            el.style.fontSize = basePx + 'px';
        });

        // 2. Query Phase (batch all DOM measurements together to prevent layout thrashing)
        const measurements = cardElms.map(el => {
            const parent = el.parentElement;
            if (!parent) return null;
            const cs = getComputedStyle(parent);
            const availableW = parent.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
            const textW = el.scrollWidth;
            const basePx = parseFloat(el.dataset.baseFont || '16');
            return { el, basePx, availableW, textW };
        }).filter((item): item is NonNullable<typeof item> => item !== null);

        // 3. Command Phase (batch all style mutations together)
        measurements.forEach(({ el, basePx, availableW, textW }) => {
            if (textW > availableW && availableW > 0) {
                el.style.fontSize = Math.max((availableW / textW) * basePx, 10) + 'px';
            } else {
                el.style.fontSize = basePx + 'px';
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

        // Odometer spring animation for KPI numbers
        this.odometer.animateValue('summary-invested', totalInvested);
        this.odometer.animateValue('summary-interest', finalGains);
        this.odometer.animateValue('summary-withdrawn', totalWithdrawn);
        this.odometer.animateValue('summary-corpus', finalCorpus);

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
            if (totalInvested > 0) {
                const gainPct = Math.round((finalGains / totalInvested) * 100);
                const sign = gainPct >= 0 ? '+' : '';
                gainBadge.textContent = `${sign}${gainPct}%`;
                gainBadge.style.display = '';
            } else {
                gainBadge.style.display = 'none';
            }
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

        // SWP Retirement Longevity Feasibility (Benchmarked against starting retirement corpus)
        const longevityBadge = this.dom.getElement('summary-longevity-badge');
        if (longevityBadge) {
            if (inputs.enable_swp && inputs.swp_withdrawal > 0) {
                const startingRetirementCorpus = inputs.years > 0
                    ? (data[inputs.years - 1]?.combined_total || 0)
                    : (inputs.lumpsum || 0);
                const initialAnnualSwp = inputs.swp_withdrawal * 12;
                const swrRate = startingRetirementCorpus > 0 ? (initialAnnualSwp / startingRetirementCorpus) * 100 : 99;
                const finalYearCorpus = lastRow.combined_total;

                if (finalYearCorpus <= 0) {
                    longevityBadge.textContent = '🚨 Depletes Before Horizon';
                    longevityBadge.className = 'inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 border border-rose-200';
                } else if (swrRate <= 4.0) {
                    longevityBadge.textContent = '🛡️ Highly Sustainable (Safe 4% Rule)';
                    longevityBadge.className = 'inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200';
                } else if (swrRate <= 6.0) {
                    longevityBadge.textContent = '⚠️ Moderate Depletion Risk';
                    longevityBadge.className = 'inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-200';
                } else {
                    longevityBadge.textContent = '🚨 High Depletion Risk';
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
            if (inputs.rate > 0) {
                const doublingYrs = (72 / inputs.rate).toFixed(1);
                doublingBadge.textContent = `⏳ Capital doubles every ${doublingYrs} yrs (@${inputs.rate}%)`;
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

        // Populate Tax Waterfall Modal values
        const taxGross = this.dom.getElement('tax-modal-gross-gains');
        const taxTaxable = this.dom.getElement('tax-modal-taxable-gains');
        const taxAmount = this.dom.getElement('tax-modal-tax-amount');
        const taxNet = this.dom.getElement('tax-modal-net-corpus');

        const ltcgExemption = inputs.ltcg_exemption ?? 125000;
        if (taxGross) taxGross.textContent = this.formatter.format(preTaxGains);
        if (taxTaxable) taxTaxable.textContent = this.formatter.format(Math.max(0, preTaxGains - ltcgExemption));
        if (taxAmount) taxAmount.textContent = `- ${this.formatter.format(lastRow.ltcg_tax ?? 0)}`;
        if (taxNet) taxNet.textContent = this.formatter.format(lastRow.post_tax_total ?? preTaxCorpus);

        this.fitSummaryCards();
    }

    initTaxWaterfallModal(onOpen?: () => void): void {
        const modal = this.dom.getElement<HTMLDialogElement>('tax-waterfall-modal');
        const openBtn = this.dom.getElement('open-tax-waterfall-btn');
        const closeBtn = this.dom.getElement('close-tax-waterfall-btn');
        const footerCloseBtn = this.dom.getElement('close-tax-waterfall-footer-btn');

        if (!modal) return;

        const closeModal = () => {
            modal.close();
            ModalScrollLockHelper.unlock();
        };

        if (openBtn) {
            openBtn.addEventListener('click', () => {
                modal.showModal();
                ModalScrollLockHelper.lock(openBtn);
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
            ModalScrollLockHelper.unlock();
        });
    }
}
