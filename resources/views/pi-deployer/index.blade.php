<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Raspberry Pi 5 | Laravel Migration & Deployment Assistent</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN for instant rendering -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['Fira Code', 'monospace'],
                    },
                    colors: {
                        pi: {
                            50: '#ecfdf5',
                            500: '#10b981',
                            600: '#059669',
                            900: '#064e3b',
                            dark: '#0a0e17',
                            card: '#131b2e',
                            border: '#1e293b',
                            accent: '#06b6d4',
                            pironman: '#8b5cf6',
                        }
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            background-color: #0a0e17;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(16, 185, 129, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(139, 92, 246, 0.08) 0%, transparent 40%);
        }
        .glass-card {
            background: rgba(19, 27, 46, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
        }
        .glow-emerald {
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.25);
        }
        .glow-pironman {
            box-shadow: 0 0 25px rgba(139, 92, 246, 0.3);
        }
        .log-terminal {
            background-color: #050811;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.8);
        }
        /* Button Click Animation & Ripple Effects */
        .btn-action {
            position: relative;
            overflow: hidden;
            transition: transform 0.12s ease, box-shadow 0.12s ease, background-color 0.15s ease, filter 0.15s ease;
            user-select: none;
            cursor: pointer;
        }
        .btn-action:hover {
            filter: brightness(1.15);
        }
        .btn-action:active, .btn-action.clicked {
            transform: scale(0.93) translateY(1px) !important;
            filter: brightness(1.25);
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.5);
        }
        .btn-ripple {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: ripple-anim 0.6s ease-out;
            background-color: rgba(255, 255, 255, 0.4);
            pointer-events: none;
        }
        @keyframes ripple-anim {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        .btn-success-flash {
            animation: successPulse 0.7s ease-out;
        }
        @keyframes successPulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.9); }
            50% { box-shadow: 0 0 22px 10px rgba(16, 185, 129, 0.5); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>
</head>
<body class="text-slate-200 font-sans min-h-screen pb-16">

    <!-- Top Navigation Header -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-emerald-500 via-teal-500 to-purple-600 p-0.5 flex items-center justify-center glow-pironman">
                    <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                        <i class="fa-solid fa-microchip text-xl text-emerald-400"></i>
                    </div>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white flex items-center gap-2">
                        <span>Pi Deployer & Migrator</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 font-semibold">
                            <i class="fa-solid fa-cube text-[10px] mr-1"></i> Raspberry Pi 5 Pironman (16GB)
                        </span>
                    </h1>
                    <p class="text-xs text-slate-400">Automatisierter Laravel Migrations- & Deployment-Assistent</p>
                </div>
            </div>

            <div class="flex items-center space-x-3 text-xs">
                <div class="flex items-center space-x-2 bg-slate-950/80 px-3 py-1.5 rounded-xl border border-purple-500/30">
                    <label class="text-[11px] text-purple-300 font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-folder-open text-purple-400"></i> Ziel-Projektpfad:
                    </label>
                    <input type="text" id="targetProjectPath" value="{{ $targetPath }}" onchange="runAudit()" placeholder="z.B. C:\laragon\www\pi-demo oder /var/www/pi-demo" class="px-2.5 py-1 bg-slate-900 text-xs rounded-lg border border-slate-800 text-emerald-300 font-mono w-64 md:w-80 focus:outline-none focus:border-purple-400" title="Pfad des Ziel-Laravel-Projekts auf dem Server">
                </div>
                <button onclick="runAudit(event)" class="btn-action px-4 py-2 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-medium transition duration-200 flex items-center gap-2">
                    <i class="fa-solid fa-rotate text-xs"></i> System-Check
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 space-y-8">

        <!-- Banner & Quick Action Workflow Header -->
        <div class="glass-card rounded-2xl p-6 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none text-emerald-400">
                <i class="fa-brands fa-raspberry-pi text-9xl"></i>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                <div class="md:col-span-3 space-y-2">
                    <h2 class="text-2xl font-extrabold text-white flex items-center gap-3">
                        <i class="fa-solid fa-wand-magic-sparkles text-emerald-400"></i>
                        <span>1-Klick Automatisierte Projekt-Migration</span>
                    </h2>
                    <p class="text-sm text-slate-300 leading-relaxed">
                        Führt in einem Durchgang alle Schritte aus: GitHub Sync, Directory-Berechtigungen (0775), `.env` & Datenbank-Prüfung, Composer Vendor-Generierung, `migrate --force` + Seeders sowie Cache-Optimierung.
                    </p>
                </div>
                <div class="flex justify-start md:justify-end">
                    <button onclick="runFullDeployment(event)" id="fullDeployBtn" class="btn-action w-full md:w-auto px-6 py-4 rounded-xl bg-gradient-to-r from-emerald-500 via-teal-600 to-cyan-600 text-white font-bold shadow-lg glow-emerald hover:opacity-95 transition-all duration-200 flex items-center justify-center gap-3 text-base">
                        <i class="fa-solid fa-rocket text-lg"></i>
                        <span>Jetzt Komplett-Migration ausführen</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 6 Step Grid Wizard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Step 1: System & Permissions -->
            <div class="glass-card rounded-2xl p-6 flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Schritt 1</span>
                        <span id="permBadge" class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $audit['all_permissions_ok'] ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                            {{ $audit['all_permissions_ok'] ? '✓ Berechtigungen OK' : '⚠️ Anpassen nötig' }}
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-folder-shield text-emerald-400"></i>
                        <span>System & Rechte Prüfen</span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        Prüft Schreibrechte für <code class="text-emerald-300">storage/</code> und <code class="text-emerald-300">bootstrap/cache</code> (0775 / www-data).
                    </p>
                    <div class="space-y-2 text-xs bg-slate-950/60 p-3 rounded-lg border border-slate-800">
                        <div class="flex justify-between">
                            <span class="text-slate-400">PHP Version:</span>
                            <span id="phpVerText" class="font-mono text-emerald-400 font-semibold">{{ $audit['php_version'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Freier Speicher (Pi):</span>
                            <span id="diskFreeText" class="font-mono text-cyan-400 font-semibold">{{ $audit['disk_free_space'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Schreibrechte (Storage):</span>
                            <span id="storagePermText" class="font-mono {{ $audit['permissions']['storage']['is_writable'] ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $audit['permissions']['storage']['perms'] ?? '0775' }} ({{ $audit['permissions']['storage']['is_writable'] ? 'Writable' : 'Locked' }})
                            </span>
                        </div>
                    </div>
                </div>
                <button onclick="fixPermissions(event)" class="btn-action w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-wrench text-emerald-400"></i>
                    <span>Rechte automatisch korrigieren (0775)</span>
                </button>
            </div>

            <!-- Step 2: GitHub Repository Sync -->
            <div class="glass-card rounded-2xl p-6 flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">Schritt 2</span>
                        <span id="gitBadge" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                            {{ $gitStatus['is_git_repo'] ? 'Git Repository' : 'Kein Git' }}
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-brands fa-github text-cyan-400"></i>
                        <span>GitHub Sync & Fetch</span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        Holt den neuesten Quellcode direkt aus deinem Git-Repository vom Laptop auf den Raspberry Pi.
                    </p>
                    <div class="space-y-2 text-xs bg-slate-950/60 p-3 rounded-lg border border-slate-800">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Branch:</span>
                            <span id="gitBranchText" class="font-mono text-cyan-400 font-semibold">{{ $gitStatus['current_branch'] ?? 'main' }}</span>
                        </div>
                        <div class="truncate text-slate-400" id="gitCommitContainer" title="{{ $gitStatus['last_commit'] ?? 'No commit' }}">
                            <span class="text-slate-500">Commit:</span> <span id="gitCommitText" class="font-mono text-slate-300">{{ $gitStatus['last_commit'] ?? 'Nicht verfügbar' }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <input type="text" id="gitBranchInput" value="{{ $gitStatus['current_branch'] ?? 'main' }}" class="w-1/3 px-3 py-2 bg-slate-950 text-xs rounded-xl border border-slate-800 focus:outline-none focus:border-cyan-500 font-mono">
                    <button onclick="runGitPull(event)" class="btn-action w-2/3 py-2.5 px-4 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-400 text-xs font-semibold border border-cyan-500/30 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-download"></i>
                        <span>Code von Git holen</span>
                    </button>
                </div>
            </div>

            <!-- Step 3: .env & DB Credentials -->
            <div class="glass-card rounded-2xl p-6 flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-purple-400 uppercase tracking-wider">Schritt 3</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                            .env & DB
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-database text-purple-400"></i>
                        <span>.env & Datenbank Setup</span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        Prüft und speichert Datenbankzugangsdaten im <code class="text-purple-300">.env</code> des Ziel-Projektpfads.
                    </p>
                    <div id="envPathBadge" class="text-[11px] font-mono text-purple-300/90 bg-purple-950/60 px-2.5 py-1.5 rounded-lg border border-purple-800/50 truncate flex items-center gap-1.5" title="{{ $envData['env_path'] }}">
                        <i class="fa-regular fa-file-code text-purple-400"></i>
                        <span class="text-slate-400 shrink-0">Ziel .env:</span>
                        <span id="envPathText" class="text-purple-200 font-semibold truncate">{{ $envData['env_path'] }}</span>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="grid grid-cols-4 gap-2">
                            <div class="col-span-1">
                                <label class="text-[10px] text-slate-400">DB Conn</label>
                                <select id="envDbConnection" class="w-full px-2 py-1.5 bg-slate-950 text-xs rounded-lg border border-slate-800 font-mono text-emerald-400">
                                    <option value="mysql" {{ $envData['db_connection'] === 'mysql' ? 'selected' : '' }}>mysql</option>
                                    <option value="sqlite" {{ $envData['db_connection'] === 'sqlite' ? 'selected' : '' }}>sqlite</option>
                                    <option value="pgsql" {{ $envData['db_connection'] === 'pgsql' ? 'selected' : '' }}>pgsql</option>
                                    <option value="mariadb" {{ $envData['db_connection'] === 'mariadb' ? 'selected' : '' }}>mariadb</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="text-[10px] text-slate-400">DB Host</label>
                                <input type="text" id="envDbHost" value="{{ $envData['db_host'] }}" class="w-full px-2 py-1.5 bg-slate-950 text-xs rounded-lg border border-slate-800 font-mono">
                            </div>
                            <div class="col-span-1">
                                <label class="text-[10px] text-slate-400">Port</label>
                                <input type="text" id="envDbPort" value="{{ $envData['db_port'] ?? '3306' }}" class="w-full px-2 py-1.5 bg-slate-950 text-xs rounded-lg border border-slate-800 font-mono">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 pt-0.5">
                            <div>
                                <label class="text-[10px] text-slate-400">DB Name</label>
                                <input type="text" id="envDbName" value="{{ $envData['db_database'] }}" class="w-full px-2 py-1.5 bg-slate-950 text-xs rounded-lg border border-slate-800 font-mono">
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-400">DB User</label>
                                <input type="text" id="envDbUser" value="{{ $envData['db_username'] }}" class="w-full px-2 py-1.5 bg-slate-950 text-xs rounded-lg border border-slate-800 font-mono">
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-400">DB Passwort</label>
                                <input type="password" id="envDbPass" value="{{ $envData['db_password'] }}" class="w-full px-2 py-1.5 bg-slate-950 text-xs rounded-lg border border-slate-800 font-mono">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-2">
                    <button onclick="testDbConnection(event)" class="btn-action py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-plug text-purple-400"></i>
                        <span>DB Test</span>
                    </button>
                    <button onclick="saveEnvSettings(event)" class="btn-action py-2.5 px-3 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-200 text-xs font-bold border border-purple-500/40 shadow-md transition flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk text-purple-400"></i>
                        <span>Speichern</span>
                    </button>
                </div>
            </div>

            <!-- Step 4: Composer Package Generator -->
            <div class="glass-card rounded-2xl p-6 flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Schritt 4</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                            Composer
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-box-open text-indigo-400"></i>
                        <span>Vendor Package Erzeugen</span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        Installiert alle PHP-Pakete neu für die ARM64 Architektur des Raspberry Pi 5 (<code class="text-indigo-300">composer install --no-dev</code>).
                    </p>
                    <div class="p-3 bg-slate-950/60 rounded-lg border border-slate-800 text-xs text-slate-400">
                        Autoloader-Optimierung aktiviert. Garantiert beste Performance auf 16GB RAM.
                    </div>
                </div>
                <button onclick="runComposerInstall(event)" class="btn-action w-full py-2.5 px-4 rounded-xl bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 text-xs font-semibold border border-indigo-500/30 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-gears text-indigo-400"></i>
                    <span>Vendor Pakete generieren</span>
                </button>
            </div>

            <!-- Step 5: Database Migration & Seeders -->
            <div class="glass-card rounded-2xl p-6 flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">Schritt 5</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            Migrations & Seeds
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-table text-amber-400"></i>
                        <span>Datenbank Migrieren & Seeden</span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        Führt Tabellen-Migrationen aus und importiert Daten mittels Seeder-Scripte auf dem Pi.
                    </p>
                    <div class="space-y-2">
                        <input type="text" id="seederClassInput" placeholder="DatabaseSeeder (Optional Class)" class="w-full px-3 py-1.5 bg-slate-950 text-xs rounded-xl border border-slate-800 font-mono text-slate-200">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="runMigrations(event, false)" class="btn-action py-2.5 px-3 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 text-xs font-semibold border border-amber-500/30 transition flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-play"></i>
                        <span>Migrate</span>
                    </button>
                    <button onclick="runSeeders(event)" class="btn-action py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-300 text-xs font-semibold border border-slate-700 transition flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-seedling"></i>
                        <span>Run Seeders</span>
                    </button>
                </div>
            </div>

            <!-- Step 6: Cache & System Optimization -->
            <div class="glass-card rounded-2xl p-6 flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Schritt 6</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            Optimieren
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-bolt text-emerald-400"></i>
                        <span>Cache & Live Speedup</span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        Erzeugt Routen-, Config- & View-Caches für maximale Ladegeschwindigkeiten.
                    </p>
                    <div class="p-3 bg-slate-950/60 rounded-lg border border-slate-800 text-xs text-slate-400">
                        <i class="fa-solid fa-circle-check text-emerald-400 mr-1"></i> <code class="text-emerald-300">config:cache</code>, <code class="text-emerald-300">route:cache</code>, <code class="text-emerald-300">view:cache</code>
                    </div>
                </div>
                <button onclick="runOptimize(event)" class="btn-action w-full py-2.5 px-4 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 text-xs font-semibold border border-emerald-500/30 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-gauge-high text-emerald-400"></i>
                    <span>App Caches aktivieren</span>
                </button>
            </div>

        </div>

        <!-- Nginx Generic Configuration Generator Section -->
        <div class="glass-card rounded-2xl p-6 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
                <div class="space-y-1">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-server text-purple-400"></i>
                        <span>Generischer Nginx Server-Block Generator</span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        Erstellt eine maßgeschneiderte, sichere Nginx-Serverkonfiguration für den Raspberry Pi 5.
                    </p>
                </div>
                <div class="flex items-center space-x-2 bg-slate-950/80 px-3 py-1.5 rounded-xl border border-purple-500/30 text-xs font-mono">
                    <span class="text-slate-400">Dateiname:</span>
                    <span id="nginxFilenamePreview" class="text-purple-300 font-bold">{{ $nginxGenerated['filename'] }}</span>
                </div>
            </div>

            <!-- Input Controls Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <label class="block text-slate-400 font-medium mb-1">App-Name / Slug</label>
                    <input type="text" id="nginxAppName" value="pi-deployer" oninput="generateNginxConfig()" class="w-full px-3 py-2 bg-slate-950 rounded-xl border border-slate-800 text-purple-300 font-mono focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Port</label>
                    <input type="number" id="nginxPort" value="8445" oninput="generateNginxConfig()" class="w-full px-3 py-2 bg-slate-950 rounded-xl border border-slate-800 text-emerald-400 font-mono focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Server Name (Domain/IP)</label>
                    <input type="text" id="nginxServerName" value="rhz.internet-box.ch" oninput="generateNginxConfig()" class="w-full px-3 py-2 bg-slate-950 rounded-xl border border-slate-800 text-cyan-300 font-mono focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-slate-400 font-medium mb-1">PHP Version</label>
                    <select id="nginxPhpVersion" onchange="generateNginxConfig()" class="w-full px-3 py-2 bg-slate-950 rounded-xl border border-slate-800 text-emerald-400 font-mono focus:outline-none focus:border-purple-400">
                        <option value="8.4" selected>PHP 8.4 (/run/php/php8.4-fpm.sock)</option>
                        <option value="8.3">PHP 8.3 (/run/php/php8.3-fpm.sock)</option>
                        <option value="8.2">PHP 8.2 (/run/php/php8.2-fpm.sock)</option>
                        <option value="8.1">PHP 8.1 (/run/php/php8.1-fpm.sock)</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-slate-400 font-medium mb-1">Projekt Root-Pfad</label>
                    <input type="text" id="nginxRootPath" value="{{ $targetPath }}" oninput="generateNginxConfig()" class="w-full px-3 py-2 bg-slate-950 rounded-xl border border-slate-800 text-amber-300 font-mono focus:outline-none focus:border-purple-400">
                </div>
                <div class="flex items-center pt-5">
                    <label class="inline-flex items-center cursor-pointer space-x-2 text-xs text-slate-300 font-medium">
                        <input type="checkbox" id="nginxSslEnabled" checked onchange="generateNginxConfig()" class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-purple-500 focus:ring-0">
                        <span>SSL Verschlüsselung aktivieren</span>
                    </label>
                </div>
            </div>

            <!-- Code Preview & Target Path -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-mono flex items-center gap-1.5">
                        <i class="fa-regular fa-file-code text-purple-400"></i>
                        <span>Ziel-Datei auf dem Pi:</span>
                        <code id="nginxSitesAvailablePath" class="text-purple-300 font-bold">{{ $nginxGenerated['sites_available_path'] }}</code>
                    </span>
                    <button onclick="copyNginxConfig()" class="btn-action px-3 py-1.5 rounded-lg bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 border border-purple-500/30 transition flex items-center gap-1.5">
                        <i class="fa-regular fa-copy"></i> Konfiguration kopieren
                    </button>
                </div>

                <pre id="nginxConfigDisplay" class="bg-slate-950 text-cyan-300 font-mono text-xs p-4 rounded-xl border border-slate-800 overflow-x-auto whitespace-pre leading-relaxed select-all max-h-80">{{ $nginxGenerated['config'] }}</pre>
            </div>

            <!-- Setup Commands for Raspberry Pi -->
            <div class="space-y-2 pt-2 border-t border-slate-800/80">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-terminal text-emerald-400"></i>
                        <span>Befehle zur Aktivierung auf dem Raspberry Pi:</span>
                    </span>
                    <button onclick="copyNginxCommands()" class="btn-action px-3 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/30 transition flex items-center gap-1.5 text-xs">
                        <i class="fa-regular fa-copy"></i> Befehle kopieren
                    </button>
                </div>
                <pre id="nginxCommandsDisplay" class="bg-slate-950 text-emerald-400 font-mono text-xs p-3 rounded-xl border border-slate-800 overflow-x-auto whitespace-pre select-all">{{ $nginxGenerated['setup_commands'] }}</pre>
            </div>
        </div>

        <!-- Terminal Output Window -->
        <div class="glass-card rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex space-x-1.5">
                        <span class="w-3 h-3 rounded-full bg-red-500/80"></span>
                        <span class="w-3 h-3 rounded-full bg-yellow-500/80"></span>
                        <span class="w-3 h-3 rounded-full bg-green-500/80"></span>
                    </div>
                    <h3 class="text-sm font-bold text-slate-300 font-mono flex items-center gap-2">
                        <i class="fa-solid fa-terminal text-emerald-400"></i>
                        <span>Raspberry Pi Execution Output Terminal</span>
                    </h3>
                </div>
                <button onclick="clearTerminal()" class="text-xs text-slate-400 hover:text-slate-200 transition flex items-center gap-1">
                    <i class="fa-solid fa-trash-can"></i> Logs leeren
                </button>
            </div>

            <div id="terminalOutput" class="log-terminal rounded-xl p-4 h-64 overflow-y-auto font-mono text-xs text-emerald-400 leading-relaxed border border-slate-800/80">
                [SYSTEM READY] Raspberry Pi 5 Migrations Assistent initialisiert. .env Pfad: {{ $envData['env_path'] }}
            </div>
        </div>

        <!-- Section: How to copy this module into future Laravel projects -->
        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-copy text-purple-400"></i>
                <span>Dieses Migrations-Modul in ein neues Laravel-Projekt kopieren</span>
            </h3>
            <p class="text-xs text-slate-300">
                Um diesen Assistenten in jedem deiner zukünftigen Laravel-Projekte zu nutzen, führe einfach diesen Konsolenbefehl aus oder erstelle die Befehle mit <code class="text-emerald-300">php artisan pi:deploy</code>:
            </p>
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 font-mono text-xs text-cyan-300 space-y-2">
                <div class="text-slate-400">// Konsolen-Migration auf dem Raspberry Pi 5 ausführen:</div>
                <div class="text-emerald-400">php artisan pi:deploy --branch=main --seed</div>
                <div class="text-slate-400 mt-2">// Rechte & Systemvoraussetzungen prüfen:</div>
                <div class="text-cyan-400">php artisan pi:check --fix</div>
            </div>
        </div>

    </main>

    <!-- JavaScript Functions for Realtime Interactivity -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function triggerRipple(e, btn) {
            if (!btn) return;
            const rect = btn.getBoundingClientRect();
            const circle = document.createElement('span');
            const diameter = Math.max(rect.width, rect.height);
            const radius = diameter / 2;
            circle.style.width = circle.style.height = `${diameter}px`;
            circle.style.left = `${(e && e.clientX ? e.clientX : rect.left + radius) - rect.left - radius}px`;
            circle.style.top = `${(e && e.clientY ? e.clientY : rect.top + radius) - rect.top - radius}px`;
            circle.classList.add('btn-ripple');

            const existing = btn.getElementsByClassName('btn-ripple')[0];
            if (existing) { existing.remove(); }
            btn.appendChild(circle);
        }

        async function handleBtnClick(eventOrBtn, asyncFn) {
            let btn = null;
            if (eventOrBtn && eventOrBtn.currentTarget) {
                btn = eventOrBtn.currentTarget;
                triggerRipple(eventOrBtn, btn);
            } else if (eventOrBtn instanceof HTMLElement) {
                btn = eventOrBtn;
            } else if (typeof eventOrBtn === 'string') {
                btn = document.getElementById(eventOrBtn);
            }

            if (!btn) {
                return await asyncFn();
            }

            btn.classList.add('clicked');
            const originalContent = btn.innerHTML;
            btn.disabled = true;

            const iconEl = btn.querySelector('i');
            if (iconEl) {
                iconEl.className = 'fa-solid fa-spinner fa-spin text-xs';
            }

            try {
                const result = await asyncFn();
                btn.classList.remove('clicked');
                btn.classList.add('btn-success-flash');

                if (iconEl) {
                    iconEl.className = 'fa-solid fa-check text-emerald-400 text-xs';
                }

                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                    btn.classList.remove('btn-success-flash');
                }, 1000);

                return result;
            } catch (err) {
                btn.classList.remove('clicked');
                if (iconEl) {
                    iconEl.className = 'fa-solid fa-xmark text-red-400 text-xs';
                }
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }, 1000);
                throw err;
            }
        }

        function log(message, type = 'info') {
            const terminal = document.getElementById('terminalOutput');
            const time = new Date().toLocaleTimeString();
            let colorClass = 'text-emerald-400';
            if (type === 'error') colorClass = 'text-red-400 font-bold';
            if (type === 'warn') colorClass = 'text-amber-300';
            if (type === 'sys') colorClass = 'text-cyan-400';

            const line = document.createElement('div');
            line.className = `${colorClass} py-0.5 border-b border-slate-900/50`;
            line.innerHTML = `<span class="text-slate-600">[${time}]</span> ${message}`;
            terminal.appendChild(line);
            terminal.scrollTop = terminal.scrollHeight;
        }

        function clearTerminal(event) {
            handleBtnClick(event, async () => {
                document.getElementById('terminalOutput').innerHTML = '<div class="text-slate-500">[CLEARED] Terminal zurückgesetzt.</div>';
            });
        }

        function getTargetPath() {
            const el = document.getElementById('targetProjectPath');
            return el ? el.value.trim() : '';
        }

        async function post(url, data = {}) {
            data.target_path = getTargetPath();
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            return await res.json();
        }

        async function generateNginxConfig() {
            const appName = document.getElementById('nginxAppName').value || 'pi-deployer';
            const port = document.getElementById('nginxPort').value || '8445';
            const serverName = document.getElementById('nginxServerName').value || 'rhz.internet-box.ch';
            const rootPath = document.getElementById('nginxRootPath').value || '/var/www/pi-deployer/public';
            const phpVersion = document.getElementById('nginxPhpVersion').value || '8.4';
            const sslEnabled = document.getElementById('nginxSslEnabled').checked;

            try {
                const res = await post('/pi-deploy/api/generate-nginx', {
                    app_name: appName,
                    port: port,
                    server_name: serverName,
                    root_path: rootPath,
                    php_version: phpVersion,
                    ssl_enabled: sslEnabled
                });

                if (res.success) {
                    document.getElementById('nginxFilenamePreview').innerText = res.filename;
                    document.getElementById('nginxSitesAvailablePath').innerText = res.sites_available_path;
                    document.getElementById('nginxConfigDisplay').innerText = res.config;
                    document.getElementById('nginxCommandsDisplay').innerText = res.setup_commands;
                }
            } catch (err) {
                console.error('Nginx generator error:', err);
            }
        }

        function copyNginxConfig() {
            const text = document.getElementById('nginxConfigDisplay').innerText;
            navigator.clipboard.writeText(text);
            log('✓ Nginx-Konfiguration in die Zwischenablage kopiert!', 'info');
        }

        function copyNginxCommands() {
            const text = document.getElementById('nginxCommandsDisplay').innerText;
            navigator.clipboard.writeText(text);
            log('✓ Nginx Setup-Befehle in die Zwischenablage kopiert!', 'info');
        }

        async function runAudit(event) {
            return handleBtnClick(event, async () => {
                log('System Audit gestartet...', 'sys');
                const target = encodeURIComponent(getTargetPath());
                const res = await (await fetch(`/pi-deploy/api/audit?target_path=${target}`)).json();
                
                const audit = res.audit || res;
                log(`Ziel-Pfad: ${res.target_path || audit.target_path} | PHP Version: ${audit.php_version} | Disk Free: ${audit.disk_free_space}`);
                log(`Schreibrechte storage: ${audit.permissions?.storage?.is_writable ? 'OK (0775)' : 'Gesperrt'}`);

                if (res.envData) {
                    const env = res.envData;
                    if (document.getElementById('envPathText')) {
                        document.getElementById('envPathText').innerText = env.env_path;
                        document.getElementById('envPathBadge').setAttribute('title', env.env_path);
                    }
                    if (document.getElementById('envDbConnection')) document.getElementById('envDbConnection').value = env.db_connection || 'mysql';
                    if (document.getElementById('envDbHost')) document.getElementById('envDbHost').value = env.db_host || '127.0.0.1';
                    if (document.getElementById('envDbPort')) document.getElementById('envDbPort').value = env.db_port || '3306';
                    if (document.getElementById('envDbName')) document.getElementById('envDbName').value = env.db_database || '';
                    if (document.getElementById('envDbUser')) document.getElementById('envDbUser').value = env.db_username || '';
                    if (document.getElementById('envDbPass')) document.getElementById('envDbPass').value = env.db_password || '';
                }

                if (res.gitStatus) {
                    if (document.getElementById('gitBranchText')) document.getElementById('gitBranchText').innerText = res.gitStatus.current_branch || 'main';
                    if (document.getElementById('gitBranchInput')) document.getElementById('gitBranchInput').value = res.gitStatus.current_branch || 'main';
                    if (document.getElementById('gitCommitText')) document.getElementById('gitCommitText').innerText = res.gitStatus.last_commit || 'Nicht verfügbar';
                    if (document.getElementById('gitBadge')) document.getElementById('gitBadge').innerText = res.gitStatus.is_git_repo ? 'Git Repository' : 'Kein Git';
                }
            });
        }

        async function fixPermissions(event) {
            return handleBtnClick(event, async () => {
                log('Korrigiere Verzeichnis-Berechtigungen (0775 / www-data)...', 'sys');
                const res = await post('/pi-deploy/api/fix-permissions');
                if (res.success) {
                    log('✓ Rechte für storage und bootstrap/cache erfolgreich auf 0775 gesetzt!', 'info');
                } else {
                    log('❌ Fehler beim Setzen der Rechte: ' + JSON.stringify(res), 'error');
                }
            });
        }

        async function runGitPull(event) {
            return handleBtnClick(event, async () => {
                const branch = document.getElementById('gitBranchInput').value || 'main';
                log(`Hole neuesten Quellcode von GitHub (Branch: ${branch})...`, 'sys');
                const res = await post('/pi-deploy/api/git-pull', { branch });
                if (res.success) {
                    log(`✓ Git Pull erfolgreich:\n${res.output}`, 'info');
                } else {
                    log(`❌ Git Pull fehlgeschlagen:\n${res.output}`, 'error');
                }
            });
        }

        async function testDbConnection(event) {
            return handleBtnClick(event, async () => {
                log('Prüfe Datenbank-Verbindung...', 'sys');
                const username = document.getElementById('envDbUser').value;
                const password = document.getElementById('envDbPass').value;
                const dbName = document.getElementById('envDbName').value;
                const host = document.getElementById('envDbHost').value;

                const res = await post('/pi-deploy/api/test-db', {
                    db_connection: document.getElementById('envDbConnection').value,
                    db_host: host,
                    db_port: document.getElementById('envDbPort').value || '3306',
                    db_database: dbName,
                    db_username: username,
                    db_password: password
                });
                if (res.success) {
                    log('✓ DB-Verbindung erfolgreich hergestellt!', 'info');
                } else {
                    log('❌ DB-Verbindung fehlgeschlagen: ' + res.message, 'error');
                    openDbUserModal(username, password, dbName, host);
                }
            });
        }

        function openDbUserModal(username, password, dbName, host) {
            document.getElementById('modalDbUser').innerText = username || '(kein User)';
            document.getElementById('modalDbPass').innerText = password ? '••••••••' : '(kein Passwort)';
            document.getElementById('modalDbName').innerText = dbName || '(keine DB)';
            document.getElementById('modalDbHost').innerText = host || '127.0.0.1';

            const modal = document.getElementById('dbUserModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                const card = modal.querySelector('.glass-card');
                if (card) {
                    card.classList.remove('scale-95');
                    card.classList.add('scale-100');
                }
            }, 10);
        }

        function closeDbUserModal(isCancel = true) {
            const modal = document.getElementById('dbUserModal');
            modal.classList.add('opacity-0');
            const card = modal.querySelector('.glass-card');
            if (card) {
                card.classList.remove('scale-100');
                card.classList.add('scale-95');
            }
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
            if (isCancel) {
                log('Benutzererstellung abgebrochen.', 'warn');
            }
        }

        async function confirmCreateDbUser(event) {
            return handleBtnClick(event, async () => {
                const username = document.getElementById('envDbUser').value;
                const password = document.getElementById('envDbPass').value;
                const dbName = document.getElementById('envDbName').value;
                const host = document.getElementById('envDbHost').value;

                log(`Erstelle Datenbank-Benutzer '${username}'...`, 'sys');
                const res = await post('/pi-deploy/api/create-db-user', {
                    db_connection: document.getElementById('envDbConnection').value,
                    db_host: host,
                    db_port: document.getElementById('envDbPort').value || '3306',
                    db_database: dbName,
                    db_username: username,
                    db_password: password
                });

                if (res.success) {
                    log('✓ ' + res.message, 'info');
                    closeDbUserModal(false);
                } else {
                    log('❌ Fehler beim Erstellen des DB-Users: ' + res.message, 'error');
                }
            });
        }

        async function saveEnvSettings(event) {
            return handleBtnClick(event, async () => {
                const targetPath = getTargetPath();
                log(`Speichere .env Variablen für Ziel-Projekt (${targetPath})...`, 'sys');
                const res = await post('/pi-deploy/api/save-env', {
                    DB_CONNECTION: document.getElementById('envDbConnection').value,
                    DB_HOST: document.getElementById('envDbHost').value,
                    DB_PORT: document.getElementById('envDbPort').value || '3306',
                    DB_DATABASE: document.getElementById('envDbName').value,
                    DB_USERNAME: document.getElementById('envDbUser').value,
                    DB_PASSWORD: document.getElementById('envDbPass').value
                });
                if (res.success) {
                    if (res.env_path) {
                        document.getElementById('envPathText').innerText = res.env_path;
                        document.getElementById('envPathBadge').setAttribute('title', res.env_path);
                    }
                    log(`✓ .env Datei für Ziel-Projekt (${res.target_path || targetPath}) erfolgreich neu gespeichert unter:\n${res.env_path}`, 'info');
                } else {
                    log('❌ Fehler beim Speichern der .env im Ziel-Projektpfad', 'error');
                }
            });
        }

        async function runComposerInstall(event) {
            return handleBtnClick(event, async () => {
                log('Starte composer install --no-dev --optimize-autoloader...', 'sys');
                const res = await post('/pi-deploy/api/composer-install');
                if (res.success) {
                    log(`✓ Composer Pakete erfolgreich erzeugt:\n${res.output}`, 'info');
                } else {
                    log(`❌ Composer Fehler:\n${res.output}`, 'error');
                }
            });
        }

        async function runMigrations(event, fresh = false) {
            return handleBtnClick(event, async () => {
                log(`Starte php artisan migrate ${fresh ? '--fresh' : ''} --force...`, 'sys');
                const res = await post('/pi-deploy/api/run-migrations', { fresh });
                if (res.success) {
                    log(`✓ Datenbank-Migrationen erfolgreich ausgeführt:\n${res.output}`, 'info');
                } else {
                    log(`❌ Migrationsfehler: ${res.error || 'Unbekannt'}`, 'error');
                }
            });
        }

        async function runSeeders(event) {
            return handleBtnClick(event, async () => {
                const seederClass = document.getElementById('seederClassInput').value || null;
                log(`Starte Database Seeder (${seederClass || 'DatabaseSeeder'})...`, 'sys');
                const res = await post('/pi-deploy/api/run-seeders', { seeder_class: seederClass });
                if (res.success) {
                    log(`✓ Seeder erfolgreich ausgeführt:\n${res.output}`, 'info');
                } else {
                    log(`❌ Seeder Fehler: ${res.error || 'Unbekannt'}`, 'error');
                }
            });
        }

        async function runOptimize(event) {
            return handleBtnClick(event, async () => {
                log('Starte Route-, Config- & View-Caching (optimize)...', 'sys');
                const res = await post('/pi-deploy/api/optimize');
                if (res.success) {
                    log(`✓ Anwendung erfolgreich optimiert & gecached:\n${res.output}`, 'info');
                } else {
                    log(`❌ Optimierungsfehler: ${res.error}`, 'error');
                }
            });
        }

        async function runFullDeployment(event) {
            return handleBtnClick(event, async () => {
                log('==================================================', 'sys');
                log('🚀 STARTE VOLLSTÄNDIGE AUTOMATISCHE MIGRATION...', 'sys');
                log('==================================================', 'sys');

                try {
                    await post('/pi-deploy/api/fix-permissions');
                    log('✓ Verzeichnis-Berechtigungen (0775 / www-data) angepasst.', 'info');
                    
                    const branch = document.getElementById('gitBranchInput').value || 'main';
                    const gitRes = await post('/pi-deploy/api/git-pull', { branch });
                    log(`✓ Git Pull (${branch}): ${gitRes.output || 'OK'}`, 'info');

                    const compRes = await post('/pi-deploy/api/composer-install');
                    log(`✓ Composer Packages: ${compRes.output || 'OK'}`, 'info');

                    const migRes = await post('/pi-deploy/api/run-migrations', { fresh: false });
                    log(`✓ Migrations: ${migRes.output || 'OK'}`, 'info');

                    const seederClass = document.getElementById('seederClassInput').value || null;
                    const seedRes = await post('/pi-deploy/api/run-seeders', { seeder_class: seederClass });
                    log(`✓ Seeders: ${seedRes.output || 'OK'}`, 'info');

                    const optRes = await post('/pi-deploy/api/optimize');
                    log(`✓ Optimization Caches: ${optRes.output || 'OK'}`, 'info');

                    log('==================================================', 'info');
                    log('🎉 DEPLOYMENT & MIGRATION ERFOLGREICH ABGESCHLOSSEN!', 'info');
                    log('==================================================', 'info');
                } catch (err) {
                    log('❌ Fehler während des automatischen Deployments: ' + err, 'error');
                    throw err;
                }
            });
        }
    </script>

    <!-- DB User Creation Confirmation Modal -->
    <div id="dbUserModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm hidden opacity-0 transition-all duration-200">
        <div class="glass-card rounded-2xl border border-purple-500/30 w-full max-w-md p-6 space-y-5 shadow-2xl glow-pironman transform scale-95 transition-all duration-200">
            <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-400 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-user-slash text-2xl"></i>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>DB-Zugriff fehlgeschlagen</span>
                    </h3>
                    <p class="text-xs text-amber-300 font-medium leading-relaxed">
                        User oder Passwort stimmen nicht; Soll ein User mit diesem Passwort angelegt werden?
                    </p>
                </div>
            </div>

            <div class="bg-slate-950/80 p-3.5 rounded-xl border border-slate-800 space-y-2 font-mono text-xs">
                <div class="flex justify-between items-center text-slate-400">
                    <span>Benutzer:</span>
                    <span id="modalDbUser" class="text-emerald-400 font-bold bg-emerald-950/50 px-2 py-0.5 rounded border border-emerald-800/50"></span>
                </div>
                <div class="flex justify-between items-center text-slate-400">
                    <span>Passwort:</span>
                    <span id="modalDbPass" class="text-purple-300 bg-purple-950/50 px-2 py-0.5 rounded border border-purple-800/50"></span>
                </div>
                <div class="flex justify-between items-center text-slate-400">
                    <span>Datenbank:</span>
                    <span id="modalDbName" class="text-cyan-400 bg-cyan-950/50 px-2 py-0.5 rounded border border-cyan-800/50"></span>
                </div>
                <div class="flex justify-between items-center text-slate-400">
                    <span>Host:</span>
                    <span id="modalDbHost" class="text-slate-300 bg-slate-900 px-2 py-0.5 rounded border border-slate-800"></span>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-2">
                <button onclick="closeDbUserModal(true)" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold border border-slate-700 transition duration-150">
                    Abbrechen
                </button>
                <button onclick="confirmCreateDbUser(event)" id="confirmCreateUserBtn" class="btn-action px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white text-xs font-bold shadow-lg glow-emerald transition flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-xs"></i>
                    <span>OK</span>
                </button>
            </div>
        </div>
    </div>
</body>
</html>
