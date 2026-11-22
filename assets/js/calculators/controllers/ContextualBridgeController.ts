import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { InvestmentInputs } from '../../types';

export interface MilestoneBridgeItem {
    threshold: number;
    url: string;
    label: string;
    action: string;
}

/**
 * ContextualBridgeController.ts
 *
 * Surfaces relevant milestone/corpus hub links contextually based on active
 * slider inputs and thresholds without global window state pollution.
 * Suppresses self-links when the user is already on the target route.
 */
export class ContextualBridgeController {
    private dom: DOMAdapter;
    private currentPath: string;

    private static readonly SIP_MILESTONES: MilestoneBridgeItem[] = [
        { threshold: 5000, url: '/sip-5000-per-month', label: 'Dedicated ₹5k/mo SIP Wealth Blueprint', action: 'Explore' },
        { threshold: 10000, url: '/sip-10000-per-month', label: 'Dedicated ₹10k/mo SIP Wealth Blueprint', action: 'Explore' },
        { threshold: 15000, url: '/sip-15000-per-month', label: 'Dedicated ₹15k/mo Mid-Career Blueprint', action: 'Explore' },
        { threshold: 20000, url: '/sip-20000-per-month', label: 'Dedicated ₹20k/mo Dual-Income Blueprint', action: 'Explore' },
        { threshold: 25000, url: '/sip-25000-per-month', label: 'Dedicated ₹25k/mo IT/Corporate Blueprint', action: 'Explore' },
        { threshold: 50000, url: '/sip-50000-per-month', label: 'Dedicated ₹50k/mo Wealth Accelerator', action: 'Explore' }
    ];

    private static readonly CORPUS_MILESTONES: MilestoneBridgeItem[] = [
        { threshold: 2500000, url: '/25-lakh-swp-calculator', label: 'Dedicated ₹25 Lakh SWP Pension Guide', action: 'View Guide' },
        { threshold: 5000000, url: '/50-lakh-swp-calculator', label: 'Dedicated ₹50 Lakh Tier-2 Pension Blueprint', action: 'View Guide' },
        { threshold: 10000000, url: '/1-crore-swp-calculator', label: 'Dedicated ₹1 Crore Executive Pension Guide', action: 'View Guide' },
        { threshold: 20000000, url: '/2-crore-swp-calculator', label: 'Dedicated ₹2 Crore HNI Distribution Guide', action: 'View Guide' }
    ];

    private static readonly GOAL_MILESTONES: MilestoneBridgeItem[] = [
        { threshold: 10000000, url: '/my-first-crore-calculator', label: 'How to reach ₹1 Crore faster', action: 'View Plan' },
        { threshold: 20000000, url: '/2-crore-sip-calculator', label: 'Dedicated ₹2 Crore Wealth Plan', action: 'View Plan' },
        { threshold: 30000000, url: '/3-crore-sip-calculator', label: 'Dedicated ₹3 Crore FIRE Strategy', action: 'View Plan' },
        { threshold: 50000000, url: '/reach-5-crore-via-sip', label: 'Dedicated ₹5 Crore Wealth Engine', action: 'View Plan' }
    ];

    constructor(dom: DOMAdapter = new DOMAdapter(), currentPath?: string) {
        this.dom = dom;
        if (currentPath) {
            this.currentPath = currentPath;
        } else if (typeof window !== 'undefined') {
            this.currentPath = window.location.pathname;
        } else {
            this.currentPath = '/';
        }
    }

    /**
     * Updates in-flow contextual milestone bridges based on current calculator inputs.
     */
    update(inputs: InvestmentInputs): void {
        this.updateSipBridge(inputs);
        this.updateSwpBridge(inputs);
        this.updateCorpusBridge(inputs);
    }

    private updateSipBridge(inputs: InvestmentInputs): void {
        const bridgeEl = this.dom.getElement('contextual-sip-milestone-bridge');
        if (!bridgeEl) return;

        const currentSip = Math.round(inputs.sip || 0);
        const match = ContextualBridgeController.SIP_MILESTONES.find(m => Math.abs(m.threshold - currentSip) <= 0.01);

        if (match && this.currentPath !== match.url) {
            const msgEl = this.dom.getElement('sip-bridge-message');
            const linkEl = this.dom.getElement<HTMLAnchorElement>('sip-bridge-link');
            const actionEl = this.dom.getElement('sip-bridge-action');

            if (msgEl) msgEl.textContent = match.label;
            if (actionEl) actionEl.textContent = match.action;
            if (linkEl) linkEl.href = match.url;

            bridgeEl.classList.remove('hidden');
        } else {
            bridgeEl.classList.add('hidden');
        }
    }

    private updateSwpBridge(inputs: InvestmentInputs): void {
        const bridgeEl = this.dom.getElement('contextual-swp-milestone-bridge');
        if (!bridgeEl) return;

        const currentWithdrawal = Math.round(inputs.swp_withdrawal || 0);

        // Check if withdrawal aligns with a standard 7.8% annualized benchmark
        // e.g. 16,250 (25L), 32,500 (50L), 65,000 (1 Cr), 1,30,000 (2 Cr)
        let matchUrl: string | null = null;
        let matchLabel: string | null = null;

        if (Math.abs(currentWithdrawal - 16250) <= 250) {
            matchUrl = '/25-lakh-swp-calculator';
            matchLabel = 'Dedicated ₹25 Lakh SWP Pension Guide (₹16,250/mo)';
        } else if (Math.abs(currentWithdrawal - 32500) <= 500) {
            matchUrl = '/50-lakh-swp-calculator';
            matchLabel = 'Dedicated ₹50 Lakh Tier-2 Pension Blueprint (₹32,500/mo)';
        } else if (Math.abs(currentWithdrawal - 65000) <= 1000) {
            matchUrl = '/1-crore-swp-calculator';
            matchLabel = 'Dedicated ₹1 Crore Executive Pension Guide (₹65,000/mo)';
        } else if (Math.abs(currentWithdrawal - 130000) <= 2000) {
            matchUrl = '/2-crore-swp-calculator';
            matchLabel = 'Dedicated ₹2 Crore HNI Distribution Guide (₹1.3L/mo)';
        } else if (inputs.enable_swp && this.currentPath !== '/swp-vs-annuity') {
            matchUrl = '/swp-vs-annuity';
            matchLabel = 'SWP vs Life Annuity (12.5% LTCG Tax Analysis)';
        }

        if (matchUrl && this.currentPath !== matchUrl) {
            const msgEl = this.dom.getElement('swp-bridge-message');
            const linkEl = this.dom.getElement<HTMLAnchorElement>('swp-bridge-link');
            const actionEl = this.dom.getElement('swp-bridge-action');

            if (msgEl) msgEl.textContent = matchLabel || 'Dedicated Pension Blueprint';
            if (actionEl) actionEl.textContent = 'View Guide';
            if (linkEl) linkEl.href = matchUrl;

            bridgeEl.classList.remove('hidden');
        } else {
            bridgeEl.classList.add('hidden');
        }
    }

    private updateCorpusBridge(inputs: InvestmentInputs): void {
        const bridgeEl = this.dom.getElement('contextual-corpus-milestone-bridge');
        if (!bridgeEl) return;

        const currentCorpus = Math.round(inputs.corpus || 0);
        const targetCorpusVal = parseFloat(this.dom.getValue('target_corpus') || '0') || 0;
        const currentTarget = Math.round(targetCorpusVal);

        const match = ContextualBridgeController.CORPUS_MILESTONES.find(m => Math.abs(m.threshold - currentCorpus) <= 10000)
            || ContextualBridgeController.GOAL_MILESTONES.find(m => Math.abs(m.threshold - currentTarget) <= 10000);

        if (match && this.currentPath !== match.url) {
            const msgEl = this.dom.getElement('corpus-bridge-message');
            const linkEl = this.dom.getElement<HTMLAnchorElement>('corpus-bridge-link');
            const actionEl = this.dom.getElement('corpus-bridge-action');

            if (msgEl) msgEl.textContent = match.label;
            if (actionEl) actionEl.textContent = match.action;
            if (linkEl) linkEl.href = match.url;

            bridgeEl.classList.remove('hidden');
        } else {
            bridgeEl.classList.add('hidden');
        }
    }
}
