import { test } from '@playwright/test';
import { loginAsDoctor, pause, slowScroll } from './helpers';

test('Showcase: Doctor patient detail with vitals and labs', async ({ page }) => {
    // 1. First start a scenario for data
    await page.goto('/demo/scenarios');
    await page.waitForLoadState('networkidle');
    await pause(page, 500);
    const pvcsCard = page.getByText('PVCs / Palpitations').first();
    if (await pvcsCard.isVisible()) {
        await pvcsCard.click();
        await page.waitForLoadState('networkidle');
        await pause(page, 1000);
    }

    // 2. Login as doctor
    await loginAsDoctor(page);
    await pause(page, 2000);

    // 3. Navigate to patients list
    await page.goto('/doctor/patients');
    await page.waitForLoadState('networkidle');
    await pause(page, 2000);

    // 4. Click on a patient
    const patientRow = page.getByText('Alex Johnson').first();
    if (await patientRow.isVisible()) {
        await patientRow.click();
        await page.waitForLoadState('networkidle');
        await pause(page, 2500);
    }

    // 5. Scroll through patient details
    await slowScroll(page, 300);
    await pause(page, 2000);

    await slowScroll(page, 300);
    await pause(page, 2000);

    // 6. Scroll back up
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
    await pause(page, 2000);
});
