/**
 * script.ts
 * Entry point for modular client-side calculations and reading ergonomics.
 * Instantiates and bootstraps the Object-Oriented CalculatorApp, hydrates
 * dynamic financial amounts, and manages editorial reading progress.
 */
import { CalculatorApp } from '../../assets/js/calculators/CalculatorApp';
import { CurrencyFormatter } from '../../assets/js/calculators/CurrencyHelper';
import { WebHapticEngine } from '../../assets/js/calculators/helpers/WebHapticEngine';
import { initSaveCalculationUI } from './save-calculation';

/**
 * Hydrates any SSR `.dynamic-amount` spans with Indian Rupee formatting
 * if client-side content is missing or requires formatting.
 */
export function hydrateDynamicAmounts(): void {
    const elements = document.querySelectorAll<HTMLElement>('.dynamic-amount[data-amount-inr]');
    if (elements.length === 0) return;

    const formatter = new CurrencyFormatter('en-IN', 'INR', '₹');
    elements.forEach((el) => {
        const raw = el.getAttribute('data-amount-inr');
        if (raw !== null) {
            const num = parseFloat(raw);
            if (!isNaN(num)) {
                if (!el.textContent || el.textContent.trim() === '') {
                    el.textContent = formatter.format(num);
                }
            }
        }
    });
}

/**
 * Tracks article reading progress and updates the fixed progress bar
 * and reading dock percentage with requestAnimationFrame debouncing.
 */
export function initReadingProgress(): void {
    const progressBar = document.getElementById('reading-progress-bar');
    const progressPercent = document.getElementById('reading-progress-percent');
    const article = document.querySelector('article') || document.querySelector('.entry-content') || document.getElementById('main-content');

    if (!progressBar || !article) return;

    let ticking = false;

    const updateProgress = () => {
        const rect = article.getBoundingClientRect();
        const windowHeight = window.innerHeight || document.documentElement.clientHeight;
        const totalScrollable = rect.height - windowHeight;

        let percent = 0;
        if (totalScrollable > 0) {
            const scrolled = -rect.top;
            percent = Math.min(Math.max((scrolled / totalScrollable) * 100, 0), 100);
        } else {
            percent = rect.top <= 0 ? 100 : 0;
        }

        const rounded = Math.round(percent);
        progressBar.style.width = `${percent}%`;
        if (progressPercent) {
            progressPercent.textContent = `${rounded}%`;
        }
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(updateProgress);
            ticking = true;
        }
    }, { passive: true });

    window.addEventListener('resize', () => {
        if (!ticking) {
            window.requestAnimationFrame(updateProgress);
            ticking = true;
        }
    }, { passive: true });

    // Initial check
    updateProgress();

    // Mobile reading dock share button
    const shareBtn = document.getElementById('mobile-reading-share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', async () => {
            WebHapticEngine.triggerTick(6);
            const shareData = {
                title: document.title,
                url: window.location.href,
            };
            if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
                try {
                    await navigator.share(shareData);
                } catch {
                    // Silently ignore user aborts
                }
            } else {
                try {
                    await navigator.clipboard.writeText(window.location.href);
                    const announcer = document.getElementById('calculator-a11y-live-announcer');
                    if (announcer) {
                        announcer.textContent = 'Link copied to clipboard!';
                    }
                } catch {
                    // Silently ignore clipboard permissions
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const app = new CalculatorApp();
    app.init();
    initSaveCalculationUI();
    hydrateDynamicAmounts();
    initReadingProgress();
});
