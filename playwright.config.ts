import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/playwright',
    timeout: 30000,
    retries: 0,
    use: {
        baseURL: 'http://127.0.0.1:8000',
        headless: true,
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { browserName: 'chromium' },
        },
    ],
    webServer: {
        command: 'php artisan serve --port=8000',
        port: 8000,
        reuseExistingServer: true,
        timeout: 10000,
    },
});
