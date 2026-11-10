<?php

namespace App\Repositories;

class ProductRepository
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listPage($collection, $limit, $offset)
    {
        $where = array('p.active = 1', 'p.estatus = 1', 'p.oculto = "0"');
        $order = 'p.id DESC';

        if ($collection === 'ofertas') {
            $where[] = 'LOWER(TRIM(COALESCE(p.descuento, ""))) = "si"';
            $order = 'p.id DESC';
        } elseif ($collection === 'nuevos') {
            $where[] = 'LOWER(TRIM(COALESCE(p.descuento, ""))) <> "si"';
            $order = 'p.product_nuevo DESC, p.id DESC';
        } elseif ($collection === 'vistos') {
            $order = 'RAND()';
        }

        $sql = 'SELECT p.id, p.nombre, p.codigo, p.preciou, p.preciom, p.precioc,
                       p.topem, p.topec, p.descripcion, p.visitas, p.estatus,
                       p.minimo, p.fecha_restock, p.sub_categoria,
                       p.descuento, p.descuento_general, p.descuento_especial, p.promo,
                       p.barcode, p.product_nuevo, i.a AS imagen
                FROM productos p
                LEFT JOIN img i ON i.id_producto = p.id AND i.posicion = 1
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ' . $order . '
                LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countOffers()
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM productos p
            WHERE p.active = 1 AND p.estatus = 1 AND p.oculto = "0"
              AND LOWER(TRIM(COALESCE(p.descuento, ""))) = "si"');

        return (int) $stmt->fetchColumn();
    }

    public function listRandomSubcategoryGroups($subcategoryLimit, $productLimit)
    {
        $subcategorySql = 'SELECT c.id_categoria, c.nombre_categoria,
                                  s.id_subcategoria, s.nombre_subcategoria
                        FROM admin_categorias c
                        INNER JOIN admin_subcategorias s ON s.id_categoria = c.id_categoria
                        INNER JOIN productos p ON p.sub_categoria = s.id_subcategoria
                        WHERE c.estatus_categoria = "1"
                          AND s.estatus_subcategoria = "1"
                          AND p.active = 1
                          AND p.estatus = 1
                          AND p.oculto = "0"
                        GROUP BY c.id_categoria, c.nombre_categoria,
                                 s.id_subcategoria, s.nombre_subcategoria
                        HAVING COUNT(DISTINCT p.id) >= :minimum_products
                        ORDER BY RAND()
                        LIMIT :subcategory_limit';

        $subcategoryStmt = $this->pdo->prepare($subcategorySql);
        $subcategoryStmt->bindValue(':minimum_products', $productLimit, \PDO::PARAM_INT);
        $subcategoryStmt->bindValue(':subcategory_limit', $subcategoryLimit, \PDO::PARAM_INT);
        $subcategoryStmt->execute();
        $subcategories = $subcategoryStmt->fetchAll();

        $productSql = 'SELECT p.id, p.nombre, p.codigo, p.preciou, p.preciom, p.precioc,
                              p.topem, p.topec, p.descripcion, p.visitas, p.estatus,
                              p.minimo, p.fecha_restock, p.sub_categoria,
                              p.descuento, p.descuento_general, p.descuento_especial, p.promo,
                              p.barcode, p.product_nuevo, i.a AS imagen
                       FROM productos p
                       LEFT JOIN img i ON i.id_producto = p.id AND i.posicion = 1
                       WHERE p.sub_categoria = :subcategory_id
                         AND p.active = 1
                         AND p.estatus = 1
                         AND p.oculto = "0"
                       ORDER BY RAND()
                       LIMIT :product_limit';

        $groups = array();
        foreach ($subcategories as $subcategory) {
            $productStmt = $this->pdo->prepare($productSql);
            $productStmt->bindValue(
                ':subcategory_id',
                (int) $subcategory['id_subcategoria'],
                \PDO::PARAM_INT
            );
            $productStmt->bindValue(':product_limit', $productLimit, \PDO::PARAM_INT);
            $productStmt->execute();

            $groups[] = array(
                'subcategory' => $subcategory,
                'products' => $productStmt->fetchAll(),
            );
        }

        return $groups;
    }
}
