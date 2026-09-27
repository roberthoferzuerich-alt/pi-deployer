// user_desc_screen.dart
// Raspberry Pi 5 Laravel Deployer & Migrator - User Description Screen

import 'package:flutter/material.dart';

class UserDescScreen extends StatelessWidget {
  const UserDescScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0E17),
      appBar: AppBar(
        title: const Text('Benutzerbeschreibung'),
        backgroundColor: const Color(0xFF131B2E),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildSectionHeader('Zweck der Anwendung'),
            const SizedBox(height: 8),
            const Text(
              'Der Raspberry Pi 5 Deployer & Migrator ist ein spezialisiertes Werkzeug zur vollautomatischen Bereitstellung und Migration von Laravel-Anwendungen auf einem Raspberry Pi 5 (z.B. Pironman 16GB Edition).',
              style: TextStyle(color: Colors.white70, fontSize: 14, height: 1.4),
            ),
            const SizedBox(height: 24),
            _buildSectionHeader('Funktionsübersicht & Schritte'),
            const SizedBox(height: 12),
            _buildFeatureTile('1. System-Check & Rechte', 'Prüft Schreibrechte (0775 / www-data) für storage/ und bootstrap/cache.'),
            _buildFeatureTile('2. GitHub Code Sync', 'Holt den neuesten Quellcode automatisch von GitHub (git pull origin main).'),
            _buildFeatureTile('3. .env & Datenbank Setup', 'Verwaltet DB-Zugangsdaten, testet Verbindungen und erstellt MySQL/MariaDB-Datenbanken.'),
            _buildFeatureTile('4. Composer Vendor Packages', 'Generiert ARM64-optimierte PHP-Pakete (composer install --no-dev) & baut Vite Assets.'),
            _buildFeatureTile('5. Migrations & Seeders', 'Führt Tabellen-Migrationen (php artisan migrate --force) und Seeders aus.'),
            _buildFeatureTile('6. Speed Caches & Nginx', 'Aktiviert Laravel-Caches und generiert Nginx Server-Block Konfigurationen mit SSL.'),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Text(
      title,
      style: const TextStyle(
        color: Color(0xFF10B981),
        fontSize: 18,
        fontWeight: FontWeight.bold,
      ),
    );
  }

  Widget _buildFeatureTile(String title, String description) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF131B2E),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(color: Color(0xFF8B5CF6), fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          Text(description, style: const TextStyle(color: Colors.white60, fontSize: 13)),
        ],
      ),
    );
  }
}
