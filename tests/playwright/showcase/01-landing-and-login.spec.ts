import { test } from '@playwright/test';
import { pause, slowScroll } from './helpers';

test('Showcase: Landing page and demo login flow', async ({ page }) => {
    // 1. Visit landing page
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    await pause(page, 2000);

    // 2. Navigate to login
    await page.goto('/login');
    await page.waitForLoadState('networkidle');
    await pause(page, 2000);

    // 3. Show the "Sign in as Patient" button leads to scenario picker
    const patientBtn = page.getByRole('button', { name: 'Sign in as Patient' });
    await patientBtn.scrollIntoViewIfNeeded();
    await pause(page, 1500);
    await patientBtn.click();

    // Wait for scenario picker
    await page.waitForURL('**/demo/scenarios', { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    await pause(page, 2500);

    // 4. Show scenario picker with all options
    await slowScroll(page, 200);
    await pause(page, 1500);

    // 5. Go back and show doctor login
    await page.goto('/login');
    await page.waitForLoadState('networkidle');
    await pause(page, 1500);

    // 6. Click Sign in as Doctor
    const doctorBtn = page.getByRole('button', { name: 'Sign in as Doctor' });
    await doctorBtn.click();
    await page.waitForURL('**/doctor', { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    await pause(page, 3000);

    // 7. Show the doctor dashboard loaded
    await slowScroll(page, 300);
    await pause(page, 2000);
});
