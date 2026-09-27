# 🍓 Raspberry Pi 5 | Laravel Migration & Deployment Assistent

Automatisierter Laravel Migrations- & Deployment-Assistent für den **Raspberry Pi 5 (Pironman 16GB Edition)** und lokale Entwicklungs- / Serverumgebungen.

---

## 🌟 Übersicht & Hauptfunktionen

Der **Pi Deployer & Migrator** ermöglicht die vollautomatische Bereitstellung, Datenbank-Verwaltung und Performance-Optimierung von Laravel-Projekten über ein modernes Web-Dashboard oder die Konsole.

### 🛡️ Kernfeatures & Sicherheitsfunktionen
- **Sicherheitssperre gegen Self-Deployment**: Verhindert versehentliches Überschreiben der Deployer-App selbst.
- **Zielpfad- & Existenzvalidierung**: Prüft vor jeder Aktion, ob der konfigurierte Zielpfad existiert.
- **System-Check & Rechte-Audit**: Überprüft Verzeichnis-Schreibrechte (`0775 / www-data`) für `storage/` und `bootstrap/cache`.
- **GitHub Code Sync**: Zieht automatisch den neuesten Code vom GitHub-Repository (`git pull origin main`).
- **.env & Datenbank-Setup**: Prüft Verbindungen, verwaltet `.env`-Variablen und erstellt MySQL/MariaDB-Datenbanken & Benutzer automatisch.
- **Composer Vendor Packages**: Generiert ARM64-optimierte PHP-Pakete (`composer install --no-dev`) und baut Assets mit Vite/NPM.
- **Migrations & Seeds**: Führt Datenbank-Tabellen-Migrationen (`artisan migrate --force`) und Seeders aus.
- **Speed Caches & Nginx**: Generiert `config:cache`, `route:cache` und `view:cache` sowie maßgeschneiderte Nginx Server-Blocks mit SSL.

---

## 💻 Schnellstart & Installation auf dem Raspberry Pi

### 1. Repository klonen & Rechte setzen:
```bash
cd /var/www
git clone https://github.com/dein-user/pi-deployer.git
cd pi-deployer

# Entwicklungs-Pakete installieren:
composer install

# Schreibrechte setzen:
sudo chown -R www-data:www-data /var/www/pi-deployer
sudo chmod -R 775 storage bootstrap/cache
```

### 2. `.env` konfigurieren:
Trage in `/var/www/pi-deployer/.env` den Pfad deines Ziel-Laravel-Projekts ein:
```env
PI_TARGET_PROJECT_PATH=/var/www/mein-ziel-projekt
PI_ALLOW_SELF_DEPLOY=false
```

---

## ⚙️ Nginx Konfiguration für den Raspberry Pi

Eine vorgefertigte Konfigurationsvorlage befindet sich in [`nginx.conf.example`](file:///c:/laragon/www/pi-deployer/nginx.conf.example).

```bash
# 1. Datei nach /etc/nginx/sites-available/pi-deployer kopieren:
sudo cp /var/www/pi-deployer/nginx.conf.example /etc/nginx/sites-available/pi-deployer

# 2. Symlink aktivieren:
sudo ln -s /etc/nginx/sites-available/pi-deployer /etc/nginx/sites-enabled/

# 3. Nginx testen und neu laden:
sudo nginx -t
sudo systemctl reload nginx
```

Das Portal ist anschliessend erreichbar unter: `https://rhz.internet-box.ch:8445`

---

## 🖥️ CLI Befehle

### Rechte-Check & automatische Reparatur:
```bash
php artisan pi:check --fix
```

### Automatisches Deployment via Konsole:
```bash
php artisan pi:deploy --branch=main --seed
```

---

## 👨‍💻 Entwickler & Credits

- **Developer & System Architect**: Robert Hofer (Zürich, Schweiz) — `robert.hofer.zuerich@bluewin.ch`
- **AI Pair Programmer**: Antigravity AI (Google DeepMind Team) — *Advanced Agentic Coding*
