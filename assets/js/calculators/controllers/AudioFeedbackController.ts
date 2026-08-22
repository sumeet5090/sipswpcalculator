import { DOMAdapter } from '../../adapters/DOMAdapter';

export class AudioFeedbackController {
    private dom: DOMAdapter;
    private audioCtx: AudioContext | null = null;
    private isEnabled: boolean = false;

    constructor(dom: DOMAdapter) {
        this.dom = dom;
    }

    init(): void {
        this.isEnabled = localStorage.getItem('sip_sound_enabled') === 'true';
        this.updateToggleButton();

        const soundBtn = this.dom.getElement('sound-toggle-btn');
        if (soundBtn) {
            soundBtn.addEventListener('click', () => {
                this.toggleSound();
            });
        }

        // Cross-tab preference synchronization
        if (typeof window !== 'undefined') {
            window.addEventListener('storage', (e: StorageEvent) => {
                if (e.key === 'sip_sound_enabled') {
                    this.isEnabled = e.newValue === 'true';
                    this.updateToggleButton();
                }
            });
        }
    }

    toggleSound(): boolean {
        this.isEnabled = !this.isEnabled;
        localStorage.setItem('sip_sound_enabled', String(this.isEnabled));
        this.updateToggleButton();

        if (this.isEnabled) {
            this.ensureAudioContext();
            this.playTick(440, 0.02);
            this.vibrate(10);
        }
        return this.isEnabled;
    }

    playTick(freq: number = 380, duration: number = 0.015): void {
        if (!this.isEnabled) return;
        this.ensureAudioContext();
        if (!this.audioCtx) return;

        const safeFreq = Math.max(50, Math.min(20000, Number.isFinite(freq) ? freq : 380));
        const safeDuration = Math.max(0.005, Math.min(1.0, Number.isFinite(duration) ? duration : 0.015));

        try {
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(safeFreq, this.audioCtx.currentTime);

            gain.gain.setValueAtTime(0.04, this.audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, this.audioCtx.currentTime + safeDuration);

            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            osc.start();
            osc.stop(this.audioCtx.currentTime + duration);

            // Clean up audio graph nodes to prevent memory retention
            setTimeout(() => {
                try {
                    osc.disconnect();
                    gain.disconnect();
                } catch {
                    // Safe cleanup ignore
                }
            }, Math.ceil((duration + 0.05) * 1000));
        } catch {
            // Graceful silence on audio failure
        }
    }

    playChime(): void {
        if (!this.isEnabled) return;
        this.ensureAudioContext();
        if (!this.audioCtx) return;

        try {
            const now = this.audioCtx.currentTime;
            [523.25, 659.25, 783.99].forEach((freq, i) => {
                const osc = this.audioCtx!.createOscillator();
                const gain = this.audioCtx!.createGain();

                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, now + i * 0.08);

                gain.gain.setValueAtTime(0.05, now + i * 0.08);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + i * 0.08 + 0.3);

                osc.connect(gain);
                gain.connect(this.audioCtx!.destination);

                osc.start(now + i * 0.08);
                osc.stop(now + i * 0.08 + 0.3);

                setTimeout(() => {
                    try {
                        osc.disconnect();
                        gain.disconnect();
                    } catch {
                        // Safe cleanup ignore
                    }
                }, Math.ceil((0.08 * i + 0.35) * 1000));
            });
        } catch {
            // Graceful silence
        }
    }

    private lastVibrateTime: number = 0;

    vibrate(pattern: number | number[] = 8): void {
        const now = Date.now();
        if (now - this.lastVibrateTime < 35) return;
        this.lastVibrateTime = now;

        if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
            try {
                navigator.vibrate(pattern);
            } catch {
                // Ignore vibration failure
            }
        }
    }

    private ensureAudioContext(): void {
        if (!this.audioCtx && typeof window !== 'undefined') {
            const AudioCtxClass = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
            if (AudioCtxClass) {
                this.audioCtx = new AudioCtxClass();
            }
        }
        if (this.audioCtx && this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
    }

    private updateToggleButton(): void {
        const soundBtn = this.dom.getElement('sound-toggle-btn');
        if (!soundBtn) return;

        const icon = soundBtn.querySelector('.sound-icon');
        const text = soundBtn.querySelector('.sound-text');

        if (this.isEnabled) {
            soundBtn.classList.remove('text-slate-400', 'bg-slate-50');
            soundBtn.classList.add('text-emerald-700', 'bg-emerald-50', 'border-emerald-200');
            if (icon) icon.textContent = '🔊';
            if (text) text.textContent = 'Sound ON';
        } else {
            soundBtn.classList.remove('text-emerald-700', 'bg-emerald-50', 'border-emerald-200');
            soundBtn.classList.add('text-slate-400', 'bg-slate-50');
            if (icon) icon.textContent = '🔇';
            if (text) text.textContent = 'Muted';
        }
    }
}
