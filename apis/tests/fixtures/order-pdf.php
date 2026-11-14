<?php

function orderPdfFixture($long = false)
{
    $names = array(
        array('UN-565', 'Molde de silicona para decoración de uñas con forma de lazo 3D (1 pz)', 12, 2900),
        array('GEL-01', 'Gel semi permanente Le MUSSA - Gama B, tono rosa translúcido', 18, 6500),
        array('GEL-01', 'Gel semi permanente Le MUSSA - Gama BASE FRANCESA, tono natural', 6, 7000),
        array('GEL-70', 'Gel Le MUSSA - Gama D (12 pz)', 3, 10000),
        array('PU-06', 'Punta de metal profesional M206 para pulidor', 8, 2000),
        array('PU-08', 'Punta de cerámica para retiro de producto, grano medio', 4, 4500),
        array('EL-109', 'Lámpara LED de mesa para uñas - Black', 2, 5200),
        array('LA-317', 'Lámpara UV/LED profesional 48W - Pink', 1, 33000),
    );
    $items = array();
    $subtotal = 0;
    $pieces = 0;
    for ($i = 0; $i < ($long ? 96 : count($names)); $i++) {
        $source = $names[$i % count($names)];
        $item = array('codigo' => $source[0], 'nombre' => $source[1], 'cantidad' => $source[2],
            'precio_centavos' => $source[3], 'importe_centavos' => $source[2] * $source[3]);
        if ($long) {
            $variant = str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            if ($item['codigo'] === 'GEL-01') $item['nombre'] = 'Gel semi permanente Le MUSSA - Gama TONO ' . $variant;
            elseif ($item['codigo'] === 'GEL-70') $item['nombre'] = 'Gel Le MUSSA - Gama G' . $variant . ' (12pz)';
            elseif ($item['codigo'] === 'PU-06') $item['nombre'] = 'Punta de Metal M' . $variant;
            elseif ($item['codigo'] === 'PU-08') $item['nombre'] = 'Punta de cerámica C' . $variant;
            else $item['nombre'] .= ' - Variante ' . $variant;
            if ($i === 31) $item['nombre'] .= '. Incluye adaptador y accesorios para trabajo profesional, con soporte ajustable y base estable para uso continuo en el área de manicura.';
        }
        $items[] = $item;
        $pieces += $item['cantidad'];
        $subtotal += $item['importe_centavos'];
    }
    return array('id' => 10428, 'orden' => 'DEMO-10428', 'fecha' => '2026-11-13 14:35:00',
        'cliente' => 'María Fernanda López Hernández (DEMOSTRACIÓN)',
        'correo' => 'cliente-demostracion@example.com', 'entrega_tipo' => $long ? 'Entrega en punto' : 'Envío por paquetería',
        'transportista' => $long ? 'Punto de entrega Supernova Centro' : 'Paquetería de muestra',
        'pagado' => !$long, 'estatus' => $long ? 'Pendiente de pago' : 'En preparación',
        'productos' => $items, 'piezas' => $pieces, 'subtotal_centavos' => $subtotal,
        'ajustes_centavos' => $long ? 0 : 15000, 'total_centavos' => $subtotal + ($long ? 0 : 15000));
}
