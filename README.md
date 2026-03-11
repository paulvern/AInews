```markdown
# 📰 Global News Aggregator con AI

> Aggregatore intelligente di notizie internazionali con riassunti AI powered by Mistral

[![Demo Live](https://img.shields.io/badge/Demo-Live-success?style=for-the-badge)](http://paulvern.free.nf/News/)
[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php)](https://php.net)
[![Mistral AI](https://img.shields.io/badge/Mistral-AI-orange?style=flat)](https://mistral.ai)
[![License](https://img.shields.io/badge/License-MIT-blue?style=flat)](LICENSE)

---

## 🌐 Demo Live

Prova l'app in azione: **[paulvern.free.nf/News](http://paulvern.free.nf/News/)**

---

## ✨ Caratteristiche

- 📡 **20+ fonti RSS internazionali** da 6 continenti
- 🤖 **Riassunti AI** generati con Mistral AI
- 🌍 **Filtraggio geografico** (Europa, Americhe, Asia, Africa, Oceania, Medio Oriente)
- 🎨 **UI moderna** e responsive
- 🔄 **CORS proxy multipli** con sistema di fallback automatico
- ⚙️ **Admin panel** completo per gestione fonti e proxy
- 🔐 **Sicurezza** con API key segregate e rate limiting
- 📱 **Mobile-friendly**

---

## 📸 Screenshot

### Frontend
![News Feed](https://via.placeholder.com/800x400/667eea/ffffff?text=News+Feed+Screenshot)

### Admin Panel
![Admin Panel](https://via.placeholder.com/800x400/764ba2/ffffff?text=Admin+Panel+Screenshot)

---

## 🚀 Installazione Rapida

### Prerequisiti

- PHP 7.4+ (consigliato PHP 8.0+)
- Estensioni PHP: `curl`, `json`, `simplexml`
- Mistral AI API Key ([Registrati qui](https://console.mistral.ai))

### Setup in 3 minuti

```bash
# 1. Clona il repository
git clone https://github.com/tuo-username/news-aggregator.git
cd news-aggregator

# 2. Configura i segreti
cp config.secret.example.php config.secret.php
nano config.secret.php  # Inserisci la tua Mistral API key

# 3. Configura l'app
cp config.json.example config.json

# 4. Avvia il server
php -S localhost:8000
```

Apri il browser: **http://localhost:8000**

---

## ⚙️ Configurazione

### 1️⃣ File Segreti (`config.secret.php`)

Questo file contiene le credenziali sensibili e **NON deve essere committato**.

```php
<?php
return [
    // Ottieni la chiave da: https://console.mistral.ai/api-keys/
    'mistral_api_key' => 'your_mistral_api_key_here',
    
    // Password per accesso admin panel
    'admin_password' => 'YourSecurePassword123!',
    
    // OPPURE usa password hashata (più sicuro):
    // 'admin_password_hash' => password_hash('YourPassword', PASSWORD_DEFAULT),
];
```

**Genera password hashata:**
```bash
php -r "echo password_hash('TuaPassword', PASSWORD_DEFAULT) . PHP_EOL;"
```

### 2️⃣ Configurazione App (`config.json`)

```json
{
    "mistral_model": "mistral-small-latest",
    "user_agent": "Mozilla/5.0...",
    "fetch_timeout": 15000,
    "cors_proxies": [...],
    "sources": {...}
}
```

Puoi modificare:
- **Modello Mistral**: `mistral-small-latest`, `mistral-medium-latest`, `mistral-large-latest`
- **CORS Proxies**: Aggiungi/rimuovi proxy
- **Fonti RSS**: Aggiungi nuove fonti di notizie

---

## 🎛️ Admin Panel

Accedi a: **http://localhost:8000/admin.php**

### Funzionalità Admin

| Sezione | Funzione |
|---------|----------|
| **⚙️ Generale** | Configura modello AI, timeout, user agent |
| **🌐 Proxy CORS** | Gestisci proxy multipli con fallback automatico |
| **📰 Fonti RSS** | Aggiungi/modifica/disabilita fonti di notizie |
| **🧪 Test** | Testa feed RSS e visualizza configurazione |

### Dashboard Statistiche

- Numero totale fonti
- Fonti attive
- Proxy CORS configurati
- Visualizzazione config JSON

---

## 📁 Struttura Progetto

```
news-aggregator/
├── 📄 index.html                   # Frontend principale
├── 🔐 admin.php                    # Admin panel (richiede login)
├── 🔌 api.php                      # API endpoints (config, feed proxy)
├── 🤖 proxy_ai.php                 # Proxy per Mistral AI (nasconde API key)
│
├── ⚙️ config.json                  # Config app (locale, non committare)
├── 📋 config.json.example          # Template config (committa)
├── 🔑 config.secret.php            # Segreti (locale, non committare)
├── 📋 config.secret.example.php    # Template segreti (committa)
│
├── 🚫 .gitignore                   # File da ignorare
├── 📖 README.md                    # Questa guida
└── 📜 LICENSE                      # Licenza MIT
```

---

## 🔐 Sicurezza

### ✅ Best Practices Implementate

| Feature | Implementazione |
|---------|----------------|
| **API Key Isolation** | Solo `config.secret.php` contiene chiavi, mai esposto via API |
| **Password Hashing** | Supporto per `password_hash()` in admin |
| **Rate Limiting** | 1 richiesta/3sec per IP su proxy AI |
| **Input Validation** | Validazione lunghezza messaggi (max 10K chars) |
| **Domain Whitelist** | Solo domini autorizzati per feed RSS |
| **CORS Headers** | Configurati correttamente per sicurezza |

### ⚠️ File da NON committare

```bash
# Già presenti in .gitignore
config.secret.php      # Contiene API keys e password
config.json            # Può contenere configurazioni personali
*.log                  # Log files
```

### ✅ File sicuri da committare

```bash
config.secret.example.php
config.json.example
admin.php
api.php
proxy_ai.php
index.html
.gitignore
README.md
```

---

## 🌍 Fonti RSS Predefinite

### Europa 🇪🇺
- 🇬🇧 BBC News, The Guardian, Sky News
- 🇮🇹 ANSA
- 🇳🇴 Aftenposten

### Americhe 🌎
- 🇺🇸 CNN, New York Times

### Asia 🌏
- 🇶🇦 Al Jazeera
- 🇭🇰 South China Morning Post
- 🇯🇵 Japan Times
- 🇸🇬 Straits Times
- 🇮🇳 Hindustan Times

### Africa 🌍
- 🇿🇦 News24
- 🇪🇬 Rassd Egypt
- 🌍 AllAfrica

### Oceania 🦘
- 🇦🇺 ABC Australia
- 🇳🇿 Stuff NZ

### Medio Oriente 🕌
- 🇮🇱 Times of Israel, Jerusalem Post
- 🇱🇧 LBC Lebanon
- 🇵🇸 Palestine TV
- 🇶🇦 Gulf Times

### Globale 🌐
- International Crisis Group

---

## 🛠️ API Endpoints

### `api.php?action=config`
Restituisce configurazione pubblica (fonti, proxy, modello)

**Response:**
```json
{
  "sources": {...},
  "cors_proxies": [...],
  "model": "mistral-small-latest",
  "user_agent": "...",
  "fetch_timeout": 15000
}
```

### `api.php?action=fetch_feed&url=...`
Proxy server-side per fetch RSS feed (bypassa CORS)

### `proxy_ai.php` (POST)
Proxy per Mistral AI API

**Request:**
```json
{
  "messages": [
    {"role": "system", "content": "..."},
    {"role": "user", "content": "..."}
  ],
  "temperature": 0.7,
  "max_tokens": 1500
}
```

---

## 🔧 Personalizzazione

### Aggiungere una nuova fonte RSS

**Via Admin Panel:**
1. Login → Tab "📰 Fonti RSS"
2. Compila form: nome, URL, flag, colore, regione
3. Click "Aggiungi Fonte"

**Via config.json:**
```json
{
  "sources": {
    "tua_fonte": {
      "name": "Tua Fonte News",
      "url": "https://example.com/rss.xml",
      "color": "#ff6600",
      "region": "europe",
      "flag": "🇮🇹",
      "enabled": true
    }
  }
}
```

### Cambiare modello Mistral

Via Admin Panel → ⚙️ Generale → Modello Mistral:
- `mistral-small-latest` (veloce, economico)
- `mistral-medium-latest` (bilanciato)
- `mistral-large-latest` (migliori risultati)

---

## 🚀 Deploy in Produzione

### Hosting Consigliati

- ✅ **Shared Hosting** (es. Altervista, Aruba, SiteGround)
- ✅ **VPS** (es. DigitalOcean, Linode, Hetzner)
- ✅ **Serverless** (es. Vercel con PHP, Railway)

### Checklist Deploy

```bash
# 1. Upload files via FTP/Git
# Escludi: config.secret.php, config.json

# 2. Sul server, crea config.secret.php
nano config.secret.php
# Inserisci API key di produzione

# 3. Crea config.json
cp config.json.example config.json

# 4. Imposta permessi
chmod 644 *.php
chmod 600 config.secret.php  # Solo lettura owner

# 5. Test
curl https://tuo-dominio.com/api.php?action=config
```

### Variabili d'Ambiente (opzionale)

Invece di `config.secret.php`, puoi usare variabili d'ambiente:

```php
<?php
// config.secret.php alternativo
return [
    'mistral_api_key' => getenv('MISTRAL_API_KEY'),
    'admin_password' => getenv('ADMIN_PASSWORD'),
];
```

---

## 🐛 Troubleshooting

### "Secrets file not found"
```bash
# Soluzione: Crea il file
cp config.secret.example.php config.secret.php
nano config.secret.php
```

### "API key not configured"
```bash
# Verifica che config.secret.php contenga:
'mistral_api_key' => 'tua_chiave_valida'  # NON 'YOUR_MISTRAL_API_KEY_HERE'
```

### "Config file not found"
```bash
cp config.json.example config.json
```

### Feed RSS non carica
1. Verifica URL feed nell'admin panel → Test
2. Controlla CORS proxy attivi
3. Verifica whitelist domini in `api.php`

### Riassunto AI non funziona
1. Verifica API key Mistral valida
2. Controlla credito Mistral su console.mistral.ai
3. Verifica rate limit (max 1 req/3sec)

### Password admin dimenticata
```bash
# Genera nuova password
php -r "echo password_hash('NuovaPassword', PASSWORD_DEFAULT);"

# Aggiorna config.secret.php
'admin_password_hash' => '$2y$10$nuovo_hash...'
```

---

## 📊 Performance

| Metrica | Valore |
|---------|--------|
| **Tempo caricamento feed** | ~2-5s (dipende da proxy) |
| **Riassunto AI** | ~3-8s |
| **Rate limit AI** | 1 req / 3s per IP |
| **Max lunghezza articolo** | 10.000 caratteri |
| **Max tokens AI** | 2.000 |

---

## 🤝 Contribuire

I contributi sono benvenuti! 

1. Fork il progetto
2. Crea un branch (`git checkout -b feature/AmazingFeature`)
3. Commit (`git commit -m 'Add AmazingFeature'`)
4. Push (`git push origin feature/AmazingFeature`)
5. Apri una Pull Request

### To-Do List

- [ ] Caching riassunti AI (Redis/file)
- [ ] Supporto traduzione multilingua
- [ ] Esportazione feed OPML
- [ ] Dark mode UI
- [ ] Progressive Web App (PWA)
- [ ] Dashboard analytics fonti
- [ ] Search full-text articoli
- [ ] Bookmark/Preferiti
- [ ] Notifiche push

---

## 📄 Licenza

Questo progetto è rilasciato sotto licenza **MIT**.

Vedi il file [LICENSE](LICENSE) per dettagli.

```
MIT License - Copyright (c) 2024

Permission is hereby granted, free of charge, to any person obtaining a copy...
```

---

## 🙏 Ringraziamenti

- [Mistral AI](https://mistral.ai) - Modelli AI
- [RSS Feed Providers](#-fonti-rss-predefinite) - Fonti notizie
- [CORS Proxies](#) - CorsProxy.io, AllOrigins, CodeTabs, ThingProxy

---

## 📞 Supporto

- 🐛 **Bug Report**: [GitHub Issues](https://github.com/tuo-username/news-aggregator/issues)
- 💡 **Feature Request**: [GitHub Discussions](https://github.com/tuo-username/news-aggregator/discussions)
- 📧 **Email**: tuo@email.com
- 🌐 **Demo**: [paulvern.free.nf/News](http://paulvern.free.nf/News/)

---

## ⭐ Supporta il Progetto

Se questo progetto ti è utile, lascia una ⭐ su GitHub!

[![GitHub stars](https://img.shields.io/github/stars/tuo-username/news-aggregator?style=social)](https://github.com/tuo-username/news-aggregator)

---

<div align="center">

**Made with ❤️ and ☕**

[Demo](http://paulvern.free.nf/News/) • [Documentazione](#) • [Report Bug](https://github.com/tuo-username/news-aggregator/issues)

</div>
```


