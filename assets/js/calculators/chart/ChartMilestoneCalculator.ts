import { YearResult } from '../../types';
import { CurrencyFormatter } from '../CurrencyHelper';
import { InputValidator } from '../InputValidator';
import { DOMAdapter } from '../../adapters/DOMAdapter';

export interface Milestone {
    type: 'wealth' | 'security';
    label: string;
    description?: string;
    year: number;
    icon: string;
    value: number;
    index: number;
}

/**
 * ChartMilestoneCalculator
 * Computes compounding inflection crossovers, wealth milestones, harmonic tick strides,
 * and celebratory DOM card badges.
 */
export class ChartMilestoneCalculator {
    private formatter: CurrencyFormatter;
    private validator: InputValidator;
    private dom: DOMAdapter;

    constructor(
        formatter: CurrencyFormatter,
        validator: InputValidator = new InputValidator(),
        dom: DOMAdapter = new DOMAdapter()
    ) {
        this.formatter = formatter;
        this.validator = validator;
        this.dom = dom;
    }

    /**
     * Calculate active milestones for current results.
     */
    public computeMilestones(results: YearResult[], enableSwp: boolean, showPostTax: boolean): Milestone[] {
        const milestones: Milestone[] = [];
        const targets = this.validator.getMilestoneTargets().map(t => ({ ...t, reached: false }));
        let swpCovered = false;
        let crossoverReached = false;

        for (let i = 0; i < results.length; i++) {
            const row = results[i];
            const postTaxVal = row.post_tax_total ?? row.combined_total;
            const activeCorpusValue = showPostTax ? postTaxVal : row.combined_total;
            const interest = Math.max(0, activeCorpusValue - row.cumulative_invested);

            // Compounding Crossover Point
            if (!crossoverReached && interest > row.cumulative_invested && row.cumulative_invested > 0) {
                crossoverReached = true;
                milestones.push({
                    type: 'wealth',
                    label: 'Compounding Crossover ⚡',
                    description: `Year ${row.year}: Interest earnings (${this.formatter.formatDynamic(interest)}) have surpassed total invested capital (${this.formatter.formatDynamic(row.cumulative_invested)})!`,
                    year: row.year,
                    icon: '⚡',
                    value: activeCorpusValue,
                    index: i,
                });
            }

            for (const target of targets) {
                if (!target.reached && activeCorpusValue >= target.value) {
                    target.reached = true;
                    milestones.push({
                        type: 'wealth',
                        label: target.label,
                        year: row.year,
                        icon: target.icon,
                        value: activeCorpusValue,
                        index: i,
                    });
                }
            }

            if (enableSwp && !swpCovered && (row.annual_withdrawal ?? 0) > 0) {
                const tenYearsWithdrawal = (row.annual_withdrawal ?? 0) * 10;
                const isSustainable = activeCorpusValue >= tenYearsWithdrawal;
                if (isSustainable) {
                    swpCovered = true;
                    milestones.push({
                        type: 'security',
                        label: 'SWP Security (10 Yrs)',
                        description: `Corpus (${this.formatter.formatDynamic(activeCorpusValue)}) covers 10 years of SWP withdrawals (Requires ${this.formatter.formatDynamic(tenYearsWithdrawal)})!`,
                        year: row.year,
                        icon: '🛡️',
                        value: activeCorpusValue,
                        index: i,
                    });
                }
            }
        }

        return milestones;
    }

    /**
     * Deterministic Harmonic Stride Tick Generator.
     * Prevents decimation dropouts by computing a clean, human-intuitive decade/semi-decade cadence.
     */
    public computeHarmonicYearTicks(totalYears: number): number[] {
        if (totalYears <= 5) {
            return Array.from({ length: totalYears }, (_, i) => i + 1);
        }
        if (totalYears <= 10) {
            const ticks = [1];
            for (let y = 2; y <= totalYears; y += 2) {
                if (!ticks.includes(y)) ticks.push(y);
            }
            if (!ticks.includes(totalYears)) ticks.push(totalYears);
            return ticks;
        }
        if (totalYears <= 20) {
            const ticks = [1];
            for (let y = 5; y <= totalYears; y += 5) {
                if (!ticks.includes(y)) ticks.push(y);
            }
            if (!ticks.includes(totalYears)) ticks.push(totalYears);
            return ticks;
        }
        const step = totalYears <= 30 ? 5 : 10;
        const ticks = [1];
        for (let y = step; y <= totalYears; y += step) {
            if (!ticks.includes(y)) ticks.push(y);
        }
        if (!ticks.includes(totalYears)) ticks.push(totalYears);
        return ticks;
    }

    /**
     * Celebratory celebratory milestone badge card renderer.
     */
    public renderMilestoneGrid(
        milestones: Milestone[],
        onHoverMilestone?: (index: number) => void,
        onLeaveMilestone?: () => void
    ): void {
        const container = this.dom.getElement('milestones-container');
        if (!container) return;

        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }

        if (milestones.length === 0) {
            container.classList.add('hidden');
            return;
        }

        container.classList.remove('hidden');
        const fragment = document.createDocumentFragment();

        milestones.forEach(m => {
            const card = document.createElement('div');
            card.className = 'bg-gradient-to-r from-amber-50/90 via-white to-emerald-50/50 p-3.5 rounded-2xl border border-amber-200/80 shadow-sm flex items-center gap-3 transition-all duration-200 hover:shadow-md hover:border-amber-300 cursor-pointer';

            if (onHoverMilestone) {
                card.addEventListener('mouseenter', () => onHoverMilestone(m.index));
            }
            if (onLeaveMilestone) {
                card.addEventListener('mouseleave', () => onLeaveMilestone());
            }

            const iconDiv = document.createElement('div');
            iconDiv.className = 'flex items-center justify-center w-10 h-10 rounded-xl bg-amber-100/80 text-xl shrink-0 shadow-sm';
            iconDiv.textContent = m.icon;

            const textDiv = document.createElement('div');
            textDiv.className = 'min-w-0 flex-1';

            const h4 = document.createElement('h4');
            h4.className = 'text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-1.5';
            h4.textContent = m.label;

            const badge = document.createElement('span');
            badge.className = 'text-[9px] font-black uppercase px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200';
            badge.textContent = `Year ${m.year}`;
            h4.appendChild(badge);

            const p = document.createElement('p');
            p.className = 'text-[11px] sm:text-xs text-slate-500 mt-0.5 truncate';
            p.textContent = m.type === 'security'
                ? (m.description || '')
                : `Corpus reached ${this.formatter.formatDynamic(m.value)} milestone`;

            textDiv.appendChild(h4);
            textDiv.appendChild(p);
            card.appendChild(iconDiv);
            card.appendChild(textDiv);
            fragment.appendChild(card);
        });

        container.appendChild(fragment);
    }
}
