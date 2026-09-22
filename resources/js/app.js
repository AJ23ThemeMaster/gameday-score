import '../../public/js/alpine-stores.js';
import Alpine from 'alpinejs';

/*
  Material Symbols Outlined auto-hospedado via @fontsource.

  Lo importamos desde JS (no desde app.css) porque Vite resuelve imports
  de node_modules en JS pero no en CSS sin el plugin postcss-import.

  Bunny Fonts solo sirve subset latin (U+0000-00FF) y todos los glyphs
  viven en Private Use Area (U+E000+), por eso la fuente se auto-hospeda
  via @fontsource que incluye los iconos completos.
*/
import '@fontsource/material-symbols-outlined/400.css';

/*
  SwalHelper: wrapper de SweetAlert2 con tema dark WattVision.
  Provee SwalHelper.alert / confirm / toast / loading / apiFetch /
  bindFormHandlers / overrideNativeDialogs.
  Importar el modulo ejecuta el bootstrap (bindFormHandlers + override
  window.alert/confirm) cuando el DOM esta listo.
*/
import './swal-helper.js';

window.Alpine = Alpine;

Alpine.start();