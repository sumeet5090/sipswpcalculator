import { CalculatorStrategy } from './CalculatorStrategy';
import { InvestmentInputs } from '../../types';

export class GrowStrategy extends CalculatorStrategy {
    override execute(inputs: InvestmentInputs): InvestmentInputs {
        // Grow strategy simply returns the inputs as they are, without goal-seeking
        return inputs;
    }
}
