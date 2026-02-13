import { test } from '@playwright/test';
import { loginAsDoctor, pause, slowScroll } from './helpers';

test('Showcase: Audit logging and HIPAA compliance', async ({ page }) => {
    // 1. First create some activity by starting a scenario
    await page.goto('/demo/scenarios');
    await page.waitForLoadState('networkidle');
    await pause(page, 500);
    const pvcsCard = page.getByText('PVCs / Palpitations').first();
    if (await pvcsCard.isVisible()) {
        await pvcsCard.click();
        await page.waitForLoadState('networkidle');
        await pause(page, 1000);
    }

    // 2. Login as doctor (to access audit logs)
    await loginAsDoctor(page);
    await pause(page, 2000);

    // 3. Navigate to audit log
    await page.goto('/doctor/audit');
    await page.waitForLoadState('networkidle');
    await pause(page, 2500);

    // 4. Scroll through audit entries
    await slowScroll(page, 300);
    await pause(page, 2000);

    await slowScroll(page, 300);
    await pause(page, 2000);

    // 5. Scroll back to top
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
    await pause(page, 2000);
});
