import { test } from '@playwright/test';
import { loginAsScenario, pause, slowScroll } from './helpers';

test('Showcase: Medication details and interactions', async ({ page }) => {
    // 1. Login via PVCs scenario (has Propranolol)
    await loginAsScenario(page, 'PVCs / Palpitations');
    await pause(page, 1500);

    // 2. Navigate to visit
    const visitLink = page.getByText('View Summary').first();
    if (await visitLink.isVisible()) {
        await visitLink.click();
        await page.waitForLoadState('networkidle');
        await pause(page, 2000);
    }

    // 3. Look for medications section or meds link
    const medsLink = page.getByText(/medication|meds|prescription/i).first();
    if (await medsLink.isVisible()) {
        await medsLink.click();
        await page.waitForLoadState('networkidle');
        await pause(page, 2500);
    }

    // 4. Scroll through medication details
    await slowScroll(page, 300);
    await pause(page, 2000);

    await slowScroll(page, 300);
    await pause(page, 2000);

    // 5. Scroll back
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
    await pause(page, 2000);
});
