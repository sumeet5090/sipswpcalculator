export class CalculatorStrategy {
    constructor(domAdapter, validator) {
        this.dom = domAdapter;
        this.validator = validator;
    }

    /**
     * Executes the strategy-specific calculation or logic.
     * @param {Object} inputs - The base validated inputs
     * @returns {Object} - The modified inputs after strategy processing
     */
    execute(inputs) {
        throw new Error("Method 'execute()' must be implemented.");
    }
}
