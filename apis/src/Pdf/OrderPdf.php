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
        $carrier = trim((string) $this->order['transportista']);
        $fields = array(
            array('Cliente:', $this->order['cliente'], 54),
            array('Orden:', $this->order['orden'] ?: $this->order['id'], 34),
            array('Fecha Pedido:', $this->dateText($this->order['fecha']), 45),
            array('Método de entrega:', $this->order['entrega_tipo'] . ($carrier !== '' ? ' - ' . $carrier : ''), 65),
        );
        $maxLines = 1;
        foreach ($fields as &$field) {
            $field[3] = $this->wrap($field[1], $field[2] - 12, 7);
            $maxLines = max($maxLines, count($field[3]));
        }
        unset($field);
        $this->bodyTop = max(36, 30 + $maxLines * 3.2);
        return $fields;
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
        $fields = $this->headerLayout();
        $this->headerBackground($this->bodyTop - 3);
        $white = array(255, 255, 255);
        if (is_file($this->logo)) $this->Image($this->logo, 84, 1.5, 42, 0, 'PNG');
        else $this->label(80, 5, 'SUPERNOVA', 12, 'B', $white, 50, 'C');
        $this->label(65, 12, 'De todo para ti a precios para ti.', 7, 'I', $white, 80, 'C');
        $this->SetDrawColor(255, 215, 229);
        $this->SetLineWidth(.4);
        $this->Line(40, 8, 72, 8);
        $this->Line(138, 8, 170, 8);
        $panelY = 18;
        $panelHeight = $this->bodyTop - 25;
        $this->roundedPanel(6, $panelY, 198, $panelHeight, 2.5);
        $x = 6;
        foreach ($fields as $index => $field) {
            if ($index > 0) {
                $this->SetDrawColor(245, 164, 190);
                $this->SetLineWidth(.3);
                $this->Line($x, $panelY + 2.5, $x, $panelY + $panelHeight - 2.5);
            }
            $this->headerIcon($index, $x + 3, $panelY + 2);
            $this->label($x + 10, $panelY + 1, $field[0], 7.1, 'B');
            $this->lines($x + 10, $panelY + 4.4, $field[3], 7, '', $this->muted);
            $x += $field[2];
        }
    }

    private function roundedPanel($x, $y, $w, $h, $r, $fill = array(255, 255, 255), $border = false, $clip = false)
    {
        $this->SetFillColor(...$fill);
        $k = $this->k;
        $pageHeight = $this->h;
        $point = function ($px, $py) use ($k, $pageHeight) { return sprintf('%.2F %.2F', $px * $k, ($pageHeight - $py) * $k); };
        $c = $r * .55228475;
        $this->_out($point($x + $r, $y) . ' m ' . $point($x + $w - $r, $y) . ' l');
        $this->_out($point($x + $w - $r + $c, $y) . ' ' . $point($x + $w, $y + $r - $c) . ' ' . $point($x + $w, $y + $r) . ' c');
        $this->_out($point($x + $w, $y + $h - $r) . ' l ' . $point($x + $w, $y + $h - $r + $c) . ' ' . $point($x + $w - $r + $c, $y + $h) . ' ' . $point($x + $w - $r, $y + $h) . ' c');
        $this->_out($point($x + $r, $y + $h) . ' l ' . $point($x + $r - $c, $y + $h) . ' ' . $point($x, $y + $h - $r + $c) . ' ' . $point($x, $y + $h - $r) . ' c');
        $this->_out($point($x, $y + $r) . ' l ' . $point($x, $y + $r - $c) . ' ' . $point($x + $r - $c, $y) . ' ' . $point($x + $r, $y) . ($clip ? ' c W n' : ($border ? ' c B' : ' c f')));
    }

    private function headerIcon($kind, $x, $y, $size = 5.5)
    {
        $this->SetDrawColor(...$this->pink);
        $this->SetLineWidth(.42);
        $this->_out('1 J 1 j');
        // Consistent 24-unit outline icons, scaled to a compact 5.5 mm square.
        $paths = array(
            array(
                array('M', 16, 7, 'C', 16, 9.21, 14.21, 11, 12, 11, 'C', 9.79, 11, 8, 9.21, 8, 7, 'C', 8, 4.79, 9.79, 3, 12, 3, 'C', 14.21, 3, 16, 4.79, 16, 7, 'Z'),
                array('M', 4, 21, 'L', 4, 19, 'C', 4, 15.69, 7.58, 14, 12, 14, 'C', 16.42, 14, 20, 15.69, 20, 19, 'L', 20, 21, 'Z'),
            ),
            array(
                array('M', 12, 3, 'L', 21, 7.5, 'L', 21, 16.5, 'L', 12, 21, 'L', 3, 16.5, 'L', 3, 7.5, 'Z'),
                array('M', 3, 7.5, 'L', 12, 12, 'L', 21, 7.5, 'M', 12, 12, 'L', 12, 21, 'M', 7.5, 5.25, 'L', 16.5, 9.75, 'L', 16.5, 13.5),
            ),
            array(
                array('M', 5, 5, 'L', 19, 5, 'C', 20.1, 5, 21, 5.9, 21, 7, 'L', 21, 19, 'C', 21, 20.1, 20.1, 21, 19, 21, 'L', 5, 21, 'C', 3.9, 21, 3, 20.1, 3, 19, 'L', 3, 7, 'C', 3, 5.9, 3.9, 5, 5, 5, 'Z'),
                array('M', 7, 3, 'L', 7, 7, 'M', 17, 3, 'L', 17, 7, 'M', 3, 10, 'L', 21, 10, 'M', 7, 14, 'L', 9, 14, 'M', 14, 14, 'L', 16, 14, 'M', 7, 17, 'L', 9, 17, 'M', 14, 17, 'L', 16, 17),
            ),
            array(
                array('M', 3, 17, 'L', 2, 17, 'L', 2, 5, 'L', 14, 5, 'L', 14, 17, 'L', 9, 17, 'M', 14, 9, 'L', 18, 9, 'L', 22, 13, 'L', 22, 17, 'L', 21, 17, 'M', 14, 17, 'L', 15, 17, 'M', 14, 13, 'L', 22, 13),
                array('M', 9, 17, 'C', 9, 18.66, 7.66, 20, 6, 20, 'C', 4.34, 20, 3, 18.66, 3, 17, 'C', 3, 15.34, 4.34, 14, 6, 14, 'C', 7.66, 14, 9, 15.34, 9, 17, 'Z'),
                array('M', 21, 17, 'C', 21, 18.66, 19.66, 20, 18, 20, 'C', 16.34, 20, 15, 18.66, 15, 17, 'C', 15, 15.34, 16.34, 14, 18, 14, 'C', 19.66, 14, 21, 15.34, 21, 17, 'Z'),
            ),
        );
        $paths[4] = array(
            array('M', 3, 5, 'C', 3, 3.9, 3.9, 3, 5, 3, 'L', 12, 3, 'C', 12.53, 3, 13.04, 3.21, 13.41, 3.59, 'L', 21, 11.17, 'C', 21.78, 11.95, 21.78, 13.22, 21, 14, 'L', 14, 21, 'C', 13.22, 21.78, 11.95, 21.78, 11.17, 21, 'L', 3.59, 13.41, 'C', 3.21, 13.04, 3, 12.53, 3, 12, 'Z'),
            array('M', 9, 7.5, 'C', 9, 8.33, 8.33, 9, 7.5, 9, 'C', 6.67, 9, 6, 8.33, 6, 7.5, 'C', 6, 6.67, 6.67, 6, 7.5, 6, 'C', 8.33, 6, 9, 6.67, 9, 7.5, 'Z'),
        );
        foreach ($paths[$kind] as $path) {
            $commands = array();
            for ($i = 0; $i < count($path);) {
                $operation = $path[$i++];
                $pairs = $operation === 'C' ? 3 : ($operation === 'Z' ? 0 : 1);
                for ($j = 0; $j < $pairs; $j++) {
                    $px = $x + $path[$i++] * $size / 24;
                    $py = $y + $path[$i++] * $size / 24;
                    $commands[] = sprintf('%.2F %.2F', $px * $this->k, ($this->h - $py) * $this->k);
                }
                $commands[] = $operation === 'Z' ? 'h' : strtolower($operation);
            }
            $this->_out(implode(' ', $commands) . ' S');
        }
        $this->_out('0 J 0 j');
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
        return 22 + (!empty($this->order['ahorro_centavos']) ? 6 : 0) + (!empty($this->order['redondeo_centavos']) ? 4 : 0) + ($this->order['ajustes_centavos'] !== 0 ? 4 : 0) + ($mixed ? 4 : 0);
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
        $end = 200;
        $rounding = isset($this->order['redondeo_centavos']) ? $this->order['redondeo_centavos'] : 0;
        if ($rounding !== 0 || $this->order['ajustes_centavos'] !== 0) {
            $this->label(11, $y, 'Productos: ' . $this->money($this->order['subtotal_centavos'] - $rounding), 6.2, '', $this->muted);
            $right = array();
            if ($rounding !== 0) $right[] = 'Redondeo: ' . $this->money($rounding);
            if ($this->order['ajustes_centavos'] !== 0) $right[] = 'Ajustes: ' . $this->money($this->order['ajustes_centavos']);
            $this->fitLabel($end - 78, $y, implode(' · ', $right), 77, 6.2, '', 'R', $this->muted);
            $y += 4;
        }
        $savings = $this->order['ahorro_centavos'] ?? 0;
        $width = 88;
        $x = 10;
        $end = $x + $width;
        $totalY = $y + ($savings > 0 ? 13 : 7);
        $white = array(255, 255, 255);
        $this->SetDrawColor(219, 216, 220);
        $this->SetLineWidth(.2);
        $this->roundedPanel($x, $y, $width, $totalY - $y + 7, 2, $white, true);
        $this->headerIcon(1, $x + 3.5, $y + 1.5, 4.2);
        $this->SetDrawColor(...$this->pink);
        $this->Line($x + 10, $y + 1.5, $x + 10, $y + 5.8);
        $this->label($x + 12, $y + 2, 'RESUMEN DEL PEDIDO', 7.4, 'B');
        $this->fitLabel($x + 47, $y + 2, 'Productos: ' . count($this->order['productos']), 21, 6.5, 'B', 'L', $this->muted);
        $this->fitLabel($x + 70, $y + 2, 'Piezas: ' . $this->order['piezas'], 15, 6.5, 'B', 'L', $this->muted);
        $this->SetDrawColor(224, 219, 223);
        $this->Line($x + 68, $y + 2, $x + 68, $y + 5);
        $this->Line($x + 4, $y + 6.5, $end - 4, $y + 6.5);
        if ($savings > 0) {
            $this->headerIcon(4, $x + 3.5, $y + 7.6, 4.2);
            $this->label($x + 11, $y + 8.2, 'Tu ahorro', 8, 'B', $this->pink);
            $this->fitLabel($x + 44, $y + 8.2, $this->money($savings), 40, 9.5, 'B', 'R', $this->pink);
        }
        $this->roundedPanel($x, $totalY, $width, 7, 2, $this->pink);
        $this->SetFillColor(...$this->pink);
        $this->Rect($x, $totalY, $width, 2, 'F');
        $background = $this->headerImagePath();
        if (is_file($background) && is_readable($background)) {
            $this->_out('q');
            $this->roundedPanel($x, $y, $width, $totalY - $y + 7, 2, $white, false, true);
            try {
                $this->Image($background, $x, $totalY, $width, 7, 'PNG');
            } catch (\Exception $error) {
                error_log('OrderPdf: total background could not be decoded; using solid pink.');
            }
            $this->_out('Q');
        }
        // Keep FPDF's tracked fill color in sync after restoring the PDF graphics state.
        $this->SetFillColor(...$this->pink);
        $this->label($x + 4, $totalY + 1.8, 'TOTAL A PAGAR:', 9, 'B', $white);
        $this->fitLabel($x + 37, $totalY + 1.8, $this->money($this->order['total_centavos']), $width - 41, 12, 'B', 'R', $white);
        $noteY = $totalY + 8;
        if ($mixed) $this->label(11, $noteY, '* Varios precios unitarios: consulta cada variante en el desglose lateral.', 6, '', $this->muted);
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
            } else $this->label(11, $this->bodyTop, 'Continuación del desglose de variantes', 8, 'B', $this->muted);
            foreach ($sides as $column => $pages) if (isset($pages[$page])) $this->drawSidebar($pages[$page], 10 + $this->mainWidth + 3 + $column * 33);
            if ($page === $pageCount - 1) {
                $summaryY = isset($main[$page]) ? max($y, $main[$page]['end']) : $this->bodyTop;
                foreach ($sides as $pages) {
                    foreach ($pages[$page] ?? array() as $segment) {
                        $summaryY = max($summaryY, $segment['footerY'] + 7);
                    }
                }
                if ($summaryY + $this->totalHeight($mixed) > $this->bottom) {
                    $this->AddPage();
                    $summaryY = $this->bodyTop;
                }
                $this->totals($summaryY, $mixed);
            }
        }
        return $this->Output('S');
    }
}
