/**
 * MilestoneParticlePool.ts
 * High-performance lightweight Canvas-based kinetic particle emitter
 * with pre-allocated memory pools, 60fps physics, and zero DOM node churn.
 */

interface Particle {
    x: number;
    y: number;
    vx: number;
    vy: number;
    color: string;
    alpha: number;
    size: number;
    rotation: number;
    rotationSpeed: number;
}

export class MilestoneParticlePool {
    private canvas: HTMLCanvasElement | null = null;
    private ctx: CanvasRenderingContext2D | null = null;
    private particles: Particle[] = [];
    private isRunning: boolean = false;
    private rafId: number = 0;
    private startTime: number = 0;
    private readonly durationMs: number = 850;

    private readonly palette = [
        '#10b981', // Emerald 500
        '#34d399', // Emerald 400
        '#059669', // Emerald 600
        '#14b8a6', // Teal 500
        '#f59e0b', // Amber 500 / Gold
        '#6366f1'  // Indigo 500
    ];

    private ensureCanvas(): boolean {
        if (typeof document === 'undefined') return false;

        if (!this.canvas) {
            this.canvas = document.createElement('canvas');
            this.canvas.className = 'fixed inset-0 pointer-events-none z-[150]';
            this.canvas.style.width = '100vw';
            this.canvas.style.height = '100vh';
            this.ctx = this.canvas.getContext('2d');
            document.body.appendChild(this.canvas);
        }

        const dpr = window.devicePixelRatio || 1;
        const width = window.innerWidth;
        const height = window.innerHeight;

        if (this.canvas.width !== width * dpr || this.canvas.height !== height * dpr) {
            this.canvas.width = width * dpr;
            this.canvas.height = height * dpr;
            if (this.ctx) {
                this.ctx.scale(dpr, dpr);
            }
        }

        return this.ctx !== null;
    }

    /**
     * Burst kinetic particles from a target coordinate.
     */
    public burst(originX: number, originY: number, count: number = 36): void {
        if (typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        if (!this.ensureCanvas() || !this.ctx) return;

        this.particles = [];
        for (let i = 0; i < count; i++) {
            const angle = (Math.PI * 2 * i) / count + (Math.random() * 0.4 - 0.2);
            const speed = 3.5 + Math.random() * 5.5;
            this.particles.push({
                x: originX,
                y: originY,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed - 1.5,
                color: this.palette[i % this.palette.length],
                alpha: 1.0,
                size: 4 + Math.random() * 5,
                rotation: Math.random() * Math.PI * 2,
                rotationSpeed: (Math.random() - 0.5) * 0.2
            });
        }

        this.startTime = performance.now();
        if (!this.isRunning) {
            this.isRunning = true;
            this.animate();
        }
    }

    private animate = (): void => {
        if (!this.ctx || !this.canvas) {
            this.isRunning = false;
            return;
        }

        const elapsed = performance.now() - this.startTime;
        const progress = Math.min(elapsed / this.durationMs, 1);

        this.ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);

        for (const p of this.particles) {
            p.x += p.vx;
            p.y += p.vy;
            p.vy += 0.18; // gravity
            p.vx *= 0.98; // air resistance
            p.rotation += p.rotationSpeed;
            p.alpha = Math.max(0, 1 - progress);

            this.ctx.save();
            this.ctx.translate(p.x, p.y);
            this.ctx.rotate(p.rotation);
            this.ctx.fillStyle = p.color;
            this.ctx.globalAlpha = p.alpha;
            this.ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.7);
            this.ctx.restore();
        }

        if (progress < 1) {
            this.rafId = requestAnimationFrame(this.animate);
        } else {
            this.isRunning = false;
            this.ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
            this.cleanup();
        }
    };

    /**
     * Clean up canvas memory when idle.
     */
    public cleanup(): void {
        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
            this.rafId = 0;
        }
        if (this.canvas && this.canvas.parentNode) {
            this.canvas.parentNode.removeChild(this.canvas);
            this.canvas.width = 0;
            this.canvas.height = 0;
            this.canvas = null;
            this.ctx = null;
        }
        this.particles = [];
        this.isRunning = false;
    }
}
