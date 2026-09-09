# Refactor: Gameday Score con Laravel + MySQL

> **Estado actual (DISI-1):** Bootstrap de Laravel. La PWA original sigue accesible en `/legacy/`.

## 🎯 Objetivo

Migrar **Gameday Score** de una PWA vanilla (JavaScript + IndexedDB) a una aplicación full-stack con:

- **Backend:** Laravel 13 (PHP 8.4) + MySQL
- **Autenticación:** Usuarios registrados gestionan sus propios juegos
- **Persistencia real:** Base de datos MySQL (no más IndexedDB ni localStorage)
- **Juegos públicos:** Un juego puede marcarse como público para que cualquiera vea el avance en vivo sin login
- **Gestión de entidades:** CRUDs para Categorías, Estadios, Equipos, Atletas, Anotadores, Árbitros, con soporte para fotos y logos

## 📂 Estructura del Proyecto

```
gameday-score/
├── app/                    # Lógica Laravel (Models, Controllers, Services)
├── bootstrap/              # Bootstrap del framework (Laravel 11+)
├── config/                 # Configuraciones
├── database/               # Migraciones, factories, seeders
├── public/                 # Document root de Laravel
│   ├── index.php           # Entry point Laravel
│   ├── .htaccess
│   ├── legacy/             # ⚡ PWA ORIGINAL (preservada como legacy)
│   │   ├── index.html      # Accesible en /legacy/
│   │   ├── js/
│   │   ├── css/
│   │   ├── assets/
│   │   ├── manifest.json
│   │   └── sw.js
│   ├── favicon.ico
│   └── robots.txt
├── resources/
│   ├── views/              # Blade templates
│   ├── css/                # Estilos
│   └── js/                 # JavaScript compilado
├── routes/                 # Rutas web y API
├── storage/                # Archivos generados, logs, caché
├── tests/                  # Tests automatizados
├── vendor/                 # Dependencias Composer (no se commitea)
├── .env                    # Variables de entorno (no se commitea)
├── artisan                 # CLI de Laravel
├── composer.json
├── package.json
└── README.md
```

## 🌐 URLs del Sistema

| Ruta                | Descripción                                                  |
|---------------------|--------------------------------------------------------------|
| `/`                 | Nueva app Laravel (próximos tickets: login, dashboard)       |
| `/legacy/`          | PWA original intacta (funciona standalone, sin login)        |

## 🗺️ Tickets Planificados (Roadmap)

| Ticket    | Descripción                                                                                  | Estado   |
|-----------|----------------------------------------------------------------------------------------------|----------|
| **DISI-1**  | Bootstrap Laravel + estructura master/develop + mover PWA a `public/legacy/`                | ✅ Hecho |
| **DISI-2**  | Laravel Breeze (Blade) con UI 100% en español                                              | ✅ Hecho |
| **DISI-3**  | Modelo de datos: User, Game, Category, Stadium, Team, Athlete, Scorekeeper, Referee + migraciones | ✅ Hecho |
| **DISI-4**  | CRUDs base: Categorías y Estadios (sin uploads aún)                                        | ✅ Hecho |
| **DISI-5**  | CRUD de Equipos con logo (Storage + intervention/image si aplica)                          | ✅ Hecho |
| **DISI-6**  | CRUD de Atletas, Anotadores, Árbitros con foto                                             | Pendiente |
| **DISI-7**  | CRUD de Juegos (Game) con relación a teams, stadium, category, scorekeepers, referees      | Pendiente |
| **DISI-8**  | Marcar juego como público: ruta pública sin auth para ver avance en vivo                   | Pendiente |
| **DISI-9**  | UI del Scoreboard nueva (Blade + JS) conectado a la API de Games                           | Pendiente |

## 🔧 Convenciones del Proyecto

- **Frontend en español:** Todos los strings de UI, mensajes, validaciones y emails en español por defecto
- **Backend en inglés:** Clases, métodos, variables, columnas de BD en inglés (siguiendo el laravel-skill)
- **Git workflow:** Par de ramas `feature-master/DISI-N` y `feature-develop/DISI-N` con cherry-pick
- **Tests:** Solo cuando se soliciten explícitamente

## 🔄 Compatibilidad Legacy

La PWA original en `/legacy/` se mantiene 100% funcional. No requiere login, no depende de la base de datos, y sigue usando su propio Service Worker (`/legacy/sw.js`) con scope limitado a `/legacy/`.

Los usuarios que ya tenían la PWA instalada (v1.1.12) verán que la versión legacy sigue funcionando desde el mismo dispositivo, pero las nuevas instalaciones deberán acceder a `/legacy/` explícitamente. La nueva app Laravel será la entrada por defecto en la raíz.
