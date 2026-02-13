import { test } from '@playwright/test';
import { loginAsScenario, pause, slowScroll } from './helpers';

test('Showcase: AI chat sidebar with context-aware questions', async ({ page }) => {
    // 1. Login via PVCs scenario
    await loginAsScenario(page, 'PVCs / Palpitations');
    await pause(page, 1500);

    // 2. Navigate to visit view
    const visitLink = page.getByText('View Summary').first();
    if (await visitLink.isVisible()) {
        await visitLink.click();
        await page.waitForLoadState('networkidle');
        await pause(page, 2000);
    }

    // 3. Look for chat toggle / sidebar
    const chatToggle = page.getByRole('button', { name: /chat|ask|question/i }).first();
    if (await chatToggle.isVisible()) {
        await chatToggle.click();
        await pause(page, 2000);
    }

    // 4. Type a medical question
    const chatInput = page.getByPlaceholder(/ask|question|type/i).first();
    if (await chatInput.isVisible()) {
        await chatInput.click();
        await pause(page, 500);
        await chatInput.fill('What are PVCs and should I be worried?');
        await pause(page, 1500);

        // Submit the question
        const sendBtn = page.getByRole('button', { name: /send|submit/i }).first();
        if (await sendBtn.isVisible()) {
            await sendBtn.click();
            await pause(page, 3000);
        } else {
            await chatInput.press('Enter');
            await pause(page, 3000);
        }
    }

    // 5. Show the response
    await slowScroll(page, 200);
    await pause(page, 3000);
});
