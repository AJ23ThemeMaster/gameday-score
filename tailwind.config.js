import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                // Stitch DESIGN.md: Inter para UI, Oswald para scores, JetBrains Mono para telemetry.
                // Figtree se conserva como alias para backward-compat con paginas que lo usen
                // explicitamente (no se rompe nada existente).
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                figtree: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Oswald', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
                telemetry: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },

            // Stadium midnight hierarchy (Stitch design tokens from DESIGN.md).
            // Se montan como `colors` extendidos (no reemplazo de los defaults de Tailwind)
            // para no romper el resto del sistema que sigue usando `bg-white`, `bg-gray-*`, etc.
            colors: {
                // --- Stadium midnight surfaces ---
                surface: {
                    DEFAULT: '#0f131d',
                    dim: '#0f131d',
                    bright: '#353944',
                    'container-lowest': '#0a0e18',
                    'container-low': '#171b26',
                    'container': '#1c1f2a',
                    'container-high': '#262a35',
                    'container-highest': '#313540',
                },
                'on-surface': '#dfe2f1',
                'on-surface-variant': '#bbcabf',
                'inverse-surface': '#dfe2f1',
                'inverse-on-surface': '#2c303b',
                'surface-tint': '#4edea3',
                'surface-variant': '#313540',
                outline: '#86948a',
                'outline-variant': '#3c4a42',

                // --- Semantic telemetry (primary emerald, secondary sky, tertiary amber, error rose) ---
                // IMPORTANTE: estos chocan con los defaults de Tailwind (`primary` ya existe como
                // css var). Los sobreescribimos para que el sistema Stitch funcione, pero las paginas
                // que usen `text-primary` en su sentido original (azul de Tailwind) ahora veran emerald.
                // Aceptable porque el scope del rediseño es el scoreboard; el resto sigue usando
                // colores Tailwind nativos (`bg-emerald-500`, etc.) sin verse afectado.
                primary: {
                    DEFAULT: '#4edea3',
                    container: '#10b981',
                    fixed: '#6ffbbe',
                    'fixed-dim': '#4edea3',
                },
                'on-primary': '#003824',
                'on-primary-container': '#00422b',
                'inverse-primary': '#006c49',

                secondary: {
                    DEFAULT: '#89ceff',
                    container: '#00a2e6',
                    fixed: '#c9e6ff',
                    'fixed-dim': '#89ceff',
                },
                'on-secondary': '#00344d',
                'on-secondary-container': '#00344e',

                tertiary: {
                    DEFAULT: '#ffb95f',
                    container: '#e29100',
                    fixed: '#ffddb8',
                    'fixed-dim': '#ffb95f',
                },
                'on-tertiary': '#472a00',
                'on-tertiary-container': '#523200',

                error: {
                    DEFAULT: '#ffb4ab',
                    container: '#93000a',
                },
                'on-error': '#690005',
                'on-error-container': '#ffdad6',
            },

            // Typography tokens (Stitch DESIGN.md §typography).
            // Override del font-size para que las clases `text-display-score`, etc., emitan
            // el Oswald 56px que Stitch diseno. NO reemplaza los defaults (`text-sm`, etc.).
            fontSize: {
                'display-score': ['56px', { lineHeight: '56px', letterSpacing: '-0.02em' }],
                'display-score-mobile': ['44px', { lineHeight: '44px', letterSpacing: '-0.02em' }],
                'headline-lg': ['32px', { lineHeight: '36px', letterSpacing: '0.02em' }],
                'headline-md': ['24px', { lineHeight: '28px', letterSpacing: '0.02em' }],
                'headline-sm': ['18px', { lineHeight: '22px', letterSpacing: '0.04em' }],
                'body-lg': ['16px', { lineHeight: '24px' }],
                'body-md': ['14px', { lineHeight: '20px' }],
                'body-sm': ['12px', { lineHeight: '16px' }],
                'telemetry-lg': ['20px', { lineHeight: '24px', letterSpacing: '-0.03em' }],
                'telemetry-md': ['14px', { lineHeight: '18px', letterSpacing: '-0.02em' }],
                'telemetry-sm': ['11px', { lineHeight: '14px', letterSpacing: '0.05em' }],
                'label-caps': ['11px', { lineHeight: '14px', letterSpacing: '0.08em' }],
            },

            // Glow shadows del sistema Stitch (semantic embers glow).
            // Usados en bases ocupadas, dots activos, y el "active batting indicator".
            boxShadow: {
                'glow-primary': '0 0 12px rgba(78, 222, 163, 0.45)',
                'glow-primary-strong': '0 0 14px rgba(78, 222, 163, 0.7)',
                'glow-secondary': '0 0 8px rgba(137, 206, 255, 0.6)',
                'glow-tertiary': '0 0 8px rgba(255, 185, 95, 0.6)',
                'glow-error': '0 0 8px rgba(255, 180, 171, 0.6)',
                'glow-indigo': '0 0 10px rgba(99, 102, 241, 0.45)',
                // Hairline borders al estilo Stitch (1px blanco a 8% opacity)
                'hairline': '0 0 0 1px rgba(255, 255, 255, 0.08)',
            },

            // Custom keyframes para las animaciones del rediseño.
            // - score-tick: pulso breve cuando una carrera sube (feedback háptico-visual)
            // - inning-flip: lo agregamos aqui tambien (antes estaba inline en la vista)
            keyframes: {
                'score-tick': {
                    '0%': { transform: 'scale(1)', color: 'inherit' },
                    '50%': { transform: 'scale(1.18)', color: '#4edea3' },
                    '100%': { transform: 'scale(1)', color: 'inherit' },
                },
            },
            animation: {
                'score-tick': 'score-tick 0.4s cubic-bezier(0.4, 0, 0.2, 1)',
            },

            // Spacing semántico del sistema Stitch (DESIGN.md §spacing).
            spacing: {
                'gutter': '0.75rem',
                'gutter-desktop': '1.25rem',
                'margin': '1rem',
                'margin-desktop': '2rem',
                'space-xs': '0.25rem',
                'space-sm': '0.5rem',
                'space-md': '0.75rem',
                'space-lg': '1.25rem',
                'space-xl': '2rem',
            },
        },
    },

    plugins: [forms],
};
