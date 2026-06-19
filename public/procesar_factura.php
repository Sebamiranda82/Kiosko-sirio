<?php
// =========================================================================
// ARCHIVO: procesar_factura.php | v12.2 LOCAL (TERMUX)
// =========================================================================

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Conexión local Termux
$host     = getenv('MYSQLHOST')     ?: "127.0.0.1";
$dbname   = getenv('MYSQLDATABASE') ?: "kiosko";
$username = getenv('MYSQLUSER')     ?: "root";
$password = getenv('MYSQLPASSWORD') ?: "";
$port     = getenv('MYSQLPORT')     ?: "3306";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Error de conexión: " . $e->getMessage()]);
    exit;
}

$action   = isset($_GET['action']) ? $_GET['action'] : '';
$inputRaw = file_get_contents("php://input");
$data     = json_decode($inputRaw, true);

if ($action === 'obtener_productos') {
    try {
        $stmt = $pdo->prepare("SELECT codigo, detalle, precio, iva, categoria FROM productos");
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $listaFormat = [];
        foreach ($productos as $p) {
            $listaFormat[$p['codigo']] = [
                "detalle"   => $p['detalle'],
                "precio"    => (float)$p['precio'],
                "iva"       => (bool)$p['iva'],
                "categoria" => $p['categoria']
            ];
        }
        echo json_encode(["success" => true, "productos" => $listaFormat]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    $pdo = null;
    exit;
}

if ($action === 'obtener_clientes') {
    try {
        $stmt = $pdo->prepare("SELECT id_cliente, nombre, direccion, email FROM clientes");
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $listaFormat = [];
        foreach ($clientes as $c) {
            $listaFormat[$c['id_cliente']] = [
                "nombre"    => $c['nombre'],
                "direccion" => $c['direccion'],
                "email"     => $c['email']
            ];
        }
        echo json_encode(["success" => true, "clientes" => $listaFormat]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    $pdo = null;
    exit;
}

if ($action === 'eliminar_producto') {
    if (!$data || !isset($data['codigo'])) {
        echo json_encode(["success" => false, "error" => "No se recibió el código del producto."]);
        $pdo = null; exit;
    }
    $codigo = trim($data['codigo']);
    try {
        $stmt = $pdo->prepare("DELETE FROM productos WHERE codigo = ?");
        $stmt->execute([$codigo]);
        echo json_encode(["success" => true, "message" => "Producto eliminado."]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    $pdo = null; exit;
}

if ($action === 'eliminar_cliente') {
    if (!$data || !isset($data['id_cliente'])) {
        echo json_encode(["success" => false, "error" => "No se recibió la identificación del cliente."]);
        $pdo = null; exit;
    }
    $idCliente = trim($data['id_cliente']);
    if ($idCliente === "9999999999999") {
        echo json_encode(["success" => false, "error" => "No se puede eliminar el registro por defecto del sistema."]);
        $pdo = null; exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = ?");
        $stmt->execute([$idCliente]);
        echo json_encode(["success" => true, "message" => "Cliente eliminado."]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    $pdo = null; exit;
}

if (!$data || !isset($data['productos'])) {
    echo json_encode(["status" => "error", "mensaje" => "No hay productos recibidos para facturar."]);
    $pdo = null; exit;
}

echo json_encode(["status" => "success", "mensaje" => "Factura procesada con éxito."]);
$pdo = null;
?>
