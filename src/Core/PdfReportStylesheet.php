<?php

declare(strict_types=1);

namespace Core;

/**
 * PdfReportStylesheet
 * Dedicated CSS stylesheet generator for Dompdf executive report templates.
 */
class PdfReportStylesheet
{
    /**
     * Generate dynamic CSS styles based on year count for optimal page flow.
     */
    public function getStyles(int $yearsCount): string
    {
        $tablePadding = ($yearsCount > 25) ? '3px 6px' : '5px 8px';
        $thPadding = ($yearsCount > 25) ? '5px 6px' : '7px 8px';
        $tableFontSize = ($yearsCount > 25) ? '7.5px' : '8.5px';
        $boxMargin = ($yearsCount > 25) ? '10px' : '16px';

        return "
            @page { margin: 24px 32px 28px 32px; }
            body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 9.5px; line-height: 1.45; background-color: #ffffff; margin: 0; padding: 0; }
            .top-accent { height: 4px; background: #059669; margin-bottom: 16px; border-radius: 2px; }
            .header-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
            .header-table td { vertical-align: middle; }
            .doc-title { font-size: 20px; font-weight: bold; color: #0f172a; letter-spacing: -0.3px; margin: 0 0 2px 0; }
            .doc-subtitle { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
            .advisor-badge { background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 6px; text-align: right; display: inline-block; }
            .advisor-label { font-size: 8px; color: #059669; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; }
            .advisor-name { font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 1px; }
            .meta-ribbon { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 14px; margin-bottom: 16px; }
            .meta-table { width: 100%; border-collapse: collapse; font-size: 9px; }
            .meta-table td { color: #64748b; }
            .meta-table td strong { color: #0f172a; font-weight: bold; }
            .kpi-container { width: 100%; margin-bottom: 16px; }
            .kpi-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; }
            .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-top: 3px solid #64748b; padding: 10px 6px; border-radius: 6px; text-align: center; }
            .kpi-card.invested { border-top-color: #0f172a; background: #f8fafc; }
            .kpi-card.returns { border-top-color: #059669; background: #f0fdf4; }
            .kpi-card.swp { border-top-color: #e11d48; background: #fff1f2; }
            .kpi-card.corpus { border-top-color: #0284c7; background: #f0f9ff; }
            .kpi-card.multiplier { border-top-color: #6366f1; background: #f5f3ff; }
            .kpi-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; margin-bottom: 3px; display: block; font-weight: bold; }
            .kpi-val { font-size: 13.5px; font-weight: bold; color: #0f172a; margin: 0; white-space: nowrap; }
            .section-heading { font-size: 11px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.6px; padding-bottom: 4px; border-bottom: 2px solid #059669; margin-top: 16px; margin-bottom: 10px; page-break-after: avoid; }
            .config-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; page-break-inside: avoid; }
            .config-table { width: 100%; border-collapse: collapse; font-size: 9px; }
            .config-table th { text-align: left; color: #64748b; font-weight: bold; width: 25%; padding: 4px 0; }
            .config-table td { text-align: left; color: #0f172a; font-weight: bold; width: 25%; padding: 4px 0; white-space: nowrap; }
            .phase-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
            .phase-badge.sip { background: #dcfce7; color: #166534; }
            .phase-badge.swp { background: #ffe4e6; color: #9f1239; }
            .chart-box { text-align: center; margin: 6px 0 8px 0; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px; background: #ffffff; page-break-inside: avoid; width: 100%; box-sizing: border-box; }
            .chart-box img { width: 100%; height: auto; max-height: 475px; display: block; margin: 0 auto; }
            .milestones-container { margin: 8px 0 0 0; page-break-inside: avoid; }
            .milestones-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; table-layout: fixed; }
            .milestone-card { background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #059669; padding: 6px 6px; border-radius: 6px; text-align: center; vertical-align: top; }
            .milestone-badge { font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #15803d; margin-bottom: 2px; display: block; white-space: nowrap; }
            .milestone-val { font-size: 11px; font-weight: bold; color: #0f172a; margin: 0; white-space: nowrap; }
            .milestone-sub { font-size: 7.5px; color: #475569; margin-top: 2px; white-space: nowrap; }
            .results-table-container { margin-top: 10px; }
            .results-table-container table { width: 100%; border-collapse: collapse; font-size: {$tableFontSize}; }
            .results-table-container table thead { display: table-header-group; }
            .results-table-container tr { page-break-inside: avoid; }
            .results-table-container th { background-color: #0f172a; color: #ffffff; padding: {$thPadding}; text-align: right; font-weight: bold; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.5px; }
            .results-table-container th:first-child { text-align: left; border-top-left-radius: 4px; }
            .results-table-container th:last-child { border-top-right-radius: 4px; }
            .results-table-container td { padding: {$tablePadding}; text-align: right; border-bottom: 1px solid #e2e8f0; color: #334155; white-space: nowrap; }
            .results-table-container tr:nth-child(even) td { background-color: #f8fafc; }
            .results-table-container td:first-child { text-align: left; font-weight: bold; color: #0f172a; }
            .purchasing-power { margin-top: {$boxMargin}; padding: 10px 14px; background-color: #fffbeb; border-left: 4px solid #f59e0b; font-size: 8.5px; color: #92400e; border-radius: 0 6px 6px 0; page-break-inside: avoid; }
            .purchasing-power strong { display: block; margin-bottom: 2px; font-size: 9px; text-transform: uppercase; color: #b45309; font-weight: bold; }
            .disclaimer { margin-top: {$boxMargin}; padding: 10px 14px; background-color: #fef2f2; border-left: 4px solid #e11d48; font-size: 8px; color: #991b1b; border-radius: 0 6px 6px 0; page-break-inside: avoid; }
            .disclaimer strong { display: block; margin-bottom: 2px; text-transform: uppercase; font-size: 8.5px; font-weight: bold; }
            .doc-footer { margin-top: 18px; padding-top: 10px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 7.5px; }
        ";
    }
}
