<?php

class Feedback {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(?int $user_id, string $subject, string $message): bool {
        $stmt = $this->db->prepare("
            INSERT INTO feedback (user_id, subject, message)
            VALUES (:user_id, :subject, :message)
        ");
        return $stmt->execute([
            'user_id' => $user_id,
            'subject' => $subject,
            'message' => $message
        ]);
    }

    public function getAllFeedback(): array {
        $stmt = $this->db->prepare("
            SELECT f.*, u.username 
            FROM feedback f 
            LEFT JOIN users u ON f.user_id = u.id 
            ORDER BY f.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Counts unread feedback messages.
     */
    public function countUnread(): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM feedback WHERE is_read = 0");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marks all unread feedback as read.
     */
    public function markAllAsRead(): bool {
        $stmt = $this->db->prepare("UPDATE feedback SET is_read = 1 WHERE is_read = 0");
        return $stmt->execute();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM feedback WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}