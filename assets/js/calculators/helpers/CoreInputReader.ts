import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { CurrencyFormatter } from '../CurrencyHelper.ts';
import { InputValidator } from '../InputValidator.ts';
import { SliderManager } from '../SliderManager.ts';
import { CalculatorRegistry } from '../constants/CalculatorRegistry.ts';
import { InvestmentInputs } from '../../types';

export function readCoreInputs(dom: DOMAdapter, validator: InputValidator): InvestmentInputs {
    const appEl = dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');
    const mode = appEl?.dataset?.mode ?? 'combo';

    if (CalculatorRegistry.isSpecializedMode(mode)) {
        return {
            sip: 0,
            years: 0,
            rate: 0,
            stepup: 0,
            inflation: 0,
            lumpsum: 0,
            enable_swp: false,
            swp_withdrawal: 0,
            swp_years: 0,
            swp_stepup: 0,
            swp_rate: 0
        };
    }

    if (mode === 'swp') {
        return {
            sip: 0,
            years: 0,
            rate: 0,
            stepup: 0,
            inflation: validator.validate('inflation', dom.getValue('inflation') || 0),
            lumpsum: validator.validate('corpus', dom.getValue('corpus') || 0),
            enable_swp: true,
            swp_withdrawal: validator.validate('swp_withdrawal', dom.getValue('swp_withdrawal') || 0),
            swp_years: validator.validate('swp_years', dom.getValue('swp_years') || 0),
            swp_stepup: validator.validate('swp_stepup', dom.getValue('swp_stepup') || 0),
            swp_rate: validator.validate('swp_rate', dom.getValue('swp_rate') || 0)
        };
    }

    if (mode === 'lumpsum') {
        return {
            sip: 0,
            years: validator.validate('years', dom.getValue('years') || 0),
            rate: validator.validate('rate', dom.getValue('rate') || 0),
            stepup: 0,
            inflation: validator.validate('inflation', dom.getValue('inflation') || 0),
            lumpsum: validator.validate('lumpsum', dom.getValue('lumpsum') || 0),
            enable_swp: false,
            swp_withdrawal: 0,
            swp_years: 0,
            swp_stepup: 0,
            swp_rate: 0
        };
    }

    return {
        sip: validator.validate('sip', dom.getValue('sip') || 0),
        years: validator.validate('years', dom.getValue('years') || 0),
        rate: validator.validate('rate', dom.getValue('rate') || 0),
        stepup: validator.validate('stepup', dom.getValue('stepup') || 0),
        inflation: validator.validate('inflation', dom.getValue('inflation') || 0),
        lumpsum: validator.validate('lumpsum', dom.getValue('lumpsum') || 0),
        enable_swp: (dom.getElement<HTMLInputElement>('enable_swp')?.checked) || false,
        swp_withdrawal: validator.validate('swp_withdrawal', dom.getValue('swp_withdrawal') || 0),
        swp_years: validator.validate('swp_years', dom.getValue('swp_years') || 0),
        swp_stepup: validator.validate('swp_stepup', dom.getValue('swp_stepup') || 0),
        swp_rate: validator.validate('swp_rate', dom.getValue('swp_rate') || 0)
    };
}

export function buildExportPayload(
    dom: DOMAdapter,
    formatter: CurrencyFormatter,
    validator: InputValidator
): Record<string, string | number | boolean> {
    const appEl = dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');
    const mode = appEl?.dataset?.mode ?? 'combo';

    const payload: Record<string, string | number | boolean> = {
        calculator_type: mode,
        currency: formatter.getCurrency(),
        currency_symbol: formatter.getSymbol(),
    };

    if (CalculatorRegistry.isSpecializedMode(mode)) {
        const form = dom.getElement<HTMLFormElement>('calculator-form');
        if (form) {
            const fields = form.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input, select');
            fields.forEach(field => {
                const name = field.name || field.id;
                if (!name || name === 'website_url' || name.endsWith('_range')) return;
                if (field instanceof HTMLInputElement && field.type === 'radio') {
                    if (field.checked) {
                        payload[name] = field.value;
                    }
                } else if (field instanceof HTMLInputElement && field.type === 'checkbox') {
                    payload[name] = field.checked ? '1' : '0';
                } else {
                    payload[name] = field.value;
                }
            });
        }
        return payload;
    }

    const baseInputs = readCoreInputs(dom, validator);
    Object.entries(baseInputs).forEach(([k, v]) => {
        payload[k] = v;
    });
    if (mode === 'swp') {
        payload['corpus'] = baseInputs.lumpsum;
    }
    const targetCorpusEl = dom.getElement<HTMLInputElement>('target_corpus');
    if (targetCorpusEl && targetCorpusEl.value) {
        payload['target_corpus'] = targetCorpusEl.value;
    }
    return payload;
}

export function applyCoreRestoredInputs(
    dom: DOMAdapter,
    sliderManager: SliderManager,
    inputs: InvestmentInputs,
    syncLumpsum: (val: number, programmatic: boolean) => void,
    syncSwpToggleState: () => void
): void {
    const appEl = dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');
    const mode = appEl?.dataset?.mode ?? 'combo';
    const isSwpMode = (mode === 'swp');

    if (!isSwpMode) {
        if (inputs.sip !== undefined && inputs.sip > 0) sliderManager.updateFieldValue('sip', inputs.sip, true);
        if (inputs.years !== undefined && inputs.years > 0) sliderManager.updateFieldValue('years', inputs.years, true);
        if (inputs.rate !== undefined && inputs.rate > 0) sliderManager.updateFieldValue('rate', inputs.rate, true);
        if (inputs.stepup !== undefined) sliderManager.updateFieldValue('stepup', inputs.stepup, true);
    }
    if (inputs.inflation !== undefined) sliderManager.updateFieldValue('inflation', inputs.inflation, true);
    if (inputs.lumpsum !== undefined) {
        sliderManager.updateFieldValue('lumpsum', inputs.lumpsum, true);
        sliderManager.updateFieldValue('corpus', inputs.lumpsum, true);
        syncLumpsum(inputs.lumpsum, inputs.lumpsum <= 0);
    }
    if (inputs.swp_withdrawal !== undefined && inputs.swp_withdrawal > 0) sliderManager.updateFieldValue('swp_withdrawal', inputs.swp_withdrawal, true);
    if (inputs.swp_years !== undefined && inputs.swp_years > 0) sliderManager.updateFieldValue('swp_years', inputs.swp_years, true);
    if (inputs.swp_rate !== undefined && inputs.swp_rate > 0) sliderManager.updateFieldValue('swp_rate', inputs.swp_rate, true);
    if (inputs.swp_stepup !== undefined) sliderManager.updateFieldValue('swp_stepup', inputs.swp_stepup, true);

    const swpToggle = dom.getElement<HTMLInputElement>('enable_swp');
    if (swpToggle && inputs.enable_swp !== undefined && swpToggle.checked !== inputs.enable_swp) {
        swpToggle.checked = inputs.enable_swp;
        syncSwpToggleState();
    }
}
