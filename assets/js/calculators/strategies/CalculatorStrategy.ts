import { DOMAdapter } from '../../adapters/DOMAdapter';
import { InputValidator } from '../InputValidator';
import { InvestmentInputs } from '../../types';

export abstract class CalculatorStrategy {
    protected dom: DOMAdapter;
    protected validator: InputValidator;

    constructor(domAdapter: DOMAdapter, validator: InputValidator) {
        this.dom = domAdapter;
        this.validator = validator;
    }

    /**
     * Executes the strategy-specific calculation or logic.
     */
    abstract execute(inputs: InvestmentInputs): InvestmentInputs;
}
