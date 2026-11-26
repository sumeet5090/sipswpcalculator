import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { ModeTableConfig } from '../constants/ModeTableSchema';
import { InvestmentInputs, YearResult } from '../../types';

export interface MobileRenderParams {
    dom: DOMAdapter;
    formatter: CurrencyFormatter;
    data: YearResult[];
    inputs: InvestmentInputs;
    appMode: string;
    schema: ModeTableConfig;
    enableSwp: boolean;
    showPostTax: boolean;
    denominationMode: 'exact' | 'lakh';
    heatmapEnabled: boolean;
    density: 'all' | '5y';
    mobileExpanded: boolean;
    mobileViewMode: 'carousel' | 'list';
    searchYear: number | null;
}

export function renderMobileBreakdownView(params: MobileRenderParams): void {
    const {
        dom,
        formatter,
        data,
        inputs,
        appMode,
        schema,
        enableSwp,
        showPostTax,
        denominationMode,
        heatmapEnabled,
        density,
        mobileExpanded,
        mobileViewMode,
        searchYear,
    } = params;

    const mobileContainer = dom.getElement('mobile-breakdown-cards');
    const listTbody = dom.getElement('mobile-compact-list-body');
    const dotsContainer = dom.getElement('mobile-carousel-dots');

    let mobileData = data;
    if (searchYear !== null) {
        mobileData = data.filter(r => r.year === searchYear);
    } else if (density === '5y' && !mobileExpanded) {
        mobileData = data.filter(r => r.year === 1 || r.year % 5 === 0 || r.year === data.length);
    }

    const fmt = (v: number | null | undefined): string => {
        if (v === null || v === undefined) return '-';
        return denominationMode === 'lakh' ? formatter.formatDynamic(v) : formatter.format(v);
    };

    if (mobileViewMode === 'carousel') {
        if (listTbody) listTbody.innerHTML = '';
        if (!mobileContainer) return;

        const mobileFragment = document.createDocumentFragment();
        const maxInterest = Math.max(1, ...data.map(r => r.interest));

        mobileData.forEach((row) => {
            const card = document.createElement('div');
            const isMilestone = row.year === 1 || row.year % 5 === 0 || row.year === data.length;
            const isFinal = row.year === data.length;

            card.className = 'snap-center shrink-0 w-[84vw] max-w-[320px] p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-subtle space-y-2 transition-all flex flex-col justify-between';
            card.dataset.cardYear = String(row.year);

            if (heatmapEnabled && !schema.mobileCard.isDebtOrCostMode && row.interest > 0) {
                const intensity = Math.min(1, row.interest / maxInterest);
                card.style.backgroundColor = `rgba(16, 185, 129, ${(0.04 + intensity * 0.10).toFixed(2)})`;
                card.style.borderColor = `rgba(16, 185, 129, ${(0.2 + intensity * 0.25).toFixed(2)})`;
            }

            let finalCorpus = showPostTax ? (row.post_tax_total ?? row.combined_total) : row.combined_total;
            if (inputs.inflation > 0) {
                finalCorpus = MathEngine.calculateInflationDiscount(finalCorpus, row.year, inputs.inflation);
            }

            const header = document.createElement('div');
            header.className = 'flex items-center justify-between';

            const yearBadge = document.createElement('span');
            yearBadge.className = isFinal
                ? 'px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-600 text-white shadow-flat'
                : (isMilestone ? 'px-2.5 py-0.5 rounded-lg text-xs font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200' : 'text-xs font-bold text-slate-700');
            yearBadge.textContent = `${schema.mobileCard.yearPrefix} ${row.year}${isFinal ? ` (${schema.mobileCard.finalSuffix})` : ''}`;
            header.appendChild(yearBadge);

            const corpusWrap = document.createElement('div');
            corpusWrap.className = 'flex items-center gap-1.5';

            const corpusVal = document.createElement('span');
            corpusVal.className = 'text-base font-black font-financial-mono tabular-nums text-slate-900 whitespace-nowrap';
            corpusVal.textContent = fmt(finalCorpus);
            corpusWrap.appendChild(corpusVal);

            if (schema.showMultiplier && row.cumulative_invested > 0 && finalCorpus > 0) {
                const multiplier = (finalCorpus / row.cumulative_invested).toFixed(1);
                const multiplierBadge = document.createElement('span');
                multiplierBadge.className = 'px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-black shrink-0 shadow-flat';
                multiplierBadge.textContent = `${multiplier}×`;
                corpusWrap.appendChild(multiplierBadge);
            }

            header.appendChild(corpusWrap);
            card.appendChild(header);

            appendMobileProgressBar(card, appMode, finalCorpus, row.cumulative_invested);

            const grid = document.createElement('div');
            grid.className = 'grid grid-cols-2 gap-2 text-xs pt-1.5 border-t border-slate-100';

            const investedCol = document.createElement('div');
            const investedLabel = document.createElement('span');
            investedLabel.className = 'text-slate-500 block text-caption font-semibold';
            investedLabel.textContent = schema.mobileCard.invested;
            const investedVal = document.createElement('span');
            investedVal.className = 'font-bold font-financial-mono tabular-nums text-slate-800 whitespace-nowrap text-ui-xs';
            investedVal.textContent = fmt(row.cumulative_invested);
            investedCol.appendChild(investedLabel);
            investedCol.appendChild(investedVal);
            grid.appendChild(investedCol);

            const interestCol = document.createElement('div');
            interestCol.className = 'text-right';
            const interestLabel = document.createElement('span');
            interestLabel.className = schema.mobileCard.isDebtOrCostMode
                ? 'text-slate-600 block text-caption font-semibold'
                : 'text-emerald-700 block text-caption font-semibold';
            interestLabel.textContent = schema.mobileCard.interest;
            const interestVal = document.createElement('span');
            interestVal.className = schema.mobileCard.isDebtOrCostMode
                ? 'font-extrabold font-financial-mono tabular-nums text-slate-700 whitespace-nowrap text-ui-xs'
                : 'font-extrabold font-financial-mono tabular-nums text-emerald-700 whitespace-nowrap text-ui-xs';
            const prefix = schema.mobileCard.interestPositivePrefix && row.interest >= 0 ? '+' : '';
            interestVal.textContent = `${prefix}${fmt(row.interest)}`;
            interestCol.appendChild(interestLabel);
            interestCol.appendChild(interestVal);
            grid.appendChild(interestCol);

            if (enableSwp && (row.cumulative_withdrawals ?? 0) > 0) {
                const withCol = document.createElement('div');
                withCol.className = 'col-span-2 pt-1 border-t border-slate-100 flex items-center justify-between text-xs';
                const withLabel = document.createElement('span');
                withLabel.className = 'text-rose-700 text-caption font-semibold';
                withLabel.textContent = schema.mobileCard.withdrawn;
                const withVal = document.createElement('span');
                withVal.className = 'font-extrabold font-financial-mono tabular-nums text-rose-700 whitespace-nowrap text-ui-xs';
                withVal.textContent = fmt(row.cumulative_withdrawals ?? 0);
                withCol.appendChild(withLabel);
                withCol.appendChild(withVal);
                grid.appendChild(withCol);
            }

            card.appendChild(grid);
            mobileFragment.appendChild(card);
        });

        mobileContainer.innerHTML = '';
        mobileContainer.appendChild(mobileFragment);

        if (dotsContainer) {
            renderCarouselDots(mobileContainer, dotsContainer, mobileData.length);
        }
    } else {
        if (mobileContainer) mobileContainer.innerHTML = '';
        if (dotsContainer) dotsContainer.innerHTML = '';
        if (!listTbody) return;

        const listFragment = document.createDocumentFragment();
        const prefix = schema.mobileCard.interestPositivePrefix;
        const interestClass = schema.mobileCard.isDebtOrCostMode ? 'text-slate-700' : 'text-emerald-700';

        mobileData.forEach((row) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors';

            let finalCorpus = showPostTax ? (row.post_tax_total ?? row.combined_total) : row.combined_total;
            if (inputs.inflation > 0) {
                finalCorpus = MathEngine.calculateInflationDiscount(finalCorpus, row.year, inputs.inflation);
            }

            const sign = prefix && row.interest >= 0 ? '+' : '';
            tr.innerHTML = `
                <td class="py-2.5 px-2 font-bold text-slate-800 text-left">${schema.mobileCompact.year} ${row.year}</td>
                <td class="py-2.5 px-2 text-right text-slate-600 font-mono">${fmt(row.cumulative_invested)}</td>
                <td class="py-2.5 px-2 text-right ${interestClass} font-bold font-mono">${sign}${fmt(row.interest)}</td>
                <td class="py-2.5 px-2 text-right font-extrabold text-slate-900 font-mono">${fmt(finalCorpus)}</td>
            `;
            listFragment.appendChild(tr);
        });
        listTbody.innerHTML = '';
        listTbody.appendChild(listFragment);
    }
}

function appendMobileProgressBar(
    card: HTMLElement,
    appMode: string,
    finalCorpus: number,
    cumulativeInvested: number
): void {
    if (appMode === 'emi') {
        const loanPrincipal = finalCorpus + cumulativeInvested;
        const repaidPct = loanPrincipal > 0 ? Math.min(100, Math.max(0, (cumulativeInvested / loanPrincipal) * 100)) : 0;
        const bar = document.createElement('div');
        bar.className = 'w-full h-1.5 bg-slate-100 rounded-full overflow-hidden flex my-1';
        bar.innerHTML = `<div class="bg-emerald-500 h-full transition-all" style="width: ${repaidPct}%" title="Repaid: ${repaidPct.toFixed(0)}%"></div><div class="bg-slate-300 h-full transition-all" style="width: ${100 - repaidPct}%" title="Balance: ${(100 - repaidPct).toFixed(0)}%"></div>`;
        card.appendChild(bar);
    } else if (finalCorpus > 0 && cumulativeInvested > 0) {
        const investedPct = Math.min(100, Math.max(5, (cumulativeInvested / finalCorpus) * 100));
        const gainsPct = Math.max(0, 100 - investedPct);
        const bar = document.createElement('div');
        bar.className = 'w-full h-1.5 bg-slate-100 rounded-full overflow-hidden flex my-1';
        bar.innerHTML = `<div class="bg-slate-300 h-full transition-all" style="width: ${investedPct}%" title="Invested: ${investedPct.toFixed(0)}%"></div><div class="bg-emerald-500 h-full transition-all" style="width: ${gainsPct}%" title="Gains: ${gainsPct.toFixed(0)}%"></div>`;
        card.appendChild(bar);
    }
}

function renderCarouselDots(mobileContainer: HTMLElement, dotsContainer: HTMLElement, rowCount: number): void {
    dotsContainer.innerHTML = '';
    const dotCount = Math.min(rowCount, 12);
    for (let i = 0; i < dotCount; i++) {
        const dot = document.createElement('span');
        dot.className = i === 0
            ? 'w-4 h-1.5 rounded-full bg-emerald-600 transition-all'
            : 'w-1.5 h-1.5 rounded-full bg-slate-300 transition-all';
        dotsContainer.appendChild(dot);
    }

    if (!mobileContainer.dataset.scrollBound) {
        mobileContainer.dataset.scrollBound = 'true';
        mobileContainer.addEventListener('scroll', () => {
            const scrollLeft = mobileContainer.scrollLeft;
            const cardWidth = mobileContainer.firstElementChild?.clientWidth || 280;
            const activeIndex = Math.min(dotCount - 1, Math.round(scrollLeft / (cardWidth + 12)));
            const dots = dotsContainer.children;
            for (let i = 0; i < dots.length; i++) {
                dots[i].className = i === activeIndex
                    ? 'w-4 h-1.5 rounded-full bg-emerald-600 transition-all'
                    : 'w-1.5 h-1.5 rounded-full bg-slate-300 transition-all';
            }
        }, { passive: true });
    }
}
