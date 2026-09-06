/**
 * StrategyBlueprintController.ts
 * Manages 1-click strategic wealth blueprints and synchronizes active state
 * with the in-form conversational narrative strip and interactive sliders.
 * Strictly adheres to SOLID, DRY, WCAG AAA accessibility standards and pure light-mode aesthetics.
 */

import { DOMAdapter } from '../../adapters/DOMAdapter';
import { SliderManager } from '../SliderManager';
import { AnalyticsService } from '../AnalyticsLogger';
import { A11yAnnouncer } from '../helpers/A11yAnnouncer';
import { InvestmentInputs } from '../../types';

export interface StrategyBlueprintPreset {
    id: string;
    title: string;
    description: string;
    sip: number;
    years: number;
    rate: number;
    stepup: number;
    lumpsum?: number;
    corpus?: number;
    enableSwp?: boolean;
    swp?: number;
    swpYears?: number;
    swpRate?: number;
    swpHike?: number;
}

export class StrategyBlueprintController {
    private dom: DOMAdapter;
    private sliderManager: SliderManager;
    private analytics: AnalyticsService;
    private onSyncSwp: () => void;
    private onCalculate: () => void;
    private activeBlueprintId: string | null = null;

    private readonly blueprints: Record<string, StrategyBlueprintPreset> = {
        first_crore: {
            id: 'first_crore',
            title: '🎯 First ₹1 Crore Rush',
            description: '₹25k/mo SIP @ 12% CAGR with 10% annual top-up to cross ₹1 Cr fast.',
            sip: 25000,
            years: 9,
            rate: 12,
            stepup: 10,
            lumpsum: 0,
            enableSwp: false
        },
        fire_retirement: {
            id: 'fire_retirement',
            title: '🏖️ FIRE Early Retirement',
            description: '15 Yrs Accumulation → 25 Yrs of inflation-adjusted monthly pension.',
            sip: 50000,
            years: 15,
            rate: 12,
            stepup: 10,
            lumpsum: 0,
            enableSwp: true,
            swp: 120000,
            swpYears: 25,
            swpRate: 8,
            swpHike: 6
        },
        child_education: {
            id: 'child_education',
            title: '🎓 Child Higher Education',
            description: '₹15k/mo SIP + ₹1L seed corpus compounding to ₹1.5+ Cr for college fees.',
            sip: 15000,
            years: 18,
            rate: 12,
            stepup: 10,
            lumpsum: 100000,
            enableSwp: false
        },
        capital_preservation: {
            id: 'capital_preservation',
            title: '🛡️ Senior Citizen Monthly Income',
            description: '₹50 Lakh corpus generating ₹35,000/mo steady tax-efficient retirement cashflow.',
            sip: 0,
            years: 0,
            rate: 8,
            stepup: 0,
            lumpsum: 5000000,
            corpus: 5000000,
            enableSwp: true,
            swp: 35000,
            swpYears: 20,
            swpRate: 8,
            swpHike: 0
        }
    };

    constructor(
        dom: DOMAdapter,
        sliderManager: SliderManager,
        analytics: AnalyticsService,
        onSyncSwp: () => void,
        onCalculate: () => void
    ) {
        this.dom = dom;
        this.sliderManager = sliderManager;
        this.analytics = analytics;
        this.onSyncSwp = onSyncSwp;
        this.onCalculate = onCalculate;
    }

    public init(): void {
        this.bindButtons();
    }

    /**
     * Attaches click & keyboard accessibility listeners to all strategy blueprint buttons.
     */
    public bindButtons(): void {
        const buttons = this.dom.getElements<HTMLButtonElement>('.persona-btn');
        buttons.forEach(btn => {
            const personaId = btn.dataset.persona;
            if (!personaId || !this.blueprints[personaId]) return;

            const handleActivation = (e: Event) => {
                e.preventDefault();
                this.applyBlueprint(personaId);
            };

            btn.addEventListener('click', handleActivation);
            btn.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.applyBlueprint(personaId);
                }
            });
        });
    }

    /**
     * Injects the blueprint parameters into sliders, updates active styling & syncs narrative strip.
     */
    public applyBlueprint(personaId: string): void {
        const preset = this.blueprints[personaId];
        if (!preset) return;

        this.activeBlueprintId = personaId;
        this.analytics.setStrategyStarterUsed(personaId);

        // 1. Inject field values via SliderManager
        this.sliderManager.updateFieldValue('sip', preset.sip, true);
        this.sliderManager.updateFieldValue('years', preset.years, true);
        this.sliderManager.updateFieldValue('rate', preset.rate, true);
        this.sliderManager.updateFieldValue('stepup', preset.stepup, true);

        if (preset.lumpsum !== undefined) {
            this.sliderManager.updateFieldValue('lumpsum', preset.lumpsum, true);
        }
        if (preset.corpus !== undefined) {
            this.sliderManager.updateFieldValue('corpus', preset.corpus, true);
        }

        if (preset.swp !== undefined) {
            this.sliderManager.updateFieldValue('swp_withdrawal', preset.swp, true);
        }
        if (preset.swpYears !== undefined) {
            this.sliderManager.updateFieldValue('swp_years', preset.swpYears, true);
        }
        if (preset.swpRate !== undefined) {
            this.sliderManager.updateFieldValue('swp_rate', preset.swpRate, true);
        }
        if (preset.swpHike !== undefined) {
            this.sliderManager.updateFieldValue('swp_stepup', preset.swpHike, true);
        }

        // 2. Synchronize SWP switch
        const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
        if (swpToggle) {
            swpToggle.checked = Boolean(preset.enableSwp);
            this.onSyncSwp();
        }

        // 3. Update Visual Active States across blueprint cards
        this.updateButtonsVisualState(personaId);

        // 4. Update in-form Live Narrative Strip Badge
        this.updateNarrativeBadge(preset.title);

        // 5. Trigger Calculation
        this.onCalculate();

        // 6. Tactile haptics & accessibility announcement
        if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
            try {
                navigator.vibrate([15, 30, 15]);
            } catch {}
        }

        A11yAnnouncer.announce(`Applied ${preset.title} blueprint.`);
    }

    /**
     * Resets active blueprint selection when user manual tuning causes drift from preset.
     */
    public resetActiveState(): void {
        if (!this.activeBlueprintId) return;
        this.activeBlueprintId = null;

        const buttons = this.dom.getElements<HTMLButtonElement>('.persona-btn');
        buttons.forEach(btn => {
            btn.setAttribute('aria-pressed', 'false');
            btn.classList.remove(
                'border-emerald-500',
                'bg-emerald-50/80',
                'ring-2',
                'ring-emerald-500/25',
                'shadow-card'
            );
            btn.classList.add('border-slate-200/90', 'bg-white/95');
        });

        const badge = this.dom.getElement('narrative-strategy-badge');
        if (badge) {
            badge.textContent = '✨ Custom Strategy';
            badge.className = 'inline-flex items-center gap-1 font-bold text-micro px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-300 shadow-flat shrink-0 transition-all';
        }
    }

    /**
     * Compares active inputs against current blueprint to detect manual user deviation.
     */
    public syncWithInputs(inputs: InvestmentInputs): void {
        if (!this.activeBlueprintId) return;
        const preset = this.blueprints[this.activeBlueprintId];
        if (!preset) return;

        const isMatch =
            inputs.sip === preset.sip &&
            inputs.years === preset.years &&
            inputs.rate === preset.rate &&
            inputs.stepup === preset.stepup &&
            (preset.enableSwp === undefined || inputs.enable_swp === preset.enableSwp);

        if (!isMatch) {
            this.resetActiveState();
        }
    }

    private updateButtonsVisualState(activeId: string): void {
        const buttons = this.dom.getElements<HTMLButtonElement>('.persona-btn');
        buttons.forEach(btn => {
            const isSelected = btn.dataset.persona === activeId;
            btn.setAttribute('aria-pressed', String(isSelected));

            if (isSelected) {
                btn.classList.add(
                    'border-emerald-500',
                    'bg-emerald-50/80',
                    'ring-2',
                    'ring-emerald-500/25',
                    'shadow-card'
                );
                btn.classList.remove('border-slate-200/90', 'bg-white/95');
            } else {
                btn.classList.remove(
                    'border-emerald-500',
                    'bg-emerald-50/80',
                    'ring-2',
                    'ring-emerald-500/25',
                    'shadow-card'
                );
                btn.classList.add('border-slate-200/90', 'bg-white/95');
            }
        });
    }

    private updateNarrativeBadge(title: string): void {
        const badge = this.dom.getElement('narrative-strategy-badge');
        if (badge) {
            badge.textContent = title;
            badge.className = 'inline-flex items-center gap-1 font-bold text-micro px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-flat shrink-0 transition-all';
        }
    }

    public getActiveBlueprint(): string | null {
        return this.activeBlueprintId;
    }

    public getBlueprints(): Record<string, StrategyBlueprintPreset> {
        return { ...this.blueprints };
    }
}
