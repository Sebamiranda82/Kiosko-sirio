<?php
// =========================================================================
// ARCHIVO: procesar_factura.php | COMPILACIÓN: v12.2 (PROD - RAILWAY.APP)
// CAMBIOS: CIERRES EXPRESOS PDO, RUTAS UNIFICADAS, REESTRUCTURACIÓN DE ENTORNOS
// =========================================================================

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Manejo obligatorio de peticiones CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 1. Configuración de Conexión Flexible (Soporta Variables de Entorno de Railway o Rígidas)
$host     = getenv('MYSQLHOST')     ?: "mysql.railway.internal";
$dbname   = getenv('MYSQLDATABASE') ?: "railway";
$username = getenv('MYSQLUSER')     ?: "root";
$password = getenv('MYSQLPASSWORD') ?: "hKzzjMobyaFvWlhGAjDTtAQFcBQUbyAx";
$port     = getenv('MYSQLPORT')     ?: "3306";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Error de conexión BD en Railway: " . $e->getMessage()]);
    exit;
}

// 2. Capturar parámetros de la URL y cuerpo del JSON de forma segura
$action = isset($_GET['action']) ? $_GET['action'] : '';
$inputRaw = file_get_contents("php://input");
$data = json_decode($inputRaw, true);

// =========================================================================
// ACCIÓN: OBTENER TODOS LOS PRODUCTOS (Para cargar el catálogo dinámico)
// =========================================================================
if ($action === 'obtener_productos') {
    try {
        $stmt = $pdo->prepare("SELECT codigo, detalle, precio, iva, categoria FROM productos");
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $listaFormat = [];
        foreach ($productos as $p) {
            $listaFormat[$p['codigo']] = [
                "detalle" => $p['detalle'],
                "precio" => (float)$p['precio'],
                "iva" => (bool)$p['iva'],
                "categoria" => $p['categoria']
            ];
        }
        
        echo json_encode(["success" => true, "productos" => $listaFormat]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "Error al obtener productos: " . $e->getMessage()]);
    }
    $pdo = null; // Cierre de canal persistente
    exit;
}

// =========================================================================
// ACCIÓN: OBTENER TODOS LOS CLIENTES (Para sincronización inicial de interfaz)
// =========================================================================
if ($action === 'obtener_clientes') {
    try {
        $stmt = $pdo->prepare("SELECT id_cliente, nombre, direccion, email FROM clientes");
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $listaFormat = [];
        foreach ($clientes as $c) {
            $listaFormat[$c['id_cliente']] = [
                "nombre" => $c['nombre'],
                "direccion" => $c['direccion'],
                "email" => $c['email']
            ];
        }
        
        echo json_encode(["success" => true, "clientes" => $listaFormat]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "Error al obtener clientes: " . $e->getMessage()]);
    }
    $pdo = null;
    exit;
}

// =========================================================================
// RUTA 1: ELIMINAR PRODUCTO (action=eliminar_producto)
// =========================================================================
if ($action === 'eliminar_producto') {
    if (!$data || !isset($data['codigo'])) {
        echo json_encode(["success" => false, "error" => "No se recibió el código del producto."]);
        $pdo = null;
        exit;
    }

    $codigo = trim($data['codigo']);
    try {
        $stmt = $pdo->prepare("DELETE FROM productos WHERE codigo = ?");
        $stmt->execute([$codigo]);
        echo json_encode(["success" => true, "message" => "Producto eliminado correctamente de la base de datos."]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "Error al ejecutar DELETE en MySQL: " . $e->getMessage()]);
    }
    $pdo = null;
    exit; 
}

// =========================================================================
// RUTA 2: ELIMINAR CLIENTE (action=eliminar_cliente)
// =========================================================================
if ($action === 'eliminar_cliente') {
    if (!$data || !isset($data['id_cliente'])) {
        echo json_encode(["success" => false, "error" => "No se recibió la identificación del cliente."]);
        $pdo = null;
        exit;
    }

    $idCliente = trim($data['id_cliente']);
    
    // Bloqueo lógico de seguridad para Consumidor Final
    if ($idCliente === "9999999999999") {
        echo json_encode(["success" => false, "error" => "No se puede eliminar el registro por defecto del sistema."]);
        $pdo = null;
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = ?");
        $stmt->execute([$idCliente]);
        echo json_encode(["success" => true, "message" => "Cliente eliminado correctamente de la base de datos."]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "Error al ejecutar DELETE en MySQL: " . $e->getMessage()]);
    }
    $pdo = null;
    exit; 
}

// =========================================================================
// RUTA 3: PROCESO ESTÁNDAR DE FACTURACIÓN (Si no se pasa ninguna acción de borrado)
// =========================================================================
if (!$data || !isset($data['productos'])) {
    echo json_encode(["status" => "error", "mensaje" => "No hay productos recibidos para facturar."]);
    $pdo = null;
    exit;
}

// --- TU LÓGICA DE FACTURACIÓN ORIGINAL (PROCESAR XML, FIRMA SRI, ETC.) ---
$ruc_emisor   = "1793083115001";
$ambiente     = "1";
$tipoComprob  = "01";
$establec     = "001";
$puntoEmision = "001";
$secuencial   = "000000125";

// (Conserva aquí tus inserciones a tablas transaccionales de facturas...)

echo json_encode(["status" => "success", "mensaje" => "Factura procesada con éxito."]);
$pdo = null; // Cierre definitivo
?>
