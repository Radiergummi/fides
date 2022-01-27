module.exports = {
    content: [ './templates/**/*.twig', './assets/**/*.{js,jsm}' ],
    theme:   {
        extend: {
            fontFamily: {
                sans: [
                    'Outfit',
                    'ui-sans-serif',
                    'system-ui',
                    '-apple-system',
                    'BlinkMacSystemFont',
                    'Segoe UI',
                    'Roboto',
                    'Helvetica Neue',
                    'Arial',
                    'Noto Sans',
                    'sans-serif',
                    'Apple Color Emoji',
                    'Segoe UI Emoji',
                    'Segoe UI Symbol',
                    'Noto Color Emoji',
                ],
            },

            brightness: {
                1000: '100',
            },
        },
    },
    plugins: [
        require( '@tailwindcss/forms' ),
    ],
};
