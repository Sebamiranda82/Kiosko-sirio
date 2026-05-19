<?php
// Configuración de cabeceras para responder en formato JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 1. Capturar los datos JSON enviados desde el frontend (index.html)
$datosRecibidos = file_get_contents("php://input");
$data = json_decode($datosRecibidos, true);

// Validar que existan datos válidos
if (!$data || !isset($data['productos'])) {
    echo json_encode([
        "status" => "error",
        "mensaje" => "No se recibieron productos válidos para procesar."
    ]);
    exit;
}

$productos = $data['productos'];
$subtotal0 = 0;
$subtotal15 = 0; // Tarifa vigente de IVA en Ecuador (15%)
$detallesXML = "";

// 2. Procesar cada artículo del carrito
foreach ($productos as $item) {
    // Sanitizar entradas básicas
    $codigo = htmlspecialchars($item['codigo']);
    $descripcion = htmlspecialchars($item['descripcion']);
    $cantidad = floatval($item['cantidad']);
    $precioUnitario = floatval($item['precio']);
    
    // Calcular el total de la línea
    $totalLinea = $cantidad * $precioUnitario;
    
    // Clasificar según el tipo de impuesto (Simulación de esquema SRI)
    if (isset($item['llevaIva']) && $item['llevaIva'] === true) {
        $subtotal15 += $totalLinea;
        $codigoPorcentaje = "4"; // Código SRI para IVA 15% (reforma)
        $tarifaIva = 15.00;
        $valorIvaLinea = $totalLinea * 0.15;
    } else {
        $subtotal0 += $totalLinea;
        $codigoPorcentaje = "0"; // Código SRI para IVA 0%
        $tarifaIva = 0.00;
        $valorIvaLinea = 0.00;
    }

    // Estructurar el nodo <detalle> en formato XML String para el comprobante
    $detallesXML .= "    <detalle>\n";
    $detallesXML .= "        <codigoPrincipal>{$codigo}</codigoPrincipal>\n";
    $detallesXML .= "        <descripcion>{$descripcion}</descripcion>\n";
    $detallesXML .= "        <cantidad>" . number_format($cantidad, 2, '.', '') . "</cantidad>\n";
    $detallesXML .= "        <precioUnitario>" . number_format($precioUnitario, 4, '.', '') . "</precioUnitario>\n";
    $detallesXML .= "        <descuento>0.00</descuento>\n";
    $detallesXML .= "        <precioTotalSinImpuesto>" . number_format($totalLinea, 2, '.', '') . "</precioTotalSinImpuesto>\n";
    $detallesXML .= "        <impuestos>\n";
    $detallesXML .= "            <impuesto>\n";
    $detallesXML .= "                <codigo>2</codigo>\n"; // 2 siempre es IVA
    $detallesXML .= "                <codigoPorcentaje>{$codigoPorcentaje}</codigoPorcentaje>\n";
    $detallesXML .= "                <tarifa>" . number_format($tarifaIva, 2, '.', '') . "</tarifa>\n";
    $detallesXML .= "                <baseImponible>" . number_format($totalLinea, 2, '.', '') . "</baseImponible>\n";
    $detallesXML .= "                <valor>" . number_format($valorIvaLinea, 2, '.', '') . "</valor>\n";
    $detallesXML .= "            </impuesto>\n";
    $detallesXML .= "        </impuestos>\n";
    $detallesXML .= "    </detalle>\n";
}

// 3. Totales Finales del Comprobante
$totalIva = $subtotal15 * 0.15;
$importeTotal = $subtotal0 + $subtotal15 + $totalIva;

// 4. Retornar respuesta exitosa al frontend con los cálculos del servidor
echo json_encode([
    "status" => "success",
    "mensaje" => "Cálculos del servidor procesados con éxito.",
    "totales" => [
        "subtotal_0" => number_format($subtotal0, 2, '.', ''),
        "subtotal_15" => number_format($subtotal15, 2, '.', ''),
        "iva_15" => number_format($totalIva, 2, '.', ''),
        "importe_total" => number_format($importeTotal, 2, '.', '')
    ],
    "xml_parcial" => $detallesXML
]);

