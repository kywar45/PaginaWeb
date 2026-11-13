<?php

namespace App\Services;

class CartPricing
{
    public function codeKey($product)
    {
        $code = strtoupper(trim((string) $product['codigo']));
        return $code === '' ? 'product:' . $product['id_producto'] : 'code:' . $code;
    }

    public function apply($product, $codeQuantity)
    {
        $product['piezas_codigo'] = $codeQuantity;
        $product['tipo_precio'] = 'unidad';
        if ($product['es_vip']) {
            $product['tipo_precio'] = 'vip';
        } elseif ($product['tiene_descuento']) {
            $product['tipo_precio'] = 'oferta';
        } elseif ($product['minimo_caja'] > 0 && $codeQuantity >= $product['minimo_caja'] && $product['precio_caja'] > 0) {
            $product['precio_final'] = $product['precio_caja'];
            $product['tipo_precio'] = 'caja';
        } elseif ($product['minimo_mayoreo'] > 0 && $codeQuantity >= $product['minimo_mayoreo'] && $product['precio_mayoreo'] > 0) {
            $product['precio_final'] = $product['precio_mayoreo'];
            $product['tipo_precio'] = 'mayoreo';
        }
        return $product;
    }
}
