import { test } from '@playwright/test';
import { applyTheme, capture, themeFromProject } from './helpers';

const routes = [
  { name: 'marketing-home', path: '/' },
  { name: 'marketing-features', path: '/features' },
  { name: 'marketing-pricing', path: '/pricing' },
  { name: 'marketing-faq', path: '/faq' },
  { name: 'marketing-about', path: '/about' },
  { name: 'marketing-contact', path: '/contact' },
  { name: 'marketing-trial', path: '/trial' },
  { name: 'marketing-for-creatives', path: '/for-creatives' },
];

test.beforeEach(async ({ page }, testInfo) => {
  await applyTheme(page, themeFromProject(testInfo.project.name));
});

for (const route of routes) {
  test(route.name, async ({ page }, testInfo) => {
    await page.goto(route.path, { waitUntil: 'networkidle' });
    await capture(page, testInfo, route.name);
  });
}
