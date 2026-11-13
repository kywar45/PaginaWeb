<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;

class CartService
{
    private $carts;
    private $products;
    private $productService;
    private $owner;
    private $user;
    private $pricing;
    private $guestOwner;
    public const MAX_QUANTITY = 9999;
    public const MAX_PRODUCTS = 300;

    public function __construct(CartRepository $carts, ProductRepository $products, $owner, $user = null, $guestOwner = null)
    {
        $this->carts = $carts;
        $this->products = $products;
        $this->productService = new ProductService($products, $user);
        $this->owner = $owner;
        $this->user = $user;
        $this->pricing = new CartPricing();
        $this->guestOwner = $guestOwner;
    }

    public function execute($action = 'leer', $id = null, $quantity = null)
    {
        if (!in_array($action, array('leer', 'agregar', 'cantidad', 'eliminar', 'fusionar'), true)) {
            throw new ApiException('VALIDATION_ERROR', 'Accion de carrito invalida.', 422);
        }
        if ($action === 'fusionar' && (!$this->user || $this->owner['user_id'] < 1)) {
            throw new ApiException('UNAUTHENTICATED', 'Inicia sesion para recuperar tu carrito.', 401);
        }
        if (in_array($action, array('agregar', 'cantidad', 'eliminar'), true) && (!is_int($id) || $id < 1 || $id > 2147483647)) {
            throw new ApiException('VALIDATION_ERROR', 'Producto invalido.', 422);
        }
        if (in_array($action, array('agregar', 'cantidad'), true)
            && (!is_int($quantity) || $quantity < 1 || $quantity > self::MAX_QUANTITY)) {
            throw new ApiException('VALIDATION_ERROR', 'La cantidad debe estar entre 1 y 9999 piezas.', 422);
        }
        if ($action === 'leer' || $action === 'fusionar') $id = null;

        $operation = function () use ($action, $id, $quantity) {
            $lines = array();
            $removeIds = array();
            foreach ($this->carts->lines($this->owner) as $row) {
                $productId = (int) $row['id_producto'];
                if ((int) $row['cantidad'] < 1) {
                    $removeIds[] = (int) $row['id'];
                    continue;
                }
                if (isset($lines[$productId])) {
                    $lines[$productId]['cantidad'] += (int) $row['cantidad'];
                    $removeIds[] = (int) $row['id'];
                } else {
                    $row['cantidad'] = (int) $row['cantidad'];
                    $lines[$productId] = $row;
                }
            }
            $guestIds = array();
            $mergedPieces = 0;
            if ($action === 'fusionar' && $this->guestOwner !== null) {
                foreach ($this->carts->lines($this->guestOwner) as $guestLine) {
                    $guestIds[] = (int) $guestLine['id'];
                    $guestQuantity = (int) $guestLine['cantidad'];
                    if ($guestQuantity < 1) continue;
                    $productId = (int) $guestLine['id_producto'];
                    $next = ($lines[$productId]['cantidad'] ?? 0) + $guestQuantity;
                    if ($next > self::MAX_QUANTITY) {
                        throw new ApiException('CART_MERGE_LIMIT', 'No pudimos combinar los carritos: un producto supera 9999 piezas. Tu carrito de visitante sigue guardado.', 409);
                    }
                    if (!isset($lines[$productId])) {
                        if (count($lines) >= self::MAX_PRODUCTS) {
                            throw new ApiException('CART_MERGE_LIMIT', 'No pudimos combinar los carritos: se superan 300 productos. Tu carrito de visitante sigue guardado.', 409);
                        }
                        $guestLine['id'] = null;
                        $lines[$productId] = $guestLine;
                    }
                    $lines[$productId]['cantidad'] = $next;
                    $mergedPieces += $guestQuantity;
                }
            }
            $ids = array_keys($lines);
            if ($id !== null && !in_array($id, $ids, true)) $ids[] = $id;
            $products = array();
            foreach ($this->products->cartProducts($ids) as $row) {
                $products[(int) $row['id']] = $this->productService->cartProduct($row);
            }

            if ($action === 'eliminar') {
                if (isset($lines[$id])) {
                    $removeIds[] = (int) $lines[$id]['id'];
                    unset($lines[$id]);
                }
            } elseif ($action === 'agregar' || $action === 'cantidad') {
                $existing = isset($lines[$id]) ? $lines[$id]['cantidad'] : 0;
                if ($action === 'cantidad' && !$existing) {
                    throw new ApiException('CART_ITEM_NOT_FOUND', 'El producto ya no esta en el carrito.', 404);
                }
                $next = $action === 'agregar' ? $existing + $quantity : $quantity;
                if ($next > self::MAX_QUANTITY) {
                    throw new ApiException('VALIDATION_ERROR', 'La cantidad excede el limite de 9999 piezas por producto.', 422);
                }
                if (!isset($products[$id]) || !$products[$id]['puede_comprar']) {
                    if ($action === 'agregar' || $next > $existing) {
                        throw new ApiException('PRODUCT_UNAVAILABLE', 'Este producto no esta disponible para agregar piezas.', 409);
                    }
                }
                if (!$existing && count($lines) >= self::MAX_PRODUCTS) {
                    throw new ApiException('CART_LIMIT', 'El carrito admite hasta 300 productos diferentes.', 422);
                }
                if (!$existing) $lines[$id] = array('id' => null, 'id_producto' => $id);
                $lines[$id]['cantidad'] = $next;
            }

            $codeQuantities = array();
            foreach ($lines as $productId => $line) {
                if (isset($products[$productId]) && $products[$productId]['puede_comprar']) {
                    $key = $this->pricing->codeKey($products[$productId]);
                    $codeQuantities[$key] = ($codeQuantities[$key] ?? 0) + $line['cantidad'];
                }
            }
            $this->carts->deactivate($this->owner, $removeIds);
            $items = array();
            $totalCents = 0;
            $savingsCents = 0;
            $normalTotalCents = 0;
            $count = 0;
            foreach ($lines as $productId => $line) {
                if (isset($products[$productId])) {
                    $product = $products[$productId];
                } else {
                    $product = array(
                        'id_producto' => $productId, 'nombre' => (string) $line['nombre'],
                        'codigo' => (string) $line['codigo'], 'imagen' => null, 'precio_final' => 0,
                        'estatus' => 0, 'es_vip' => false, 'tiene_descuento' => false,
                        'puede_comprar' => false, 'minimo_mayoreo' => 0, 'minimo_caja' => 0,
                        'precio_mayoreo' => 0, 'precio_caja' => 0,
                    );
                }
                $key = $this->pricing->codeKey($product);
                $product = $this->pricing->apply($product, $codeQuantities[$key] ?? 0);
                $product['quantity'] = $line['cantidad'];
                $priceCents = (int) round($product['precio_final'] * 100);
                $normalCents = (int) round(($product['precio_normal'] ?? 0) * 100);
                $product['subtotal'] = $product['puede_comprar'] ? $priceCents * $line['cantidad'] / 100 : 0;
                $lineSavings = $product['puede_comprar'] ? max(0, $normalCents - $priceCents) * $line['cantidad'] : 0;
                $product['ahorro_total'] = $lineSavings / 100;
                $savingsCents += $lineSavings;
                if ($product['puede_comprar']) $normalTotalCents += $normalCents * $line['cantidad'];
                $this->carts->save($this->owner, $line['id'], $product, $line['cantidad']);
                $items[] = $product;
                $count += $line['cantidad'];
                $totalCents += (int) round($product['subtotal'] * 100);
            }
            $result = array(
                'items' => $items, 'total' => $totalCents / 100, 'cantidad_total' => $count,
                'ahorro_total' => $savingsCents / 100, 'total_sin_descuentos' => $normalTotalCents / 100,
                'tipo_cliente' => !$this->user ? 'invitado' : ((int) $this->user['user_vip'] === 1 ? 'vip' : 'normal'),
                'usuario_id' => $this->owner['user_id'] > 0 ? $this->owner['user_id'] : null,
            );
            if ($action === 'fusionar') {
                // El vaciado y los precios nuevos se confirman juntos en la misma transaccion.
                if ($guestIds) $this->carts->deactivate($this->guestOwner, $guestIds);
                $unavailable = 0;
                foreach ($items as $item) if (!$item['puede_comprar']) $unavailable++;
                $result['fusion'] = array('fusionado' => $mergedPieces > 0, 'piezas' => $mergedPieces, 'no_disponibles' => $unavailable);
            }
            return $result;
        };
        if ($action === 'fusionar' && $this->guestOwner !== null) {
            return $this->carts->withOwnersLock(array($this->owner, $this->guestOwner), $operation);
        }
        return $this->carts->withOwnerLock($this->owner, $operation);
    }
}
