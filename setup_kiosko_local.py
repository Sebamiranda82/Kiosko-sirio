#!/usr/bin/env python3
# setup_kiosko_local.py
# Crea la BD kiosko, las tablas y datos de prueba en MariaDB local
# Ejecutar: python3 ~/setup_kiosko_local.py

import subprocess

SQL = """
CREATE DATABASE IF NOT EXISTS kiosko CHARACTER SET utf8 COLLATE utf8_general_ci;
USE kiosko;

CREATE TABLE IF NOT EXISTS productos (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    codigo   VARCHAR(20)    NOT NULL UNIQUE,
    detalle  VARCHAR(255)   NOT NULL,
    precio   DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    iva      TINYINT(1)     NOT NULL DEFAULT 1,
    categoria VARCHAR(100)  DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS clientes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente  VARCHAR(20)   NOT NULL UNIQUE,
    nombre      VARCHAR(150)  NOT NULL,
    direccion   VARCHAR(255)  DEFAULT NULL,
    email       VARCHAR(150)  DEFAULT NULL
);

-- Datos de prueba productos
INSERT IGNORE INTO productos (codigo, detalle, precio, iva, categoria) VALUES
('001', 'COCA COLA 500ml',        150.00, 1, 'Bebidas'),
('002', 'AGUA MINERAL 500ml',      80.00, 1, 'Bebidas'),
('003', 'ALFAJOR TRIPLE CHOCOLATE',200.00, 1, 'Golosinas');

-- Datos de prueba clientes (9999999999999 = Consumidor Final por defecto)
INSERT IGNORE INTO clientes (id_cliente, nombre, direccion, email) VALUES
('9999999999999', 'CONSUMIDOR FINAL',  '-',                      '-'),
('20123456789',   'JUAN PEREZ',        'Av. Siempreviva 742',    'juan@mail.com'),
('27987654321',   'MARIA GARCIA',      'Calle Falsa 123',        'maria@mail.com');
"""

r = subprocess.run(
    ['mariadb', '-u', 'root', '-e', SQL],
    capture_output=True, text=True
)
if r.returncode == 0:
    print('✓ BD kiosko creada con tablas y datos de prueba')
else:
    print('x ERROR:', r.stderr)
    exit(1)

# Verificar
r2 = subprocess.run(
    ['mariadb', '-u', 'root', 'kiosko', '-e',
     'SELECT COUNT(*) as productos FROM productos; SELECT COUNT(*) as clientes FROM clientes;'],
    capture_output=True, text=True
)
print(r2.stdout)
print('✅ Listo — ahora configurá procesar_factura.php para apuntar a localhost')
