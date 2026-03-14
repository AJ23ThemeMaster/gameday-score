# ⚾ Gameday Score - Beisbol

Esta es la documentación oficial sobre las funcionalidades y características técnicas implementadas en la aplicación de control de marcadores (**Scoreboard**) de Gameday Score.

## 🌟 Funcionalidades Principales

### 1. Gestión Integral de Partidos
- **Creación Múltiple**: Inicia nuevos juegos asignando nombres a los equipos Local y Visitante, Estadio, Categoría de juego (Pre-Infantil, Infantil, etc.), Fecha y Hora.
- **Pizarra en Vivo (Scoreboard)**: Interfaz dinámica que muestra el marcador carrera por carrera organizada por *Innings* (episodios).
- **Control de Inning y Turno**: Indicador inteligente ("Parte Alta / Parte Baja" y "Batea X Equipo") que se actualiza al avanzar la jugada o completar los Outs.
- **Panel Interactivo (B-S-O)**: Botones táctiles para registrar de forma rápida **Bolas (B)**, **Strikes (S)** y **Outs (O)**.
- **Bases Visuales**: Representación gráfica del diamante con bases interactivas que se iluminan al ser ocupadas.
- **Totalización Automática**: El sistema suma en tiempo real las Carreras (C) general por equipo. Los **Hits (H)** y **Errores (E)** se calculan y se ingresan de forma manual o automática de acuerdo a la configuración.

### 2. Roster y Alineaciones 👥
- **Gestión por Equipo**: Cada equipo cuenta con su propia plantilla de jugadores o "Lineup".
- **Datos Detallados**: Permite ingresar Orden al bate, Posición defensiva (1B, SS, CF, etc.), Número de dorsal y Nombre de cada jugador.
- **Configurable**: El manejo de Roster puede ser desactivado globalmente para juegos más rápidos y casuales.

### 3. Histórico de Juegos 📅
- **Autoguardado Persistente**: Si se cierra el navegador por accidente, al volver a abrir la app el juego seguirá en la misma cuenta de bolas, strikes y carreras exactamente donde se dejó.
- **Gestión de Archivo**: Sección dedicada a "Juegos Pasados", desde donde puedes revisar resultados o **reanudar** partidos que fueron pausados previamente.
- **Borrado Inteligente**: Puedes borrar partidos históricos si habilitas la opción secreta desde Configuración, con opciones masivas de selección o borrado individual.

### 4. Personalización y Ajustes ⚙️
- **Innings Personalizables**: Escoge la longitud del partido por defecto (ej. a 6, 7 o 9 episodios).
- **Temas (Modo Oscuro / Claro)**: Cambia a voluntad la apariencia del sistema. El tema Oscuro maximiza el ahorro de batería, mientras que el tema Claro ofrece máxima legibilidad bajo el sol durante los juegos diurnos.
- **Visibilidad Opcional**: Puedes escoger mostrar u ocultar libremente las columnas de "Hits" y "Errores".

### 5. Compartir y Exportar 📸
- **Motor Fotográfico Integrado**: Botón para exportar el marcador actual como una imagen de alta calidad, perfecta e inmaculada sin los botones de control intrusivos.
- **Web Share nativo**: Al capturar, la app lanza la ventana nativa de tu celular (WhatsApp, Instagram, Twitter) para presumir el resultado, incluyendo el fondo temático que hayas elegido.

## 🚀 Capacidades Técnicas Avanzadas

- **PWA (Progressive Web App)**: La aplicación incluye diseño de íconos adaptativos y `manifest.json`. Es **100% Instalable** de forma paralela a cualquier App de la tienda oficial (apareciendo en la pantalla principal del dispositivo) sin requerir paso por tiendas de aplicaciones.
- **Soporte Offline Robusto**: Incorpora un **Service Worker (`sw.js`)** y caché semántico (Semantic Versioning), el cual descarga la app a la memoria. Una vez instalada, funcionará a la velocidad de la luz en condiciones donde la conexión a internet sea inestable o nula dentro del campo deportivo.
- **Almacenamiento Local Silencioso**: Toda la persistencia de datos descansa sobre una estructura optimizada conectada permanentemente a `localStorage`, lo que significa que el consumo de datos es $0.
- **Smart Updates & Versioning**: 
  - **Detección Automática Silenciosa**: El Service Worker rastrea proactivamente los atributos del proyecto base (`sw.js`). En cuanto se efectúa un nuevo despliegue de versión por el desarrollador, emite una notificación Push local al celular del usuario (SweetAlert2) para que aplique y recargue el nuevo parche instantáneamente.
  - **Búsqueda Forzada de Actualizaciones**: Integración de un módulo informativo "Acerca de la App" en la ventana de Configuración. En este panel, no solo se expone la versión semántica local instalada (Ej. `v1.0.1`), sino que se dispone de un botón dedicado para **Buscar Novedades**. Dicho botón elude los tiempos de respuesta del caché del sistema e interroga en tiempo real al servidor en busca de un nuevo Service Worker, garantizando estar siempre al día.
