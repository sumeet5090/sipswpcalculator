import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { A11yAnnouncer } from './A11yAnnouncer';

export function computeElasticMax(val: number, defaultMax: number): number {
    if (val <= defaultMax) {
        return defaultMax;
    }
    const target = val * 1.5;
    if (target >= 10000000) return Math.ceil(target / 5000000) * 5000000;
    if (target >= 1000000) return Math.ceil(target / 500000) * 500000;
    if (target >= 100000) return Math.ceil(target / 50000) * 50000;
    if (target >= 10000) return Math.ceil(target / 10000) * 10000;
    return Math.ceil(target);
}

export function formatSliderTooltipText(formatter: CurrencyFormatter, inputId: string, val: number): string {
    const isCurrency = inputId === 'sip' || inputId === 'lumpsum' || inputId === 'target_corpus' || inputId === 'swp_withdrawal'
        || inputId === 'corpus' || inputId.endsWith('_corpus') || inputId.endsWith('_salary') || inputId.endsWith('_expense')
        || inputId.endsWith('_pension') || inputId.endsWith('_value') || inputId.endsWith('_cost') || inputId.endsWith('_received')
        || inputId.endsWith('_paid') || inputId.endsWith('_80c') || inputId.endsWith('_80d') || inputId.endsWith('_80ccd1b')
        || inputId.endsWith('_24b') || inputId.endsWith('_contribution') || inputId.endsWith('_principal') || inputId.endsWith('_deposit')
        || inputId.endsWith('_amount') || inputId.endsWith('_initial') || inputId.endsWith('_final');
    const isYears = inputId === 'years' || inputId === 'swp_years' || inputId.endsWith('_years');

    if (isCurrency) return formatter.formatDynamic(val);
    if (isYears) return `${val} Yrs`;
    return `${val}%`;
}

export function updateTrackProgress(rangeEl: HTMLInputElement): void {
    const min = parseFloat(rangeEl.min) || 0;
    const max = parseFloat(rangeEl.max) || 100;
    const val = parseFloat(rangeEl.value) || 0;
    const rangeSpan = Math.max(max - min, 1);
    const percent = Math.min(Math.max(((val - min) / rangeSpan) * 100, 0), 100);
    rangeEl.style.setProperty('--range-progress', `${percent}%`);
}

export function updateSubtext(dom: DOMAdapter, formatter: CurrencyFormatter, fieldId: string, val: number): void {
    const subtextEl = dom.getElement(`${fieldId}_subtext`);
    if (!subtextEl) return;
    const sipVal = parseFloat(dom.getValue('sip') || '0') || 0;
    subtextEl.textContent = formatter.formatSubtext(fieldId, val, { sip: sipVal });

    if (fieldId === 'sip') {
        const stepupEl = dom.getElement('stepup_subtext');
        if (stepupEl) {
            const stepupVal = parseFloat(dom.getValue('stepup') || '0') || 0;
            stepupEl.textContent = formatter.formatSubtext('stepup', stepupVal, { sip: val });
        }
    }
}

export function updateWordBadge(dom: DOMAdapter, formatter: CurrencyFormatter, fieldId: string, val: number): void {
    const badgeEl = dom.getElement(`${fieldId}_word_badge`);
    if (!badgeEl) return;
    const text = formatter.formatWordBadge(val);
    badgeEl.textContent = text;
    badgeEl.style.display = text ? 'inline-block' : 'none';
}

export function updatePresetChips(dom: DOMAdapter, fieldId: string, currentVal: number): void {
    const chips = dom.getElements<HTMLButtonElement>(`button[data-preset-for="${fieldId}"]`);
    const inactiveClasses = [
        'bg-slate-50', 'text-slate-700', 'border-slate-200/90', 'hover:border-slate-300', 'hover:bg-slate-100', 'hover:text-slate-900',
        'bg-slate-50/90', 'text-slate-600', 'border-transparent', 'bg-slate-100/80', 'hover:bg-slate-200/80', 'shadow-none'
    ];
    const activeColorClasses = [
        'is-active', 'is-active-rose', 'is-active-amber',
        'bg-emerald-600', 'bg-rose-600', 'bg-amber-600',
        'text-white', 'border-emerald-600', 'border-rose-600', 'border-amber-600', 'shadow-flat'
    ];

    chips.forEach(chip => {
        const presetVal = parseFloat(chip.dataset.presetVal || '');
        const isActive = !isNaN(presetVal) && Math.abs(presetVal - currentVal) < 0.01;
        const isAmberTheme = chip.classList.contains('is-active-amber')
            || chip.closest('[data-field-id]')?.querySelector('.accent-amber-500, .accent-amber-600') !== null;
        const isRoseTheme = chip.classList.contains('is-active-rose')
            || chip.closest('[data-field-id]')?.querySelector('.accent-rose-500, .accent-rose-600') !== null;

        if (isActive) {
            chip.setAttribute('aria-pressed', 'true');
            chip.classList.remove(...inactiveClasses);
            if (isAmberTheme) {
                chip.classList.add('is-active-amber', 'bg-amber-600', 'text-white', 'border-amber-600', 'shadow-flat');
            } else if (isRoseTheme) {
                chip.classList.add('is-active-rose', 'bg-rose-600', 'text-white', 'border-rose-600', 'shadow-flat');
            } else {
                chip.classList.add('is-active', 'bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-flat');
            }
        } else {
            chip.setAttribute('aria-pressed', 'false');
            chip.classList.remove(...activeColorClasses);
            chip.classList.add('border-transparent', 'bg-slate-100/80', 'text-slate-600', 'hover:bg-slate-200/80', 'hover:text-slate-900', 'shadow-none');
        }
    });
}

export function updateSliderAria(formatter: CurrencyFormatter, rangeEl: HTMLInputElement, val: number | string): void {
    rangeEl.setAttribute('aria-valuenow', String(val));
    const fieldId = rangeEl.id.replace(/_range$/, '');
    const readableText = formatter.formatAriaAnnouncement(fieldId, Number(val));
    rangeEl.setAttribute('aria-valuetext', readableText);
    A11yAnnouncer.announce(readableText, 700);
}

export function showFieldError(dom: DOMAdapter, fieldId: string, message: string): void {
    const errorEl = dom.getElement(`${fieldId}_error`);
    if (!errorEl) return;
    errorEl.textContent = message;
    errorEl.classList.remove('hidden');
    const input = dom.getElement(fieldId);
    if (input) input.classList.add('border-rose-400', 'bg-rose-50');
}

export function clearFieldError(dom: DOMAdapter, fieldId: string): void {
    const errorEl = dom.getElement(`${fieldId}_error`);
    if (!errorEl) return;
    errorEl.textContent = '';
    errorEl.classList.add('hidden');
    const input = dom.getElement(fieldId);
    if (input) input.classList.remove('border-rose-400', 'bg-rose-50');
}
