# ADR 0006: SEBI/AMFI Regulatory Compounding and Tax Parity

## Status
Accepted

## Context
Discrepancies in compounding formulas (e.g. geometric monthly rates vs nominal rates, Annuity-Immediate vs Annuity-Due, zero-indexed vs one-indexed annual step-up) and capital gains tax rules lead to investor confusion and regulatory non-compliance.

## Decision
All calculation engines (PHP and TypeScript) strictly adhere to standardized Indian regulatory methodologies:
1. **Nominal Rate Division:** Monthly compounding rate is nominal $r / 100 / 12$ (Indian mutual fund standard), never geometric $(1+r)^{1/12} - 1$.
2. **Timing (Annuity-Due):** Contributions are added before monthly compounding: $B_m = (B_{m-1} + C_m - W_m) \times (1 + r/12)$.
3. **Annual Step-Up Progression:** Compounded annually using zero-indexed exponents: $\text{SIP}_y = \text{SIP}_1 \times (1 + g)^{y-1}$.
4. **Budget 2024 Section 112A LTCG Tax:** Capital gains are taxed at 12.5% on profits exceeding the ₹1,25,000 annual statutory exemption.
5. **Strict Accounting Identity Invariance:** $\text{Post-Tax Total} \equiv \text{Combined Total} - \text{LTCG Tax}$ across both runtimes down to the paisa.

## Consequences
- **Positive:** Complete mathematical alignment with AMFI/SEBI standards and banking industry baselines.
- **Negative:** Rigid equation constraints requiring exhaustive mathematical parity test vectors in CI.
