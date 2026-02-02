import { test } from '@playwright/test';
import { applyTheme, capture, themeFromProject } from './helpers';

const tenantId = process.env.UI_QA_TENANT_ID || '1';
const projectId = process.env.UI_QA_PROJECT_ID || '1';
const proposalId = process.env.UI_QA_PROPOSAL_ID || '1';

const routes = [
  { name: 'app-dashboard', path: `/app/${tenantId}/dashboards` },
  { name: 'app-schedule', path: `/app/${tenantId}/schedule` },
  { name: 'app-projects-index', path: `/app/${tenantId}/projects` },
  { name: 'app-projects-show', path: `/app/${tenantId}/projects/${projectId}` },
  { name: 'app-tasks-index', path: `/app/${tenantId}/tasks` },
  { name: 'app-proposals-index', path: `/app/${tenantId}/proposals` },
  { name: 'app-proposals-edit', path: `/app/${tenantId}/proposals/${proposalId}/edit` },
  { name: 'app-proposals-show', path: `/app/${tenantId}/proposals/${proposalId}` },
];

test.use({ storageState: 'tests/ui/.auth/tenant.json' });

test.beforeEach(async ({ page }, testInfo) => {
  await applyTheme(page, themeFromProject(testInfo.project.name));
});

for (const route of routes) {
  test(route.name, async ({ page }, testInfo) => {
    await page.goto(route.path, { waitUntil: 'networkidle' });
    await capture(page, testInfo, route.name);
  });
}
