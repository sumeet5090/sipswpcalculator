import { DOMAdapter } from '../../adapters/DOMAdapter';
import { InputValidator } from '../InputValidator';
import { AudioFeedbackController } from './AudioFeedbackController';

export class StepperController {
    private dom: DOMAdapter;
    private validator: InputValidator;
    private onValueChange: (fieldId: string, value: number) => void;
    private audio?: AudioFeedbackController;
    private holdTimer: ReturnType<typeof setTimeout> | null = null;
    private stepInterval: ReturnType<typeof setInterval> | null = null;

    constructor(
        dom: DOMAdapter,
        validator: InputValidator,
        onValueChange: (fieldId: string, value: number) => void,
        audio?: AudioFeedbackController
    ) {
        this.dom = dom;
        this.validator = validator;
        this.onValueChange = onValueChange;
        this.audio = audio;
    }

    private executeStep(btn: HTMLButtonElement, multiplier: number = 1): void {
        const fieldId = btn.dataset.stepFor;
        const action = btn.dataset.stepAction;
        if (!fieldId || !action) return;

        const input = this.dom.getElement<HTMLInputElement>(fieldId);
        const range = this.dom.getElement<HTMLInputElement>(`${fieldId}_range`);
        if (!input) return;

        const currentVal = parseFloat(input.value) || 0;
        let baseStep = parseFloat(btn.dataset.stepVal || '1');
        if (isNaN(baseStep) || baseStep <= 0) baseStep = 1;

        const step = baseStep * multiplier;
        const isFloatStep = baseStep % 1 !== 0;
        let nextVal = action === 'inc' ? (currentVal + step) : (currentVal - step);
        if (isFloatStep) {
            nextVal = parseFloat(nextVal.toFixed(2));
        } else {
            nextVal = Math.round(nextVal);
        }

        const validated = this.validator.validate(fieldId, nextVal);

        input.value = String(validated);
        if (range) {
            const defaultMax = parseFloat(range.getAttribute('max') || '100000');
            if (validated > defaultMax) {
                range.max = String(validated);
            }
            range.value = String(validated);
        }

        this.audio?.playTick(multiplier > 1 ? 480 : 380, 0.012);
        this.audio?.vibrate(5);
        this.onValueChange(fieldId, validated);
    }

    private clearHold(): void {
        if (this.holdTimer) {
            clearTimeout(this.holdTimer);
            this.holdTimer = null;
        }
        if (this.stepInterval) {
            clearInterval(this.stepInterval);
            this.stepInterval = null;
        }
    }

    /**
     * Bind click and hold-to-accelerate listeners to all micro-stepper (+ / -) buttons.
     */
    init(): void {
        const steppers = document.querySelectorAll<HTMLButtonElement>('button[data-step-action][data-step-for]');
        steppers.forEach(btn => {
            const endHold = () => {
                this.clearHold();
                window.removeEventListener('pointerup', endHold);
                window.removeEventListener('touchend', endHold);
                window.removeEventListener('touchcancel', endHold);
            };

            const startHold = (e: Event) => {
                e.preventDefault();
                this.clearHold();
                this.executeStep(btn, 1);

                window.addEventListener('pointerup', endHold, { once: true });
                window.addEventListener('touchend', endHold, { once: true });
                window.addEventListener('touchcancel', endHold, { once: true });

                let holdDuration = 0;
                this.holdTimer = setTimeout(() => {
                    const stepLoop = () => {
                        holdDuration += 40;
                        let multiplier = 1;
                        if (holdDuration > 2500) multiplier = 10;
                        else if (holdDuration > 1500) multiplier = 5;
                        else if (holdDuration > 800) multiplier = 2;

                        this.executeStep(btn, multiplier);
                        const nextInterval = Math.max(15, 60 - Math.floor(holdDuration / 50));
                        this.stepInterval = setTimeout(stepLoop, nextInterval) as unknown as ReturnType<typeof setInterval>;
                    };
                    stepLoop();
                }, 280);
            };

            btn.addEventListener('mousedown', startHold);
            btn.addEventListener('touchstart', startHold, { passive: false });
            btn.addEventListener('mouseup', endHold);
            btn.addEventListener('mouseleave', endHold);
            btn.addEventListener('touchend', endHold);
            btn.addEventListener('touchcancel', endHold);
            btn.addEventListener('contextmenu', (e) => e.preventDefault());
        });
    }
}
