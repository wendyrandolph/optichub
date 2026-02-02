import { chromium, FullConfig } from '@playwright/test';

export default async function globalSetup(config: FullConfig) {
  const baseURL = process.env.UI_BASE_URL || 'http://127.0.0.1:8000';
  const email = process.env.UI_QA_EMAIL || '';
  const password = process.env.UI_QA_PASSWORD || '';

  if (!email || !password) {
    console.warn('[ui-qa] Missing UI_QA_EMAIL/UI_QA_PASSWORD. Authenticated screenshots will likely fail.');
    return;
  }

  const browser = await chromium.launch();
  const page = await browser.newPage();

  await page.goto(`${baseURL}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input#email', email);
  await page.fill('input#password', password);
  await page.getByRole('button', { name: /log in/i }).click();

  await page.waitForLoadState('networkidle');

  await page.context().storageState({ path: 'tests/ui/.auth/tenant.json' });
  await browser.close();
}
