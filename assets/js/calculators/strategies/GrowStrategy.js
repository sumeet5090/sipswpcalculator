import { CalculatorStrategy } from './CalculatorStrategy.js';

export class GrowStrategy extends CalculatorStrategy {
    execute(inputs) {
        // Grow strategy simply returns the inputs as they are, without goal-seeking
        return inputs;
    }
}
