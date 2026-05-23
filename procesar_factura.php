<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Manejo obligatorio de peticiones CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 1. Conexión segura a la base de datos MySQL
$host     = "btulkdyvcpxf93ovtmvh-mysql.services.clever-cloud.com";
$dbname   = "btulkdyvcpxf93ovtmvh";
$username = "uhfykaay2nzfj7w2";
$password = "87bimOAodwmWggZKrZfl"; // <-- Pon tu clave de Clever Cloud aquí

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Error de conexión BD: " . $e->getMessage()]);
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
        
        // Formateamos la respuesta para que el JavaScript la entienda como un objeto estructurado
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
    exit;
}

// =========================================================================
// RUTA 1: ELIMINAR PRODUCTO (action=eliminar_producto)
// =========================================================================
if ($action === 'eliminar_producto') {
    if (!$data || !isset($data['codigo'])) {
        echo json_encode(["success" => false, "error" => "No se recibió el código del producto."]);
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
    exit; // Finaliza aquí para no evaluar la estructura de facturas
}

// =========================================================================
// RUTA 2: ELIMINAR CLIENTE (action=eliminar_cliente)
// =========================================================================
if ($action === 'eliminar_cliente') {
    if (!$data || !isset($data['id_cliente'])) {
        echo json_encode(["success" => false, "error" => "No se recibió la identificación del cliente."]);
        exit;
    }

    $idCliente = trim($data['id_cliente']);
    try {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = ?");
        $stmt->execute([$idCliente]);
        echo json_encode(["success" => true, "message" => "Cliente eliminado correctamente de la base de datos."]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "Error al ejecutar DELETE en MySQL: " . $e->getMessage()]);
    }
    exit; // Finaliza aquí
}

// =========================================================================
// RUTA 3: PROCESO ESTÁNDAR DE FACTURACIÓN (Si no se pasa ninguna acción de borrado)
// =========================================================================
if (!$data || !isset($data['productos'])) {
    echo json_encode(["status" => "error", "mensaje" => "No hay productos recibidos para facturar."]);
    exit;
}

// --- TU LÓGICA DE FACTURACIÓN ORIGINAL (PROCESAR XML, FIRMA SRI, ETC.) ---
// Aquí puedes mantener o pegar el resto de tus variables de entorno del emisor:
$ruc_emisor   = "1793083115001";
$ambiente     = "1";
$tipoComprob  = "01";
$establec     = "001";
$puntoEmision = "001";
$secuencial   = "000000125";

// (Conserva el resto de las inserciones a tus tablas de facturas abajo...)
echo json_encode(["status" => "success", "mensaje" => "Factura procesada con éxito."]);
?>
