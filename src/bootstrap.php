<?php
// Calcular la ruta raíz del proyecto de forma dinámica
define('ROOT_PATH', dirname(__DIR__));

// Cargar autoloader
require_once ROOT_PATH . '/vendor/autoload.php';

// Cargar variables de entorno
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();