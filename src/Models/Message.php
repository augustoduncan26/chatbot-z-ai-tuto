<?php

namespace App\Models;

use App\Database;
use PDO;

class Message
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): ?array
    {
        $sql = "INSERT INTO messages (conversation_id, role, content, metadata) 
                VALUES (:conversation_id, :role, :content, :metadata)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'conversation_id' => $data['conversation_id'],
            'role' => $data['role'],
            'content' => $data['content'],
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
        ]);

        $id = $this->db->lastInsertId();
        return $this->find($id);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM messages WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
}