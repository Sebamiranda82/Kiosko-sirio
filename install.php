<?php
// CONTROL DE VERSIÓN: CONFIGURACIÓN DE PUERTO Y SOCKET v12.58

// Forzar la lectura de las variables de Railway o usar la IP de loopback (no localhost)
$host     = getenv('MYSQLHOST') ?: '127.0.0.1';
$port     = getenv('MYSQLPORT') ?: '3306';
$dbname   = getenv('MYSQLDATABASE');
$username = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');

try {
    // Es mandatorio incluir el parámetro host y port separados para TCP
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    // 1. Inicialización única y correcta de PDO
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Conexión exitosa a la base de datos de Railway.<br>";

    // 2. Script estructural nativo para el ecosistema del Kiosco
    $sql = "
    CREATE TABLE IF NOT EXISTS `productos` (
      `codigo` VARCHAR(50) NOT NULL,
      `detalle` VARCHAR(255) NOT NULL,
      `precio` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
      `iva` TINYINT(1) NOT NULL DEFAULT 0,
      `categoria` VARCHAR(100) DEFAULT NULL,
      PRIMARY KEY (`codigo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `clientes` (
      `id_cliente` VARCHAR(20) NOT NULL,
      `nombre` VARCHAR(255) NOT NULL,
      `direccion` VARCHAR(255) DEFAULT NULL,
      `email` VARCHAR(150) DEFAULT NULL,
      PRIMARY KEY (`id_cliente`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    INSERT INTO `clientes` (`id_cliente`, `nombre`, `direccion`, `email`) 
    VALUES ('9999999999999', 'CONSUMIDOR FINAL', 'QUITO', 'consumidor@eglobal.com')
    ON DUPLICATE KEY UPDATE `nombre`=`nombre`;
    ";

    // 3. Ejecutar la inyección estructural de las tablas
    $pdo->exec($sql);
    echo "¡Tablas 'productos' y 'clientes' creadas (o verificadas) correctamente con Consumidor Final inicializado!";

} catch (\PDOException $e) {
    // En caso de falla, nos arrojará el detalle técnico exacto sin romper el servidor
    die("Error crítico de conexión o ejecución: " . $e->getMessage());
}
