<?php

require __DIR__ . '/../src/Pdf/OrderPdf.php';
require __DIR__ . '/fixtures/order-pdf.php';

function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
$fixture = orderPdfFixture();
$display = \App\Pdf\OrderPdf::groupProducts($fixture['productos']);
check(count($display['main']) === 7 && count($display['groups']) === 4, 'Four special codes move to the side');
check(array_sum(array_column($display['main'], 'cantidad')) === $fixture['piezas'], 'Grouped quantities reconcile with all pieces');
check(array_sum(array_column($display['main'], 'importe_centavos')) === $fixture['subtotal_centavos'], 'Grouped amounts reconcile without losing historical prices');
$gel = $display['groups']['GEL-01'];
check($gel['cantidad'] === 24 && count($gel['variantes']) === 2 && count($gel['precios']) === 2, 'Variants and different prices retained');
$gelSummary = array_values(array_filter($display['main'], function ($item) { return $item['codigo'] === 'GEL-01'; }))[0];
check($gelSummary['precios_varios'] && $gelSummary['precio_unidades'] === null && $gelSummary['importe_centavos'] === 159000, 'Mixed-price summary never invents a uniform price');
$repeat = $fixture['productos'][1];
$deduplicated = \App\Pdf\OrderPdf::groupProducts(array($repeat, $repeat));
check(count($deduplicated['groups']['GEL-01']['variantes']) === 1 && $deduplicated['main'][0]['cantidad'] === 36, 'Repeated exact variant combined, quantities preserved');
check(!$deduplicated['main'][0]['precios_varios'] && $deduplicated['main'][0]['precio_unidades'] === 650000, 'Uniform price stays numeric');
$noGroups = \App\Pdf\OrderPdf::groupProducts(array($fixture['productos'][0]));
check(!$noGroups['groups'] && count($noGroups['main']) === 1, 'Non-special products remain in main list');

// Many real-size variant names exercise independent sidebar pagination.
$stress = $fixture;
$stress['productos'] = array();
for ($i = 1; $i <= 180; $i++) $stress['productos'][] = array('codigo' => 'GEL-01', 'nombre' => 'Gel semi permanente Le MUSSA - Gama TONO ' . $i, 'cantidad' => 1, 'precio_centavos' => 6500, 'importe_centavos' => 6500);
$stress['piezas'] = 180;
$stress['subtotal_centavos'] = $stress['total_centavos'] = 180 * 6500;
$stress['ajustes_centavos'] = 0;
$bytes = (new \App\Pdf\OrderPdf($stress))->render();
check(substr($bytes, 0, 5) === '%PDF-' && preg_match_all('/\/Type \/Page\b/', $bytes) >= 3, 'Long variant tables continue safely across pages');
if (in_array('--render-stress', $argv, true)) {
    file_put_contents(dirname(__DIR__, 2) . '/tmp/pdfs/side-overflow.pdf', $bytes);
    $plain = $fixture;
    foreach ($plain['productos'] as $i => $item) $plain['productos'][$i]['codigo'] = 'NORMAL-' . ($i + 1);
    file_put_contents(dirname(__DIR__, 2) . '/tmp/pdfs/no-sidebar.pdf', (new \App\Pdf\OrderPdf($plain))->render());
}
echo "Compact PDF tests passed: grouped quantities, historical totals, mixed prices, exact variants and sidebar pagination.\n";
