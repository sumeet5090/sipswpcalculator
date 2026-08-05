<?php

declare(strict_types=1);

namespace Services;

use Core\PdfReportTemplate;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PdfGeneratorService
 * Encapsulates Dompdf configuration and rendering workflow for report generation.
 */
class PdfGeneratorService
{
    /**
     * Render inputs into binary PDF content string.
     *
     * @param array<string, mixed> $inputs
     * @return string Binary PDF stream
     */
    public function generate(array $inputs): string
    {
        $html = PdfReportTemplate::render($inputs);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);

        ob_start();
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfBinary = $dompdf->output();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return (string) $pdfBinary;
    }
}
