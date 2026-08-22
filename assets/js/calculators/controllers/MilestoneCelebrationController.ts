import { DOMAdapter } from '../../adapters/DOMAdapter';

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
        const checkpoints = document.querySelectorAll<HTMLElement>('.roadmap-checkpoint');
        checkpoints.forEach(cp => {
            const target = parseFloat(cp.dataset.target || '0');
            const icon = cp.querySelector('.checkpoint-icon');
            const line = cp.querySelector('.checkpoint-line');

            if (corpus >= target && target > 0) {
                cp.classList.add('active-checkpoint');
                if (icon) {
                    icon.classList.remove('bg-slate-100', 'text-slate-400', 'border-slate-200');
                    icon.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-md', 'shadow-emerald-600/30');
                }
                if (line) {
                    line.classList.remove('bg-slate-200');
                    line.classList.add('bg-emerald-500');
                }
            } else {
                cp.classList.remove('active-checkpoint');
                if (icon) {
                    icon.classList.add('bg-slate-100', 'text-slate-400', 'border-slate-200');
                    icon.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-md', 'shadow-emerald-600/30');
                }
                if (line) {
                    line.classList.add('bg-slate-200');
                    line.classList.remove('bg-emerald-500');
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

        const colors = ['#10b981', '#059669', '#34d399', '#f59e0b', '#fbbf24', '#6366f1'];

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
