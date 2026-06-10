<?php

require_once __DIR__ . '/Database.php';

class DashboardStats {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getTotalOrders(): int {
        return (int) $this->db->query("SELECT COUNT(id) FROM orders")->fetchColumn();
    }

    public function getTotalSales(): float {
        $query = "
            SELECT SUM(oi.price_at_purchase * oi.quantity)
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status IN ('approved', 'completed')
        ";
        return (float) $this->db->query($query)->fetchColumn();
    }

    public function getStatusCounts(): array {
        $stmt = $this->db->query("
            SELECT status, COUNT(id) as count 
            FROM orders 
            WHERE status IN ('pending', 'approved', 'cancelled') 
            GROUP BY status
        ");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getProductImages(): array {
        $stmt = $this->db->query("SELECT image_url FROM products WHERE image_url IS NOT NULL AND image_url != ''");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getMaintenanceFundStats(array $withdrawals): array {
        $totalSales = $this->getTotalSales();
        $maintenanceFund = $totalSales * 0.01;
        
        $totalWithdrawn = 0;
        foreach ($withdrawals as $w) {
            $totalWithdrawn += ($w['amount'] ?? 0);
        }

        return [
            'total_sales' => $totalSales,
            'accumulated' => $maintenanceFund,
            'withdrawn' => $totalWithdrawn,
            'available' => max(0, $maintenanceFund - $totalWithdrawn)
        ];
    }
}