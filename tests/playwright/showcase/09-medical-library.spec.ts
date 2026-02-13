import { test } from '@playwright/test';
import { loginAsScenario, pause, slowScroll } from './helpers';

test('Showcase: Medical library and reference management', async ({ page }) => {
    // 1. Login via any scenario
    await loginAsScenario(page, 'PVCs / Palpitations');
    await pause(page, 1500);

    // 2. Navigate to medical library
    await page.goto('/library');
    await page.waitForLoadState('networkidle');
    await pause(page, 2500);

    // 3. Scroll through library content
    await slowScroll(page, 300);
    await pause(page, 2000);

    await slowScroll(page, 300);
    await pause(page, 2000);

    // 4. Scroll back
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
    await pause(page, 2000);
});
