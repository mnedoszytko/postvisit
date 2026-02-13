import { test } from '@playwright/test';
import { loginAsScenario, pause, slowScroll } from './helpers';

test('Showcase: Health dashboard with vital trends', async ({ page }) => {
    // 1. Login via Hypertension scenario (has BP data)
    await loginAsScenario(page, 'Hypertension Follow-up');
    await pause(page, 1500);

    // 2. Navigate to health dashboard
    await page.goto('/health');
    await page.waitForLoadState('networkidle');
    await pause(page, 2500);

    // 3. Scroll through the dashboard
    await slowScroll(page, 300);
    await pause(page, 2000);

    await slowScroll(page, 300);
    await pause(page, 2000);

    await slowScroll(page, 300);
    await pause(page, 2000);

    // 4. Scroll back to top
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
    await pause(page, 2000);
});
