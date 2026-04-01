# ⚾ Gameday Score - Beisbol & Sófbol

**Gameday Score** es una Progressive Web App (PWA) de alto rendimiento diseñada para el control profesional de marcadores y estadísticas de béisbol y sófbol. Esta documentación detalla las funcionalidades actuales y la arquitectura técnica del sistema.

## 🌟 Funcionalidades Principales

### 1. Gestión Integral de Partidos
- **Configuración Detallada**: Inicia juegos personalizando equipos (Local/Visitante), Estadio, Categoría (Pre-Infantil hasta Profesional), Fecha, Hora y Reglas de juego.
- **Pizarra Dinámica (Scoreboard)**: Interfaz de tiempo real que rastrea carreras inning por inning.
- **Control de Inning Inteligente**: Cambio automático entre "Parte Alta" y "Parte Baja", con indicadores visuales de turno al bate.
- **Panel B-S-O & Diamante**: Registro rápido de Bolas, Strikes y Outs con representación gráfica de corredores en bases.
- **Regla del Nocaut (Mercy Rule)**: Validación automática de diferencia de carreras según la categoría para finalizar juegos de forma reglamentaria.

### 2. Roster 2.0 y Alineaciones 👥
- **Estructura Dual**: Separación clara entre **Titulares (Lineup)** y **Sustituciones (Suplentes)**.
- **Posiciones Pre-definidas**: Inicialización automática con las 9 posiciones estándar (P, C, 1B, 2B, 3B, SS, LF, CF, RF) para agilizar el proceso.
- **Drag & Drop Nativo**: Reordenamiento intuitivo de jugadores dentro del lineup o entre secciones mediante arrastrar y soltar.
- **Validación de Integridad**: El sistema asegura que el roster cuente con al menos 9 nombres antes de permitir el guardado, garantizando un registro completo.

### 3. Control de Pitcheo Profesional 📈
- **Seguimiento Individual**: Rastreo de conteo de lanzamientos por **lanzador específico**, no solo por equipo.
- **Gestión de Relevos**: Al realizar un cambio de lanzador, el sistema solicita el nombre del nuevo atleta y reinicia el conteo automáticamente.
- **Alertas de Límite**: Notificaciones visuales inmediatas cuando un lanzador alcanza el límite de pitcheos configurado según las reglas de la categoría.

### 4. Histórico y Persistencia 📅
- **Autoguardado Seguro**: Persistencia total del estado del juego en `localStorage`. Si el navegador se cierra o refresca, el partido continúa exactamente donde quedó.
- **Archivo de Juegos**: Sección de "Juegos Pasados" para revisar resultados históricos o reanudar partidos pausados.
- **Gestión de Datos**: Opciones para borrar partidos individuales o limpiar el historial completo desde los ajustes avanzados.

### 5. Personalización y UX ⚙️
- **Temas Dinámicos**: Modo Oscuro (ahorro de batería/noche) y Modo Claro (alta visibilidad bajo el sol) con estética premium.
- **Visualización Flexible**: Configura la visibilidad de columnas de Hits (H) y Errores (E) según la necesidad del torneo.
- **Exportación Social**: Motor de generación de imágenes que crea una captura limpia del marcador (sin botones de control) optimizada para compartir en WhatsApp, Instagram o Twitter.

## 🚀 Capacidades Técnicas

- **PWA (Progressive Web App)**: 100% instalable en Android, iOS y Escritorio. Proporciona una experiencia de aplicación nativa sin pasar por tiendas oficiales.
- **Arquitectura Offline-First**: Utiliza un **Service Worker (`sw.js`)** con caché semántico. Una vez cargada, la app funciona sin conexión a internet, ideal para estadios con cobertura limitada.
- **Rendimiento Óptimo**: Construida con Vanilla JS y CSS moderno, garantizando tiempos de carga instantáneos y animaciones fluidas a 60fps.
- **Sistema de Actualizaciones**: Notificaciones automáticas (SweetAlert2) cuando hay una nueva versión disponible (v1.1.8+), permitiendo al usuario actualizar con un solo clic.

---
*Desarrollado para anotadores que buscan precisión y velocidad en el diamante.*
