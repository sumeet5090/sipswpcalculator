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
            if (el) {
                this.cache.set(id, el);
            }
            return el;
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
     * Get multiple elements matching a CSS selector or class name.
     */
    getElements<T extends HTMLElement = HTMLElement>(selector: string): T[] {
        const isSelector = selector.startsWith('.') ||
            selector.startsWith('#') ||
            selector.startsWith('[') ||
            selector.includes(' ') ||
            selector.includes('>') ||
            selector.includes(':');

        const query = isSelector ? selector : `.${selector}`;
        try {
            const results = document.querySelectorAll<T>(query);
            if (results.length > 0) {
                return Array.from(results);
            }
            // Fallback: test if selector was a valid HTML tag name
            return Array.from(document.querySelectorAll<T>(selector));
        } catch (_err) {
            return [];
        }
    }

    /**
     * Safely query current window viewport height.
     */
    getViewportHeight(): number {
        return window.innerHeight || document.documentElement.clientHeight || 0;
    }

    /**
     * Copy text to system clipboard via navigator.clipboard with fallback textarea element creation.
     */
    copyToClipboard(text: string, onSuccess: () => void): void {
        if (typeof navigator !== 'undefined' && navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(onSuccess).catch(() => {
                this.fallbackCopyToClipboard(text, onSuccess);
            });
        } else {
            this.fallbackCopyToClipboard(text, onSuccess);
        }
    }

    private fallbackCopyToClipboard(text: string, onSuccess: () => void): void {
        try {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            textArea.remove();
            onSuccess();
        } catch {
            // Silently complete if clipboard is fully restricted
        }
    }

    /**
     * Clear cached DOM element references to handle dynamic DOM re-rendering.
     */
    clearCache(): void {
        this.cache.clear();
    }
}
