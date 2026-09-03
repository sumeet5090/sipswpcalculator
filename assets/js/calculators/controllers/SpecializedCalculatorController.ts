import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { SliderManager } from '../SliderManager';
import type { ChartManager } from '../ChartManager';
import type { ResultsController } from './ResultsController';
import type { SummaryMetricsController } from './SummaryMetricsController';
import { OdometerController } from './OdometerController';
import { YearResult } from '../../types';

import { CompoundInterestEngine } from '../engines/CompoundInterestEngine';
import { CagrEngine } from '../engines/CagrEngine';
import { EmiEngine } from '../engines/EmiEngine';
import { InflationEngine } from '../engines/InflationEngine';
import { PpfEngine } from '../engines/PpfEngine';
import { FdEngine } from '../engines/FdEngine';

export class SpecializedCalculatorController {
    private mode: string;
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private sliderManager: SliderManager;
    private chartManager: ChartManager;
    private resultsController: ResultsController;
    private summaryMetricsController: SummaryMetricsController;
    private odometer: OdometerController;

    constructor(
        mode: string,
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        sliderManager: SliderManager,
        chartManager: ChartManager,
        resultsController: ResultsController,
        summaryMetricsController: SummaryMetricsController
    ) {
        this.mode = mode;
        this.dom = dom;
        this.formatter = formatter;
        this.sliderManager = sliderManager;
        this.chartManager = chartManager;
        this.resultsController = resultsController;
        this.summaryMetricsController = summaryMetricsController;
        this.odometer = new OdometerController(dom, formatter);
    }

    public init(): void {
        this.sliderManager.setTriggerFn(() => this.calculate());
        this.bindSliderPairs();
        this.bindAdditionalControls();
        this.setupCustomCardLabels();
        this.calculate();
    }

    private bindSliderPairs(): void {
        switch (this.mode) {
            case 'compound_interest':
                this.sliderManager.sync('ci_principal', 'ci_principal_range');
                this.sliderManager.sync('ci_rate', 'ci_rate_range');
                this.sliderManager.sync('ci_years', 'ci_years_range');
                break;
            case 'cagr':
                this.sliderManager.sync('cagr_initial', 'cagr_initial_range');
                this.sliderManager.sync('cagr_final', 'cagr_final_range');
                this.sliderManager.sync('cagr_years', 'cagr_years_range');
                break;
            case 'emi':
                this.sliderManager.sync('emi_principal', 'emi_principal_range');
                this.sliderManager.sync('emi_rate', 'emi_rate_range');
                this.sliderManager.sync('emi_years', 'emi_years_range');
                break;
            case 'inflation':
                this.sliderManager.sync('inf_amount', 'inf_amount_range');
                this.sliderManager.sync('inf_rate', 'inf_rate_range');
                this.sliderManager.sync('inf_years', 'inf_years_range');
                break;
            case 'ppf':
                this.sliderManager.sync('ppf_deposit', 'ppf_deposit_range');
                this.sliderManager.sync('ppf_rate', 'ppf_rate_range');
                this.sliderManager.sync('ppf_years', 'ppf_years_range');
                break;
            case 'fd':
                this.sliderManager.sync('fd_principal', 'fd_principal_range');
                this.sliderManager.sync('fd_rate', 'fd_rate_range');
                this.sliderManager.sync('fd_years', 'fd_years_range');
                break;
        }

        // Attach live input recalculation listeners
        const form = this.dom.getElement('calculator-form');
        if (form) {
            form.addEventListener('input', () => this.calculate());
            form.addEventListener('change', () => this.calculate());
        }
    }

    private bindAdditionalControls(): void {
        const ciFreq = this.dom.getElement('ci_frequency');
        if (ciFreq) {
            ciFreq.addEventListener('change', () => this.calculate());
        }

        const ppfTiming = this.dom.getElement('ppf_timing');
        if (ppfTiming) {
            ppfTiming.addEventListener('change', () => this.calculate());
        }

        const fdSenior = this.dom.getElement('fd_senior');
        if (fdSenior) {
            fdSenior.addEventListener('change', () => this.calculate());
        }

        const fdFreq = this.dom.getElement('fd_frequency');
        if (fdFreq) {
            fdFreq.addEventListener('change', () => this.calculate());
        }
    }

    private setupCustomCardLabels(): void {
        // Adjust grid for 3 cards
        const summaryGrid = this.dom.getElement('summary-cards-grid');
        const cardWithdrawn = this.dom.getElement('card-withdrawn');
        if (summaryGrid && cardWithdrawn) {
            summaryGrid.className = 'grid grid-cols-2 sm:grid-cols-3 gap-3 transition-all duration-300';
            cardWithdrawn.classList.add('hidden');
        }

        const titleInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) span:nth-child(2)');
        const subInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) div:last-child');
        const titleInterest = document.querySelector('#title-interest span:nth-child(2)');
        const titleCorpus = document.querySelector('#title-corpus span:nth-child(2)');

        switch (this.mode) {
            case 'compound_interest':
                if (titleInvested) titleInvested.textContent = 'Principal Invested';
                if (subInvested) subInvested.textContent = 'Starting Capital';
                if (titleInterest) titleInterest.textContent = 'Compound Interest';
                if (titleCorpus) titleCorpus.textContent = 'Maturity Value';
                break;
            case 'cagr':
                if (titleInvested) titleInvested.textContent = 'Initial Value';
                if (subInvested) subInvested.textContent = 'Starting Investment';
                if (titleInterest) titleInterest.textContent = 'Absolute Gain';
                if (titleCorpus) titleCorpus.textContent = 'CAGR Return';
                break;
            case 'emi':
                if (titleInvested) titleInvested.textContent = 'Loan Amount';
                if (subInvested) subInvested.textContent = 'Principal Borrowed';
                if (titleInterest) titleInterest.textContent = 'Total Interest';
                if (titleCorpus) titleCorpus.textContent = 'Monthly EMI';
                break;
            case 'inflation':
                if (titleInvested) titleInvested.textContent = "Today's Cost";
                if (subInvested) subInvested.textContent = 'Current Living Expense';
                if (titleInterest) titleInterest.textContent = 'Cost Escalation';
                if (titleCorpus) titleCorpus.textContent = 'Future Living Cost';
                break;
            case 'ppf':
                if (titleInvested) titleInvested.textContent = 'Total PPF Deposit';
                if (subInvested) subInvested.textContent = 'Statutory Contributions';
                if (titleInterest) titleInterest.textContent = 'Tax-Free Interest';
                if (titleCorpus) titleCorpus.textContent = 'Maturity Corpus';
                break;
            case 'fd':
                if (titleInvested) titleInvested.textContent = 'Deposit Amount';
                if (subInvested) subInvested.textContent = 'Bank FD Principal';
                if (titleInterest) titleInterest.textContent = 'Interest Earned';
                if (titleCorpus) titleCorpus.textContent = 'Maturity Value';
                break;
        }
    }

    public calculate(): void {
        switch (this.mode) {
            case 'compound_interest':
                this.calculateCompoundInterest();
                break;
            case 'cagr':
                this.calculateCagr();
                break;
            case 'emi':
                this.calculateEmi();
                break;
            case 'inflation':
                this.calculateInflation();
                break;
            case 'ppf':
                this.calculatePpf();
                break;
            case 'fd':
                this.calculateFd();
                break;
        }
    }

    private calculateCompoundInterest(): void {
        const principal = Math.max(0, parseFloat(this.dom.getValue('ci_principal') || '500000') || 500000);
        const rate = Math.max(0, parseFloat(this.dom.getValue('ci_rate') || '12') || 12);
        const years = Math.max(1, parseFloat(this.dom.getValue('ci_years') || '10') || 10);
        const freqSelect = this.dom.getElement<HTMLSelectElement>('ci_frequency');
        const frequency = freqSelect ? parseInt(freqSelect.value, 10) || 12 : 12;

        const res = CompoundInterestEngine.calculate(principal, rate, years, frequency);

        const combined: YearResult[] = res.schedule.map(item => ({
            year: item.year,
            begin_balance: item.opening_balance,
            sip_monthly: null,
            annual_contribution: item.year === 1 ? principal : 0,
            cumulative_invested: principal,
            interest: item.interest_earned,
            combined_total: item.closing_balance,
            post_tax_total: item.closing_balance
        }));

        this.odometer.animateValue('summary-invested', principal);
        this.odometer.animateValue('summary-interest', res.total_interest);
        this.odometer.animateValue('summary-corpus', res.final_amount);

        const gainBadge = this.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            const gainPct = principal > 0 ? Math.round((res.total_interest / principal) * 100) : 0;
            gainBadge.textContent = `+${gainPct}% (EAR: ${res.effective_annual_rate}%)`;
        }

        this.resultsController.updateTable(combined, false);
        this.chartManager.updateChart(combined, false);
        this.summaryMetricsController.fitSummaryCards();
    }

    private calculateCagr(): void {
        const initial = Math.max(1, parseFloat(this.dom.getValue('cagr_initial') || '100000') || 100000);
        const finalVal = Math.max(1, parseFloat(this.dom.getValue('cagr_final') || '250000') || 250000);
        const years = Math.max(0.1, parseFloat(this.dom.getValue('cagr_years') || '5') || 5);

        const res = CagrEngine.calculate(initial, finalVal, years);

        const combined: YearResult[] = [];
        const fullYears = Math.max(1, Math.ceil(years));
        const annualGrowthRate = res.cagr_percentage / 100.0;
        let prevVal = initial;
        for (let yr = 1; yr <= fullYears; yr++) {
            const tFrac = Math.min(yr, years);
            const currentVal = Math.round(initial * Math.pow(1.0 + annualGrowthRate, tFrac));
            combined.push({
                year: yr,
                begin_balance: prevVal,
                sip_monthly: null,
                annual_contribution: yr === 1 ? initial : 0,
                cumulative_invested: initial,
                interest: currentVal - prevVal,
                combined_total: currentVal,
                post_tax_total: currentVal
            });
            prevVal = currentVal;
        }

        this.odometer.animateValue('summary-invested', initial);
        this.odometer.animateValue('summary-interest', res.total_gain);

        const corpusEl = this.dom.getElement('summary-corpus');
        if (corpusEl) {
            corpusEl.textContent = `${res.cagr_percentage.toFixed(2)}% p.a.`;
        }

        const gainBadge = this.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = `+${res.absolute_return_percentage.toFixed(1)}% (${res.multiplier.toFixed(2)}x)`;
        }

        this.resultsController.updateTable(combined, false);
        this.chartManager.updateChart(combined, false);
        this.summaryMetricsController.fitSummaryCards();
    }

    private calculateEmi(): void {
        const principal = Math.max(1000, parseFloat(this.dom.getValue('emi_principal') || '3000000') || 3000000);
        const rate = Math.max(0.1, parseFloat(this.dom.getValue('emi_rate') || '8.5') || 8.5);
        const years = Math.max(1, parseFloat(this.dom.getValue('emi_years') || '20') || 20);

        const res = EmiEngine.calculate(principal, rate, years);

        let cumulativePrincipalPaid = 0;
        const combined: YearResult[] = res.schedule.map(item => {
            cumulativePrincipalPaid += item.principal_paid;
            return {
                year: item.year,
                begin_balance: item.opening_balance,
                sip_monthly: Math.round(res.monthly_emi),
                annual_contribution: item.principal_paid,
                cumulative_invested: Math.round(cumulativePrincipalPaid),
                interest: item.interest_paid,
                combined_total: item.closing_balance,
                post_tax_total: item.closing_balance
            };
        });

        this.odometer.animateValue('summary-invested', principal);
        this.odometer.animateValue('summary-interest', res.total_interest);

        const corpusEl = this.dom.getElement('summary-corpus');
        if (corpusEl) {
            corpusEl.textContent = `${this.formatter.format(Math.round(res.monthly_emi))} / mo`;
        }

        const gainBadge = this.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = `${res.interest_ratio_percentage.toFixed(1)}% of Loan`;
        }

        this.resultsController.updateTable(combined, false);
        this.chartManager.updateChart(combined, false);
        this.summaryMetricsController.fitSummaryCards();
    }

    private calculateInflation(): void {
        const amount = Math.max(1, parseFloat(this.dom.getValue('inf_amount') || '50000') || 50000);
        const rate = Math.max(0, parseFloat(this.dom.getValue('inf_rate') || '6') || 6);
        const years = Math.max(1, parseFloat(this.dom.getValue('inf_years') || '15') || 15);

        const res = InflationEngine.calculate(amount, rate, years);

        let prevCost = amount;
        const combined: YearResult[] = res.schedule.map(item => {
            const interestStep = item.future_cost - prevCost;
            prevCost = item.future_cost;
            return {
                year: item.year,
                begin_balance: item.future_cost - interestStep,
                sip_monthly: null,
                annual_contribution: amount,
                cumulative_invested: amount,
                interest: interestStep,
                combined_total: item.future_cost,
                post_tax_total: item.purchasing_power
            };
        });

        this.odometer.animateValue('summary-invested', amount);
        this.odometer.animateValue('summary-interest', res.cost_increase);
        this.odometer.animateValue('summary-corpus', res.future_cost);

        const gainBadge = this.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = `-${res.purchasing_power_loss_percentage.toFixed(1)}% Value`;
        }

        this.resultsController.updateTable(combined, false);
        this.chartManager.updateChart(combined, false);
        this.summaryMetricsController.fitSummaryCards();
    }

    private calculatePpf(): void {
        const deposit = Math.max(500, parseFloat(this.dom.getValue('ppf_deposit') || '150000') || 150000);
        const rate = Math.max(1, parseFloat(this.dom.getValue('ppf_rate') || '7.1') || 7.1);
        const years = Math.max(15, parseFloat(this.dom.getValue('ppf_years') || '15') || 15);
        const timingSelect = this.dom.getElement<HTMLSelectElement>('ppf_timing');
        const timing = timingSelect?.value === 'monthly' ? 'monthly' : 'beginning';

        const res = PpfEngine.calculate(deposit, rate, years, timing);

        let cumulativeDeposit = 0;
        const combined: YearResult[] = res.schedule.map(item => {
            cumulativeDeposit += item.annual_deposit;
            return {
                year: item.year,
                begin_balance: item.opening_balance,
                sip_monthly: null,
                annual_contribution: item.annual_deposit,
                cumulative_invested: cumulativeDeposit,
                interest: item.interest_earned,
                combined_total: item.closing_balance,
                post_tax_total: item.closing_balance
            };
        });

        this.odometer.animateValue('summary-invested', res.total_invested);
        this.odometer.animateValue('summary-interest', res.total_interest);
        this.odometer.animateValue('summary-corpus', res.maturity_amount);

        const gainBadge = this.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = '100% Tax-Free (EEE)';
        }

        this.resultsController.updateTable(combined, false);
        this.chartManager.updateChart(combined, false);
        this.summaryMetricsController.fitSummaryCards();
    }

    private calculateFd(): void {
        const principal = Math.max(1000, parseFloat(this.dom.getValue('fd_principal') || '500000') || 500000);
        const rate = Math.max(0.1, parseFloat(this.dom.getValue('fd_rate') || '7.0') || 7.0);
        const years = Math.max(0.25, parseFloat(this.dom.getValue('fd_years') || '3.0') || 3.0);
        const seniorCheck = this.dom.getElement<HTMLInputElement>('fd_senior');
        const isSenior = seniorCheck ? seniorCheck.checked : false;
        const freqSelect = this.dom.getElement<HTMLSelectElement>('fd_frequency');
        const frequency = (freqSelect?.value || 'cumulative') as 'cumulative' | 'monthly' | 'quarterly' | 'annual';

        const res = FdEngine.calculate(principal, rate, years, isSenior, frequency);

        const combined: YearResult[] = res.yearly_schedule.map(item => ({
            year: item.year,
            begin_balance: item.opening_balance,
            sip_monthly: null,
            annual_contribution: item.year === 1 ? principal : 0,
            cumulative_invested: principal,
            interest: item.interest_earned,
            combined_total: item.closing_balance,
            post_tax_total: item.closing_balance
        }));

        this.odometer.animateValue('summary-invested', principal);
        this.odometer.animateValue('summary-interest', res.total_interest);

        const finalVal = frequency === 'cumulative' ? res.maturity_amount : res.periodic_payout;
        this.odometer.animateValue('summary-corpus', finalVal);

        const titleCorpus = document.querySelector('#title-corpus span:nth-child(2)');
        if (titleCorpus) {
            titleCorpus.textContent = frequency === 'cumulative' ? 'Maturity Amount' : 'Periodic Payout';
        }

        const gainBadge = this.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = isSenior ? '+0.5% Senior Card' : `${res.effective_rate}% Card Rate`;
        }

        this.resultsController.updateTable(combined, false);
        this.chartManager.updateChart(combined, false);
        this.summaryMetricsController.fitSummaryCards();
    }
}
