export class DOMAdapter {
    constructor() {
        this.cache = new Map();
    }

    /**
     * Get an element by ID, strictly enforcing its existence if required.
     * @param {string} id - The DOM element ID
     * @param {boolean} required - If true, throws an error if the element is not found
     * @returns {HTMLElement|null}
     */
    getElement(id, required = false) {
        if (!this.cache.has(id)) {
            const el = document.getElementById(id);
            if (!el && required) {
                throw new Error(`[DOMAdapter] Critical Error: Required element with ID '${id}' was not found in the DOM. This indicates a structural mismatch between the Strategy and the HTML template.`);
            }
            this.cache.set(id, el);
        }
        return this.cache.get(id);
    }

    /**
     * Get the value of an input element.
     * @param {string} id - The input element ID
     * @param {boolean} required - Whether the element is required
     * @returns {string|undefined}
     */
    getValue(id, required = false) {
        const el = this.getElement(id, required);
        return el ? el.value : undefined;
    }

    /**
     * Set the value of an input element.
     * @param {string} id - The input element ID
     * @param {string|number} value - The value to set
     */
    setValue(id, value) {
        const el = this.getElement(id, false);
        if (el) {
            el.value = value;
            if (el.hasAttribute('aria-valuenow')) {
                el.setAttribute('aria-valuenow', value);
            }
        }
    }
}
