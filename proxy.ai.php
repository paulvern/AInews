<?php
// proxy_ai.php - Proxy sicuro per Mistral AI
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Only POST allowed']));
}

// Carica SOLO i segreti (unico punto dove carica l'API key)
$secretsFile = __DIR__ . '/config.secret.php';
if (!file_exists($secretsFile)) {
    http_response_code(500);
    exit(json_encode(['error' => 'Secrets file not found. Check README.md for setup instructions.']));
}

$secrets = require $secretsFile;
$apiKey = $secrets['mistral_api_key'] ?? '';

if (!$apiKey || $apiKey === 'YOUR_MISTRAL_API_KEY_HERE') {
    http_response_code(500);
    exit(json_encode(['error' => 'Mistral API key not configured']));
}

// Carica config pubblica per il modello
$configFile = __DIR__ . '/config.json';
$config = file_exists($configFile) 
    ? json_decode(file_get_contents($configFile), true) 
    : [];
$model = $config['mistral_model'] ?? 'mistral-small-latest';

// Rate limiting (1 req / 3 secondi per IP)
$rateLimitSeconds = 3;
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
$ip = explode(',', $ip)[0];
$rateLimitFile = sys_get_temp_dir() . "/mistral_rate_" . md5($ip) . ".txt";
$now = time();

if (file_exists($rateLimitFile)) {
    $lastRequest = (int)file_get_contents($rateLimitFile);
    if ($now - $lastRequest < $rateLimitSeconds) {
        http_response_code(429);
        exit(json_encode([
            'error' => 'Rate limit exceeded',
            'retry_after' => $rateLimitSeconds - ($now - $lastRequest)
        ]));
    }
}
file_put_contents($rateLimitFile, $now);

// Leggi input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['messages']) || !is_array($input['messages'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid request: messages array required']));
}

// Valida struttura messaggi
foreach ($input['messages'] as $idx => $msg) {
    if (!isset($msg['role']) || !isset($msg['content'])) {
        http_response_code(400);
        exit(json_encode(['error' => "Invalid message at index $idx"]));
    }
    if (!in_array($msg['role'], ['system', 'user', 'assistant'])) {
        http_response_code(400);
        exit(json_encode(['error' => "Invalid role at index $idx"]));
    }
}

// Limita lunghezza totale
$maxLength = 10000;
$totalLength = 0;
foreach ($input['messages'] as $msg) {
    $totalLength += strlen($msg['content'] ?? '');
}

if ($totalLength > $maxLength) {
    http_response_code(400);
    exit(json_encode([
        'error' => 'Message too long',
        'max_chars' => $maxLength,
        'received' => $totalLength
    ]));
}

// Prepara payload per Mistral
$payload = [
    'model' => $model,
    'messages' => $input['messages'],
    'temperature' => max(0, min(1, floatval($input['temperature'] ?? 0.7))),
    'max_tokens' => min(intval($input['max_tokens'] ?? 1500), 2000)
];

// Chiama Mistral API
$ch = curl_init('https://api.mistral.ai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(500);
    exit(json_encode(['error' => 'Connection error', 'details' => $error]));
}

http_response_code($httpCode);
echo $response;
