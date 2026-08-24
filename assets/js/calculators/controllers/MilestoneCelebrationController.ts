import { DOMAdapter } from '../../adapters/DOMAdapter';
import { YearResult, InvestmentInputs } from '../../types';
import { THEME_COLORS } from '../constants/ThemeTokens.ts';
import { CurrencyFormatter } from '../CurrencyHelper';

export interface MilestoneCheckpoint {
    threshold: number;
    label: string;
    description: string;
}

export class MilestoneCelebrationController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private celebratedMilestones: Set<number> = new Set();
    private currentInputs: InvestmentInputs | null = null;
    private lastBurstTime: number = 0;

    private readonly checkpoints: MilestoneCheckpoint[] = [
        { threshold: 1000000, label: '₹10.0 Lakh', description: 'Seed Momentum' },
        { threshold: 2500000, label: '₹25.0 Lakh', description: 'Compounding Ignition' },
        { threshold: 5000000, label: '₹50.0 Lakh', description: 'Half-Crore Waypoint' },
        { threshold: 10000000, label: '₹1.00 Crore', description: 'First Crore Club' },
        { threshold: 50000000, label: '₹5.00 Crore', description: 'Financial Freedom' }
    ];

    constructor(dom: DOMAdapter, formatter?: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter || new CurrencyFormatter();
    }

    public init(): void {
        this.celebratedMilestones.clear();
    }

    public getCheckpoints(): MilestoneCheckpoint[] {
        return [...this.checkpoints];
    }

    public checkMilestones(corpus: number, results: YearResult[] = [], inputs?: InvestmentInputs): void {
        if (inputs) this.currentInputs = inputs;

        // Update roadmap checkpoints with dynamic ETAs and purchasing power
        this.updateRoadmap(corpus, results);

        // Check if a major threshold was just crossed
        this.checkpoints.forEach(m => {
            if (corpus >= m.threshold && !this.celebratedMilestones.has(m.threshold)) {
                this.celebratedMilestones.add(m.threshold);
                if (m.threshold >= 10000000) {
                    this.triggerMicroBurst();
                    this.triggerSheenSweep();
                }
            } else if (corpus < m.threshold && this.celebratedMilestones.has(m.threshold)) {
                this.celebratedMilestones.delete(m.threshold);
            }
        });
    }

    private triggerSheenSweep(): void {
        if (typeof window === 'undefined') return;
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const corpusCard = this.dom.getElement('summary-corpus')?.closest('.bg-white\\/95') as HTMLElement | null;
        if (corpusCard) {
            corpusCard.classList.add('milestone-sheen-active');
            setTimeout(() => {
                corpusCard.classList.remove('milestone-sheen-active');
            }, 700);
        }
    }

    private updateRoadmap(corpus: number, results: YearResult[] = []): void {
        const checkpoints = this.dom.getElements<HTMLElement>('.roadmap-checkpoint');
        let unlockedCount = 0;
        const inflationRate = this.currentInputs?.inflation ?? 6.0;

        checkpoints.forEach(cp => {
            const target = parseFloat(cp.dataset.target || '0');
            const icon = cp.querySelector('.checkpoint-icon');
            const bar = cp.querySelector<HTMLElement>('.checkpoint-bar');
            const status = cp.querySelector('.checkpoint-status');
            const etaEl = cp.querySelector('.checkpoint-eta');
            const realValEl = cp.querySelector('.checkpoint-real-value');

            const pct = target > 0 ? Math.min(Math.round((corpus / target) * 100), 100) : 0;
            if (corpus >= target && target > 0) {
                unlockedCount++;
            }

            if (bar) {
                bar.style.width = `${pct}%`;
            }

            if (status) {
                if (pct >= 100) {
                    status.textContent = '✓ Achieved';
                    status.className = 'checkpoint-status text-[9px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200';
                } else if (pct > 0) {
                    status.textContent = `${pct}%`;
                    status.className = 'checkpoint-status text-[9px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100';
                } else {
                    status.textContent = '0%';
                    status.className = 'checkpoint-status text-[9px] font-extrabold px-2 py-0.5 rounded-full bg-slate-200/80 text-slate-600';
                }
            }

            // Find crossover year from results
            const crossoverRow = results.find(r => r.combined_total >= target);
            if (etaEl) {
                if (crossoverRow) {
                    etaEl.textContent = `🎯 Achieved Year ${crossoverRow.year}`;
                    etaEl.className = 'checkpoint-eta text-[10.5px] font-extrabold text-emerald-800';
                } else if (corpus > 0 && target > corpus) {
                    const finalRow = results.length > 0 ? results[results.length - 1] : null;
                    const finalCorpus = finalRow ? finalRow.combined_total : corpus;
                    const gap = target - finalCorpus;
                    if (gap > 0) {
                        etaEl.textContent = `Gap: ${this.formatter.formatDynamic(gap)}`;
                        etaEl.className = 'checkpoint-eta text-[10.5px] font-semibold text-slate-500';
                    } else {
                        etaEl.textContent = 'ETA: --';
                        etaEl.className = 'checkpoint-eta text-[10.5px] font-semibold text-slate-500';
                    }
                } else {
                    etaEl.textContent = 'ETA: --';
                    etaEl.className = 'checkpoint-eta text-[10.5px] font-semibold text-slate-500';
                }
            }

            // Real purchasing power calculation
            if (realValEl) {
                if (crossoverRow && crossoverRow.year > 0) {
                    const realValue = target / Math.pow(1 + inflationRate / 100, crossoverRow.year);
                    realValEl.textContent = `Real: ${this.formatter.formatDynamic(Math.round(realValue))}`;
                    realValEl.className = 'checkpoint-real-value text-[9px] text-emerald-700 font-bold block mt-0.5';
                } else {
                    realValEl.textContent = 'Real: ₹--';
                    realValEl.className = 'checkpoint-real-value text-[9px] text-slate-400 font-medium block mt-0.5';
                }
            }

            if (corpus >= target && target > 0) {
                cp.classList.add('border-emerald-300', 'bg-emerald-50/50');
                cp.classList.remove('border-slate-200/90', 'bg-slate-50/80');
                if (icon) {
                    icon.classList.remove('bg-white', 'text-slate-500', 'border-slate-200');
                    icon.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-sm');
                }
            } else {
                cp.classList.remove('border-emerald-300', 'bg-emerald-50/50');
                cp.classList.add('border-slate-200/90', 'bg-slate-50/80');
                if (icon) {
                    icon.classList.add('bg-white', 'text-slate-500', 'border-slate-200');
                    icon.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-sm');
                }
            }
        });

        // Update Header Counter Badge
        const counterEl = this.dom.getElement('milestone-hit-counter');
        if (counterEl) {
            counterEl.textContent = `${unlockedCount}/${this.checkpoints.length} Milestones Hit`;
        }

        // Dynamic Velocity Insight
        const velocityText = this.dom.getElement('milestone-velocity-text');
        if (velocityText && results.length > 0) {
            const y10L = results.find(r => r.combined_total >= 1000000)?.year;
            const y1Cr = results.find(r => r.combined_total >= 10000000)?.year;
            const y5Cr = results.find(r => r.combined_total >= 50000000)?.year;

            if (y1Cr && y5Cr) {
                const deltaYears = y5Cr - y1Cr;
                velocityText.innerHTML = `Your 1st Crore takes <strong>Year ${y1Cr}</strong>. Compounding accelerates 5× to reach <strong>₹5 Crore</strong> in just <strong>+${deltaYears} more years</strong>!`;
            } else if (y10L && y1Cr) {
                const deltaYears = y1Cr - y10L;
                velocityText.innerHTML = `Seed ₹10 Lakh reached in <strong>Year ${y10L}</strong>. Compounding velocity accelerates to unlock <strong>₹1 Crore</strong> in <strong>+${deltaYears} years</strong>!`;
            } else {
                velocityText.innerHTML = `The first ₹1 Crore takes the longest (~13 years). The second ₹1 Crore typically arrives in under 5 years because your accumulated interest overtakes fresh contributions!`;
            }
        }
    }

    public triggerMicroBurst(): void {
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

