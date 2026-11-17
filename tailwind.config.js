import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Design tokens sampled from docs/design/reference (see docs/design/README.md).
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
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#f3f8ff', // card / form surfaces
                    100: '#d9e0fb', // badges, pills
                    200: '#b6c3f6',
                    300: '#94a7f3',
                    400: '#738def',
                    500: '#4567ed',
                    600: '#3c5fec', // primary buttons
                    700: '#2641bf', // login panel, badge text
                    800: '#1c34a6',
                    900: '#152c8c', // navbar, footer, checkout stepper
                    950: '#0d1e57', // top bar
                },
                ink: '#09101e',
                warning: '#ffbb00', // countdown card
                danger: '#dc2626', // Sold Out (AA on white)
            },
            borderRadius: {
                card: '0.75rem',
            },
            boxShadow: {
                card: '0 1px 3px 0 rgb(13 30 87 / 0.06), 0 1px 2px -1px rgb(13 30 87 / 0.06)',
            },
        },
    },

    plugins: [forms],
};
