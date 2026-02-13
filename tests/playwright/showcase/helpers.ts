import { Page, expect } from '@playwright/test';

/**
 * Login as demo patient via the UI login page.
 * Uses the "Sign in as Patient" demo button.
 */
export async function loginAsPatient(page: Page) {
    await page.goto('/login');
    await page.waitForLoadState('networkidle');
    await page.getByRole('button', { name: 'Sign in as Patient' }).click();
    // Wait for redirect to profile
    await page.waitForURL('**/profile', { timeout: 10000 });
    await page.waitForLoadState('networkidle');
}

/**
 * Login as demo doctor via the UI login page.
 * Uses the "Sign in as Doctor" demo button.
 */
export async function loginAsDoctor(page: Page) {
    await page.goto('/login');
    await page.waitForLoadState('networkidle');
    await page.getByRole('button', { name: 'Sign in as Doctor' }).click();
    // Wait for redirect to doctor dashboard
    await page.waitForURL('**/doctor', { timeout: 10000 });
    await page.waitForLoadState('networkidle');
}

/**
 * Login as a specific demo scenario patient.
 * Navigates to scenario picker, selects scenario, waits for redirect.
 */
export async function loginAsScenario(page: Page, scenarioName: string) {
    await page.goto('/demo/scenarios');
    await page.waitForLoadState('networkidle');
    await pause(page, 1000);

    // Click on the scenario card
    const card = page.getByText(scenarioName).first();
    if (await card.isVisible()) {
        await card.click();
        await page.waitForLoadState('networkidle');
        await pause(page, 1000);
    }
}

/** Pause for visual effect in video recording. */
export async function pause(page: Page, ms: number = 1500) {
    await page.waitForTimeout(ms);
}

/** Scroll down slowly for visual effect. */
export async function slowScroll(page: Page, pixels: number = 300) {
    await page.mouse.wheel(0, pixels);
    await pause(page, 800);
}
