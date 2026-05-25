<?php

$host = getenv('PGHOST');
$port = getenv('PGPORT');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');

$conn = pg_connect("
    host=$host
    port=$port
    dbname=$db
    user=$user
    password=$pass
");

if (!$conn) {
    die("Error de conexión");
}

$sql = "
CREATE TABLE IF NOT EXISTS clientes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100),
    telefono VARCHAR(30),
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

$result = pg_query($conn, $sql);

if ($result) {
    echo "Tabla creada correctamente";
} else {
    echo "Error al crear tabla";
}

?>
