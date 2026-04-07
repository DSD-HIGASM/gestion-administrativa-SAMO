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
            colors: {
                'pba-magenta': '#e81f76', // Rosa institucional
                'pba-blue': '#417099',    // Azul institucional
                'pba-cyan': '#00AEC3',    // Celeste institucional
            },
            fontFamily: {
                'sans': ['Roboto', ...defaultTheme.fontFamily.sans], // Secundaria
                'pba': ['Encode Sans', 'sans-serif'],                // Principal
            },
        },
    },

    plugins: [forms],
};
