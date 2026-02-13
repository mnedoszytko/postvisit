import { test } from '@playwright/test';
import { loginAsScenario, pause, slowScroll } from './helpers';

test('Showcase: Visit summary with SOAP notes and medical terms', async ({ page }) => {
    // 1. Login via PVCs scenario
    await loginAsScenario(page, 'PVCs / Palpitations');
    await pause(page, 1500);

    // 2. Click on the visit to see its summary
    const visitLink = page.getByText('View Summary').first();
    if (await visitLink.isVisible()) {
        await visitLink.click();
    } else {
        // Try clicking on visit card
        const visitCard = page.locator('[data-visit-id]').first();
        if (await visitCard.isVisible()) {
            await visitCard.click();
        } else {
            // Navigate to visits directly via profile
            const anyVisit = page.getByRole('link', { name: /visit|summary/i }).first();
            if (await anyVisit.isVisible()) {
                await anyVisit.click();
            }
        }
    }

    await page.waitForLoadState('networkidle');
    await pause(page, 2500);

    // 3. Scroll through the summary content
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
