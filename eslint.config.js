import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';

export default [
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{js,ts,vue}'],
        languageOptions: {
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
                setInterval: 'readonly',
                clearInterval: 'readonly',
                fetch: 'readonly',
                URL: 'readonly',
                FormData: 'readonly',
                Blob: 'readonly',
                File: 'readonly',
                FileReader: 'readonly',
                HTMLElement: 'readonly',
                Event: 'readonly',
                EventSource: 'readonly',
                MediaRecorder: 'readonly',
                navigator: 'readonly',
                Audio: 'readonly',
                AbortController: 'readonly',
                requestAnimationFrame: 'readonly',
                IntersectionObserver: 'readonly',
                ResizeObserver: 'readonly',
                alert: 'readonly',
                confirm: 'readonly',
                TextDecoder: 'readonly',
            },
        },
        rules: {
            'no-undef': 'off',
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
            'vue/multi-word-component-names': 'off',
            'vue/no-v-html': 'off',
        },
    },
    {
        ignores: ['public/', 'vendor/', 'node_modules/', '*.config.js'],
    },
];
