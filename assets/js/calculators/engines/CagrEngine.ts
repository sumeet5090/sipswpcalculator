/**
 * CagrEngine
 * High-precision TypeScript Compound Annual Growth Rate (CAGR) engine.
 * Computes CAGR = (V_final / V_begin)^(1 / t) - 1 matching PHP parity.
 */

export interface CagrResult {
    beginning_value: number;
    ending_value: number;
    years: number;
    cagr_percentage: number;
    absolute_return_percentage: number;
    total_gain: number;
    multiplier: number;
}

import { MathPrecisionHelper } from '../helpers/MathPrecisionHelper.ts';

export class CagrEngine {
    public static calculate(
        beginningValue: number,
        endingValue: number,
        years: number
    ): CagrResult {
        if (beginningValue <= 0) {
            throw new Error('Beginning value must be strictly greater than 0.');
        }

        if (years <= 0) {
            throw new Error('Investment duration must be strictly greater than 0 years.');
        }

        const v0 = beginningValue;
        const vt = Math.max(0, endingValue);
        const t = years;

        const totalGain = vt - v0;
        const absoluteReturn = (totalGain / v0) * 100.0;
        const multiplier = vt / v0;

        let cagr: number;
        if (vt === 0) {
            cagr = -100.0;
        } else {
            cagr = (Math.pow(vt / v0, 1.0 / t) - 1.0) * 100.0;
        }

        return {
            beginning_value: MathPrecisionHelper.round2(v0),
            ending_value: MathPrecisionHelper.round2(vt),
            years: MathPrecisionHelper.round2(t),
            cagr_percentage: MathPrecisionHelper.round4(cagr),
            absolute_return_percentage: MathPrecisionHelper.round4(absoluteReturn),
            total_gain: MathPrecisionHelper.round2(totalGain),
            multiplier: MathPrecisionHelper.round4(multiplier)
        };
    }
}
