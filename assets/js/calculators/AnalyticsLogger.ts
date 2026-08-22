import { InvestmentInputs, YearResult } from '../types';

export interface ExtraSignals {
    pdf_downloaded?: boolean;
    pdf_has_custom_name?: boolean;
    inflation_enabled?: boolean;
    interaction_count?: number;
    preset_clicked?: string;
    exit_action?: string;
    table_viewed?: number;
    device_type?: string;
    currency?: string;
    landing_path?: string;
    referrer_category?: string;
    utm_source?: string;
    utm_medium?: string;
    scroll_depth_pct?: number;
    dwell_time_seconds?: number;
    quick_answer_viewed?: number;
    faq_item_expanded?: string;
    glossary_term_clicked?: string;
    hud_shortcut_clicked?: string;
    active_studio_tab?: string;
    strategy_starter_used?: string;
    guided_wizard_completed?: number;
    stress_test_scenario?: string;
    city_benchmark_city?: string;
    scenario_diff_saved?: number;
    csv_exported?: number;
    qr_modal_opened?: number;
    tax_waterfall_opened?: number;
    goal_pledge_created?: number;
    internal_hub_clicked?: string;
    cwv_lcp_ms?: number;
    cwv_cls?: number;
    cwv_inp_ms?: number;
    connection_speed?: string;
    viewport_bucket?: string;
}

export interface AnalyticsTransport {
    send(payload: Record<string, unknown>): void;
}

/**
 * BeaconFetchTransport
 * Transmits telemetry payloads via navigator.sendBeacon with fetch fallback.
 */
export class BeaconFetchTransport implements AnalyticsTransport {
    send(payload: Record<string, unknown>): void {
        try {
            const body = JSON.stringify(payload);
            if (typeof navigator !== 'undefined' && typeof navigator.sendBeacon === 'function') {
                const blob = new Blob([body], { type: 'application/json' });
                const queued = navigator.sendBeacon('/log_insight', blob);
                if (queued) {
                    return;
                }
            }
            fetch('/log_insight', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body,
                keepalive: true
            }).catch(() => {});
        } catch {
            // Silently ignore network or serialization errors to protect UX
        }
    }
}

/**
 * AnalyticsLogger.ts
 * Manages debounced user planning behavior logging.
 * Decouples telemetry schema building and lifecycle observers from network transport.
 */
export class AnalyticsService {
    private debounceMs: number;
    private transport: AnalyticsTransport;
    private insightTimeout: ReturnType<typeof setTimeout> | null = null;
    private pendingPayload: Record<string, unknown> | null = null;

    // Passive SEO & Session State
    private dwellStartTimestamp: number;
    private accumulatedDwellSeconds: number = 0;
    private maxScrollDepthPct: number = 0;
    private quickAnswerObserved: number = 0;
    private activeStudioTab: string = 'city_benchmark';
    private strategyStarterUsed: string = 'none';
    private guidedWizardCompleted: number = 0;
    private stressTestScenario: string = 'none';
    private cityBenchmarkCity: string = 'none';
    private scenarioDiffSaved: number = 0;
    private qrModalOpened: number = 0;
    private taxWaterfallOpened: number = 0;
    private goalPledgeCreated: number = 0;
    private lastFaqExpanded: string = 'none';
    private lastGlossaryClicked: string = 'none';
    private lastHudShortcut: string = 'none';
    private lastHubClicked: string = 'none';

    // Real User Core Web Vitals (RUM)
    private cwvLcpMs: number | null = null;
    private cwvCls: number = 0;
    private cwvInpMs: number | null = null;

    constructor(
        debounceMs: number = 3000,
        transport: AnalyticsTransport = new BeaconFetchTransport()
    ) {
        this.debounceMs = debounceMs;
        this.transport = transport;
        this.dwellStartTimestamp = Date.now();
        this.registerLifecycleListeners();
        this.initPassiveSeoObservers();
        this.initCoreWebVitalsObservers();
    }

    public setActiveStudioTab(tabId: string): void {
        this.activeStudioTab = tabId.replace('tab-', '').replace('panel-', '');
    }

    public setStrategyStarterUsed(presetName: string): void {
        this.strategyStarterUsed = presetName;
    }

    public setGuidedWizardCompleted(): void {
        this.guidedWizardCompleted = 1;
    }

    public setStressTestScenario(scenario: string): void {
        this.stressTestScenario = scenario;
    }

    public setCityBenchmarkCity(city: string): void {
        this.cityBenchmarkCity = city;
    }

    public setScenarioDiffSaved(): void {
        this.scenarioDiffSaved = 1;
    }

    public setQrModalOpened(): void {
        this.qrModalOpened = 1;
    }

    public setTaxWaterfallOpened(): void {
        this.taxWaterfallOpened = 1;
    }

    public setGoalPledgeCreated(): void {
        this.goalPledgeCreated = 1;
    }

    public setFaqExpanded(faqId: string): void {
        this.lastFaqExpanded = faqId;
    }

    public setGlossaryClicked(term: string): void {
        this.lastGlossaryClicked = term;
    }

    public setHudShortcutClicked(shortcutId: string): void {
        this.lastHudShortcut = shortcutId;
    }

    public setInternalHubClicked(linkTarget: string): void {
        this.lastHubClicked = linkTarget;
    }

    private registerLifecycleListeners(): void {
        if (typeof document !== 'undefined') {
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.updateDwellTime();
                    this.flushPendingInsight();
                } else if (document.visibilityState === 'visible') {
                    this.dwellStartTimestamp = Date.now();
                }
            });
        }
        if (typeof window !== 'undefined') {
            window.addEventListener('pagehide', () => {
                this.updateDwellTime();
                this.flushPendingInsight();
            });
        }
    }

    private updateDwellTime(): void {
        const now = Date.now();
        const elapsedSecs = Math.max(0, Math.floor((now - this.dwellStartTimestamp) / 1000));
        this.accumulatedDwellSeconds += elapsedSecs;
        this.dwellStartTimestamp = now;
    }

    public getDwellTimeSeconds(): number {
        const now = Date.now();
        const activeElapsed = (typeof document !== 'undefined' && document.visibilityState === 'visible')
            ? Math.max(0, Math.floor((now - this.dwellStartTimestamp) / 1000))
            : 0;
        return this.accumulatedDwellSeconds + activeElapsed;
    }

    private initPassiveSeoObservers(): void {
        if (typeof window === 'undefined' || typeof document === 'undefined') return;

        // Passive scroll depth calculation throttled via requestAnimationFrame
        let ticking = false;
        const updateScrollDepth = () => {
            const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
            const docHeight = Math.max(
                document.body.scrollHeight,
                document.documentElement.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.offsetHeight
            ) - window.innerHeight;

            if (docHeight > 0) {
                const currentPct = Math.min(100, Math.max(0, Math.round((scrollTop / docHeight) * 100)));
                if (currentPct > this.maxScrollDepthPct) {
                    this.maxScrollDepthPct = currentPct;
                }
            }
            ticking = false;
        };

        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(updateScrollDepth);
                ticking = true;
            }
        }, { passive: true });

        // IntersectionObserver for #quick-answer box
        if (typeof window.IntersectionObserver !== 'undefined') {
            const quickAnswerEl = document.getElementById('quick-answer');
            if (quickAnswerEl) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.quickAnswerObserved = 1;
                            observer.disconnect();
                        }
                    });
                }, { threshold: 0.5 });
                observer.observe(quickAnswerEl);
            }
        }
    }

    private initCoreWebVitalsObservers(): void {
        if (typeof window === 'undefined' || typeof window.PerformanceObserver === 'undefined') return;

        try {
            // LCP Observer
            const lcpObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                const lastEntry = entries[entries.length - 1];
                if (lastEntry) {
                    this.cwvLcpMs = Math.round(lastEntry.startTime);
                }
            });
            lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });

            // CLS Observer
            const clsObserver = new PerformanceObserver((entryList) => {
                for (const entry of entryList.getEntries()) {
                    if (!(entry as any).hadRecentInput) {
                        this.cwvCls = parseFloat((this.cwvCls + ((entry as any).value || 0)).toFixed(3));
                    }
                }
            });
            clsObserver.observe({ type: 'layout-shift', buffered: true });

            // INP / Event Timing Observer
            const inpObserver = new PerformanceObserver((entryList) => {
                for (const entry of entryList.getEntries()) {
                    const duration = (entry as any).duration || 0;
                    if (this.cwvInpMs === null || duration > this.cwvInpMs) {
                        this.cwvInpMs = Math.round(duration);
                    }
                }
            });
            inpObserver.observe({ type: 'first-input', buffered: true });
        } catch {
            // PerformanceObserver buffered types not supported in older browsers
        }
    }

    private resolveReferrerCategory(): string {
        if (typeof document === 'undefined' || !document.referrer) return 'direct';
        const ref = document.referrer.toLowerCase();
        if (ref.includes('google.')) return 'google_organic';
        if (ref.includes('bing.')) return 'bing_organic';
        if (ref.includes('duckduckgo.')) return 'duckduckgo_organic';
        if (ref.includes('perplexity.ai') || ref.includes('chatgpt.com') || ref.includes('claude.ai')) return 'ai_search';
        if (ref.includes('linkedin.com') || ref.includes('t.co') || ref.includes('twitter.com') || ref.includes('x.com') || ref.includes('reddit.com')) return 'social';
        if (typeof window !== 'undefined' && ref.includes(window.location.hostname)) return 'internal_hub';
        return 'other_referral';
    }

    private resolveViewportBucket(): string {
        if (typeof window === 'undefined') return 'desktop';
        const w = window.innerWidth;
        if (w < 640) return 'mobile_sm';
        if (w < 768) return 'mobile_lg';
        if (w < 1024) return 'tablet';
        return 'desktop';
    }

    private resolveConnectionSpeed(): string | undefined {
        if (typeof navigator !== 'undefined' && 'connection' in navigator) {
            const conn = (navigator as any).connection;
            if (conn && typeof conn.effectiveType === 'string') {
                return conn.effectiveType;
            }
        }
        return undefined;
    }

    /**
     * Constructs the structured insight payload from current calculation state.
     */
    public buildPayload(
        inputs: InvestmentInputs,
        results: YearResult[] = [],
        activeGoalMode: string = 'grow',
        extraSignals: ExtraSignals = {}
    ): Record<string, unknown> {
        const lastRow = (Array.isArray(results) && results.length > 0) ? results[results.length - 1] : null;
        const finalCorpus = lastRow ? (lastRow.combined_total || 0) : null;
        const totalInvested = lastRow ? (lastRow.cumulative_invested || 0) : null;
        const wealthMultiplier = (finalCorpus !== null && totalInvested && totalInvested > 0)
            ? parseFloat((finalCorpus / totalInvested).toFixed(2))
            : null;

        const tableViewed = extraSignals.table_viewed ?? 0;
        const deviceType = extraSignals.device_type ?? ((typeof window !== 'undefined' && window.innerWidth < 768) ? 'mobile' : 'desktop');
        const currencyCode = extraSignals.currency || 'INR';

        const isLumpsumOnly = !inputs.enable_swp && inputs.lumpsum > 0 && inputs.sip === 0;
        const calcType = inputs.enable_swp ? 'SWP' : (isLumpsumOnly ? 'Lumpsum' : 'SIP');
        const primaryAmount = inputs.enable_swp
            ? inputs.swp_withdrawal
            : (isLumpsumOnly ? inputs.lumpsum : inputs.sip);

        const landingPath = (typeof window !== 'undefined') ? window.location.pathname : '/';
        const searchParams = (typeof window !== 'undefined') ? new URLSearchParams(window.location.search) : new URLSearchParams();

        return {
            calc_type: calcType,
            amount: primaryAmount,
            duration: inputs.enable_swp ? (inputs.years + inputs.swp_years) : inputs.years,
            step_up_pct: inputs.enable_swp ? inputs.swp_stepup : inputs.stepup,
            currency: currencyCode,
            interest_rate: inputs.rate,
            sip_amount: inputs.sip,
            sip_duration: inputs.years,
            sip_step_up: inputs.stepup,
            swp_enabled: inputs.enable_swp ? 1 : 0,
            swp_withdrawal: inputs.swp_withdrawal,
            swp_duration: inputs.swp_years,
            swp_step_up: inputs.swp_stepup,
            final_corpus: finalCorpus,
            total_invested: totalInvested,
            wealth_multiplier: wealthMultiplier,
            goal_mode: activeGoalMode || 'grow',
            device_type: deviceType,
            table_viewed: tableViewed,
            pdf_downloaded: extraSignals.pdf_downloaded ? 1 : 0,
            pdf_has_custom_name: extraSignals.pdf_has_custom_name ? 1 : 0,
            inflation_enabled: inputs.inflation > 0 ? 1 : (extraSignals.inflation_enabled ? 1 : 0),
            interaction_count: extraSignals.interaction_count || 1,
            preset_clicked: extraSignals.preset_clicked || this.strategyStarterUsed,
            exit_action: extraSignals.exit_action || 'calc_only',

            // SEO & Enhanced Telemetry Attributes
            landing_path: extraSignals.landing_path || landingPath,
            referrer_category: extraSignals.referrer_category || this.resolveReferrerCategory(),
            utm_source: extraSignals.utm_source || searchParams.get('utm_source') || undefined,
            utm_medium: extraSignals.utm_medium || searchParams.get('utm_medium') || undefined,
            scroll_depth_pct: extraSignals.scroll_depth_pct ?? this.maxScrollDepthPct,
            dwell_time_seconds: extraSignals.dwell_time_seconds ?? this.getDwellTimeSeconds(),
            quick_answer_viewed: extraSignals.quick_answer_viewed ?? this.quickAnswerObserved,
            faq_item_expanded: extraSignals.faq_item_expanded || this.lastFaqExpanded,
            glossary_term_clicked: extraSignals.glossary_term_clicked || this.lastGlossaryClicked,
            hud_shortcut_clicked: extraSignals.hud_shortcut_clicked || this.lastHudShortcut,
            active_studio_tab: extraSignals.active_studio_tab || this.activeStudioTab,
            strategy_starter_used: extraSignals.strategy_starter_used || this.strategyStarterUsed,
            guided_wizard_completed: extraSignals.guided_wizard_completed ?? this.guidedWizardCompleted,
            stress_test_scenario: extraSignals.stress_test_scenario || this.stressTestScenario,
            city_benchmark_city: extraSignals.city_benchmark_city || this.cityBenchmarkCity,
            scenario_diff_saved: extraSignals.scenario_diff_saved ?? this.scenarioDiffSaved,
            csv_exported: extraSignals.csv_exported ? 1 : 0,
            qr_modal_opened: extraSignals.qr_modal_opened ?? this.qrModalOpened,
            tax_waterfall_opened: extraSignals.tax_waterfall_opened ?? this.taxWaterfallOpened,
            goal_pledge_created: extraSignals.goal_pledge_created ?? this.goalPledgeCreated,
            internal_hub_clicked: extraSignals.internal_hub_clicked || this.lastHubClicked,
            cwv_lcp_ms: extraSignals.cwv_lcp_ms ?? (this.cwvLcpMs ?? undefined),
            cwv_cls: extraSignals.cwv_cls ?? (this.cwvCls > 0 ? this.cwvCls : undefined),
            cwv_inp_ms: extraSignals.cwv_inp_ms ?? (this.cwvInpMs ?? undefined),
            connection_speed: extraSignals.connection_speed || this.resolveConnectionSpeed(),
            viewport_bucket: extraSignals.viewport_bucket || this.resolveViewportBucket()
        };
    }

    /**
     * Immediately send insight without debouncing (e.g. for conversion actions like PDF download).
     */
    public sendImmediateInsight(
        inputs: InvestmentInputs,
        results: YearResult[] = [],
        activeGoalMode: string = 'grow',
        extraSignals: ExtraSignals = {}
    ): void {
        if (this.insightTimeout) {
            clearTimeout(this.insightTimeout);
            this.insightTimeout = null;
        }
        this.pendingPayload = null;
        const payload = this.buildPayload(inputs, results, activeGoalMode, extraSignals);
        this.transport.send(payload);
    }

    /**
     * Flush any currently pending debounced insight.
     */
    public flushPendingInsight(): void {
        if (this.insightTimeout) {
            clearTimeout(this.insightTimeout);
            this.insightTimeout = null;
        }
        if (this.pendingPayload) {
            const payload = this.pendingPayload;
            this.pendingPayload = null;
            this.transport.send(payload);
        }
    }

    /**
     * Log user calculations in a debounced fashion to prevent network spamming.
     */
    logInsight(
        inputs: InvestmentInputs,
        results: YearResult[] = [],
        activeGoalMode: string = 'grow',
        extraSignals: ExtraSignals = {}
    ): void {
        if (this.insightTimeout) {
            clearTimeout(this.insightTimeout);
        }

        this.pendingPayload = this.buildPayload(inputs, results, activeGoalMode, extraSignals);

        // Post to database endpoint after debounceMs of user input inactivity
        this.insightTimeout = setTimeout(() => {
            this.flushPendingInsight();
        }, this.debounceMs);
    }
}

