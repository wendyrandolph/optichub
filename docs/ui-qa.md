# UI QA Harness (Playwright)

This project includes a Playwright-based UI QA harness for Renlo.

## Setup

1) Install dependencies:

```bash
npm install
npm run ui:qa:install
```

2) Set environment variables (example):

```bash
export UI_BASE_URL="http://127.0.0.1:8000"
export UI_QA_EMAIL="you@example.com"
export UI_QA_PASSWORD="your-password"
export UI_QA_TENANT_ID="1"
export UI_QA_PROJECT_ID="1"
export UI_QA_PROPOSAL_ID="1"
export UI_QA_PROPOSAL_TOKEN="public-token-here"
```

3) Run the harness:

```bash
npm run ui:qa
```

## Output

Screenshots are stored under:

```
tests/artifacts/ui/{page}/{theme}/{viewport}.png
```

Themes: `light` / `dark`  
Viewports: `desktop` / `mobile`

## Routes

The QA route map is stored in `config/ui-qa.php` for reference.
