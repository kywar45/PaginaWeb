<?php

require __DIR__ . '/../src/Pdf/OrderPdf.php';
require __DIR__ . '/fixtures/order-pdf.php';

class OrderPdfHeaderFixture extends \App\Pdf\OrderPdf
{
    public $background;
    protected function headerImagePath() { return $this->background; }
}

// Deployment failures: absent asset and a file uploaded with invalid contents.
$invalid = tempnam(sys_get_temp_dir(), 'pdf-header-');
try {
    file_put_contents($invalid, 'not a PNG');
    foreach (array($invalid . '.missing', $invalid) as $path) {
        $pdf = new OrderPdfHeaderFixture(orderPdfFixture());
        $pdf->background = $path;
        $bytes = $pdf->render();
        if (substr($bytes, 0, 5) !== '%PDF-' || strpos($bytes, '%%EOF') === false) {
            throw new \RuntimeException('Unavailable header prevented PDF generation');
        }
    }
    echo "PDF header deployment tests passed: missing and invalid images fall back safely.\n";
} finally {
    unlink($invalid);
}
