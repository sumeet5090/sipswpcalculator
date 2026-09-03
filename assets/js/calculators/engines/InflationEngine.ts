/**
 * InflationEngine
 * High-precision TypeScript future cost projection and purchasing power erosion engine.
 * Computes forward cost inflation FV = PV * (1 + i)^t and reverse purchasing power PV = FV / (1 + i)^t matching PHP parity.
 */

export interface InflationScheduleRow {
    year: number;
    future_cost: number;
    purchasing_power: number;
    purchasing_power_loss_percentage: number;
}

export interface InflationResult {
    present_value: number;
    inflation_rate: number;
    years: number;
    future_cost: number;
    purchasing_power: number;
    cost_increase: number;
    purchasing_power_loss_percentage: number;
    schedule: InflationScheduleRow[];
}

export class InflationEngine {
    public static calculate(
        presentValue: number,
        inflationRate: number,
        years: number
    ): InflationResult {
        const pv = Math.max(0, presentValue);
        const iPercent = Math.max(0, inflationRate);
        const t = Math.max(0, Math.floor(years));

        const i = iPercent / 100.0;

        if (pv === 0 || t === 0) {
            return {
                present_value: Number(pv.toFixed(2)),
                inflation_rate: Number(iPercent.toFixed(2)),
                years: t,
                future_cost: Number(pv.toFixed(2)),
                purchasing_power: Number(pv.toFixed(2)),
                cost_increase: 0,
                purchasing_power_loss_percentage: 0,
                schedule: []
            };
        }

        const futureCost = pv * Math.pow(1.0 + i, t);
        const purchasingPower = pv / Math.pow(1.0 + i, t);
        const costIncrease = futureCost - pv;
        const powerLossPct = (1.0 - (1.0 / Math.pow(1.0 + i, t))) * 100.0;

        const schedule: InflationScheduleRow[] = [];
        for (let year = 1; year <= t; year++) {
            const yearFutureCost = pv * Math.pow(1.0 + i, year);
            const yearPower = pv / Math.pow(1.0 + i, year);
            const yearLossPct = (1.0 - (1.0 / Math.pow(1.0 + i, year))) * 100.0;

            schedule.push({
                year,
                future_cost: Number(yearFutureCost.toFixed(2)),
                purchasing_power: Number(yearPower.toFixed(2)),
                purchasing_power_loss_percentage: Number(yearLossPct.toFixed(2))
            });
        }

        return {
            present_value: Number(pv.toFixed(2)),
            inflation_rate: Number(iPercent.toFixed(2)),
            years: t,
            future_cost: Number(futureCost.toFixed(2)),
            purchasing_power: Number(purchasingPower.toFixed(2)),
            cost_increase: Number(costIncrease.toFixed(2)),
            purchasing_power_loss_percentage: Number(powerLossPct.toFixed(2)),
            schedule
        };
    }
}
