/**
 * ArchetypeInjectorController.ts
 * Manages zero-cognitive aspirational persona quick-inject chips for Indian retail investors.
 * Injects pre-calibrated, culturally standard investment models with zero form friction.
 * Strictly adheres to SOLID, DRY, and WCAG AAA accessibility standards.
 */

import { DOMAdapter } from '../../adapters/DOMAdapter';
import { SliderManager } from '../SliderManager';
import { A11yAnnouncer } from '../helpers/A11yAnnouncer';

export interface ArchetypePreset {
    id: string;
    title: string;
    description: string;
    sip: number;
    years: number;
    rate: number;
    stepup: number;
    lumpsum?: number;
}

export class ArchetypeInjectorController {
    private dom: DOMAdapter;
    private sliderManager: SliderManager;

    private readonly archetypes: Record<string, ArchetypePreset> = {
        fresher: {
            id: 'fresher',
            title: 'Tech Fresher',
            description: '₹10K/mo • 10 Yrs • 14% Small/Mid Cap',
            sip: 10000,
            years: 10,
            rate: 14,
            stepup: 10
        },
        lead: {
            id: 'lead',
            title: 'Mid-Career Lead',
            description: '₹50K/mo • 15 Yrs • 12.5% Balanced Growth',
            sip: 50000,
            years: 15,
            rate: 12.5,
            stepup: 10
        },
        fire: {
            id: 'fire',
            title: 'Aggressive FIRE',
            description: '₹1.5L/mo • 12 Yrs • 13% Flexi-Cap',
            sip: 150000,
            years: 12,
            rate: 13,
            stepup: 15
        },
        legacy: {
            id: 'legacy',
            title: 'Family Legacy',
            description: '₹25K/mo • 20 Yrs • 12% Large Cap',
            sip: 25000,
            years: 20,
            rate: 12,
            stepup: 10
        }
    };

    constructor(dom: DOMAdapter, sliderManager: SliderManager) {
        this.dom = dom;
        this.sliderManager = sliderManager;
        this.bindChips();
    }

    /**
     * Binds click and keyboard handlers to all archetype chips in the DOM.
     */
    public bindChips(): void {
        const chips = this.dom.getElements<HTMLButtonElement>('.archetype-chip');
        chips.forEach(chip => {
            const archetypeId = chip.dataset.archetype;
            if (!archetypeId || !this.archetypes[archetypeId]) return;

            const activate = (e: Event) => {
                e.preventDefault();
                this.applyArchetype(archetypeId);
            };

            chip.addEventListener('click', activate);
            chip.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.applyArchetype(archetypeId);
                }
            });
        });
    }

    /**
     * Injects the archetype parameters into the active calculator sliders.
     */
    public applyArchetype(archetypeId: string): void {
        const preset = this.archetypes[archetypeId];
        if (!preset) return;

        // 1. Synchronously update form fields via SliderManager
        this.sliderManager.updateFieldValue('sip', preset.sip, true);
        this.sliderManager.updateFieldValue('years', preset.years, true);
        this.sliderManager.updateFieldValue('rate', preset.rate, true);
        this.sliderManager.updateFieldValue('stepup', preset.stepup, false); // Triggers recalculation

        // 2. Update visual active state across all chips
        const chips = this.dom.getElements<HTMLButtonElement>('.archetype-chip');
        chips.forEach(chip => {
            const isMatch = chip.dataset.archetype === archetypeId;
            chip.setAttribute('aria-pressed', String(isMatch));

            if (isMatch) {
                chip.classList.add('bg-emerald-50', 'text-emerald-900', 'border-emerald-500', 'ring-2', 'ring-emerald-500/20', 'font-semibold');
                chip.classList.remove('bg-slate-50', 'text-slate-700', 'border-slate-200/80');
            } else {
                chip.classList.remove('bg-emerald-50', 'text-emerald-900', 'border-emerald-500', 'ring-2', 'ring-emerald-500/20', 'font-semibold');
                chip.classList.add('bg-slate-50', 'text-slate-700', 'border-slate-200/80');
            }
        });

        // 3. Tactile feedback & accessibility announcement
        if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
            try {
                navigator.vibrate([15, 30, 15]);
            } catch {}
        }

        A11yAnnouncer.announce(`Applied ${preset.title} plan: ₹${preset.sip.toLocaleString('en-IN')} monthly for ${preset.years} years.`);
    }

    public getArchetypes(): Record<string, ArchetypePreset> {
        return { ...this.archetypes };
    }
}
