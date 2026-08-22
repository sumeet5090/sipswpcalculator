import { DOMAdapter } from '../../adapters/DOMAdapter';
import { SliderManager } from '../SliderManager';

export interface QuizState {
    goal?: string;
    sip: number;
    years: number;
    stepup: number;
    rate: number;
    enableSwp: boolean;
    swp: number;
    corpus?: number;
}

export class WealthQuizController {
    private dom: DOMAdapter;
    private sliderManager: SliderManager;
    private onApply: () => void;
    private state: QuizState = {
        sip: 15000,
        years: 10,
        stepup: 10,
        rate: 12,
        enableSwp: false,
        swp: 0
    };

    constructor(dom: DOMAdapter, sliderManager: SliderManager, onApply: () => void) {
        this.dom = dom;
        this.sliderManager = sliderManager;
        this.onApply = onApply;
    }

    init(): void {
        const step1 = this.dom.getElement('quiz-step-1');
        const step2 = this.dom.getElement('quiz-step-2');
        const step3 = this.dom.getElement('quiz-step-3');
        if (!step1 || !step2 || !step3) return;

        // Step 1 choice listeners
        step1.querySelectorAll<HTMLButtonElement>('.quiz-choice').forEach(btn => {
            btn.addEventListener('click', () => {
                const goal = btn.dataset.goal;
                this.state.goal = goal;

                if (btn.dataset.years) this.state.years = parseFloat(btn.dataset.years);
                if (btn.dataset.stepup) this.state.stepup = parseFloat(btn.dataset.stepup);
                if (btn.dataset.enableSwp) this.state.enableSwp = btn.dataset.enableSwp === 'true';
                if (btn.dataset.swp) this.state.swp = parseFloat(btn.dataset.swp);
                if (btn.dataset.corpus) this.state.corpus = parseFloat(btn.dataset.corpus);

                this.goToStep(2);
            });
        });

        // Step 2 choice listeners
        step2.querySelectorAll<HTMLButtonElement>('.quiz-choice').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.dataset.sip) this.state.sip = parseFloat(btn.dataset.sip);
                this.goToStep(3);
            });
        });

        // Step 3 choice listeners
        step3.querySelectorAll<HTMLButtonElement>('.quiz-choice').forEach(btn => {
            btn.addEventListener('click', () => {
                step3.querySelectorAll('.quiz-choice').forEach(c => {
                    c.classList.remove('border-emerald-500', 'border-2', 'bg-white', 'shadow-sm');
                    c.classList.add('border-slate-200', 'bg-slate-50/90');
                });
                btn.classList.add('border-emerald-500', 'border-2', 'bg-white', 'shadow-sm');
                btn.classList.remove('border-slate-200', 'bg-slate-50/90');
            });
        });

        // Apply plan button
        const applyBtn = document.getElementById('apply-quiz-plan-btn');
        if (applyBtn) {
            applyBtn.addEventListener('click', () => this.applyPlan());
        }
    }

    private goToStep(step: number): void {
        const step1 = this.dom.getElement('quiz-step-1');
        const step2 = this.dom.getElement('quiz-step-2');
        const step3 = this.dom.getElement('quiz-step-3');
        const label = this.dom.getElement('quiz-step-label');

        if (step1) step1.classList.toggle('hidden', step !== 1);
        if (step2) step2.classList.toggle('hidden', step !== 2);
        if (step3) step3.classList.toggle('hidden', step !== 3);

        if (label) label.textContent = `Step ${step} of 3`;
    }

    private applyPlan(): void {
        this.sliderManager.updateFieldValue('sip', this.state.sip);
        this.sliderManager.updateFieldValue('years', this.state.years);
        this.sliderManager.updateFieldValue('rate', this.state.rate);
        this.sliderManager.updateFieldValue('stepup', this.state.stepup);

        if (this.state.corpus) {
            this.sliderManager.updateFieldValue('corpus', this.state.corpus);
        }
        if (this.state.swp) {
            this.sliderManager.updateFieldValue('swp_withdrawal', this.state.swp);
        }

        const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
        if (swpToggle) {
            swpToggle.checked = this.state.enableSwp;
        }

        this.onApply();

        const calcSection = this.dom.getElement('calculator-section');
        if (calcSection) {
            calcSection.scrollIntoView({ behavior: 'smooth' });
        }
    }
}
