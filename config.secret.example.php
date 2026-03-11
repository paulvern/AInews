<?php
/**
 * File configurazione segreti - TEMPLATE
 * 
 * ISTRUZIONI DI SETUP:
 * ====================
 * 1. Copia questo file come "config.secret.php"
 * 2. Inserisci i tuoi valori reali
 * 3. NON committare MAI config.secret.php!
 * 
 * GENERA HASH PASSWORD:
 * =====================
 * php -r "echo password_hash('tua_password', PASSWORD_DEFAULT) . PHP_EOL;"
 */

return [
    // ==========================================
    // MISTRAL AI - Ottieni la chiave da:
    // https://console.mistral.ai/api-keys/
    // ==========================================
    'mistral_api_key' => 'YOUR_MISTRAL_API_KEY_HERE',
    
    // ==========================================
    // ADMIN PANEL - Scegli UNA delle due opzioni:
    // ==========================================
    
    // OPZIONE 1: Password in chiaro (più semplice, meno sicuro)
    'admin_password' => 'YOUR_SECURE_PASSWORD_HERE',
    
    // OPZIONE 2: Password hashata (più sicuro - commenta quella sopra se usi questa)
    // 'admin_password_hash' => '$2y$10$...',
];
