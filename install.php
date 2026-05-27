<?php
// CONTROL DE VERSIÓN: CONFIGURACIÓN CON CREDENCIALES FINALES v12.66

$host     = 'zephyr.proxy.rlwy.net'; 
$port     = '27672'; 
$dbname   = 'railway';
$username = 'root';
$password = 'pSchnGuDRryXTUlQkutxfYSBgLxnvLzE';

try {
    // Conexión forzada por red TCP externa (evita sockets internos)
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<h3>[ÉXITO] Conexión establecida correctamente con el motor MySQL.</h3>";

    // Script estructural del Kiosco
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

    $pdo->exec($sql);
    echo "<h2>¡TABLAS CREADAS CORRECTAMENTE!</h2><p>Las estructuras de 'productos' y 'clientes' ya impactaron en la base de datos.</p>";

} catch (\PDOException $e) {
    die("<h2 style='color:red;'>Error crítico de conexión o ejecución:</h2> " . $e->getMessage());
}
