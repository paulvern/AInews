<?php
session_start();

define('CONFIG_FILE', __DIR__ . '/config.json');
define('SECRETS_FILE', __DIR__ . '/config.secret.php');

// Carica segreti
if (!file_exists(SECRETS_FILE)) {
    die('❌ ERRORE: File config.secret.php non trovato!<br><br>
         Leggi il README.md per le istruzioni di setup.');
}

$secrets = require SECRETS_FILE;

// Funzione verifica password (supporta sia plain che hash)
function verifyPassword($input, $secrets) {
    if (!empty($secrets['admin_password_hash'])) {
        return password_verify($input, $secrets['admin_password_hash']);
    }
    if (!empty($secrets['admin_password'])) {
        return $input === $secrets['admin_password'];
    }
    return false;
}

// Login
if (!isset($_SESSION['admin_logged'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (verifyPassword($_POST['password'], $secrets)) {
            $_SESSION['admin_logged'] = true;
        } else {
            $loginError = 'Password errata';
        }
    }
    
    if (!isset($_SESSION['admin_logged'])) {
        showLoginForm($loginError ?? null);
        exit;
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Funzioni config
function getConfig() {
    if (!file_exists(CONFIG_FILE)) {
        return [
            'mistral_model' => 'mistral-small-latest',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0',
            'fetch_timeout' => 15000,
            'cors_proxies' => [],
            'sources' => []
        ];
    }
    return json_decode(file_get_contents(CONFIG_FILE), true);
}

function saveConfig($config) {
    file_put_contents(CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

$config = getConfig();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Salva configurazione generale
    if (isset($_POST['save_general'])) {
        $config['mistral_model'] = trim($_POST['mistral_model']);
        $config['user_agent'] = trim($_POST['user_agent']);
        $config['fetch_timeout'] = intval($_POST['fetch_timeout']);
        saveConfig($config);
        $message = 'Configurazione generale salvata!';
        $messageType = 'success';
    }
    
    // Aggiungi proxy
    if (isset($_POST['add_proxy'])) {
        $config['cors_proxies'][] = [
            'name' => trim($_POST['proxy_name']),
            'url' => trim($_POST['proxy_url']),
            'enabled' => true
        ];
        saveConfig($config);
        $message = 'Proxy aggiunto!';
        $messageType = 'success';
    }
    
    // Modifica proxy
    if (isset($_POST['edit_proxy'])) {
        $idx = intval($_POST['proxy_index']);
        if (isset($config['cors_proxies'][$idx])) {
            $config['cors_proxies'][$idx] = [
                'name' => trim($_POST['proxy_name']),
                'url' => trim($_POST['proxy_url']),
                'enabled' => isset($_POST['proxy_enabled'])
            ];
            saveConfig($config);
            $message = 'Proxy aggiornato!';
            $messageType = 'success';
        }
    }
    
    // Elimina proxy
    if (isset($_POST['delete_proxy'])) {
        $idx = intval($_POST['proxy_index']);
        if (isset($config['cors_proxies'][$idx])) {
            array_splice($config['cors_proxies'], $idx, 1);
            saveConfig($config);
            $message = 'Proxy eliminato!';
            $messageType = 'success';
        }
    }
    
    // Toggle proxy
    if (isset($_POST['toggle_proxy'])) {
        $idx = intval($_POST['proxy_index']);
        if (isset($config['cors_proxies'][$idx])) {
            $config['cors_proxies'][$idx]['enabled'] = !$config['cors_proxies'][$idx]['enabled'];
            saveConfig($config);
        }
    }
    
    // Aggiungi fonte
    if (isset($_POST['add_source'])) {
        $key = preg_replace('/[^a-z0-9]/', '', strtolower($_POST['source_key']));
        if ($key && !isset($config['sources'][$key])) {
            $config['sources'][$key] = [
                'name' => trim($_POST['source_name']),
                'url' => trim($_POST['source_url']),
                'color' => $_POST['source_color'],
                'region' => $_POST['source_region'],
                'flag' => trim($_POST['source_flag']),
                'enabled' => true
            ];
            saveConfig($config);
            $message = 'Fonte aggiunta!';
            $messageType = 'success';
        } else {
            $message = 'Chiave non valida o già esistente';
            $messageType = 'error';
        }
    }
    
    // Modifica fonte
    if (isset($_POST['edit_source'])) {
        $key = $_POST['source_key'];
        if (isset($config['sources'][$key])) {
            $config['sources'][$key] = [
                'name' => trim($_POST['source_name']),
                'url' => trim($_POST['source_url']),
                'color' => $_POST['source_color'],
                'region' => $_POST['source_region'],
                'flag' => trim($_POST['source_flag']),
                'enabled' => isset($_POST['source_enabled'])
            ];
            saveConfig($config);
            $message = 'Fonte aggiornata!';
            $messageType = 'success';
        }
    }
    
    // Elimina fonte
    if (isset($_POST['delete_source'])) {
        $key = $_POST['source_key'];
        if (isset($config['sources'][$key])) {
            unset($config['sources'][$key]);
            saveConfig($config);
            $message = 'Fonte eliminata!';
            $messageType = 'success';
        }
    }
    
    // Toggle fonte
    if (isset($_POST['toggle_source'])) {
        $key = $_POST['source_key'];
        if (isset($config['sources'][$key])) {
            $config['sources'][$key]['enabled'] = !$config['sources'][$key]['enabled'];
            saveConfig($config);
        }
    }
    
    // Test feed
    if (isset($_POST['test_feed'])) {
        $testUrl = $_POST['test_url'];
        $testResult = testFeed($testUrl, $config['user_agent']);
        $message = $testResult['message'];
        $messageType = $testResult['success'] ? 'success' : 'error';
    }
    
    $config = getConfig();
}

function testFeed($url, $userAgent) {
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: $userAgent\r\n",
            'timeout' => 10
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false) {
        return ['success' => false, 'message' => "❌ Impossibile connettersi a: $url"];
    }
    
    $xml = @simplexml_load_string($content);
    if ($xml === false) {
        return ['success' => false, 'message' => "❌ XML non valido da: $url"];
    }
    
    $items = $xml->channel->item ?? $xml->entry ?? [];
    $count = count($items);
    
    return ['success' => true, 'message' => "✅ Feed OK: $count articoli trovati"];
}

function showLoginForm($error = null) {
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        h1 { text-align: center; margin-bottom: 30px; color: #333; }
        input[type="password"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            margin-bottom: 20px;
        }
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
        }
        .error { color: #e74c3c; text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔐 Admin Login</h1>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Password" required autofocus>
            <button type="submit">Accedi</button>
        </form>
    </div>
</body>
</html>
<?php
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - News Aggregator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #22c55e;
            --error: #ef4444;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #1e293b;
            --text-light: #64748b;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), #764ba2);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header h1 { font-size: 1.5em; display: flex; align-items: center; gap: 10px; }
        
        .header-actions { display: flex; gap: 15px; }
        
        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            font-size: 14px;
        }
        
        .header-actions a:hover { background: rgba(255,255,255,0.3); }
        
        .tabs {
            display: flex;
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 0 30px;
        }
        
        .tab {
            padding: 15px 25px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-light);
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        
        .tab:hover { color: var(--primary); }
        .tab.active { color: var(--primary); border-bottom-color: var(--primary); }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .card {
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .card h2 {
            font-size: 1.2em;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-weight: 600;
            font-size: 13px;
            color: var(--text-light);
        }
        
        .form-group input, .form-group select, .form-group textarea {
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-group textarea { resize: vertical; min-height: 100px; }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--error); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-sm { padding: 8px 15px; font-size: 12px; }
        
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success { background: #dcfce7; color: #166534; }
        .message.error { background: #fee2e2; color: #991b1b; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        th {
            background: var(--bg);
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-light);
        }
        
        tr:hover { background: #f8fafc; }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-error { background: #fee2e2; color: #991b1b; }
        
        .actions { display: flex; gap: 8px; }
        
        .source-color {
            width: 20px;
            height: 20px;
            border-radius: 5px;
            border: 2px solid rgba(0,0,0,0.1);
        }
        
        .url-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 12px;
            color: var(--text-light);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show { display: flex; }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary), #764ba2);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-card .number { font-size: 2em; font-weight: 700; }
        .stat-card .label { font-size: 12px; opacity: 0.9; }
        
        .code-preview {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .note {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 8px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>⚙️ News Aggregator Admin</h1>
        <div class="header-actions">
            <a href="index.html" target="_blank">📰 Vai al sito</a>
            <a href="?logout=1">🚪 Logout</a>
        </div>
    </header>
    
    <div class="tabs">
        <button class="tab active" onclick="showTab('general')">⚙️ Generale</button>
        <button class="tab" onclick="showTab('proxies')">🌐 Proxy CORS</button>
        <button class="tab" onclick="showTab('sources')">📰 Fonti RSS</button>
        <button class="tab" onclick="showTab('test')">🧪 Test</button>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <!-- TAB GENERALE -->
        <div class="tab-content active" id="tab-general">
            <div class="stats">
                <div class="stat-card">
                    <div class="number"><?= count($config['sources'] ?? []) ?></div>
                    <div class="label">Fonti Totali</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= count(array_filter($config['sources'] ?? [], fn($s) => $s['enabled'] ?? false)) ?></div>
                    <div class="label">Fonti Attive</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= count($config['cors_proxies'] ?? []) ?></div>
                    <div class="label">Proxy CORS</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= count(array_filter($config['cors_proxies'] ?? [], fn($p) => $p['enabled'] ?? false)) ?></div>
                    <div class="label">Proxy Attivi</div>
                </div>
            </div>
            
            <div class="card">
                <h2>🤖 Configurazione Mistral AI</h2>
                <div class="note">
                    ℹ️ <strong>Nota:</strong> La chiave API Mistral è configurata in <code>config.secret.php</code> per motivi di sicurezza.
                </div>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Modello Mistral</label>
                            <select name="mistral_model">
                                <option value="mistral-small-latest" <?= ($config['mistral_model'] ?? '') === 'mistral-small-latest' ? 'selected' : '' ?>>mistral-small-latest</option>
                                <option value="mistral-medium-latest" <?= ($config['mistral_model'] ?? '') === 'mistral-medium-latest' ? 'selected' : '' ?>>mistral-medium-latest</option>
                                <option value="mistral-large-latest" <?= ($config['mistral_model'] ?? '') === 'mistral-large-latest' ? 'selected' : '' ?>>mistral-large-latest</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Timeout Fetch (ms)</label>
                            <input type="number" name="fetch_timeout" value="<?= $config['fetch_timeout'] ?? 15000 ?>" min="5000" max="60000">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>User Agent (per richieste HTTP)</label>
                            <input type="text" name="user_agent" value="<?= htmlspecialchars($config['user_agent'] ?? '') ?>" placeholder="Mozilla/5.0...">
                        </div>
                    </div>
                    <button type="submit" name="save_general" class="btn btn-primary">💾 Salva Configurazione</button>
                </form>
            </div>
        </div>
        
        <!-- TAB PROXY -->
        <div class="tab-content" id="tab-proxies">
            <div class="card">
                <h2>➕ Aggiungi Proxy CORS</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nome Proxy</label>
                            <input type="text" name="proxy_name" placeholder="es: MyCorsProxy" required>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>URL Proxy (deve terminare con ? o /)</label>
                            <input type="text" name="proxy_url" placeholder="https://proxy.example.com/?" required>
                        </div>
                    </div>
                    <button type="submit" name="add_proxy" class="btn btn-success">➕ Aggiungi Proxy</button>
                </form>
            </div>
            
            <div class="card">
                <h2>🌐 Proxy CORS Configurati (<?= count($config['cors_proxies'] ?? []) ?>)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>URL</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($config['cors_proxies'] ?? []) as $idx => $proxy): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($proxy['name']) ?></strong></td>
                            <td class="url-cell" title="<?= htmlspecialchars($proxy['url']) ?>">
                                <?= htmlspecialchars($proxy['url']) ?>
                            </td>
                            <td>
                                <span class="badge <?= ($proxy['enabled'] ?? false) ? 'badge-success' : 'badge-error' ?>">
                                    <?= ($proxy['enabled'] ?? false) ? '✓ Attivo' : '✗ Disattivo' ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-primary btn-sm" onclick="openProxyModal(<?= $idx ?>, <?= htmlspecialchars(json_encode($proxy)) ?>)">✏️</button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="proxy_index" value="<?= $idx ?>">
                                        <button type="submit" name="toggle_proxy" class="btn btn-sm" style="background: <?= ($proxy['enabled'] ?? false) ? 'var(--warning)' : 'var(--success)' ?>; color: white;">
                                            <?= ($proxy['enabled'] ?? false) ? '⏸️' : '▶️' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Eliminare questo proxy?')">
                                        <input type="hidden" name="proxy_index" value="<?= $idx ?>">
                                        <button type="submit" name="delete_proxy" class="btn btn-danger btn-sm">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- TAB FONTI -->
        <div class="tab-content" id="tab-sources">
            <div class="card">
                <h2>➕ Aggiungi Nuova Fonte</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Chiave (senza spazi)</label>
                            <input type="text" name="source_key" placeholder="es: bbc" required pattern="[a-zA-Z0-9]+">
                        </div>
                        <div class="form-group">
                            <label>Nome</label>
                            <input type="text" name="source_name" placeholder="es: BBC News" required>
                        </div>
                        <div class="form-group">
                            <label>Flag/Emoji</label>
                            <input type="text" name="source_flag" placeholder="es: 🇬🇧" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>URL RSS Feed</label>
                            <input type="url" name="source_url" placeholder="https://..." required>
                        </div>
                        <div class="form-group">
                            <label>Colore</label>
                            <input type="color" name="source_color" value="#6366f1">
                        </div>
                        <div class="form-group">
                            <label>Regione</label>
                            <select name="source_region" required>
                                <option value="europe">🇪🇺 Europa</option>
                                <option value="americas">🌎 Americhe</option>
                                <option value="asia">🌏 Asia</option>
                                <option value="africa">🌍 Africa</option>
                                <option value="oceania">🦘 Oceania</option>
                                <option value="middleeast">🕌 Medio Oriente</option>
                                <option value="global">🌐 Globale</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_source" class="btn btn-success">➕ Aggiungi Fonte</button>
                </form>
            </div>
            
            <div class="card">
                <h2>📰 Fonti RSS (<?= count($config['sources'] ?? []) ?>)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Fonte</th>
                            <th>URL RSS</th>
                            <th>Regione</th>
                            <th>Colore</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($config['sources'] ?? []) as $key => $source): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 18px;"><?= $source['flag'] ?? '📰' ?></span>
                                    <strong><?= htmlspecialchars($source['name']) ?></strong>
                                </div>
                            </td>
                            <td class="url-cell" title="<?= htmlspecialchars($source['url']) ?>">
                                <?= htmlspecialchars($source['url']) ?>
                            </td>
                            <td><?= ucfirst($source['region'] ?? '') ?></td>
                            <td>
                                <div class="source-color" style="background: <?= $source['color'] ?? '#ccc' ?>"></div>
                            </td>
                            <td>
                                <span class="badge <?= ($source['enabled'] ?? false) ? 'badge-success' : 'badge-error' ?>">
                                    <?= ($source['enabled'] ?? false) ? '✓ Attiva' : '✗ Disattiva' ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-primary btn-sm" onclick="openSourceModal('<?= $key ?>', <?= htmlspecialchars(json_encode($source)) ?>)">✏️</button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="source_key" value="<?= $key ?>">
                                        <button type="submit" name="toggle_source" class="btn btn-sm" style="background: <?= ($source['enabled'] ?? false) ? 'var(--warning)' : 'var(--success)' ?>; color: white;">
                                            <?= ($source['enabled'] ?? false) ? '⏸️' : '▶️' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Eliminare questa fonte?')">
                                        <input type="hidden" name="source_key" value="<?= $key ?>">
                                        <button type="submit" name="delete_source" class="btn btn-danger btn-sm">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- TAB TEST -->
        <div class="tab-content" id="tab-test">
            <div class="card">
                <h2>🧪 Test Feed RSS</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 3;">
                            <label>URL Feed da testare</label>
                            <input type="url" name="test_url" placeholder="https://www.example.com/rss.xml" required>
                        </div>
                    </div>
                    <button type="submit" name="test_feed" class="btn btn-primary">🧪 Testa Feed</button>
                </form>
            </div>
            
            <div class="card">
                <h2>📋 Configurazione Attuale (config.json)</h2>
                <div class="note">
                    ℹ️ L'API key Mistral NON è visualizzata qui per sicurezza (si trova in config.secret.php)
                </div>
                <div class="code-preview"><?= htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></div>
            </div>
        </div>
    </div>
    
    <!-- MODAL PROXY -->
    <div class="modal" id="proxyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Modifica Proxy</h2>
                <button class="modal-close" onclick="closeProxyModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="proxy_index" id="edit_proxy_index">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Nome</label>
                    <input type="text" name="proxy_name" id="edit_proxy_name" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>URL</label>
                    <input type="text" name="proxy_url" id="edit_proxy_url" required>
                </div>
                <div class="form-group" style="margin: 20px 0;">
                    <label>
                        <input type="checkbox" name="proxy_enabled" id="edit_proxy_enabled"> Proxy attivo
                    </label>
                </div>
                <button type="submit" name="edit_proxy" class="btn btn-primary">💾 Salva</button>
            </form>
        </div>
    </div>
    
    <!-- MODAL SOURCE -->
    <div class="modal" id="sourceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Modifica Fonte</h2>
                <button class="modal-close" onclick="closeSourceModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="source_key" id="edit_source_key">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Nome</label>
                    <input type="text" name="source_name" id="edit_source_name" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>URL RSS</label>
                    <input type="url" name="source_url" id="edit_source_url" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Flag</label>
                        <input type="text" name="source_flag" id="edit_source_flag" required>
                    </div>
                    <div class="form-group">
                        <label>Colore</label>
                        <input type="color" name="source_color" id="edit_source_color">
                    </div>
                    <div class="form-group">
                        <label>Regione</label>
                        <select name="source_region" id="edit_source_region" required>
                            <option value="europe">Europa</option>
                            <option value="americas">Americhe</option>
                            <option value="asia">Asia</option>
                            <option value="africa">Africa</option>
                            <option value="oceania">Oceania</option>
                            <option value="middleeast">Medio Oriente</option>
                            <option value="global">Globale</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin: 20px 0;">
                    <label>
                        <input type="checkbox" name="source_enabled" id="edit_source_enabled"> Fonte attiva
                    </label>
                </div>
                <button type="submit" name="edit_source" class="btn btn-primary">💾 Salva</button>
            </form>
        </div>
    </div>
    
    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            document.querySelector(`[onclick="showTab('${tabId}')"]`).classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        }
        
        function openProxyModal(idx, proxy) {
            document.getElementById('edit_proxy_index').value = idx;
            document.getElementById('edit_proxy_name').value = proxy.name;
            document.getElementById('edit_proxy_url').value = proxy.url;
            document.getElementById('edit_proxy_enabled').checked = proxy.enabled;
            document.getElementById('proxyModal').classList.add('show');
        }
        
        function closeProxyModal() {
            document.getElementById('proxyModal').classList.remove('show');
        }
        
        function openSourceModal(key, source) {
            document.getElementById('edit_source_key').value = key;
            document.getElementById('edit_source_name').value = source.name;
            document.getElementById('edit_source_url').value = source.url;
            document.getElementById('edit_source_flag').value = source.flag;
            document.getElementById('edit_source_color').value = source.color;
            document.getElementById('edit_source_region').value = source.region;
            document.getElementById('edit_source_enabled').checked = source.enabled;
            document.getElementById('sourceModal').classList.add('show');
        }
        
        function closeSourceModal() {
            document.getElementById('sourceModal').classList.remove('show');
        }
        
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', e => {
                if (e.target === modal) {
                    modal.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>
