<?php
require_once __DIR__ . '/../vendor/autoload.php';

$client = new Google_Client();

// 1) Soporta credenciales como campos individuales en variables de entorno
$envClientId = getenv('GOOGLE_CLIENT_ID');
if ($envClientId) {
    $envClientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
    $envProjectId = getenv('GOOGLE_PROJECT_ID') ?: '';
    $envAuthUri = getenv('GOOGLE_AUTH_URI') ?: 'https://accounts.google.com/o/oauth2/auth';
    $envTokenUri = getenv('GOOGLE_TOKEN_URI') ?: 'https://oauth2.googleapis.com/token';
    $envRedirects = getenv('GOOGLE_REDIRECT_URIS') ?: 'http://localhost:8000/oauth2callback.php';
    $envJsOrigins = getenv('GOOGLE_JAVASCRIPT_ORIGINS') ?: 'http://localhost:8000';

    $redirects = array_map('trim', explode(',', $envRedirects));
    $jsOrigins = array_map('trim', explode(',', $envJsOrigins));

    $config = [
        'web' => [
            'client_id' => $envClientId,
            'project_id' => $envProjectId,
            'auth_uri' => $envAuthUri,
            'token_uri' => $envTokenUri,
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_secret' => $envClientSecret,
            'redirect_uris' => $redirects,
            'javascript_origins' => $jsOrigins,
        ]
    ];
    $client->setAuthConfig($config);
} else {
    // 2) Cargar credenciales desde variable de entorno si está disponible.
    // Se acepta JSON crudo en GOOGLE_CREDENTIALS_JSON o base64 en GOOGLE_CREDENTIALS_JSON_B64
    $credsJson = getenv('GOOGLE_CREDENTIALS_JSON');
    $credsJsonB64 = getenv('GOOGLE_CREDENTIALS_JSON_B64');
    if ($credsJsonB64) {
        $decoded = base64_decode($credsJsonB64);
        $credsJson = $decoded ?: $credsJson;
    }

    if ($credsJson) {
        $config = json_decode($credsJson, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $client->setAuthConfig($config);
        } else {
            $client->setAuthConfig(__DIR__ . '/../credentials/client_secret_oauth.json');
        }
    } else {
        $client->setAuthConfig(__DIR__ . '/../credentials/client_secret_oauth.json');
    }
}

$client->setRedirectUri('http://localhost:8000/oauth2callback.php');

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            echo '<h1>❌ Error al obtener token</h1>';
            echo '<p>Error: ' . htmlspecialchars($token['error']) . '</p>';
            exit;
        }
        
    $tokenPath = getenv('GOOGLE_TOKEN_PATH') ?: __DIR__ . '/../credentials/token.json';
    file_put_contents($tokenPath, json_encode($token));
        
        echo '<h1>✅ Autorización exitosa!</h1>';
        echo '<p>Token guardado en credentials/</p>';
        echo '<p>Ahora puedes usar tu API para subir archivos a Google Drive.</p>';
        
    } catch (Exception $e) {
        echo '<h1>❌ Excepción capturada</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
} else {
    echo '<h1>❌ No se recibió código de autorización</h1>';
}