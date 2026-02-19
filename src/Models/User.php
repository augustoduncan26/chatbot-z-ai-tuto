<?php

namespace App\Models;

use App\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): ?array
    {
        // Hash de la contraseña
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (name, email, password) 
                VALUES (:name, :email, :password)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
            ]);

            return $this->findById($this->db->lastInsertId());
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                return null; // Email ya existe
            }
            throw $e;
        }
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT id, name, email, created_at FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}