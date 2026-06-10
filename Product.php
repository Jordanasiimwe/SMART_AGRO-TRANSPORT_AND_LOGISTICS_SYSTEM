<?php

class Product {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(int $farmer_id, string $name, string $description, float $price, int $quantity, string $unit, string $image_url = null): int|false {
        $stmt = $this->db->prepare("
            INSERT INTO products (farmer_id, name, description, price, quantity, unit, image_url)
            VALUES (:farmer_id, :name, :description, :price, :quantity, :unit, :image_url)
        ");
        $stmt->execute([
            'farmer_id'   => $farmer_id,
            'name'        => $name,
            'description' => $description,
            'price'       => $price,
            'quantity'    => $quantity,
            'unit'        => $unit,
            'image_url'   => $image_url,
        ]);

        return $this->db->lastInsertId();
    }

   public function findById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByFarmerId(int $farmer_id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE farmer_id = :farmer_id");
        $stmt->execute(['farmer_id' => $farmer_id]);
        return $stmt->fetchAll();
    }

   public function update(int $id, string $name, string $description, float $price, int $quantity, string $unit, string $image_url = null): bool {
        $stmt = $this->db->prepare("
            UPDATE products 
            SET name = :name, description = :description, price = :price, quantity = :quantity, unit = :unit, image_url = :image_url
            WHERE id = :id
        ");
        return $stmt->execute([
            'id'          => $id,
            'name'        => $name,
            'description' => $description,
            'price'       => $price,
            'quantity'    => $quantity,
            'unit'        => $unit,
            'image_url'   => $image_url,
        ]);
    }

    public function delete(int $id): bool {
        try {
            $this->db->beginTransaction();

            // Delete related order items first to resolve foreign key constraint
            $stmt = $this->db->prepare("DELETE FROM order_items WHERE product_id = :id");
            $stmt->execute(['id' => $id]);

            // Delete the product
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute(['id' => $id]);

            return $this->db->commit();
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function getAllProducts(): array|false {
        $stmt = $this->db->prepare(
            "SELECT p.*, f.farm_name, f.location 
             FROM products p
             JOIN farmers f ON p.farmer_id = f.user_id
             ORDER BY p.created_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAllProducts(): int {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM products");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function searchProducts(string $query): array|false {
        $searchTerm = '%' . $query . '%';
        $stmt = $this->db->prepare(
            "SELECT p.*, f.farm_name, f.location 
             FROM products p
             JOIN farmers f ON p.farmer_id = f.user_id
             WHERE p.name LIKE :q1 OR f.farm_name LIKE :q2
             ORDER BY p.name ASC"
        );
        $stmt->execute([
            'q1' => $searchTerm,
            'q2' => $searchTerm,
        ]);
        return $stmt->fetchAll();
    }

    public function getOrdersForProduct(int $product_id): array
    {
        $stmt = $this->db->prepare("
            SELECT o.* 
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE oi.product_id = :product_id
        ");

        $stmt->execute(['product_id' => $product_id]);

        return $stmt->fetchAll();
    }

    /**
     * Updates just the quantity of a product.
     */
    public function updateStock(int $id, float $newQuantity): bool {
        $stmt = $this->db->prepare("UPDATE products SET quantity = :quantity WHERE id = :id");
        return $stmt->execute(['quantity' => $newQuantity, 'id' => $id]);
    }
}