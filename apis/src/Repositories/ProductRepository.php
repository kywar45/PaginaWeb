<?php

namespace App\Repositories;

class ProductRepository
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function cartProducts($ids)
    {
        if (!$ids) return array();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare('SELECT p.*,
            (SELECT i.a FROM img i WHERE i.id_producto = p.id ORDER BY i.posicion ASC, i.a ASC LIMIT 1) AS imagen
            FROM productos p WHERE p.id IN (' . $placeholders . ')');
        $stmt->execute(array_values($ids));
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listPage($collection, $limit, $offset, $subcategoryId = null, $onlyAvailable = false, $categoryId = null)
    {
        $where = array('p.active = 1');
        if ($onlyAvailable) {
            $where[] = 'p.estatus = 1';
        }
        $order = 'p.id DESC';
        if ($subcategoryId !== null) {
            $where[] = 'p.sub_categoria = :subcategory_id';
        }

        if ($categoryId !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM admin_subcategorias s
                WHERE s.id_subcategoria = p.sub_categoria AND s.id_categoria = :category_id)';
        }

        if ($collection === 'ofertas') {
            $where[] = 'LOWER(TRIM(COALESCE(p.descuento, ""))) = "si"';
            $order = 'p.id DESC';
        } elseif ($collection === 'nuevos') {
            $where[] = 'LOWER(TRIM(COALESCE(p.descuento, ""))) <> "si"';
            $order = 'p.product_nuevo DESC, p.id DESC';
        } elseif ($collection === 'vistos') {
            $order = 'RAND()';
        }

        $subcategoryJoin = '';
        if ($subcategoryId !== null) {
            $order = 'p.codigo ASC, p.nombre ASC, p.id ASC';
        } elseif ($categoryId !== null) {
            $subcategoryJoin = 'INNER JOIN admin_subcategorias a ON a.id_subcategoria = p.sub_categoria';
            $order = 'a.position ASC, a.id_subcategoria ASC, p.id ASC';
        }

        // Keep available products before sold-out items across all pages.
        $order = 'p.estatus DESC, ' . $order;

        $sql = 'SELECT p.id, p.nombre, p.codigo, p.preciou, p.preciom, p.precioc, p.precio_costo,
                       p.topem, p.topec, p.descripcion, p.visitas, p.estatus,
                       p.minimo, p.fecha_restock, p.sub_categoria,
                       p.descuento, p.descuento_general, p.descuento_especial, p.promo,
                       p.barcode, p.product_nuevo, i.a AS imagen
                FROM productos p
                ' . $subcategoryJoin . '
                LEFT JOIN img i ON i.id_producto = p.id AND i.posicion = 1
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ' . $order . '
                LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        if ($subcategoryId !== null) {
            $stmt->bindValue(':subcategory_id', $subcategoryId, \PDO::PARAM_INT);
        }
        if ($categoryId !== null) {
            $stmt->bindValue(':category_id', $categoryId, \PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function bestSellers($onlyAvailable = false, $subcategoryId = null, $categoryId = null)
    {
        $where = 'p.active = 1';
        if ($onlyAvailable) $where .= ' AND p.estatus = 1';
        if ($subcategoryId !== null) $where .= ' AND p.sub_categoria = :subcategory_id';
        if ($categoryId !== null) $where .= ' AND EXISTS (SELECT 1 FROM admin_subcategorias s
            WHERE s.id_subcategoria = p.sub_categoria AND s.id_categoria = :category_id)';
        $stmt = $this->pdo->prepare("SELECT p.*, i.a AS imagen, ventas.total_vendido
            FROM (
                SELECT id_producto, SUM(cantidad) AS total_vendido
                FROM registro_usuario
                WHERE estatus = 'Pagado'
                  AND fecha_procesado >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                  AND fecha_procesado <= NOW()
                GROUP BY id_producto
            ) ventas
            INNER JOIN productos p ON p.id = ventas.id_producto
            LEFT JOIN img i ON i.id_producto = p.id AND i.posicion = 1
            WHERE " . $where . '
            ORDER BY ventas.total_vendido DESC, p.id ASC
            LIMIT 30');
        if ($subcategoryId !== null) $stmt->bindValue(':subcategory_id', $subcategoryId, \PDO::PARAM_INT);
        if ($categoryId !== null) $stmt->bindValue(':category_id', $categoryId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countBrand($brandId, $onlyAvailable = false)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM productos p
            INNER JOIN web_marcas m ON m.id = p.marca AND m.estatus = 1
            WHERE p.active = 1 AND p.marca = :brand_id' . ($onlyAvailable ? ' AND p.estatus = 1' : ''));
        $stmt->bindValue(':brand_id', $brandId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function brandPage($brandId, $limit, $offset, $onlyAvailable = false)
    {
        $stmt = $this->pdo->prepare('SELECT p.*, i.a AS imagen FROM productos p
            INNER JOIN web_marcas m ON m.id = p.marca AND m.estatus = 1
            LEFT JOIN img i ON i.id_producto = p.id AND i.posicion = 1
            WHERE p.active = 1 AND p.marca = :brand_id' . ($onlyAvailable ? ' AND p.estatus = 1' : '') . '
            ORDER BY p.estatus DESC, p.nombre ASC, p.id ASC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':brand_id', $brandId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findProduct($id)
    {
        $stmt = $this->pdo->prepare('SELECT p.*, i.a AS imagen,
                s.id_categoria, s.nombre_subcategoria, c.nombre_categoria
            FROM productos p
            LEFT JOIN img i ON i.id_producto = p.id AND i.posicion = 1
            LEFT JOIN admin_subcategorias s ON s.id_subcategoria = p.sub_categoria
            LEFT JOIN admin_categorias c ON c.id_categoria = s.id_categoria
            WHERE p.id = :id AND p.active = 1
            LIMIT 1');
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    private function searchFilter($query, $onlyAvailable = false, $offersOnly = false)
    {
        $conditions = array('p.active = 1');
        if ($onlyAvailable) $conditions[] = 'p.estatus = 1';
        if ($offersOnly) $conditions[] = "LOWER(TRIM(COALESCE(p.descuento, ''))) = 'si' AND " . $this->searchMoney('p.descuento_general') . ' > 0';
        $params = array();
        foreach (explode(' ', $query) as $index => $term) {
            $pattern = '%' . str_replace(array('!', '%', '_'), array('!!', '!%', '!_'), $term) . '%';
            $conditions[] = "(CONCAT_WS(' ', p.nombre, p.codigo, p.barcode, p.descripcion) LIKE :text_$index ESCAPE '!'
                OR EXISTS (SELECT 1 FROM admin_subcategorias s
                    LEFT JOIN admin_categorias c ON c.id_categoria = s.id_categoria
                    WHERE s.id_subcategoria = p.sub_categoria
                    AND CONCAT_WS(' ', s.nombre_subcategoria, c.nombre_categoria) LIKE :category_$index ESCAPE '!'))";
            $params[':text_' . $index] = $pattern;
            $params[':category_' . $index] = $pattern;
        }
        return array(implode(' AND ', $conditions), $params);
    }

    public function countSearch($query, $onlyAvailable = false, $offersOnly = false)
    {
        list($where, $params) = $this->searchFilter($query, $onlyAvailable, $offersOnly);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM productos p WHERE ' . $where);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function searchMoney($column)
    {
        return "CAST(REPLACE(REPLACE(COALESCE($column, 0), ',', ''), '$', '') AS DECIMAL(12,2))";
    }

    private function searchOrder($sort, $isVip = false)
    {
        if ($sort === 'precio_asc' || $sort === 'precio_desc') {
            $discount = $this->searchMoney('p.descuento_general');
            $price = $this->searchMoney('p.preciou');
            if ($isVip) {
                $price = 'ROUND(' . $this->searchMoney('p.precio_costo') . ' * 1.15, 2)';
                $discount = "LEAST($discount, $price)";
            }
            return "CASE WHEN LOWER(TRIM(COALESCE(p.descuento, ''))) = 'si' AND $discount > 0 THEN $discount ELSE $price END "
                . ($sort === 'precio_asc' ? 'ASC' : 'DESC') . ', p.estatus DESC, p.id ASC';
        }
        if ($sort === 'recientes') return 'p.id DESC';
        return 'p.estatus DESC, CASE WHEN p.codigo = :exact_code THEN 0 ELSE 1 END, p.nombre ASC, p.id ASC';
    }

    public function searchPage($query, $limit, $offset, $sort = 'relevancia', $onlyAvailable = false, $offersOnly = false, $isVip = false)
    {
        list($where, $params) = $this->searchFilter($query, $onlyAvailable, $offersOnly);
        $order = $this->searchOrder($sort, $isVip);
        $stmt = $this->pdo->prepare('SELECT p.*,
            (SELECT i.a FROM img i WHERE i.id_producto = p.id ORDER BY i.posicion ASC, i.a ASC LIMIT 1) AS imagen
            FROM productos p WHERE ' . $where . '
            ORDER BY ' . $order . '
            LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $stmt->bindValue($key, $value, \PDO::PARAM_STR);
        if ($sort === 'relevancia') $stmt->bindValue(':exact_code', $query, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function productVariants($code)
    {
        $stmt = $this->pdo->prepare('SELECT p.*,
            (SELECT i.a FROM img i WHERE i.id_producto = p.id
                ORDER BY i.posicion ASC, i.a ASC LIMIT 1) AS imagen
            FROM productos p
            WHERE p.active = 1 AND p.codigo = :code
            ORDER BY p.estatus DESC, p.nombre ASC, p.id ASC');
        $stmt->bindValue(':code', $code, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function relatedProducts($subcategoryId, $excludedId, $limit)
    {
        $stmt = $this->pdo->prepare('SELECT p.*, i.a AS imagen
            FROM productos p
            LEFT JOIN img i ON i.id_producto = p.id AND i.posicion = 1
            WHERE p.active = 1 AND p.sub_categoria = :subcategory_id AND p.id <> :excluded_id
            ORDER BY p.estatus DESC, RAND()
            LIMIT :limit');
        $stmt->bindValue(':subcategory_id', $subcategoryId, \PDO::PARAM_INT);
        $stmt->bindValue(':excluded_id', $excludedId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function productImages($id)
    {
        $stmt = $this->pdo->prepare('SELECT a FROM img
            WHERE id_producto = :id ORDER BY posicion ASC, a ASC');
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countOffers($onlyAvailable = false)
    {
        return $this->countMatching('ofertas', null, $onlyAvailable);
    }

    public function countSubcategory($collection, $subcategoryId, $onlyAvailable = false)
    {
        return $this->countMatching($collection, $subcategoryId, $onlyAvailable);
    }

    public function countCategory($collection, $categoryId, $onlyAvailable = false, $subcategoryId = null)
    {
        return $this->countMatching($collection, $subcategoryId, $onlyAvailable, $categoryId);
    }

    private function countMatching($collection, $subcategoryId, $onlyAvailable, $categoryId = null)
    {
        $where = array('p.active = 1');
        if ($onlyAvailable) {
            $where[] = 'p.estatus = 1';
        }
        if ($subcategoryId !== null) {
            $where[] = 'p.sub_categoria = :subcategory_id';
        }
        if ($categoryId !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM admin_subcategorias s
                WHERE s.id_subcategoria = p.sub_categoria AND s.id_categoria = :category_id)';
        }

        if ($collection === 'ofertas') {
            $where[] = 'LOWER(TRIM(COALESCE(p.descuento, ""))) = "si"';
        } elseif ($collection === 'nuevos') {
            $where[] = 'LOWER(TRIM(COALESCE(p.descuento, ""))) <> "si"';
        }
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM productos p WHERE ' . implode(' AND ', $where));
        if ($subcategoryId !== null) {
            $stmt->bindValue(':subcategory_id', $subcategoryId, \PDO::PARAM_INT);
        }
        if ($categoryId !== null) {
            $stmt->bindValue(':category_id', $categoryId, \PDO::PARAM_INT);
        }
        $stmt->execute();
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

        $productSql = 'SELECT p.id, p.nombre, p.codigo, p.preciou, p.preciom, p.precioc, p.precio_costo,
                              p.topem, p.topec, p.descripcion, p.visitas, p.estatus,
                              p.minimo, p.fecha_restock, p.sub_categoria,
                              p.descuento, p.descuento_general, p.descuento_especial, p.promo,
                              p.barcode, p.product_nuevo, i.a AS imagen
                       FROM productos p
                       LEFT JOIN img i ON i.id_producto = p.id AND i.posicion = 1
                       WHERE p.sub_categoria = :subcategory_id
                         AND p.active = 1
                         AND p.estatus = 1
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
