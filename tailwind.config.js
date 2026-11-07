import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * Brand colours come from the public site (resources/views/welcome.blade.php):
 * ink/dark, cream/paper, line, sage. The default gray, green and indigo ramps are
 * remapped to them, so every existing view picks up the brand without class edits.
 */
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
                // Same stack as the public site (no web font is loaded there either).
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                heading: ['"Inter Tight"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: '#1f2623',
                dark: '#1a1f1c',
                cream: '#f7f4ee',
                paper: '#fbf9f5',
                line: '#e0d9cc',
                sage: { DEFAULT: '#4a6e4e', light: '#a9bfa6', bg: '#e3eadf' },
                sand: '#d4c0ae',
                gray: {
                    50: '#fbf9f5',
                    100: '#f1ede4',
                    200: '#e0d9cc',
                    300: '#d2cabb',
                    400: '#8a918b',
                    500: '#5b635d',
                    600: '#454d47',
                    700: '#343c37',
                    800: '#1f2623',
                    900: '#1a1f1c',
                },
                green: {
                    50: '#e3eadf',
                    100: '#d3dfce',
                    700: '#3a5a3e',
                    800: '#2f4a33',
                },
                indigo: {
                    50: '#e3eadf',
                    100: '#d3dfce',
                    400: '#a9bfa6',
                    500: '#4a6e4e',
                    700: '#3a5a3e',
                    800: '#2f4a33',
                },
            },
        },
    },

    plugins: [forms],
};
