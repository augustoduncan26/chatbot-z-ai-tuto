<?php

namespace App\Models;

use App\Database;
use PDO;

class Conversation
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): ?array
    {
        $sql = "INSERT INTO conversations (session_id, user_name, user_email, context) 
                VALUES (:session_id, :user_name, :user_email, :context)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'session_id' => $data['session_id'],
            'user_name' => $data['user_name'] ?? null,
            'user_email' => $data['user_email'] ?? null,
            'context' => $data['context'] ?? null,
        ]);

        return $this->findBySessionId($data['session_id']);
    }

    public function findBySessionId(string $sessionId): ?array
    {
        $sql = "SELECT * FROM conversations WHERE session_id = :session_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['session_id' => $sessionId]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getHistory(int $conversationId, int $limit = 10): array
    {
        $sql = "SELECT role, content FROM messages 
                WHERE conversation_id = :conversation_id 
                AND role != 'system'
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = $stmt->fetchAll();
        return array_reverse($messages); // Orden cronológico
    }

    public function getAllMessages(int $conversationId): array
    {
        $sql = "SELECT role, content, created_at FROM messages 
                WHERE conversation_id = :conversation_id 
                AND role != 'system'
                ORDER BY created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['conversation_id' => $conversationId]);
        
        return $stmt->fetchAll();
    }
}