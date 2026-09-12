# ⚾ Gameday Score — Béisbol & Sófbol

**Gameday Score** es una aplicación web profesional para el control en tiempo real de marcadores, jugadas y estadísticas de béisbol y sófbol. Construida sobre **Laravel 13** + **Alpine.js** + **Tailwind CSS**, pensada para anotadores que necesitan velocidad y precisión en el diamante.

---

## 🌟 Funcionalidades principales

### 🏟️ Gestión integral de partidos
- **Configuración completa**: Equipos Local/Visitante, Estadio, Liga, Torneo, Categoría (Pre-Infantil → Profesional), Fecha, Hora y Reglas.
- **Pizarra en tiempo real** (`/games/{game}/scoreboard`): Carreras, hits, errores, outs, balls, strikes, inning/half, bateador, lanzador, preventa y diamante con corredores.
- **Auto-refresco vía AJAX**: El scoreboard se actualiza sin recargar la página. Las acciones del anotador (ball, strike, out, hit, walk, corredor anotado, sustitución) se reflejan instantáneamente sin esperar al poll automático.
- **Cierre de inning automático**: Al llegar a 3 outs, el medio inning se cierra y se actualiza el scoreboard al nuevo medio.
- **Modal de resumen de inning**: Cuando se cierra un inning, se muestra un modal con Carreras / Hits / Errores del inning cerrado.

### 👥 Roster, lineups y sustituciones
- **Roster por juego**: Agregar atletas, asignar posiciones (P, C, 1B, 2B, 3B, SS, LF, CF, RF), número de jersey.
- **Drag & Drop del lineup**: Reordenar el orden al bate con drag-and-drop nativo (HTML5) y guardado vía `PATCH /games/{game}/lineup/order`.
- **Sustituciones (Pitcher / Batter / Pinch Runner)** vía modal con confirmación de identidad y base destino para los corredores.

### ⚾ Sistema de pitcheo y jugadas
La app cubre todas las jugadas del béisbol moderno, registradas en el modelo `Play` con tipos y subtipos especializados:

- **Pitcheo** (ball, strike mirando / swinging / foul tip, foul, hit single/double/triple/HR/HR de pierna, out fly/line/ground/strikeout/popup, walk, HBP, bunt, balk).
- **Gestión de corredores en el terreno** (DISI-20): Click en un corredor del diamante abre un modal **"Gestionar corredor"** con acciones contextuales:
  - Avanza a siguiente base (1B→2B, 2B→3B, 3B anota).
  - Robo de base (steal).
  - Avanza por error / wild pitch / passed ball / OBS (obstrucción).
  - Anota con RBI / sin RBI.
  - Out @ 2da / Out @ 3ra / Caught stealing / Viraje (pickoff).
  - Sustituir corredor (pinch runner).
- **Walk con bases loaded**: Al registrar un walk con todas las bases ocupadas, el motor fuerza al corredor de 3B a anotar (1 carrera).

### 📊 Box score inning-by-inning
- Vista `/games/{game}/box-score` con grid inning × equipo (Carreras / Hits / Errores).
- **Asignación de pitchers**: Pitcher ganador, perdedor y salvamento.
- **MVP del juego**: Selección del atleta más valioso.
- **Compartir como imagen 1:1** (1080×1080 px) generada con **html2canvas** y descargable / compartible vía **Web Share API** (ideal para Instagram / WhatsApp / Twitter).

### 🏛️ Gestión organizacional
- **Ligas** (5 precargadas: FVB, Criollitos, PLBV, PONY, NCS).
- **Torneos** por liga.
- **Equipos** (pertenecen a una Liga).
- **Categorías** (pertenecen a un Equipo; un equipo puede tener N categorías — Pre-Infantil, Infantil, Juvenil, etc.).
- **Atletas** (pertenecen a un Equipo + Categoría).
- **Estadios**, **Anotadores (Scorekeepers)** y **Árbitros (Referees)**.
- **Breadcrumb Liga → Torneo → Categoría → Equipo** en la navegación del scoreboard.

### 🔐 Permisología con `spatie/laravel-permission`
- **Roles**: `admin` (gestiona todo) y `anotador` (solo ve + anota juegos donde está asignado como scorekeeper).
- **`GamePolicy`** con abilities `view`, `update`, `delete`, `score`, `create`.
- **Middleware `EnsureUserIsAdmin`** registrado como alias `admin`.
- **Asignación por juego**: Un anotador es un User con rol `anotador` que tiene un `Scorekeeper` asociado; ese `Scorekeeper` se asigna al juego vía tabla pivote `game_scorekeeper`.

### 👤 Mi perfil y seguridad
- **Actualizar datos personales** (nombre, email, foto de perfil).
- **Cambiar contraseña**.
- **2FA TOTP** (Google Authenticator) con `pragmarx/google2fa-laravel` + QR generado con `bacon/bacon-qr-code`.
- **Códigos de recuperación** de 8 códigos.
- **Challenge obligatorio en cada login**: Middleware `EnsureTwoFactorChallenged` (alias `2fa.challenge`) protege todas las rutas autenticadas. Si tiene 2FA activado y no ha pasado el challenge en la sesión actual, se redirige a `/two-factor-challenge`.
- **Auto-submit al completar 6 dígitos** en el challenge (UX fluida con Alpine).

### 📺 Vista pública del juego
- Ruta `/juego/publico/{token}` (sin autenticación) para compartir el estado del juego con espectadores vía link público + token.

---

## 🛠️ Stack técnico

### Backend
- **PHP 8.4** + **Laravel 13.17** (PHP `^8.3` declarado en `composer.json`).
- **spatie/laravel-permission** (roles + abilities).
- **pragmarx/google2fa-laravel** + **bacon/bacon-qr-code** (TOTP 2FA con QR).
- **laravel/breeze** (auth scaffolding base).
- **MySQL** como base de datos (configurable vía `.env`).
- **Migrations versionadas** en `database/migrations/` (incluyendo bootstrap inicial + DISI-N: ligas, torneos, perfil, 2FA, attributions de pitchers/MVP, jerarquía equipo→liga, etc.).

### Frontend
- **Vite 8** + **Tailwind CSS 3** + **Alpine.js 3**.
- **`@tailwindcss/vite`** plugin (Vite 8 con Node 22.11 emite warning pero funciona).
- **Sin React/Vue**: Toda la interactividad del scoreboard es **Alpine.js** puro (componentes declarativos `x-data`, `x-show`, `x-on`, `x-text`, `x-model`).
- **`public/js/alpine-stores.js`** contiene los stores Alpine (`scoreboardApp`, `toastStack`, etc.) cargados vía `<script defer>`.
- **CSS bundle** generado por Vite en `public/build/assets/app-*.css` (con hash para cache-busting).

### Arquitectura del scoreboard
- Vista: `resources/views/games/scoreboard.blade.php` (Blade + Alpine, ~1300 líneas).
- Backend: `app/Http/Controllers/ScoreboardController.php` + `PlayController.php` + `app/Services/GameplayEngine.php` (motor de jugadas y reglas del béisbol).
- API interna: 12+ endpoints JSON/AJAX en `routes/web.php` para polleo, pitch, sustituciones, gestión de corredores, cierre de inning, etc.

---

## 🚀 Instalación

### Requisitos
- PHP **8.3+** (probado en 8.4.10)
- Composer **2.x**
- Node.js **20.19+** o **22.12+** (Vite 8 rechaza versiones anteriores; el repo emite warning con Node 22.11)
- MySQL **5.7+** o MariaDB equivalente

### Pasos

```bash
# 1. Instalar dependencias
composer install
npm install

# 2. Configurar entorno
cp .env.example .env
php artisan key:generate

# 3. Configurar base de datos en .env
#    DB_CONNECTION=mysql
#    DB_DATABASE=gameday_score
#    DB_USERNAME=root
#    DB_PASSWORD=

# 4. Ejecutar migraciones + seeders (roles y ligas precargadas)
php artisan migrate --seed

# 5. Compilar assets de frontend
npm run build

# 6. Servir
php artisan serve
```

Abrir en navegador: `http://localhost:8000` (o el puerto que uses para `php artisan serve`).

---

## 📂 Estructura

```
app/
├── Http/Controllers/        # 17 controllers (CRUD + scoreboard + plays + profile + 2FA)
├── Models/                  # 11 models (User, Game, Play, Team, Athlete, League, Tournament, etc.)
├── Services/
│   └── GameplayEngine.php   # Motor de jugadas (hits, walks, outs, runner management)
└── Http/Middleware/
    ├── EnsureUserIsAdmin.php
    └── EnsureTwoFactorChallenged.php

database/
├── migrations/              # 26 migraciones
└── seeders/
    ├── RoleSeeder.php       # Roles admin + anotador
    ├── LeagueSeeder.php     # 5 ligas precargadas
    └── DatabaseSeeder.php

resources/
├── views/
│   ├── games/               # scoreboard, box-score, roster, live
│   ├── profile/             # Mi perfil + secciones 2FA
│   └── auth/                # Login, register, 2FA challenge
└── js/
    └── app.js               # Entry point Alpine

public/
├── js/alpine-stores.js      # Componentes Alpine (scoreboardApp, toastStack)
└── build/assets/            # CSS + JS bundleados por Vite
```

---

## 🔑 Roles y permisos

| Rol | Acceso |
|---|---|
| `admin` | Acceso total: gestiona ligas, torneos, equipos, atletas, usuarios, roles. Puede anotar cualquier juego. |
| `anotador` | Solo ve y anota en juegos donde está asignado como **scorekeeper** (tabla `game_scorekeeper`). No tiene acceso a gestión organizacional. |

### Endpoints sensibles (requieren rol/permiso)
- `POST /games/{game}/pitch` → requiere `score` (GamePolicy).
- `POST /games/{game}/runner/action` → requiere `score`.
- `POST /games/{game}/substitute` → requiere `score`.
- CRUD de ligas/torneos/equipos/atletas → requiere rol `admin` (alias middleware `admin`).

---

## 🧪 Scripts de utilidad (debug)

En `scripts/tmp-debug/` hay scripts PHP de línea de comandos que validan flujos completos vía HTTP (útil para regression tests sin browser):

- `dbg-disi20-runner.php` — 11 escenarios para gestión de corredores en el terreno.
- `dbg-disi21-pitch-poll.php` — Reproduce el bug del scoreboard que no reflejaba out/strike/ball.
- `dbg-walk-bases-loaded.php` — Verifica que walk con bases loaded anota correctamente.
- `dbg-hit-bases-loaded.php` — Verifica hit con bases loaded anota correctamente.

Cada script se ejecuta con `php scripts/tmp-debug/<nombre>.php` y deja el juego 2 limpio al finalizar.

---

## 🧾 Historial de versiones (DISI-N commits)

| Ticket | Resumen |
|---|---|
| DISI-1 | Bootstrap Laravel 13 + estructura master/develop + PWA legacy en `public/legacy/` |
| DISI-12 | Scoreboard moderno (Fases 1-5) con panel B-S-O, diamante, modales de pitcheo/bateo |
| DISI-13 | CRUD de Ligas + Torneos + breadcrumb |
| DISI-14/14b | Jerarquía `team→league` + `category→team` + `athlete→{team,category}` |
| DISI-15 | Permisología con `spatie/laravel-permission` + roles `admin` + `anotador` |
| DISI-16 | CRUD de roles + Mi perfil + 2FA TOTP (enable, confirm, recovery codes, disable) |
| DISI-16b/16c | Challenge 2FA obligatorio en login + auto-submit + dashboard protegido |
| DISI-17 | Box score inning-by-inning con R/H/E + pitchers ganador/perdedor/save + MVP + compartir como imagen 1080×1080 |
| DISI-17b..h | Iteraciones de estilo del box score (nombres completos, flex layout, sin scroll, html2canvas inline) |
| DISI-18 | Fix walk: bases loaded ahora anota al corredor de 3B |
| DISI-19 | Header del scoreboard con grid 3 columnas centrado |
| DISI-20 | Gestión de corredores en el terreno con modal "Gestionar corredor" (robo, wild pitch, OBS, anotada, out @ 2da/3ra, pickoff, sustitución) |
| DISI-21 | Fix: out/strike/ball se reflejan inmediatamente (no esperar al poll) |
| DISI-21b | Pestaña PITCHEo reordenada + bug colateral renderBase (sin OPCIONES duplicados) |
| DISI-21c/d/e | Layouts de tabs: PITCHEo 1×4, BATEO 1×4 + HR de pierna, EXTRAS 2×4 |

---

## 📝 Licencia

MIT — ver `composer.json`.

---

*Desarrollado para anotadores que buscan precisión y velocidad en el diamante.*
