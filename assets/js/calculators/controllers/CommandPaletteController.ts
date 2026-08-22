import { DOMAdapter } from '../../adapters/DOMAdapter';

export interface CommandItem {
    id: string;
    title: string;
    description: string;
    category: 'Calculator' | 'Guide' | 'Tax & Rules' | 'Formula';
    url?: string;
    icon: string;
    action?: () => void;
}

export const COMMAND_ITEMS: CommandItem[] = [
    {
        id: 'calc-sip',
        title: 'SIP Calculator',
        description: 'Calculate future wealth with monthly systematic investments & step-up compounding.',
        category: 'Calculator',
        url: '/sip-calculator',
        icon: '📈'
    },
    {
        id: 'calc-swp',
        title: 'SWP Calculator',
        description: 'Plan systematic monthly retirement pension and portfolio longevity.',
        category: 'Calculator',
        url: '/swp-calculator',
        icon: '🏖️'
    },
    {
        id: 'calc-lumpsum',
        title: 'Lumpsum Calculator',
        description: 'Calculate wealth growth on one-time mutual fund investments.',
        category: 'Calculator',
        url: '/lumpsum-calculator',
        icon: '💰'
    },
    {
        id: 'calc-stepup',
        title: 'Step-Up SIP Calculator',
        description: 'Model exponential wealth gains with annual 5% to 15% investment top-ups.',
        category: 'Calculator',
        url: '/sip-step-up-calculator',
        icon: '🚀'
    },
    {
        id: 'calc-first-crore',
        title: 'My First Crore Rush',
        description: 'Calculate exact monthly SIP required to reach ₹1 Crore in 5, 8, or 10 years.',
        category: 'Calculator',
        url: '/my-first-crore-calculator',
        icon: '🎯'
    },
    {
        id: 'guide-cagr-xirr',
        title: 'CAGR vs XIRR Explained',
        description: 'Understand the difference between point-to-point and periodic cashflow returns.',
        category: 'Guide',
        url: '/resources',
        icon: '📚'
    },
    {
        id: 'tax-ltcg-112a',
        title: 'Budget 2024 LTCG Section 112A',
        description: 'Equity mutual fund capital gains rules: ₹1.25 Lakh annual exemption & 12.5% tax rate.',
        category: 'Tax & Rules',
        url: '/glossary',
        icon: '⚖️'
    },
    {
        id: 'formula-annuity',
        title: 'SIP Annuity Due Mathematical Formula',
        description: 'FV = P × [((1+r)^n - 1) / r] × (1+r) mathematical proof.',
        category: 'Formula',
        url: '#master-financial-future',
        icon: '📐'
    }
];

export class CommandPaletteController {
    private dom: DOMAdapter;
    private onQuickApply?: (params: { sip?: number; years?: number; rate?: number }) => void;
    private modal: HTMLDialogElement | null = null;
    private input: HTMLInputElement | null = null;
    private resultsContainer: HTMLElement | null = null;
    private selectedIndex: number = 0;
    private filteredItems: CommandItem[] = [];

    constructor(dom: DOMAdapter, onQuickApply?: (params: { sip?: number; years?: number; rate?: number }) => void) {
        this.dom = dom;
        this.onQuickApply = onQuickApply;
    }

    init(): void {
        this.modal = this.dom.getElement<HTMLDialogElement>('command-palette-modal');
        this.input = this.dom.getElement<HTMLInputElement>('command-palette-input');
        this.resultsContainer = this.dom.getElement('command-palette-results');

        if (!this.modal || !this.input || !this.resultsContainer) return;

        // Global hotkey listener (Cmd+K / Ctrl+K)
        window.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                this.toggle();
            }
        });

        // Trigger button
        const openBtn = this.dom.getElement('open-command-palette-btn');
        if (openBtn) {
            openBtn.addEventListener('click', () => this.open());
        }

        // Close on backdrop click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Input filtering
        this.input.addEventListener('input', () => {
            this.filter(this.input?.value || '');
        });

        // Keyboard navigation inside input
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.navigate(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.navigate(-1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                this.selectActive();
            }
        });

        this.filter('');
    }

    open(): void {
        if (!this.modal) return;
        this.modal.showModal();
        if (this.input) {
            this.input.value = '';
            this.filter('');
            this.input.focus();
        }
    }

    close(): void {
        if (!this.modal) return;
        this.modal.close();
    }

    toggle(): void {
        if (!this.modal) return;
        if (this.modal.open) {
            this.close();
        } else {
            this.open();
        }
    }

    private parseNaturalLanguage(query: string): CommandItem | null {
        const q = query.trim().toLowerCase();
        const sipMatch = q.match(/^sip\s+(\d+(?:\.\d+)?(?:k|l|cr)?)\s*(?:(\d+(?:\.\d+)?)\s*(?:y|yrs|years)?)?\s*(?:(\d+(?:\.\d+)?)\s*%)?/i);
        if (sipMatch) {
            const sipStr = sipMatch[1];
            let sipNum = parseFloat(sipStr);
            if (sipStr.endsWith('k')) sipNum *= 1000;
            else if (sipStr.endsWith('l')) sipNum *= 100000;
            else if (sipStr.endsWith('cr')) sipNum *= 10000000;

            const years = sipMatch[2] ? parseFloat(sipMatch[2]) : 10;
            const rate = sipMatch[3] ? parseFloat(sipMatch[3]) : 12;

            return {
                id: 'dynamic-nlp-sip',
                title: `⚡ Quick-Apply: Monthly SIP ₹${sipNum.toLocaleString('en-IN')} for ${years} Years (@${rate}%)`,
                description: 'Instant natural language prompt execution',
                category: 'Calculator',
                icon: '🚀',
                action: () => {
                    if (this.onQuickApply) {
                        this.onQuickApply({ sip: sipNum, years, rate });
                    }
                }
            };
        }
        return null;
    }

    private filter(query: string): void {
        const q = query.trim().toLowerCase();
        if (!q) {
            this.filteredItems = [...COMMAND_ITEMS];
        } else {
            this.filteredItems = COMMAND_ITEMS.filter(item => 
                item.title.toLowerCase().includes(q) ||
                item.description.toLowerCase().includes(q) ||
                item.category.toLowerCase().includes(q)
            );

            const nlpItem = this.parseNaturalLanguage(q);
            if (nlpItem) {
                this.filteredItems.unshift(nlpItem);
            }
        }
        this.selectedIndex = 0;
        this.renderResults();
    }

    private navigate(direction: number): void {
        if (this.filteredItems.length === 0) return;
        this.selectedIndex = (this.selectedIndex + direction + this.filteredItems.length) % this.filteredItems.length;
        this.renderResults();
        
        const activeEl = this.resultsContainer?.querySelector(`[data-index="${this.selectedIndex}"]`);
        if (activeEl) {
            activeEl.scrollIntoView({ block: 'nearest' });
        }
    }

    private selectActive(): void {
        if (this.filteredItems.length === 0) return;
        const item = this.filteredItems[this.selectedIndex];
        if (item.action) {
            item.action();
            this.close();
        } else if (item.url) {
            this.close();
            window.location.href = item.url;
        }
    }

    private renderResults(): void {
        if (!this.resultsContainer) return;
        this.resultsContainer.innerHTML = '';

        if (this.filteredItems.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'py-8 text-center text-xs font-semibold text-slate-400';
            empty.textContent = 'No matching financial calculators or guides found.';
            this.resultsContainer.appendChild(empty);
            return;
        }

        this.filteredItems.forEach((item, index) => {
            const isSelected = index === this.selectedIndex;
            const row = document.createElement('a');
            row.href = item.url || '#';
            row.dataset.index = String(index);
            row.className = `flex items-center justify-between p-3 rounded-2xl transition-all cursor-pointer ${
                isSelected ? 'bg-emerald-50 text-emerald-950 border border-emerald-200 shadow-2xs' : 'hover:bg-slate-50 text-slate-700'
            }`;

            row.innerHTML = `
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex items-center justify-center w-8 h-8 rounded-xl ${isSelected ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'} text-sm shrink-0">
                        ${item.icon}
                    </span>
                    <div class="min-w-0">
                        <div class="text-xs sm:text-sm font-bold truncate ${isSelected ? 'text-emerald-900' : 'text-slate-800'}">${item.title}</div>
                        <div class="text-[11px] text-slate-400 truncate">${item.description}</div>
                    </div>
                </div>
                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md ${
                    isSelected ? 'bg-emerald-200/80 text-emerald-800' : 'bg-slate-100 text-slate-500'
                } shrink-0 ml-2">
                    ${item.category}
                </span>
            `;

            row.addEventListener('click', (e) => {
                if (item.action) {
                    e.preventDefault();
                    item.action();
                    this.close();
                } else if (item.url) {
                    this.close();
                }
            });

            this.resultsContainer?.appendChild(row);
        });
    }
}
