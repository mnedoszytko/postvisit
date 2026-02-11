import { test, expect } from '@playwright/test';

test.describe('Authentication flows', () => {
    test('landing page renders with brand and navigation', async ({ page }) => {
        await page.goto('/');
        await page.waitForLoadState('networkidle');
        // Landing page or login page should show PostVisit.ai branding
        await expect(page.getByText('PostVisit.ai').first()).toBeVisible();
    });

    test('login page renders with email and password fields', async ({ page }) => {
        await page.goto('/login');
        await page.waitForLoadState('networkidle');
        await expect(page.getByLabel('Email')).toBeVisible();
        await expect(page.getByLabel('Password')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Sign In', exact: true })).toBeVisible();
    });

    test('login page has demo access buttons', async ({ page }) => {
        await page.goto('/login');
        await page.waitForLoadState('networkidle');
        await expect(page.getByRole('button', { name: 'Sign in as Patient' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Sign in as Doctor' })).toBeVisible();
    });

    test('login with invalid credentials shows error', async ({ page }) => {
        await page.goto('/login');
        await page.waitForLoadState('networkidle');
        await page.getByLabel('Email').fill('invalid@example.com');
        await page.getByLabel('Password').fill('wrongpassword');
        await page.getByRole('button', { name: 'Sign In', exact: true }).click();

        // Wait for error message to appear
        await expect(page.getByText('Invalid credentials. Please try again.')).toBeVisible({ timeout: 5000 });
    });

    test('unauthenticated user redirected to login from protected route', async ({ page }) => {
        await page.goto('/profile');
        await page.waitForLoadState('networkidle');
        // Should be redirected to login
        await expect(page.getByLabel('Email')).toBeVisible({ timeout: 10000 });
    });

    test('demo access section is visible below login form', async ({ page }) => {
        await page.goto('/login');
        await page.waitForLoadState('networkidle');
        await expect(page.getByText('Demo Access')).toBeVisible();
    });
});
