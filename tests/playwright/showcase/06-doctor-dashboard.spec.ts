import { test } from '@playwright/test';
import { loginAsDoctor, pause, slowScroll } from './helpers';

test('Showcase: Doctor dashboard with intelligent alerts', async ({ page }) => {
    // 1. First start a patient scenario so doctor has data
    await page.goto('/demo/scenarios');
    await page.waitForLoadState('networkidle');
    await pause(page, 1000);
    const pvcsCard = page.getByText('PVCs / Palpitations').first();
    if (await pvcsCard.isVisible()) {
        await pvcsCard.click();
        await page.waitForLoadState('networkidle');
        await pause(page, 1000);
    }

    // 2. Now login as doctor
    await loginAsDoctor(page);
    await pause(page, 2500);

    // 3. Show the doctor dashboard with patient data
    await slowScroll(page, 300);
    await pause(page, 2000);

    await slowScroll(page, 300);
    await pause(page, 2000);

    // 4. Scroll back to top
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
    await pause(page, 2000);
});
