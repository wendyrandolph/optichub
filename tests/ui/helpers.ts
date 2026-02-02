import type { Page, TestInfo } from '@playwright/test';

export function themeFromProject(projectName: string): 'light' | 'dark' {
  return projectName.includes('dark') ? 'dark' : 'light';
}

export function viewportLabel(projectName: string): 'desktop' | 'mobile' {
  return projectName.includes('mobile') ? 'mobile' : 'desktop';
}

export async function applyTheme(page: Page, theme: 'light' | 'dark') {
  await page.addInitScript((value) => {
    localStorage.setItem('renlo-theme', value);
  }, theme);
}

export async function capture(page: Page, testInfo: TestInfo, name: string) {
  const theme = themeFromProject(testInfo.project.name);
  const viewport = viewportLabel(testInfo.project.name);
  const safeName = name.replace(/[^a-z0-9-_]+/gi, '-').toLowerCase();
  const path = `tests/artifacts/ui/${safeName}/${theme}/${viewport}.png`;
  await page.screenshot({ path, fullPage: true });
}
