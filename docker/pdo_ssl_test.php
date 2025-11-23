<?php
// Diagnostic script to run inside the container to check PDO/SSL support and try connecting
// Save as docker/pdo_ssl_test.php and the entrypoint will run it if present.

echo "[pdo_test] Starting PDO SSL diagnostic" . PHP_EOL;
echo "[pdo_test] PHP version: " . PHP_VERSION . PHP_EOL;
echo "[pdo_test] OpenSSL extension: " . (extension_loaded('openssl') ? 'loaded' : 'missing') . PHP_EOL;
echo "[pdo_test] PDO available drivers: " . implode(',', PDO::getAvailableDrivers()) . PHP_EOL;
echo "[pdo_test] PDO::MYSQL_ATTR_SSL_CA defined: " . (defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : '(not defined)') . PHP_EOL;

$host = getenv('DB_HOST') ?: 'gateway01.us-east-1.prod.aws.tidbcloud.com';
$port = getenv('DB_PORT') ?: '4000';
$db   = getenv('DB_DATABASE') ?: 'test';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$ca   = getenv('DB_SSL_CA') ?: '/etc/secrets/isrgrootx1.pem';

echo "[pdo_test] DSN host=$host port=$port db=$db" . PHP_EOL;
echo "[pdo_test] DB_SSL_CA (env) = " . ($ca ?: '(empty)') . PHP_EOL;
if (file_exists($ca)) {
    echo "[pdo_test] CA file exists, size=" . filesize($ca) . " bytes" . PHP_EOL;
    $h = fopen($ca, 'r');
    echo "[pdo_test] CA first lines:" . PHP_EOL;
    for ($i=0;$i<5 && !feof($h);$i++) {
        echo fgets($h);
    }
    fclose($h);
} else {
    echo "[pdo_test] CA file does NOT exist at $ca" . PHP_EOL;
}

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

$attempts = [];
if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
    $attempts['pdo_const'] = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_SSL_CA => $ca];
}
$attempts['numeric_1000'] = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 1000 => $ca];
$attempts['no_opts'] = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

foreach ($attempts as $k => $opts) {
    echo PHP_EOL . "[pdo_test] === Attempt: $k ===" . PHP_EOL;
    try {
        $pdo = new PDO($dsn, $user, $pass, $opts);
        echo "[pdo_test] Connected ($k) — server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . PHP_EOL;
        $pdo = null;
    } catch (PDOException $e) {
        echo "[pdo_test] PDOException ($k): " . $e->getMessage() . PHP_EOL;
        echo "[pdo_test] Code: " . $e->getCode() . PHP_EOL;
    } catch (Exception $ex) {
        echo "[pdo_test] Exception ($k): " . $ex->getMessage() . PHP_EOL;
    }
}

echo "[pdo_test] Done." . PHP_EOL;
