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
            // Fallback a archivo si el JSON no es válido
            $client->setAuthConfig(__DIR__ . '/../credentials/client_secret_oauth.json');
        }
    } else {
        // Fallback a archivo en disco
        $client->setAuthConfig(__DIR__ . '/../credentials/client_secret_oauth.json');
    }
}
$client->setScopes([Google_Service_Drive::DRIVE_FILE]);
$client->setRedirectUri('http://localhost:8000/oauth2callback.php');
$client->setAccessType('offline');
$client->setPrompt('consent');

if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    echo '<h1>Autorizar acceso a Google Drive</h1>';
    echo '<a href="' . $authUrl . '">Click aquí para autorizar</a>';
} else {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (isset($token['error'])) {
        echo 'Error: ' . $token['error'];
        exit;
    }
    
    // Guardar token en la ruta configurada por env o en credentials/token.json
    $tokenPath = getenv('GOOGLE_TOKEN_PATH') ?: __DIR__ . '/../credentials/token.json';
    file_put_contents($tokenPath, json_encode($token));
    echo '<h1>✅ Autorización exitosa!</h1>';
    echo '<p>Token guardado</p>';
}