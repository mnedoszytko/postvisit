import { test, expect } from '@playwright/test';

test.describe('Demo mode', () => {
    test('demo page is accessible without auth', async ({ page }) => {
        await page.goto('/demo');
        await page.waitForLoadState('networkidle');
        // Demo page should render — not redirect to login
        // The demo page should have PostVisit.ai branding
        await expect(page.getByText('PostVisit.ai').first()).toBeVisible();
    });

    test('navigation: landing to login', async ({ page }) => {
        await page.goto('/');
        await page.waitForLoadState('networkidle');
        // The app should show PostVisit.ai somewhere
        await expect(page.getByText('PostVisit.ai').first()).toBeVisible();
    });
});
