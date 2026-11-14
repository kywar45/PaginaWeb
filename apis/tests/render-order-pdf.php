<?php

require __DIR__ . '/../src/Pdf/OrderPdf.php';
require __DIR__ . '/fixtures/order-pdf.php';

$directory = dirname(__DIR__, 2) . '/output/pdf';
if (!is_dir($directory)) mkdir($directory, 0777, true);
foreach (array(false => 'pedido-demostracion.pdf', true => 'pedido-multipagina.pdf') as $long => $filename) {
    $pdf = new \App\Pdf\OrderPdf(orderPdfFixture((bool) $long));
    $bytes = $pdf->render();
    if (substr($bytes, 0, 5) !== '%PDF-') throw new \RuntimeException('Invalid PDF');
    file_put_contents($directory . '/' . $filename, $bytes);
    echo $filename . ': ' . strlen($bytes) . " bytes\n";
}
