import { test } from '@playwright/test';
import { applyTheme, capture, themeFromProject } from './helpers';

const proposalToken = process.env.UI_QA_PROPOSAL_TOKEN || '';

test.beforeEach(async ({ page }, testInfo) => {
  await applyTheme(page, themeFromProject(testInfo.project.name));
});

test('public-proposal', async ({ page }, testInfo) => {
  const path = proposalToken ? `/proposal/${proposalToken}` : '/proposal/placeholder-token';
  await page.goto(path, { waitUntil: 'networkidle' });
  await capture(page, testInfo, 'public-proposal');
});
