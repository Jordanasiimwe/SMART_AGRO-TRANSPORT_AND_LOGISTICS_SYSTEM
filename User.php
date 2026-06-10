<?php

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Find a user by username and verify their password.
     * Returns user data on success, false on failure.
     */
    public function login(string $username, string $password): array|false {
        $stmt = $this->db->prepare(
            "SELECT users.id, users.username, users.password_hash, users.role_id, users.status, roles.name AS role_name 
             FROM users
             JOIN roles ON users.role_id = roles.id 
             WHERE username = :username"
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']); // Don't store the hash in the session
            return $user;
        }

        return false;
    }

    public function generateResetToken(string $email): string|false {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) return false;

        // Generate a 6-digit numeric OTP
        $token = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->db->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id");
        if ($stmt->execute(['token' => $token, 'expires' => $expires, 'id' => $user['id']])) {
            return $token;
        }
        return false;
    }

    public function resetPasswordByEmail(string $email, string $newPassword): bool {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) return false;
        return $this->updatePassword($user['id'], $newPassword);
    }

    public function findUserByResetToken(string $token): array|false {
        $stmt = $this->db->prepare("
            SELECT id FROM users 
            WHERE reset_token = :token 
            AND reset_expires > NOW()
        ");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
    }

    public function updatePassword(int $userId, string $password): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password_hash = :hash, 
                reset_token = NULL, 
                reset_expires = NULL 
            WHERE id = :id
        ");
        return $stmt->execute(['hash' => $hash, 'id' => $userId]);
    }

    public function updateUsername(int $userId, string $username): bool {
        $stmt = $this->db->prepare("UPDATE users SET username = :username WHERE id = :user_id");
        return $stmt->execute([
            'user_id' => $userId,
            'username' => $username,
        ]);
    }

    public function getFarmerProfile(int $userId): array|false {
        $stmt = $this->db->prepare(
            "SELECT u.username, u.email, f.farm_name, f.location, f.contact, f.sacco, f.latitude, f.longitude
             FROM users u
             JOIN farmers f ON u.id = f.user_id
             WHERE u.id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }

    public function updateFarmerProfile(int $userId, string $farmName, string $location, string $contact, ?string $sacco, ?float $latitude, ?float $longitude): bool {
        $stmt = $this->db->prepare(
            "UPDATE farmers
             SET farm_name = :farm_name, location = :location, contact = :contact, sacco = :sacco, latitude = :latitude, longitude = :longitude
             WHERE user_id = :user_id"
        );
        return $stmt->execute([
            'user_id' => $userId,
            'farm_name' => $farmName,
            'location' => $location,
            'contact' => $contact,
            'sacco' => $sacco,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function updateVendorProfile(int $userId, string $marketStallId, string $sacco, string $contact): bool {
        $stmt = $this->db->prepare(
            "UPDATE vendors 
             SET market_stall_id = :market_stall_id, sacco = :sacco, contact = :contact
             WHERE user_id = :user_id"
        );
        return $stmt->execute([
            'user_id' => $userId,
            'market_stall_id' => $marketStallId,
            'sacco' => $sacco,
            'contact' => $contact
        ]);
    }

    public function getVendorDetails(int $userId): array|false {
        $stmt = $this->db->prepare(
            "SELECT u.username, u.email, v.market_stall_id, v.sacco AS sacco_name, v.contact
             FROM users u
             JOIN vendors v ON u.id = v.user_id
             WHERE u.id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }

    public function getUsersByRole(string $roleName): array|false {
        if ($roleName === 'farmer') {
            $stmt = $this->db->prepare(
                "SELECT u.id, u.username, u.email, u.created_at, u.status, f.farm_name, f.location, f.contact, f.latitude, f.longitude
                 FROM users u
                 JOIN roles r ON u.role_id = r.id
                 JOIN farmers f ON u.id = f.user_id
                 WHERE r.name = :role_name
                 ORDER BY u.created_at DESC"
            );
        } elseif ($roleName === 'vendor') {
            $stmt = $this->db->prepare(
                "SELECT u.id, u.username, u.email, u.created_at, u.status, v.market_stall_id, v.sacco AS sacco_name, v.contact 
                 FROM users u
                 JOIN roles r ON u.role_id = r.id
                 JOIN vendors v ON u.id = v.user_id
                 WHERE r.name = :role_name
                 ORDER BY u.created_at DESC"
            );
        } else {
            return false;
        }

        $stmt->execute(['role_name' => $roleName]);
        return $stmt->fetchAll();
    }

    public function countUsersByRole(string $roleName): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(u.id) 
             FROM users u
             JOIN roles r ON u.role_id = r.id
             WHERE r.name = :role_name"
        );
        $stmt->execute(['role_name' => $roleName]);
        return (int) $stmt->fetchColumn();
    }

    public function searchUsersByRole(string $roleName, string $query): array|false {
        $searchTerm = '%' . $query . '%';

        if ($roleName === 'farmer') {
            $stmt = $this->db->prepare(
                "SELECT u.id, u.username, u.email, u.created_at, u.status, f.farm_name, f.location, f.contact, f.latitude, f.longitude
                 FROM users u
                 JOIN roles r ON u.role_id = r.id
                 JOIN farmers f ON u.id = f.user_id
                 WHERE r.name = :role_name AND (u.username LIKE :q1 OR u.email LIKE :q2 OR f.farm_name LIKE :q3 OR f.location LIKE :q4)
                 ORDER BY u.username ASC"
            );
            $stmt->execute([
                'role_name' => $roleName,
                'q1' => $searchTerm,
                'q2' => $searchTerm,
                'q3' => $searchTerm,
                'q4' => $searchTerm,
            ]);
        } elseif ($roleName === 'vendor') {
            $stmt = $this->db->prepare(
                "SELECT u.id, u.username, u.email, u.created_at, u.status, v.market_stall_id, v.sacco AS sacco_name
                 , v.contact FROM users u
                 JOIN roles r ON u.role_id = r.id
                 JOIN vendors v ON u.id = v.user_id
                 WHERE r.name = :role_name AND (u.username LIKE :q1 OR u.email LIKE :q2 OR v.sacco LIKE :q3 OR v.contact LIKE :q4)
                 ORDER BY u.username ASC"
            );
            $stmt->execute([
                'role_name' => $roleName,
                'q1' => $searchTerm,
                'q2' => $searchTerm,
                'q3' => $searchTerm,
                'q4' => $searchTerm,
            ]);
        } else {
            return false;
        }
        return $stmt->fetchAll();
    }

    public function findContactByUserId(int $userId): ?string
    {
        // This single query attempts to find a contact number from either the farmers or vendors table.
        // It uses LEFT JOINs so that it works even if a user is in one table but not the other.
        $stmt = $this->db->prepare("
            SELECT f.contact AS farmer_contact, v.contact AS vendor_contact, r.name AS role_name
            FROM users u
            LEFT JOIN farmers f ON u.id = f.user_id
            LEFT JOIN vendors v ON u.id = v.user_id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $contacts = $stmt->fetch();

        if ($contacts) {
            // If user is admin, return a placeholder contact so messages can be sent
            if ($contacts['role_name'] === 'admin') {
                return 'System Admin';
            }

            // Return the first non-empty contact number found, checking farmer first.
            if (!empty($contacts['farmer_contact'])) {
                return $contacts['farmer_contact'];
            }
            if (!empty($contacts['vendor_contact'])) {
                return $contacts['vendor_contact'];
            }
        }

        // If no user is found or they have no contact in either table
        return null;
    }

    /**
     * Gets a list of all users that can be contacted (farmers and vendors), excluding the given user ID.
     */
    public function getContactableUsers(int $excludeUserId): array
    {
        $stmt = $this->db->prepare("
            SELECT users.id, users.username, roles.name as role_name
            FROM users
            JOIN roles ON users.role_id = roles.id
            WHERE users.id != :exclude_user_id AND roles.name IN ('farmer', 'vendor', 'admin')
            ORDER BY users.username ASC
        ");
        $stmt->execute(['exclude_user_id' => $excludeUserId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all vendors and admins for system-wide notifications.
     */
    public function getSystemNotificationRecipients(): array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, COALESCE(v.contact, 'System Admin') as contact, r.name as role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN vendors v ON u.id = v.user_id
            WHERE r.name IN ('vendor', 'admin')
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all users with their contact details for bulk messaging.
     */
    public function getAllUsersWithContacts(): array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, 
                   COALESCE(f.contact, v.contact) as contact
            FROM users u
            LEFT JOIN farmers f ON u.id = f.user_id
            LEFT JOIN vendors v ON u.id = v.user_id
            WHERE u.role_id != 1 -- Exclude admins from receiving the bulk blast if desired, or keep them.
            AND (f.contact IS NOT NULL OR v.contact IS NOT NULL)
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Delete a user and their associated data.
     */
    public function delete(int $id): bool {
        try {
            $this->db->beginTransaction();

            /**
             * Note: Tables are defined with ON DELETE CASCADE in the schema.
             * Deleting the user will automatically remove entries in:
             * farmers, vendors, products, orders, messages, etc.
             * We only need to handle SET NULL dependencies or multi-step logic if required.
             */

            // 4. Delete Feedback
            $stmt = $this->db->prepare("DELETE FROM feedback WHERE user_id = :id");
            $stmt->execute(['id' => $id]);

            // 5. Delete Withdrawals
            $stmt = $this->db->prepare("DELETE FROM withdrawals WHERE processed_by = :id");
            $stmt->execute(['id' => $id]);

            // 6. Finally delete the user record
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);

            return $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            // Log error
            $logMessage = date('[Y-m-d H:i:s] ') . "Delete User Error: " . $e->getMessage() . PHP_EOL;
            // Use @ to prevent warnings if the file isn't writable
            if (!@file_put_contents(__DIR__ . '/db_error_log.txt', $logMessage, FILE_APPEND)) {
                error_log("Could not write to db_error_log.txt: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Updates the status of a user (e.g., active or inactive).
     */
    public function updateStatus(int $userId, string $status): bool {
        try {
            $stmt = $this->db->prepare("UPDATE users SET status = :status WHERE id = :id");
            return $stmt->execute(['status' => $status, 'id' => $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Updates the last_active_at timestamp for a user.
     */
    public function updateLastActivity(int $userId): bool {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_active_at = NOW() WHERE id = :id");
            return $stmt->execute(['id' => $userId]);
        } catch (PDOException $e) {
            // Silently fail if column doesn't exist yet to prevent breaking the app during migration
            return false;
        }
    }

    /**
     * Get users active in the last N minutes.
     */
    public function getOnlineUsers(int $minutes = 5): array {
        $cutoff = date('Y-m-d H:i:s', time() - ($minutes * 60));
        try {
            $stmt = $this->db->prepare("
                SELECT u.username, r.name as role_name, u.last_active_at
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.last_active_at >= :cutoff
                ORDER BY u.last_active_at DESC
            ");
            $stmt->execute(['cutoff' => $cutoff]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Return empty array if column doesn't exist yet, preventing fatal error
            return [];
        }
    }

    /**
     * Get all users who have ever been active, sorted by most recent activity.
     */
    public function getAllActiveUsers(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.username, u.email, u.last_active_at, r.name as role_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.last_active_at IS NOT NULL
                ORDER BY u.last_active_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // --- Team Member Management (Replaces team.json) ---
    public function getTeamMembers(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM team_members ORDER BY display_order ASC");
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 1); // Returns array of names
        } catch (PDOException $e) {
            return [];
        }
    }

    public function updateTeamMember(int $index, string $name): bool {
        // Note: For simplicity, we map index to ID based on offset, assuming ID 1=index 0, etc.
        // In a full system, you'd pass the ID directly.
        $id = $index + 1; 
        $stmt = $this->db->prepare("UPDATE team_members SET name = :name WHERE id = :id");
        return $stmt->execute(['name' => $name, 'id' => $id]);
    }

    // --- Withdrawal Management (Replaces withdrawals.json) ---
    public function getWithdrawals(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM withdrawals ORDER BY created_at DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function logWithdrawal(float $amount, string $method, int $processedBy): bool {
        $stmt = $this->db->prepare("INSERT INTO withdrawals (amount, method, processed_by) VALUES (:amount, :method, :processed_by)");
        return $stmt->execute([
            'amount' => $amount, 
            'method' => $method, 
            'processed_by' => $processedBy
        ]);
    }
}