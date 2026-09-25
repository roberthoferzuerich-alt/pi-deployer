# Raspberry Pi 5 | Laravel Migration & Deployment Assistent

Automatisierter Laravel Migrations- & Deployment-Assistent für Raspberry Pi 5 (Pironman 16GB) und Serverumgebungen.

## Features
- **System-Check & Rechte-Audit**: Überprüft Verzeichnis-Schreibrechte (0775 / www-data) und Systemvoraussetzungen.
- **Git Repository Sync**: Zieht automatisch den neuesten Code vom GitHub-Repository.
- **.env & DB Setup**: Prüft Datenbank-Verbindungen und bietet automatische **DB-Benutzererstellung (OK/Abbrechen Modal)** bei Fehlschlägen.
- **Vendor Packages & Migrationen**: Führt `composer install --no-dev`, `migrate --force` und `db:seed` aus.
- **Cache-Optimierung**: Führt `config:cache`, `route:cache` und `view:cache` für maximale Performance durch.

---

## Nginx Konfiguration für Raspberry Pi

Das Projekt enthält eine vorgefertigte Konfigurationsvorlage in [`nginx.conf.example`](file:///c:/laragon/www/pi-deployer/nginx.conf.example).

### Einrichtung auf dem Raspberry Pi:

1. **Datei nach `/etc/nginx/sites-available/pi-deployer` kopieren**:
   ```bash
   sudo cp /var/www/pi-deployer/nginx.conf.example /etc/nginx/sites-available/pi-deployer
   ```

2. **Symlink aktivieren**:
   ```bash
   sudo ln -s /etc/nginx/sites-available/pi-deployer /etc/nginx/sites-enabled/
   ```

3. **Nginx testen und neu laden**:
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   ```

Das Portal ist damit verschlüsselt erreichbar unter: `https://rhz.internet-box.ch:8445`

---

## CLI Befehle

Rechte-Check & automatische Reparatur:
```bash
php artisan pi:check --fix
```

Automatisches Deployment via Konsole:
```bash
php artisan pi:deploy --branch=main --seed
```

