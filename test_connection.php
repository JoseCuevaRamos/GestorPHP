<?php

// Configuración de la base de datos
$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbname = getenv('DB_NAME') ?: 'test';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$ssl_ca_b64 = getenv('DB_SSL_CA_B64');

if (!$ssl_ca_b64) {
    die("Error: La variable de entorno DB_SSL_CA_B64 no está configurada." . PHP_EOL);
}

// Decodificar el certificado SSL desde base64
$ssl_ca = '/tmp/ca-cert.pem';
file_put_contents($ssl_ca, base64_decode($ssl_ca_b64));

try {
    // Opciones de conexión PDO
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
    ];

    // Crear la conexión PDO
    $dsn = "mysql:host=$host;port=4000;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, $options);

    echo "Conexión exitosa a la base de datos." . PHP_EOL;

    // Realizar una consulta de prueba (consulta simple y lectura directa)
    $stmt = $pdo->query("SELECT NOW()");
    $current = $stmt->fetchColumn();
    echo "Hora actual en la base de datos: " . $current . PHP_EOL;

} catch (PDOException $e) {
    echo "Error al conectar a la base de datos: " . $e->getMessage() . PHP_EOL;
}