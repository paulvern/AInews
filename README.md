
---

# 📰 Global News Aggregator con AI

Aggregatore intelligente di notizie internazionali con riassunti AI powered by Mistral

**🌐 Demo Live:** [paulvern.free.nf/News](http://paulvern.free.nf/News/)

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

## 🚀 Installazione Rapida

### Prerequisiti

- PHP 7.4+ (consigliato PHP 8.0+)
- Estensioni PHP: `curl`, `json`, `simplexml`
- Mistral AI API Key (registrati su [console.mistral.ai](https://console.mistral.ai))

### Setup in 3 minuti

**Passo 1: Clona il repository**

```bash
git clone https://github.com/tuo-username/news-aggregator.git
cd news-aggregator
```

**Passo 2: Configura i segreti**

```bash
cp config.secret.example.php config.secret.php
```

Modifica `config.secret.php` e inserisci:

```php
return [
    'mistral_api_key' => 'la_tua_chiave_mistral',
    'admin_password' => 'PasswordSicura123!',
];
```

**Passo 3: Configura l'app**

```bash
cp config.json.example config.json
```

**Passo 4: Avvia il server**

```bash
php -S localhost:8000
```

Apri il browser su: `http://localhost:8000`

---

## ⚙️ Configurazione

### File Segreti (config.secret.php)

Questo file contiene le credenziali sensibili e NON deve essere committato.

```php
return [
    'mistral_api_key' => 'your_mistral_api_key_here',
    'admin_password' => 'YourSecurePassword123!',
];
```

**Generare password hashata (più sicuro):**

```bash
php -r "echo password_hash('TuaPassword', PASSWORD_DEFAULT);"
```

Poi usa:

```php
return [
    'mistral_api_key' => 'your_api_key',
    'admin_password_hash' => '$2y$10$...',
];
```

### Configurazione App (config.json)

Puoi modificare:

- **Modello Mistral**: `mistral-small-latest`, `mistral-medium-latest`, `mistral-large-latest`
- **CORS Proxies**: Aggiungi/rimuovi proxy
- **Fonti RSS**: Aggiungi nuove fonti di notizie

---

## 🎛️ Admin Panel

**URL:** `http://localhost:8000/admin.php`

### Funzionalità

- ⚙️ **Generale**: Configura modello AI, timeout, user agent
- 🌐 **Proxy CORS**: Gestisci proxy multipli con fallback automatico
- 📰 **Fonti RSS**: Aggiungi/modifica/disabilita fonti di notizie
- 🧪 **Test**: Testa feed RSS e visualizza configurazione

### Dashboard

Visualizza statistiche in tempo reale:

- Numero totale fonti
- Fonti attive
- Proxy CORS configurati
- Configurazione JSON completa

---

## 📁 Struttura Progetto

```
news-aggregator/
├── index.html                   # Frontend principale
├── admin.php                    # Admin panel (richiede login)
├── api.php                      # API endpoints (config, feed proxy)
├── proxy_ai.php                 # Proxy per Mistral AI
├── config.json                  # Config app (locale, NON committare)
├── config.json.example          # Template config (committare)
├── config.secret.php            # Segreti (locale, NON committare)
├── config.secret.example.php    # Template segreti (committare)
├── .gitignore                   # File da ignorare
├── README.md                    # Questa guida
└── LICENSE                      # Licenza MIT
```

---

## 🔐 Sicurezza

### Best Practices Implementate

- ✅ **API Key Isolation**: Solo `config.secret.php` contiene chiavi
- ✅ **Password Hashing**: Supporto per `password_hash()`
- ✅ **Rate Limiting**: 1 richiesta/3sec per IP su proxy AI
- ✅ **Input Validation**: Validazione lunghezza messaggi (max 10K chars)
- ✅ **Domain Whitelist**: Solo domini autorizzati per feed RSS
- ✅ **CORS Headers**: Configurati correttamente

### File da NON committare

```
config.secret.php      # Contiene API keys e password
config.json            # Può contenere configurazioni personali
*.log                  # Log files
```

Questi file sono già presenti in `.gitignore`

### File sicuri da committare

```
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

- 🇬🇧 BBC News
- 🇬🇧 The Guardian
- 🇬🇧 Sky News
- 🇮🇹 ANSA
- 🇳🇴 Aftenposten

### Americhe 🌎

- 🇺🇸 CNN
- 🇺🇸 New York Times

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

- 🇮🇱 Times of Israel
- 🇮🇱 Jerusalem Post
- 🇱🇧 LBC Lebanon
- 🇵🇸 Palestine TV
- 🇶🇦 Gulf Times

### Globale 🌐

- 🌐 International Crisis Group

---

## 🛠️ API Endpoints

### GET /api.php?action=config

Restituisce configurazione pubblica (fonti, proxy, modello)

**Response:**

```json
{
  "sources": {},
  "cors_proxies": [],
  "model": "mistral-small-latest",
  "user_agent": "Mozilla/5.0...",
  "fetch_timeout": 15000
}
```

### GET /api.php?action=fetch_feed&url=URL

Proxy server-side per fetch RSS feed (bypassa CORS)

### POST /proxy_ai.php

Proxy per Mistral AI API

**Request:**

```json
{
  "messages": [
    {"role": "system", "content": "Sei un assistente..."},
    {"role": "user", "content": "Riassumi..."}
  ],
  "temperature": 0.7,
  "max_tokens": 1500
}
```

---

## 🔧 Personalizzazione

### Aggiungere una nuova fonte RSS

**Metodo 1: Via Admin Panel**

1. Login su `admin.php`
2. Tab "Fonti RSS"
3. Compila il form
4. Click "Aggiungi Fonte"

**Metodo 2: Via config.json**

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

Via Admin Panel → Generale → Modello Mistral:

- `mistral-small-latest` - veloce, economico
- `mistral-medium-latest` - bilanciato
- `mistral-large-latest` - migliori risultati

---

## 🚀 Deploy in Produzione

### Hosting Consigliati

- Shared Hosting (Altervista, Aruba, SiteGround)
- VPS (DigitalOcean, Linode, Hetzner)
- Serverless (Vercel, Railway)

### Checklist Deploy

**1. Upload files via FTP/Git**

Escludi: `config.secret.php`, `config.json`

**2. Sul server, crea config.secret.php**

```bash
nano config.secret.php
```

Inserisci API key di produzione

**3. Crea config.json**

```bash
cp config.json.example config.json
```

**4. Imposta permessi**

```bash
chmod 644 *.php
chmod 600 config.secret.php
```

**5. Test**

```bash
curl https://tuo-dominio.com/api.php?action=config
```

---

## 🐛 Troubleshooting

### "Secrets file not found"

**Soluzione:**

```bash
cp config.secret.example.php config.secret.php
nano config.secret.php
```

### "API key not configured"

Verifica che `config.secret.php` contenga una chiave valida:

```php
'mistral_api_key' => 'tua_chiave_valida'  // NON 'YOUR_MISTRAL_API_KEY_HERE'
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
2. Controlla credito su console.mistral.ai
3. Verifica rate limit (max 1 req/3sec)

### Password admin dimenticata

**Genera nuova password:**

```bash
php -r "echo password_hash('NuovaPassword', PASSWORD_DEFAULT);"
```

**Aggiorna config.secret.php:**

```php
'admin_password_hash' => '$2y$10$nuovo_hash...'
```

---

## 📊 Performance

- Tempo caricamento feed: ~2-5s (dipende da proxy)
- Riassunto AI: ~3-8s
- Rate limit AI: 1 req / 3s per IP
- Max lunghezza articolo: 10.000 caratteri
- Max tokens AI: 2.000

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

---

## 📄 Licenza

Questo progetto è rilasciato sotto licenza **MIT**.

```
MIT License - Copyright (c) 2024

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
```

---

## 🙏 Ringraziamenti

- [Mistral AI](https://mistral.ai) - Modelli AI
- Fonti RSS internazionali per le notizie
- CORS Proxies: CorsProxy.io, AllOrigins, CodeTabs, ThingProxy

---

## 📞 Supporto

- 🐛 Bug Report: [GitHub Issues](https://github.com/tuo-username/news-aggregator/issues)
- 💡 Feature Request: [GitHub Discussions](https://github.com/tuo-username/news-aggregator/discussions)
- 🌐 Demo: [paulvern.free.nf/News](http://paulvern.free.nf/News/)

---

## ⭐ Supporta il Progetto

Se questo progetto ti è utile, lascia una stella su GitHub!

---

**Made with ❤️ and ☕**

[Demo](http://paulvern.free.nf/News/) • [Admin Panel](http://paulvern.free.nf/News/admin.php) • [Report Bug](https://github.com/tuo-username/news-aggregator/issues)

---




