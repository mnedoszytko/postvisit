import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/playwright/showcase',
    timeout: 60000,
    retries: 0,
    workers: 1, // Sequential — one video at a time
    use: {
        baseURL: 'http://127.0.0.1:8000',
        headless: true,
        viewport: { width: 1280, height: 720 },
        video: {
            mode: 'on',
            size: { width: 1280, height: 720 },
        },
        launchOptions: {
            slowMo: 150, // Slow down for visual clarity
        },
    },
    projects: [
        {
            name: 'showcase',
            use: { browserName: 'chromium' },
        },
    ],
    webServer: {
        command: 'php artisan serve --port=8000',
        port: 8000,
        reuseExistingServer: true,
        timeout: 10000,
    },
    outputDir: './tests/playwright/showcase/results',
});
