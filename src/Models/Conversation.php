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
        $sql = "INSERT INTO conversations (session_id, user_id, user_name, user_email, title, context) 
                VALUES (:session_id, :user_id, :user_name, :user_email, :title, :context)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'session_id' => $data['session_id'],
            'user_id' => $data['user_id'] ?? null,
            'user_name' => $data['user_name'] ?? null,
            'user_email' => $data['user_email'] ?? null,
            'title' => $data['title'] ?? 'Nueva conversación',
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

    public function getAllByUserId(int $userId): array
    {
        $sql = "SELECT 
                    c.id, 
                    c.session_id, 
                    c.title,
                    c.created_at,
                    c.updated_at,
                    (SELECT content FROM messages WHERE conversation_id = c.id AND role = 'user' ORDER BY created_at ASC LIMIT 1) as first_message
                FROM conversations c
                WHERE c.user_id = :user_id
                ORDER BY c.updated_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetchAll();
    }

    public function updateTitle(int $conversationId, string $title): bool
    {
        $sql = "UPDATE conversations SET title = :title WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'title' => $title,
            'id' => $conversationId
        ]);
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
        return array_reverse($messages);
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

    public function delete(int $conversationId): bool
    {
        $sql = "DELETE FROM conversations WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $conversationId]);
    }
}