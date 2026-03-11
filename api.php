<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

define('CONFIG_FILE', __DIR__ . '/config.json');

function getConfig() {
    if (!file_exists(CONFIG_FILE)) {
        return ['error' => 'Config file not found'];
    }
    return json_decode(file_get_contents(CONFIG_FILE), true);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'config':
        $config = getConfig();
        
        // Filtra solo proxy attivi
        $enabledProxies = array_filter($config['cors_proxies'] ?? [], function($p) {
            return $p['enabled'] ?? false;
        });
        
        // NON esporre mai mistral_api_key!
        echo json_encode([
            'sources' => $config['sources'] ?? [],
            'cors_proxies' => array_values($enabledProxies),
            'model' => $config['mistral_model'] ?? 'mistral-small-latest',
            'user_agent' => $config['user_agent'] ?? '',
            'fetch_timeout' => $config['fetch_timeout'] ?? 15000
        ]);
        break;

    case 'fetch_feed':
        $feedUrl = $_GET['url'] ?? '';
        
        if (!$feedUrl) {
            http_response_code(400);
            echo json_encode(['error' => 'URL mancante']);
            break;
        }
        
        // Validazione domini
        $allowedDomains = [
            'bbci.co.uk', 'theguardian.com', 'skynews.com', 'ansa.it', 
            'aftenposten.no', 'difesanews.com', 'cnn.com', 'nytimes.com',
            'aljazeera.com', 'scmp.com', 'japantimes.co.jp', 'straitstimes.com',
            'hindustantimes.com', 'news24.com', 'allafrica.com', 'rassd.com',
            'abc.net.au', 'stuff.co.nz', 'timesofisrael.com', 'jpost.com',
            'lbcgroup.tv', 'palestinechronicle.com', 'pbc.ps', 'gulf-times.com', 
            'crisisgroup.org'
        ];
        
        $host = parse_url($feedUrl, PHP_URL_HOST);
        $isAllowed = false;
        foreach ($allowedDomains as $domain) {
            if (strpos($host, $domain) !== false) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            http_response_code(403);
            echo json_encode(['error' => 'Domain not allowed: ' . $host]);
            break;
        }
        
        $config = getConfig();
        $userAgent = $config['user_agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: $userAgent\r\n" .
                           "Accept: application/rss+xml, application/xml, text/xml, */*\r\n" .
                           "Accept-Language: en-US,en;q=0.9\r\n",
                'timeout' => 20,
                'follow_location' => true,
                'max_redirects' => 5,
                'ignore_errors' => false
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $content = @file_get_contents($feedUrl, false, $context);
        
        if ($content === false) {
            $error = error_get_last();
            http_response_code(500);
            echo json_encode([
                'error' => 'Impossibile caricare il feed',
                'details' => $error['message'] ?? 'Unknown error',
                'url' => $feedUrl
            ]);
        } else {
            if (strpos($content, '<?xml') === false && strpos($content, '<rss') === false && strpos($content, '<feed') === false) {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Risposta non XML',
                    'preview' => substr($content, 0, 200)
                ]);
            } else {
                header('Content-Type: application/xml; charset=utf-8');
                header('X-Proxy: PHP-Server');
                header('X-Content-Length: ' . strlen($content));
                echo $content;
            }
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
