<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';

            try {
                self::$instance = new PDO(
                    $config['dsn'],
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                error_log("Error de conexión a BD: " . $e->getMessage());
                error_log("DSN usado: " . $config['dsn']);
                
                throw new \Exception("No se pudo conectar a la base de datos: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}