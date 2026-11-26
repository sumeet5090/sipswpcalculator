import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { AnalyticsService } from '../AnalyticsLogger.ts';
import { InvestmentInputs, YearResult } from '../../types';

export function bindPassiveSeoClickListeners(analytics: AnalyticsService): void {
    document.querySelectorAll('details').forEach(details => {
        details.addEventListener('toggle', () => {
            if (details.open) {
                const summaryText = details.querySelector('summary')?.textContent?.trim() || details.id || 'faq';
                analytics.setFaqExpanded(summaryText.slice(0, 64));
            }
        });
    });

    document.querySelectorAll('[data-glossary]').forEach(el => {
        el.addEventListener('click', () => {
            const term = el.getAttribute('data-glossary') || el.textContent?.trim() || 'term';
            analytics.setGlossaryClicked(term.slice(0, 64));
        });
    });

    document.querySelectorAll('#floating-discovery-hud a, .hud-nav-link').forEach(el => {
        el.addEventListener('click', () => {
            const target = el.getAttribute('href') || el.id || 'hud';
            analytics.setHudShortcutClicked(target.slice(0, 64));
        });
    });

    document.querySelectorAll('.city-choice-btn, [data-city]').forEach(el => {
        el.addEventListener('click', () => {
            const city = (el as HTMLElement).dataset.city || el.textContent?.trim() || 'city';
            analytics.setCityBenchmarkCity(city.slice(0, 64));
        });
    });

    document.querySelectorAll('.stress-card, [data-scenario]').forEach(el => {
        el.addEventListener('click', () => {
            const sc = (el as HTMLElement).dataset.scenario || el.textContent?.trim() || 'stress';
            analytics.setStressTestScenario(sc.slice(0, 64));
        });
    });

    document.querySelectorAll('#related-resources a').forEach(el => {
        el.addEventListener('click', () => {
            const href = el.getAttribute('href') || 'related';
            analytics.setInternalHubClicked(href.slice(0, 64));
        });
    });
}

export function scheduleSettledAnalytics(
    dom: DOMAdapter,
    analytics: AnalyticsService,
    inputs: InvestmentInputs,
    combined: YearResult[],
    activeGoalMode: string
): void {
    const runAnalytics = () => {
        const breakdownEl = dom.getElement('yearly-breakdown-section') || dom.getElement('breakdown-body');
        const tableViewed = breakdownEl
            ? (breakdownEl.getBoundingClientRect().top < dom.getViewportHeight() ? 1 : 0)
            : 0;
        const deviceType = (window.innerWidth < 768) ? 'mobile' : 'desktop';

        analytics.logInsight(inputs, combined, activeGoalMode, {
            table_viewed: tableViewed,
            device_type: deviceType
        });
    };

    if (typeof window !== 'undefined' && 'requestIdleCallback' in window) {
        (window as unknown as { requestIdleCallback: (cb: () => void, opts: { timeout: number }) => void })
            .requestIdleCallback(runAnalytics, { timeout: 1000 });
    } else {
        setTimeout(runAnalytics, 50);
    }
}
