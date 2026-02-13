import { test } from '@playwright/test';
import { pause, slowScroll } from './helpers';

test('Showcase: Multi-scenario demo picker with 12 clinical scenarios', async ({ page }) => {
    // 1. Go to scenario picker
    await page.goto('/demo/scenarios');
    await page.waitForLoadState('networkidle');
    await pause(page, 2500);

    // 2. Scroll to see all featured scenarios
    await slowScroll(page, 200);
    await pause(page, 1500);

    // 3. Click "Show more scenarios" if visible
    const showMore = page.getByText('more scenarios');
    if (await showMore.isVisible()) {
        await showMore.click();
        await pause(page, 2000);
        await slowScroll(page, 400);
        await pause(page, 1500);
    }

    // 4. Scroll back up
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
    await pause(page, 1500);

    // 5. Select the PVCs scenario
    const pvcsCard = page.getByText('PVCs / Palpitations').first();
    if (await pvcsCard.isVisible()) {
        await pvcsCard.click();
        await page.waitForLoadState('networkidle');
        await pause(page, 3000);
    }

    // 6. Show profile page loaded with scenario data
    await slowScroll(page, 300);
    await pause(page, 2000);
});
