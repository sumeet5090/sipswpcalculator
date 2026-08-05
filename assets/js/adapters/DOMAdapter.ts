export class DOMAdapter {
    private cache: Map<string, HTMLElement | null> = new Map();

    /**
     * Get an element by ID, strictly enforcing its existence if required.
     */
    getElement<T extends HTMLElement = HTMLElement>(id: string, required: boolean = false): T | null {
        if (!this.cache.has(id)) {
            const el = document.getElementById(id) as T | null;
            if (!el && required) {
                throw new Error(`[DOMAdapter] Critical Error: Required element with ID '${id}' was not found in the DOM. This indicates a structural mismatch between the Strategy and the HTML template.`);
            }
            this.cache.set(id, el);
        }
        return this.cache.get(id) as T | null;
    }

    /**
     * Get the value of an input element.
     */
    getValue(id: string, required: boolean = false): string | undefined {
        const el = this.getElement<HTMLInputElement>(id, required);
        return el ? el.value : undefined;
    }

    /**
     * Set the value of an input element.
     */
    setValue(id: string, value: string | number): void {
        const el = this.getElement<HTMLInputElement>(id, false);
        if (el) {
            el.value = String(value);
            if (el.hasAttribute('aria-valuenow')) {
                el.setAttribute('aria-valuenow', String(value));
            }
        }
    }

    /**
     * Clear cached DOM element references to handle dynamic DOM re-rendering.
     */
    clearCache(): void {
        this.cache.clear();
    }
}
