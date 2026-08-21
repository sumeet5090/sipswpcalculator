import { DOMAdapter } from '../../adapters/DOMAdapter';
import { InputValidator } from '../InputValidator';

export class StepperController {
    private dom: DOMAdapter;
    private validator: InputValidator;
    private onValueChange: (fieldId: string, value: number) => void;

    constructor(
        dom: DOMAdapter,
        validator: InputValidator,
        onValueChange: (fieldId: string, value: number) => void
    ) {
        this.dom = dom;
        this.validator = validator;
        this.onValueChange = onValueChange;
    }

    /**
     * Bind click listeners to all micro-stepper (+ / -) buttons.
     */
    init(): void {
        const steppers = document.querySelectorAll<HTMLButtonElement>('button[data-step-action][data-step-for]');
        steppers.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const fieldId = btn.dataset.stepFor;
                const action = btn.dataset.stepAction;
                if (!fieldId || !action) return;

                const input = this.dom.getElement<HTMLInputElement>(fieldId);
                const range = this.dom.getElement<HTMLInputElement>(`${fieldId}_range`);
                if (!input) return;

                const currentVal = parseFloat(input.value) || 0;
                let step = parseFloat(btn.dataset.stepVal || '1');
                if (isNaN(step) || step <= 0) step = 1;

                // Handle precision calculation for floating point steps (e.g. 0.5% inflation or rate)
                const isFloatStep = step % 1 !== 0;
                let nextVal = action === 'inc' ? (currentVal + step) : (currentVal - step);
                if (isFloatStep) {
                    nextVal = parseFloat(nextVal.toFixed(2));
                } else {
                    nextVal = Math.round(nextVal);
                }

                // Strictly clamp to bounds via validator
                const validated = this.validator.validate(fieldId, nextVal);

                input.value = String(validated);
                if (range) {
                    const defaultMax = parseFloat(range.getAttribute('max') || '100000');
                    if (validated > defaultMax) {
                        range.max = String(validated);
                    }
                    range.value = String(validated);
                }

                // Dispatch input event on input element to trigger reactive cascade
                this.onValueChange(fieldId, validated);
            });
        });
    }
}
