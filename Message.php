<?php

class Message {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Creates a new message record in the database.
     */
    public function create(int $sender_id, int $recipient_id, string $message): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO messages (sender_id, recipient_id, message) VALUES (:sender_id, :recipient_id, :message)"
        );
        return $stmt->execute([
            'sender_id' => $sender_id,
            'recipient_id' => $recipient_id,
            'message' => $message
        ]);
    }

    /**
     * Deletes a message by ID.
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM messages WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Retrieves all messages for a specific user (both sent and received).
     */
    public function getMessagesForUser(int $user_id): array {
        $stmt = $this->db->prepare("
            SELECT
                m.id, m.message, m.created_at,
                sender.username AS sender_name,
                recipient.username AS recipient_name,
                m.sender_id, m.recipient_id
            FROM messages m
            JOIN users sender ON m.sender_id = sender.id
            JOIN users recipient ON m.recipient_id = recipient.id
            WHERE m.sender_id = :sender_user_id OR m.recipient_id = :recipient_user_id
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([
            'sender_user_id' => $user_id,
            'recipient_user_id' => $user_id
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves all messages in the system (for admin use).
     */
    public function getAllMessages(): array {
        $stmt = $this->db->prepare("
            SELECT
                m.id, m.message, m.created_at,
                sender.username AS sender_name,
                recipient.username AS recipient_name,
                m.sender_id, m.recipient_id
            FROM messages m
            JOIN users sender ON m.sender_id = sender.id
            JOIN users recipient ON m.recipient_id = recipient.id
            ORDER BY m.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Counts unread messages for a specific recipient.
     */
    public function countUnread(int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM messages WHERE recipient_id = :user_id AND is_read = 0");
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marks all messages sent to a user as read.
     */
    public function markAllAsReadForUser(int $userId): bool {
        $stmt = $this->db->prepare("UPDATE messages SET is_read = 1 WHERE recipient_id = :user_id AND is_read = 0");
        return $stmt->execute(['user_id' => $userId]);
    }
}