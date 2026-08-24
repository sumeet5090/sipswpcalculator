import { DOMAdapter } from '../../adapters/DOMAdapter';
import { A11yAnnouncer } from '../helpers/A11yAnnouncer';

/**
 * KeyboardNavigationController
 * Global FinTech hotkey bindings and roving accessibility tabindex manager.
 */
export class KeyboardNavigationController {
    private dom: DOMAdapter;
    private onSwitchToSwp: () => void;
    private onSwitchToSip: () => void;
    private isInitialized = false;

    constructor(
        dom: DOMAdapter,
        onSwitchToSip: () => void,
        onSwitchToSwp: () => void
    ) {
        this.dom = dom;
        this.onSwitchToSip = onSwitchToSip;
        this.onSwitchToSwp = onSwitchToSwp;
    }

    public init(): void {
        if (typeof window === 'undefined' || this.isInitialized) return;
        this.isInitialized = true;

        window.addEventListener('keydown', (e: KeyboardEvent) => {
            // Guard: Do not trigger if typing inside text inputs, textareas, or content-editable elements
            const activeEl = document.activeElement;
            const isTyping = activeEl && (
                activeEl.tagName === 'INPUT' ||
                activeEl.tagName === 'TEXTAREA' ||
                (activeEl as HTMLElement).isContentEditable
            );

            if (isTyping) return;

            // If user is holding Alt / Option key
            if (e.altKey && !e.ctrlKey && !e.metaKey) {
                const key = e.key.toLowerCase();

                if (key === 's') {
                    // Alt + S: Focus SIP Amount
                    e.preventDefault();
                    this.onSwitchToSip();
                    const sipInput = this.dom.getElement<HTMLInputElement>('sip');
                    if (sipInput) {
                        sipInput.focus();
                        sipInput.select();
                    }
                    A11yAnnouncer.announce('Navigated to Monthly SIP input');
                } else if (key === 'w') {
                    // Alt + W: Focus SWP Withdrawal
                    e.preventDefault();
                    this.onSwitchToSwp();
                    const swpInput = this.dom.getElement<HTMLInputElement>('swp_withdrawal');
                    if (swpInput) {
                        swpInput.focus();
                        swpInput.select();
                    }
                    A11yAnnouncer.announce('Navigated to Monthly SWP Withdrawal input');
                } else if (key === 'p') {
                    // Alt + P: Open PDF Modal
                    e.preventDefault();
                    const pdfModal = this.dom.getElement<HTMLDialogElement>('pdfModal');
                    if (pdfModal && typeof pdfModal.showModal === 'function') {
                        pdfModal.showModal();
                    }
                }
            }
        });
    }
}
