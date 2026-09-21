import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // WattVision: tipografia del sistema de monitoreo electrico.
                // Aplicada solo al dashboard (feature/style/wattvision).
                inter: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },

            // WattVision palette (DESIGN.md §2).
            // Prefijo `wv-` para no chocar con defaults de Tailwind.
            // Aplicada SOLO al dashboard via dashboard.blade.php.
            colors: {
                wv: {
                    bg: '#121212',
                    surface: '#1E1E1E',
                    'surface-hover': '#252525',
                    border: '#2C2C2E',
                    text: '#FFFFFF',
                    'text-secondary': '#98989D',
                    accent: '#00E5FF',
                    'accent-hover': '#00B8CC',
                    alert: '#FF453A',
                    success: '#32D74B',
                    'alert-bg': '#3A1C1C',
                },
            },

            // Card radius (DESIGN.md §3 — 16px).
            borderRadius: {
                card: '16px',
            },

            // Typography tokens del DESIGN.md §4.
            fontSize: {
                kpi: ['32px', { lineHeight: '40px', fontWeight: '700', letterSpacing: '-0.02em' }],
                'h-wv': ['24px', { lineHeight: '32px', fontWeight: '600' }],
            },
        },
    },

    plugins: [forms],
};

