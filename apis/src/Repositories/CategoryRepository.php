<?php

namespace App\Repositories;

class CategoryRepository
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listWithSubcategories($categoryStatus, $subcategoryStatus)
    {
        $sql = 'SELECT c.id_categoria,
                       c.id_depa,
                       c.nombre_categoria,
                       c.estatus_categoria,
                       c.position AS categoria_position,
                       c.icon,
                       c.emoji,
                       s.id_subcategoria,
                       s.nombre_subcategoria,
                       s.estatus_subcategoria,
                       s.position AS subcategoria_position
                FROM admin_categorias c
                LEFT JOIN admin_subcategorias s ON s.id_categoria = c.id_categoria';

        $where = array();
        if ($categoryStatus !== null) {
            $where[] = 'c.estatus_categoria = :category_status';
        }
        if ($subcategoryStatus !== null) {
            $where[] = '(s.id_subcategoria IS NULL OR s.estatus_subcategoria = :subcategory_status)';
        }
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY c.position IS NULL ASC,
                         c.position ASC,
                         c.id_categoria ASC,
                         s.position IS NULL ASC,
                         s.position ASC,
                         s.id_subcategoria ASC';

        $stmt = $this->pdo->prepare($sql);
        if ($categoryStatus !== null) {
            $stmt->bindValue(':category_status', $categoryStatus, \PDO::PARAM_STR);
        }
        if ($subcategoryStatus !== null) {
            $stmt->bindValue(':subcategory_status', $subcategoryStatus, \PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }
}
