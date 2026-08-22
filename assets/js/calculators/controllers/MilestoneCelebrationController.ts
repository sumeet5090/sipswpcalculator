import { DOMAdapter } from '../../adapters/DOMAdapter';

import { THEME_COLORS } from '../constants/ThemeTokens.ts';

export class MilestoneCelebrationController {
    private dom: DOMAdapter;
    private celebratedMilestones: Set<number> = new Set();

    constructor(dom: DOMAdapter) {
        this.dom = dom;
    }

    init(): void {
        this.celebratedMilestones.clear();
    }

    checkMilestones(corpus: number): void {
        const milestones = [
            { threshold: 2500000, label: '₹25 Lakh' },
            { threshold: 5000000, label: '₹50 Lakh' },
            { threshold: 10000000, label: '₹1 Crore' },
            { threshold: 50000000, label: '₹5 Crore' }
        ];

        // Update roadmap checkpoints
        this.updateRoadmap(corpus);

        // Check if a major threshold was just crossed
        milestones.forEach(m => {
            if (corpus >= m.threshold && !this.celebratedMilestones.has(m.threshold)) {
                this.celebratedMilestones.add(m.threshold);
                if (m.threshold >= 10000000) {
                    this.triggerMicroBurst();
                }
            } else if (corpus < m.threshold && this.celebratedMilestones.has(m.threshold)) {
                this.celebratedMilestones.delete(m.threshold);
            }
        });
    }

    private updateRoadmap(corpus: number): void {
        const checkpoints = this.dom.getElements<HTMLElement>('.roadmap-checkpoint');
        checkpoints.forEach(cp => {
            const target = parseFloat(cp.dataset.target || '0');
            const icon = cp.querySelector('.checkpoint-icon');
            const bar = cp.querySelector<HTMLElement>('.checkpoint-bar');
            const status = cp.querySelector('.checkpoint-status');

            const pct = target > 0 ? Math.min(Math.round((corpus / target) * 100), 100) : 0;

            if (bar) {
                bar.style.width = `${pct}%`;
            }

            if (status) {
                if (pct >= 100) {
                    status.textContent = '✓ Achieved';
                    status.className = 'checkpoint-status text-[9px] font-extrabold px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200';
                } else if (pct > 0) {
                    status.textContent = `${pct}%`;
                    status.className = 'checkpoint-status text-[9px] font-extrabold px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100';
                } else {
                    status.textContent = '0%';
                    status.className = 'checkpoint-status text-[9px] font-extrabold px-1.5 py-0.5 rounded-full bg-slate-200/80 text-slate-600';
                }
            }

            if (corpus >= target && target > 0) {
                cp.classList.add('border-emerald-300', 'bg-emerald-50/40');
                cp.classList.remove('border-slate-200/90', 'bg-slate-50/80');
                if (icon) {
                    icon.classList.remove('bg-white', 'text-slate-400', 'border-slate-200');
                    icon.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-md', 'shadow-emerald-600/30');
                }
            } else {
                cp.classList.remove('border-emerald-300', 'bg-emerald-50/40');
                cp.classList.add('border-slate-200/90', 'bg-slate-50/80');
                if (icon) {
                    icon.classList.add('bg-white', 'text-slate-400', 'border-slate-200');
                    icon.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-md', 'shadow-emerald-600/30');
                }
            }
        });
    }

    private lastBurstTime: number = 0;

    private triggerMicroBurst(): void {
        const now = Date.now();
        if (now - this.lastBurstTime < 2500) return;
        this.lastBurstTime = now;

        const targetContainer = this.dom.getElement('summary-corpus');
        let originX = typeof window !== 'undefined' ? window.innerWidth / 2 : 300;
        let originY = typeof window !== 'undefined' ? window.innerHeight / 3 : 200;

        if (targetContainer) {
            const rect = targetContainer.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
                originX = rect.left + rect.width / 2;
                originY = rect.top + rect.height / 2;
            }
        }

        const colors = THEME_COLORS.celebration;

        for (let i = 0; i < 16; i++) {
            const particle = document.createElement('div');
            particle.className = 'celebration-particle fixed z-[200] pointer-events-none';

            const angle = (Math.PI * 2 * i) / 16 + (Math.random() * 0.2 - 0.1);
            const velocity = 40 + Math.random() * 45;
            const dx = `${Math.cos(angle) * velocity}px`;
            const dy = `${Math.sin(angle) * velocity}px`;

            particle.style.left = `${originX}px`;
            particle.style.top = `${originY}px`;
            particle.style.setProperty('--dx', dx);
            particle.style.setProperty('--dy', dy);
            particle.style.backgroundColor = colors[i % colors.length];

            document.body.appendChild(particle);

            setTimeout(() => {
                particle.remove();
            }, 850);
        }
    }
}
