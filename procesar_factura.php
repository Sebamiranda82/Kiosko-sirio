
<?php
<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Manejo de peticiones preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 1. Configuración de conexión a la Base de Datos (Usa las credenciales de tu db-tienda-eglobal)
$host     = "btulkdyvcpxf93ovtmvh-mysql.services.clever-cloud.com";
$dbname   = "btulkdyvcpxf93ovtmvh";
$username = "uhfykaay2nzfj7w2";
$password = "Mete_Aqui_Tu_Password_De_La_Captura"; // <-- REEMPLAZA CON LA CONTRASEÑA REAL DE TU BASE DE DATOS

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Error de conexión a la BD: " . $e->getMessage()]);
    exit;
}

// 2. Leer datos del cuerpo de la petición (JSON)
$data = json_decode(file_get_contents("php://input"), true);
$action = isset($_GET['action']) ? $_GET['action'] : '';

// =========================================================================
// ACCIÓN: ELIMINAR PRODUCTO
// =========================================================================
if ($action === 'eliminar_producto') {
    if (!$data || !isset($data['codigo'])) {
        echo json_encode(["success" => false, "error" => "Falta el código del producto."]);
        exit;
    }

    $codigo = trim($data['codigo']);

    try {
        // Ejecutamos la baja en la tabla "productos"
        $stmt = $pdo->prepare("DELETE FROM productos WHERE codigo = ?");
        $stmt->execute([$codigo]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Producto eliminado de la base de datos correctamente."]);
        } else {
            echo json_encode(["success" => false, "error" => "El producto no se encontró en la base de datos o ya fue eliminado."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "No se pudo eliminar: " . $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// ACCIÓN: ELIMINAR CLIENTE
// =========================================================================
if ($action === 'eliminar_cliente') {
    if (!$data || !isset($data['id_cliente'])) {
        echo json_encode(["success" => false, "error" => "Falta la identificación del cliente."]);
        exit;
    }

    $idCliente = trim($data['id_cliente']);

    try {
        // Ejecutamos la baja en la tabla de clientes (ajusta el nombre de la tabla/columna si varía)
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = ?");
        $stmt->execute([$idCliente]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Cliente eliminado de la base de datos correctamente."]);
        } else {
            echo json_encode(["success" => false, "error" => "El cliente no se encontró en la base de datos."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "No se pudo eliminar el cliente: " . $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// PROCESAR FACTURACIÓN (Tu lógica existente se ejecuta si no es ninguna de las anteriores)
// =========================================================================
if (!$data || !isset($data['productos'])) {
    echo json_encode(["status" => "error", "mensaje" => "No hay productos recibidos."]);
    exit;
}

// ... (Aquí continúa el resto de tu código original para armar el XML del SRI e insertar la factura) ...

// 1. Leer datos del carrito enviados desde Panel.html
$data = json_decode(file_get_contents("php://input"), true);

// 2. Datos duros del emisor para la estructura del SRI
$ruc_emisor   = "1793083115001"; // Cambia por tu RUC real de pruebas/producción
$ambiente     = "1";             // 1 = Pruebas, 2 = Producción
$tipoComprob  = "01";            // 01 = Factura
$establec     = "001";
$puntoEmision = "001";
$secuencial   = "000000125";     // Secuencial del documento actual
$codigoNum    = "12345678";      // Código aleatorio de 8 dígitos para seguridad
$fechaEmision = date("dmy");     // Formato ddmmaaaa requerido por la clave

// 3. Generar Clave de Acceso (Algoritmo Módulo 11)
$claveSinDigito = $fechaEmision . $tipoComprob . $ruc_emisor . $ambiente . $establec . $puntoEmision . $secuencial . $codigoNum . "1";
$digitoVerificador = calcularModulo11($claveSinDigito);
$claveAcceso = $claveSinDigito . $digitoVerificador;

// 4. Procesar el desglose de productos y sus impuestos
$productos = $data['productos'];
$subtotal0 = 0;
$subtotal15 = 0;
$detallesXML = "";

foreach ($productos as $item) {
    $codigo = htmlspecialchars($item['codigo']);
    $descripcion = htmlspecialchars($item['descripcion']);
    $cantidad = floatval($item['cantidad']);
    $precioUnitario = floatval($item['precio']);
    $totalLinea = $cantidad * $precioUnitario;
    
    if (isset($item['llevaIva']) && $item['llevaIva'] === true) {
        $subtotal15 += $totalLinea;
        $codPorcentaje = "4"; // IVA 15%
        $tarifaIva = 15.00;
        $valorIvaLinea = $totalLinea * 0.15;
    } else {
        $subtotal0 += $totalLinea;
        $codPorcentaje = "0"; // IVA 0%
        $tarifaIva = 0.00;
        $valorIvaLinea = 0.00;
    }

    $detallesXML .= "        <detalle>\n";
    $detallesXML .= "            <codigoPrincipal>{$codigo}</codigoPrincipal>\n";
    $detallesXML .= "            <descripcion>{$descripcion}</descripcion>\n";
    $detallesXML .= "            <cantidad>" . number_format($cantidad, 2, '.', '') . "</cantidad>\n";
    $detallesXML .= "            <precioUnitario>" . number_format($precioUnitario, 4, '.', '') . "</precioUnitario>\n";
    $detallesXML .= "            <descuento>0.00</descuento>\n";
    $detallesXML .= "            <precioTotalSinImpuesto>" . number_format($totalLinea, 2, '.', '') . "</precioTotalSinImpuesto>\n";
    $detallesXML .= "            <impuestos>\n";
    $detallesXML .= "                <impuesto>\n";
    $detallesXML .= "                    <codigo>2</codigo>\n";
    $detallesXML .= "                    <codigoPorcentaje>{$codPorcentaje}</codigoPorcentaje>\n";
    $detallesXML .= "                    <tarifa>" . number_format($tarifaIva, 2, '.', '') . "</tarifa>\n";
    $detallesXML .= "                    <baseImponible>" . number_format($totalLinea, 2, '.', '') . "</baseImponible>\n";
    $detallesXML .= "                    <valor>" . number_format($valorIvaLinea, 2, '.', '') . "</valor>\n";
    $detallesXML .= "                </impuesto>\n";
    $detallesXML .= "            </impuestos>\n";
    $detallesXML .= "        </detalle>\n";
}

$totalIva = $subtotal15 * 0.15;
$importeTotal = $subtotal0 + $subtotal15 + $totalIva;
$fechaFormatoSRI = date("d/m/Y");

// 5. Armar la estructura XML completa (Estándar v1.1.0 Offline)
$xmlCompleto = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$xmlCompleto .= "<factura id=\"comprobante\" version=\"1.1.0\">\n";
$xmlCompleto .= "    <infoTributaria>\n";
$xmlCompleto .= "        <ambiente>{$ambiente}</ambiente>\n";
$xmlCompleto .= "        <tipoEmision>1</tipoEmision>\n";
$xmlCompleto .= "        <razonSocial>ANUAR SISTEMAS</razonSocial>\n";
$xmlCompleto .= "        <nombreComercial>ANUAR SISTEMAS</nombreComercial>\n";
$xmlCompleto .= "        <ruc>{$ruc_emisor}</ruc>\n";
$xmlCompleto .= "        <claveAcceso>{$claveAcceso}</claveAcceso>\n";
$xmlCompleto .= "        <codDoc>{$tipoComprob}</codDoc>\n";
$xmlCompleto .= "        <estab>{$establec}</estab>\n";
$xmlCompleto .= "        <ptoEmi>{$puntoEmision}</ptoEmi>\n";
$xmlCompleto .= "        <secuencial>{$secuencial}</secuencial>\n";
$xmlCompleto .= "        <dirMatriz>Av. Principal Ecuador</dirMatriz>\n";
$xmlCompleto .= "    </infoTributaria>\n";
$xmlCompleto .= "    <infoFactura>\n";
$xmlCompleto .= "        <fechaEmision>{$fechaFormatoSRI}</fechaEmision>\n";
$xmlCompleto .= "        <dirEstablecimiento>Av. Principal Ecuador</dirEstablecimiento>\n";
$xmlCompleto .= "        <obligadoContabilidad>NO</obligadoContabilidad>\n";
$xmlCompleto .= "        <tipoIdentificacionComprador>07</tipoIdentificacionComprador>\n"; // Consumidor final
$xmlCompleto .= "        <razonSocialComprador>CONSUMIDOR FINAL</razonSocialComprador>\n";
$xmlCompleto .= "        <identificacionComprador>9999999999999</identificacionComprador>\n";
$xmlCompleto .= "        <totalSinImpuestos>" . number_format(($subtotal0 + $subtotal15), 2, '.', '') . "</totalSinImpuestos>\n";
$xmlCompleto .= "        <totalDescuento>0.00</totalDescuento>\n";
$xmlCompleto .= "        <totalConImpuestos>\n";
if ($subtotal15 > 0) {
    $xmlCompleto .= "            <totalImpuesto>\n";
    $xmlCompleto .= "                <codigo>2</codigo>\n";
    $xmlCompleto .= "                <codigoPorcentaje>4</codigoPorcentaje>\n";
    $xmlCompleto .= "                <baseImponible>" . number_format($subtotal15, 2, '.', '') . "</baseImponible>\n";
    $xmlCompleto .= "                <valor>" . number_format($totalIva, 2, '.', '') . "</valor>\n";
    $xmlCompleto .= "            </totalImpuesto>\n";
}
$xmlCompleto .= "        </totalConImpuestos>\n";
$xmlCompleto .= "        <propina>0.00</propina>\n";
$xmlCompleto .= "        <importeTotal>" . number_format($importeTotal, 2, '.', '') . "</importeTotal>\n";
$xmlCompleto .= "        <moneda>DOLAR</moneda>\n";
$xmlCompleto .= "    </infoFactura>\n";
$xmlCompleto .= "    <detalles>\n" . $detallesXML . "    </detalles>\n";
$xmlCompleto .= "</factura>";

// 6. Intentar guardar el archivo XML en el directorio del servidor
$nombreArchivo = "comprobantes/FACT_" . $claveAcceso . ".xml";
@file_put_contents($nombreArchivo, $xmlCompleto);

// 7. Enviar respuesta final
echo json_encode([
    "status" => "success",
    "mensaje" => "XML generado y guardado localmente de forma exitosa.",
    "claveAcceso" => $claveAcceso,
    "archivo_guardado" => $nombreArchivo,
    "xml_generado" => $xmlCompleto
]);

// Función matemática auxiliar para el Dígito Verificador Módulo 11 del SRI
function calcularModulo11($cadena) {
    $pivote = 2;
    $suma = 0;
    for ($i = strlen($cadena) - 1; $i >= 0; $i--) {
        $suma += intval($cadena[$i]) * $pivote;
        $pivote++;
        if ($pivote > 7) {
            $pivote = 2;
        }
    }
    $resto = $suma % 11;
    $digito = 11 - $resto;
    if ($digito == 11) $digito = 0;
    if ($digito == 10) $digito = 1;
    return $digito;
}
// =========================================================================
// AGREGAR ESTO AL FINAL DE TU ARCHIVO 'procesar_factura.php'
// =========================================================================

// Detectamos si el frontend está solicitando una acción específica mediante la URL
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    
    // Aquí debes asegurarte de incluir tu conexión a la base de datos ($db o $pdo)
    // Si ya tienes la conexión definida arriba en este archivo, puedes usar esa misma variable.
    
    switch ($action) {
        // ----------------------------------------
        // ACCIÓN: ELIMINAR CLIENTE
        // ----------------------------------------
        case 'eliminar_cliente':
            $data = json_decode(file_get_contents("php://input"), true);
            $id_cliente = $data['id_cliente'] ?? '';

            if (empty($id_cliente)) {
                echo json_encode(["success" => false, "error" => "ID de cliente no proporcionado."]);
                exit;
            }

            // Validar que no tenga facturas vinculadas (Ajusta 'facturas' si tu tabla se llama distinto)
            $stmt = $db->prepare("SELECT COUNT(*) AS total FROM facturas WHERE id_cliente = :id");
            $stmt->execute([':id' => $id_cliente]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado['total'] > 0) {
                echo json_encode([
                    "success" => false, 
                    "error" => "No se puede eliminar el cliente porque tiene " . $resultado['total'] . " factura(s) registrada(s) a su nombre."
                ]);
            } else {
                // Proceder al borrado seguro
                $delete = $db->prepare("DELETE FROM clientes WHERE id = :id");
                $delete->execute([':id' => $id_cliente]);
                echo json_encode(["success" => true, "message" => "Cliente eliminado correctamente."]);
            }
            exit; // Detiene la ejecución para que no intente procesar una factura involuntariamente

        // ----------------------------------------
        // ACCIÓN: ELIMINAR PRODUCTO
        // ----------------------------------------
        case 'eliminar_producto':
            $data = json_decode(file_get_contents("php://input"), true);
            $codigo = $data['codigo'] ?? '';

            if (empty($codigo)) {
                echo json_encode(["success" => false, "error" => "Código de producto no proporcionado."]);
                exit;
            }

            // Validar que el producto no esté en un detalle de venta (Ajusta 'detalle_facturas')
            $stmt = $db->prepare("SELECT COUNT(*) AS total FROM detalle_facturas WHERE codigo_producto = :codigo");
            $stmt->execute([':codigo' => $codigo]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado['total'] > 0) {
                echo json_encode([
                    "success" => false, 
                    "error" => "No se puede eliminar el artículo. Está incluido en " . $resultado['total'] . " comprobante(s) de venta."
                ]);
            } else {
                // Proceder al borrado seguro
                $delete = $db->prepare("DELETE FROM productos WHERE codigo = :codigo");
                $delete->execute([':codigo' => $codigo]);
                echo json_encode(["success" => true, "message" => "Producto eliminado del catálogo."]);
            }
            exit;
    }
}
