<?php
// CONTROL DE VERSIÓN: INSTALADOR KIOSCO v12.25

// 1. Extraer las variables de entorno de MySQL que cargaste en Railway
$host = getenv('mysql.railway.internal');
$port = getenv('3306');
$db   = getenv('railway');
$user = getenv('root');
$pass = getenv('pSchnGuDRryXTUlQkutxfYSBgLxnvLzE');

// 2. Construir el Data Source Name (DSN) adaptado para MySQL
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

try {
    // 3. Establecer la conexión segura mediante PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Conexión exitosa a la base de datos de Railway.<br>";

    // 4. Script estructural nativo para el ecosistema del Kiosco
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

    // 5. Ejecutar la inyección estructural de las tablas
    $pdo->exec($sql);
    echo "¡Tablas 'productos' y 'clientes' creadas (o verificadas) correctamente con Consumidor Final inicializado!";

} catch (\PDOException $e) {
    // En caso de falla, nos arrojará el detalle técnico exacto sin romper el servidor
    die("Error crítico de conexión o ejecución: " . $e->getMessage());
}
