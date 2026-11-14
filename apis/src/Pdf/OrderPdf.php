<?php

namespace App\Pdf;

require_once dirname(__DIR__, 2) . '/lib/fpdf/fpdf.php';

class OrderPdf extends \FPDF
{
    private $order;
    private $logo;
    private $pink = array(223, 47, 97);
    private $ink = array(54, 39, 49);
    private $muted = array(126, 108, 119);
    private $mainWidth = 190;
    private $columns;
    private $bodyTop;
    private $bottom = 278;

    public function __construct(array $order, $logo = null)
    {
        parent::__construct('P', 'mm', 'A4');
        $this->order = $order;
        $this->logo = $logo ?: dirname(__DIR__, 2) . '/assets/logo-white.png';
        $this->SetMargins(10, 10, 10);
        $this->SetAutoPageBreak(false);
        $this->AliasNbPages();
        $this->SetTitle(self::encodeText('Pedido ' . ($order['orden'] ?: $order['id']) . ' | Supernova'));
        $this->SetAuthor('Importadora Supernova');
        $this->SetCreator('Supernova Store');
    }

    private static function encodeText($value)
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
    }

    private function label($x, $y, $value, $size = 7, $style = '', $color = null, $width = 0, $align = 'L')
    {
        $this->SetFont('Arial', $style, $size);
        $this->SetTextColor(...($color ?: $this->ink));
        $this->SetXY($x, $y);
        $this->Cell($width, 3.4, self::encodeText($value), 0, 0, $align);
    }

    private function wrap($value, $width, $size = 7, $style = '')
    {
        $this->SetFont('Arial', $style, $size);
        $lines = array();
        foreach (explode("\n", (string) $value) as $paragraph) {
            $line = '';
            foreach (preg_split('/\s+/u', trim($paragraph), -1, PREG_SPLIT_NO_EMPTY) as $word) {
                $candidate = $line === '' ? $word : $line . ' ' . $word;
                if ($this->GetStringWidth(self::encodeText($candidate)) <= $width) { $line = $candidate; continue; }
                if ($line !== '') { $lines[] = $line; $line = ''; }
                foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) as $character) {
                    if ($line !== '' && $this->GetStringWidth(self::encodeText($line . $character)) > $width) { $lines[] = $line; $line = ''; }
                    $line .= $character;
                }
            }
            $lines[] = $line;
        }
        return $lines ?: array('');
    }

    private function lines($x, $y, array $lines, $size = 7, $style = '', $color = null, $lineHeight = 3.2, $width = 0, $align = 'L')
    {
        foreach ($lines as $line) { $this->label($x, $y, $line, $size, $style, $color, $width, $align); $y += $lineHeight; }
        return $y;
    }

    private function fitLabel($x, $y, $value, $width, $size = 7, $style = '', $align = 'R', $color = null)
    {
        $this->SetFont('Arial', $style, $size);
        while ($size > 5.5 && $this->GetStringWidth(self::encodeText($value)) > $width - 2) { $size -= .25; $this->SetFont('Arial', $style, $size); }
        $this->label($x, $y, $value, $size, $style, $color, $width, $align);
    }

    private function money($cents) { return '$' . number_format($cents / 100, 2, '.', ','); }
    private function unitPrice($units) { return '$' . number_format($units / 10000, $units % 100 === 0 ? 2 : 4, '.', ','); }

    public static function groupProducts(array $items)
    {
        $titles = array('GEL-01' => 'Gel Le MUSSA (6 pz)', 'GEL-70' => 'Gel Le MUSSA (12 pz)', 'PU-06' => 'Puntas de metal', 'PU-08' => 'Puntas de cerámica');
        $regular = array();
        $groups = array();
        foreach ($items as $item) {
            $code = strtoupper(trim($item['codigo']));
            if (!isset($titles[$code])) { $regular[] = $item; continue; }
            if (!isset($groups[$code])) $groups[$code] = array('codigo' => $code, 'titulo' => $titles[$code], 'cantidad' => 0, 'importe_centavos' => 0, 'precios' => array(), 'variantes' => array());
            $units = isset($item['precio_unidades']) ? $item['precio_unidades'] : $item['precio_centavos'] * 100;
            $key = $item['nombre'] . "\0" . $units;
            if (!isset($groups[$code]['variantes'][$key])) $groups[$code]['variantes'][$key] = array('nombre' => $item['nombre'], 'cantidad' => 0, 'precio_unidades' => $units);
            $groups[$code]['variantes'][$key]['cantidad'] += $item['cantidad'];
            $groups[$code]['cantidad'] += $item['cantidad'];
            $groups[$code]['importe_centavos'] += $item['importe_centavos'];
            $groups[$code]['precios'][(string) $units] = $units;
        }
        foreach ($titles as $code => $title) {
            if (!isset($groups[$code])) continue;
            $group = $groups[$code];
            $samePrice = count($group['precios']) === 1;
            $units = $samePrice ? reset($group['precios']) : null;
            $regular[] = array('codigo' => $code, 'nombre' => $title, 'cantidad' => $group['cantidad'],
                'precio_unidades' => $units, 'precio_centavos' => $units === null ? null : intdiv($units + 50, 100),
                'importe_centavos' => $group['importe_centavos'], 'precios_varios' => !$samePrice);
        }
        return array('main' => $regular, 'groups' => $groups);
    }

    private function shortVariant($code, $name)
    {
        $prefixes = array(
            'GEL-01' => array('Gel semi permanente de 3 pasos Le MUSSA - Gama ', 'Gel semi permanente Le MUSSA - Gama ', 'Gel semi permanente Le MUSSA ', 'Gel Le MUSSA - Gama ', 'Gel semi permanente Le REVEUR - Gama ', 'Gel semi permanente Le Reveur '),
            'GEL-70' => array('Gel Le MUSSA - Gama '),
            'PU-06' => array('Punta de Metal '),
            'PU-08' => array('Punta de cerámica ', 'Punta de ceramica '),
        );
        foreach ($prefixes[$code] as $prefix) {
            $short = preg_replace('/^' . preg_quote($prefix, '/') . '/iu', '', $name, 1, $count);
            if ($count) { $name = $short; break; }
        }
        if ($code === 'GEL-01') $name = str_ireplace('- Gama', '', $name);
        if ($code === 'GEL-70') $name = preg_replace('/\(12\s*pz\)/iu', '', $name);
        return trim($name) ?: $code;
    }

    private function headerLayout()
    {
        $name = $this->wrap('Cliente: ' . $this->order['cliente'], 64, 7.4, 'B');
        $email = empty($this->order['correo']) ? array() : $this->wrap($this->order['correo'], 64, 6.6);
        $status = empty($this->order['estatus']) ? array() : $this->wrap('Estado: ' . $this->order['estatus'], 64, 6.6);
        $reference = $this->wrap('Orden: ' . ($this->order['orden'] ?: $this->order['id']), 59, 8, 'B');
        $method = $this->wrap($this->order['entrega_tipo'], 59, 6.7);
        $carrier = $this->wrap($this->order['transportista'] ?: 'Método por confirmar', 59, 7, 'B');
        $this->bodyTop = 15 + max(21, (count($name) + count($email) + count($status) + 1) * 3.2, (count($reference) + count($method) + count($carrier) + 1) * 3.2);
        return array($name, $email, $status, $reference, $method, $carrier);
    }

    protected function headerImagePath()
    {
        return dirname(__DIR__, 2) . '/assets/header-store.png';
    }

    private function headerBackground($height)
    {
        // Generated from the actual premium header CSS by tools/render-store-header.mjs.
        $background = $this->headerImagePath();
        if (is_file($background) && is_readable($background)) {
            try {
                $this->Image($background, 0, 0, 210, $height, 'PNG');
                return;
            } catch (\Exception $error) {
                error_log('OrderPdf: header-store.png could not be decoded; using solid header.');
            }
        } else {
            error_log('OrderPdf: assets/header-store.png is missing or unreadable; using solid header.');
        }
        // A missing decorative asset must not prevent customers from opening orders.
        $this->SetFillColor(...$this->pink);
        $this->Rect(0, 0, 210, $height, 'F');
    }

    public function Header()
    {
        list($name, $email, $status, $reference, $method, $carrier) = $this->headerLayout();
        $this->headerBackground($this->bodyTop - 3);
        $white = array(255, 255, 255);
        $softWhite = array(255, 239, 245);
        if (is_file($this->logo)) $this->Image($this->logo, 81, 11.5, 48, 0, 'PNG');
        else $this->label(81, 15, 'SUPERNOVA', 12, 'B', array(255, 255, 255));
        $this->label(78, 26, 'De todo para ti a precios para ti.', 6.1, '', $softWhite, 54, 'C');
        $y = $this->lines(10, 10, $name, 7.4, 'B', $white);
        $y = $this->lines(10, $y, $email, 6.6, '', $softWhite);
        $y = $this->lines(10, $y, $status, 6.6, '', $softWhite);
        $this->label(10, $y, $this->order['pagado'] ? 'Pago registrado' : 'Sin pago registrado', 6.6, 'B', $white);
        $y = $this->lines(141, 10, $reference, 8, 'B', $white, 3.2, 59, 'R');
        $this->label(141, $y, $this->dateText($this->order['fecha']), 6.7, '', $softWhite, 59, 'R');
        $y = $this->lines(141, $y + 3.2, $method, 6.7, '', $softWhite, 3.2, 59, 'R');
        $this->lines(141, $y, $carrier, 7, 'B', $white, 3.2, 59, 'R');
    }

    public function Footer()
    {
        $this->SetDrawColor(236, 229, 233);
        $this->SetLineWidth(.15);
        $this->Line(10, 284, 200, 284);
        $this->label(10, 286, 'Página ' . $this->PageNo() . '/{nb} · Orden ' . ($this->order['orden'] ?: $this->order['id']), 6, '', $this->muted);
        $this->label(130, 286, 'Importes en MXN · No es un comprobante fiscal.', 6, '', $this->muted, 70, 'R');
    }

    private function dateText($value)
    {
        if (!$value || substr($value, 0, 10) === '0000-00-00') return 'Fecha por confirmar';
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}:\d{2}))?/', $value, $parts)) return 'Fecha por confirmar';
        return $parts[3] . '/' . $parts[2] . '/' . $parts[1] . (isset($parts[4]) ? ' · ' . $parts[4] : '');
    }

    private function totalHeight($mixed)
    {
        return 9 + (!empty($this->order['redondeo_centavos']) ? 4 : 0) + ($this->order['ajustes_centavos'] !== 0 ? 4 : 0) + ($mixed ? 4 : 0);
    }

    private function mainPages(array $items, $mixed)
    {
        $pages = array(array('rows' => array(), 'end' => $this->bodyTop + 6));
        foreach ($items as $index => $item) {
            $code = $this->wrap($item['codigo'], $this->columns[0] - 2, 6.3);
            $name = $this->wrap($item['nombre'], $this->columns[1] - 2, 7);
            $count = max(count($code), count($name));
            $offset = 0;
            while ($offset < $count) {
                $page = count($pages) - 1;
                $reserve = $index === count($items) - 1 ? $this->totalHeight($mixed) : 0;
                $height = max(5.2, ($count - $offset) * 3 + 1.5);
                if ($pages[$page]['rows'] && $pages[$page]['end'] + $height + $reserve > $this->bottom) {
                    $pages[] = array('rows' => array(), 'end' => $this->bodyTop + 6);
                    $page++;
                }
                $capacity = max(1, (int) floor(($this->bottom - $pages[$page]['end'] - $reserve - 1.5) / 3));
                $take = min($count - $offset, $capacity);
                $height = max(5.2, $take * 3 + 1.5);
                $pages[$page]['rows'][] = array('item' => $item, 'code' => array_slice($code, $offset, $take), 'name' => array_slice($name, $offset, $take), 'first' => $offset === 0, 'height' => $height, 'index' => $index);
                $pages[$page]['end'] += $height;
                $offset += $take;
                if ($offset < $count) $pages[] = array('rows' => array(), 'end' => $this->bodyTop + 6);
            }
        }
        return $pages;
    }

    private function sidePages(array $codes, array $groups)
    {
        $pages = array(array());
        $page = 0;
        $y = $this->bodyTop;
        foreach ($codes as $code) {
            if (!isset($groups[$code])) continue;
            $group = $groups[$code];
            $mixed = count($group['precios']) > 1;
            $entries = array();
            foreach ($group['variantes'] as $variant) {
                $text = $this->shortVariant($code, $variant['nombre']);
                if ($mixed) $text .= "\nP.U. " . $this->unitPrice($variant['precio_unidades']);
                $entries[] = array('lines' => $this->wrap($text, 22, 6.2), 'quantity' => $variant['cantidad']);
            }
            $entry = 0;
            $offset = 0;
            while ($entry < count($entries)) {
                if ($y + 17 > $this->bottom) { $page++; $pages[$page] = array(); $y = $this->bodyTop; }
                $segment = array('y' => $y, 'group' => $group, 'rows' => array(), 'continued' => $entry > 0 || $offset > 0);
                $y += 9;
                while ($entry < count($entries)) {
                    $lines = $entries[$entry]['lines'];
                    $height = max(3.8, (count($lines) - $offset) * 3 + 1);
                    if ($segment['rows'] && $y + $height + 4 > $this->bottom) break;
                    $capacity = max(1, (int) floor(($this->bottom - $y - 5) / 3));
                    $take = min(count($lines) - $offset, $capacity);
                    $height = max(3.8, $take * 3 + 1);
                    $segment['rows'][] = array('lines' => array_slice($lines, $offset, $take), 'quantity' => $offset === 0 ? $entries[$entry]['quantity'] : null, 'height' => $height);
                    $y += $height;
                    $offset += $take;
                    if ($offset < count($lines)) break;
                    $offset = 0;
                    $entry++;
                }
                $segment['more'] = $entry < count($entries);
                $segment['footerY'] = $y;
                $pages[$page][] = $segment;
                $y += 7;
                if ($segment['more']) { $page++; $pages[$page] = array(); $y = $this->bodyTop; }
            }
        }
        return $pages;
    }

    private function tableHeader()
    {
        $x = 10;
        foreach (array('Código', 'Producto', 'Cant.', 'P. unitario', 'Importe') as $index => $label) {
            $this->label($x + 1, $this->bodyTop, $label, 6.4, 'B', null, $this->columns[$index] - 2, $index >= 2 ? 'R' : 'L');
            $x += $this->columns[$index];
        }
        $this->SetDrawColor(...$this->pink);
        $this->SetLineWidth(.6);
        $this->Line(10, $this->bodyTop + 5, 10 + $this->mainWidth, $this->bodyTop + 5);
    }

    private function drawMainRow(array $row, $y)
    {
        $this->SetFillColor(...($row['index'] % 2 === 0 ? array(246, 244, 245) : array(255, 255, 255)));
        $this->Rect(10, $y, $this->mainWidth, $row['height'], 'F');
        $this->lines(11, $y + .5, $row['code'], 6.3, '', $this->muted, 3);
        $this->lines(11 + $this->columns[0], $y + .5, $row['name'], 7, '', null, 3);
        if (!$row['first']) return;
        $item = $row['item'];
        $x = 10 + $this->columns[0] + $this->columns[1];
        $this->fitLabel($x, $y + .5, $item['cantidad'], $this->columns[2], 7, '');
        $x += $this->columns[2];
        $units = isset($item['precio_unidades']) ? $item['precio_unidades'] : $item['precio_centavos'] * 100;
        $price = !empty($item['precios_varios']) ? 'Varios*' : $this->unitPrice($units);
        $this->fitLabel($x, $y + .5, $price, $this->columns[3], 7);
        $x += $this->columns[3];
        $this->fitLabel($x, $y + .5, $this->money($item['importe_centavos']), $this->columns[4], 7, 'B');
    }

    private function drawSidebar(array $segments, $x)
    {
        foreach ($segments as $segment) {
            $y = $segment['y'];
            $group = $segment['group'];
            $this->SetFillColor(...$this->pink);
            $this->Rect($x, $y, 30, 4.5, 'F');
            $this->label($x, $y + .5, $group['codigo'] . ($segment['continued'] ? ' (cont.)' : ''), 6.5, 'B', array(255, 255, 255), 30, 'C');
            $this->SetFillColor(246, 244, 245);
            $this->Rect($x, $y + 4.5, 30, 4.5, 'F');
            $this->fitLabel($x, $y + 5, $group['titulo'], 30, 5.8, '', 'C', $this->muted);
            $y += 9;
            $this->SetDrawColor(218, 208, 214);
            $this->SetLineWidth(.15);
            foreach ($segment['rows'] as $row) {
                $this->Rect($x, $y, 30, $row['height']);
                $this->Line($x + 24, $y, $x + 24, $y + $row['height']);
                $this->lines($x + 1, $y + .2, $row['lines'], 6.2, '', null, 3);
                if ($row['quantity'] !== null) $this->fitLabel($x + 24, $y + .2, $row['quantity'], 6, 6.2, 'B', 'C');
                $y += $row['height'];
            }
            $this->SetFillColor(253, 232, 239);
            $this->Rect($x, $y, 30, 4, 'F');
            $this->label($x, $y + .2, $segment['more'] ? 'Continúa en la siguiente página' : 'Total: ' . $group['cantidad'], $segment['more'] ? 5 : 6.2, 'B', $this->ink, 30, 'C');
        }
    }

    private function totals($y, $mixed)
    {
        $y += 2;
        $end = 10 + $this->mainWidth;
        $rounding = isset($this->order['redondeo_centavos']) ? $this->order['redondeo_centavos'] : 0;
        if ($rounding !== 0 || $this->order['ajustes_centavos'] !== 0) {
            $this->label(11, $y, 'Productos: ' . $this->money($this->order['subtotal_centavos'] - $rounding), 6.2, '', $this->muted);
            $right = array();
            if ($rounding !== 0) $right[] = 'Redondeo: ' . $this->money($rounding);
            if ($this->order['ajustes_centavos'] !== 0) $right[] = 'Ajustes: ' . $this->money($this->order['ajustes_centavos']);
            $this->fitLabel($end - 78, $y, implode(' · ', $right), 77, 6.2, '', 'R', $this->muted);
            $y += 4;
        }
        $this->SetFillColor(253, 232, 239);
        $this->Rect(10, $y, $this->mainWidth, 6.5, 'F');
        $this->label(11, $y + 1.4, 'Total de piezas: ' . $this->order['piezas'], 6.7, 'B');
        $this->fitLabel(10 + $this->mainWidth * .45, $y + 1.4, 'Total del pedido: ' . $this->money($this->order['total_centavos']), $this->mainWidth * .55 - 1, 8, 'B', 'R', $this->pink);
        if ($mixed) $this->label(11, $y + 7.5, '* Varios precios unitarios: consulta cada variante en el desglose lateral.', 6, '', $this->muted);
    }

    public function render()
    {
        $display = self::groupProducts($this->order['productos']);
        $families = array();
        foreach (array(array('GEL-01', 'GEL-70'), array('PU-06', 'PU-08')) as $family) {
            if (array_intersect($family, array_keys($display['groups']))) $families[] = $family;
        }
        $this->mainWidth = 190 - count($families) * 33;
        $this->columns = array(22, $this->mainWidth - 72, 10, 19, 21);
        $this->headerLayout();
        $mixed = false;
        foreach ($display['groups'] as $group) if (count($group['precios']) > 1) $mixed = true;
        $main = $this->mainPages($display['main'], $mixed);
        $sides = array();
        $pageCount = count($main);
        foreach ($families as $family) { $sides[] = $this->sidePages($family, $display['groups']); $pageCount = max($pageCount, count(end($sides))); }
        for ($page = 0; $page < $pageCount; $page++) {
            $this->AddPage();
            if (isset($main[$page])) {
                $this->tableHeader();
                $y = $this->bodyTop + 6;
                foreach ($main[$page]['rows'] as $row) { $this->drawMainRow($row, $y); $y += $row['height']; }
                if (!$display['main']) { $this->label(11, $y + 1, 'Sin productos registrados.', 7); $y += 6; }
                if ($page === count($main) - 1) $this->totals($y, $mixed);
            } else $this->label(11, $this->bodyTop, 'Continuación del desglose de variantes', 8, 'B', $this->muted);
            foreach ($sides as $column => $pages) if (isset($pages[$page])) $this->drawSidebar($pages[$page], 10 + $this->mainWidth + 3 + $column * 33);
        }
        return $this->Output('S');
    }
}
