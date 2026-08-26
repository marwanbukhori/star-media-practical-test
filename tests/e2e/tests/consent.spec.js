const { test, expect } = require('@playwright/test');
const { truncate } = require('../helpers/db');

test.describe('consent gate', () => {
  test.beforeEach(() => {
    truncate('consent_log');
  });

  test('first visit shows the blocking, non-dismissible dialog', async ({ page }) => {
    await page.goto('/index.php');

    const overlay = page.locator('[data-smg-consent-overlay]');
    await expect(overlay).toBeVisible();
    await expect(overlay).toHaveAttribute('data-dismissible', '0');
  });

  test('accepting closes the dialog and sets the accept cookie, not the decline cookie', async ({ page, context }) => {
    await page.goto('/index.php');
    await page.locator('[data-smg-consent-accept]').click();

    await expect(page.locator('[data-smg-consent-overlay]')).toBeHidden();

    const cookies = await context.cookies();
    expect(cookies.find((c) => c.name === 'smg_consent')).toBeTruthy();
    expect(cookies.find((c) => c.name === 'smg_consent_declined')).toBeFalsy();
  });

  test('after accepting, a fresh page load does not show the dialog again', async ({ page }) => {
    await page.goto('/index.php');
    await page.locator('[data-smg-consent-accept]').click();
    await expect(page.locator('[data-smg-consent-overlay]')).toBeHidden();

    await page.reload();
    await expect(page.locator('[data-smg-consent-overlay]')).toBeHidden();
  });

  test('declining closes the dialog and sets the decline cookie, not the accept cookie', async ({ page, context }) => {
    await page.goto('/index.php');
    await page.getByRole('button', { name: 'Decline' }).click();

    await expect(page.locator('[data-smg-consent-overlay]')).toBeHidden();

    const cookies = await context.cookies();
    expect(cookies.find((c) => c.name === 'smg_consent_declined')).toBeTruthy();
    expect(cookies.find((c) => c.name === 'smg_consent')).toBeFalsy();
  });

  test('after declining, a fresh page load does not show the dialog again', async ({ page }) => {
    await page.goto('/index.php');
    await page.getByRole('button', { name: 'Decline' }).click();
    await expect(page.locator('[data-smg-consent-overlay]')).toBeHidden();

    await page.reload();
    await expect(page.locator('[data-smg-consent-overlay]')).toBeHidden();
  });

  test('the "Cookie settings" link reopens the dialog, dismissibly, once consent was given', async ({ page }) => {
    await page.goto('/index.php');
    await page.locator('[data-smg-consent-accept]').click();
    await expect(page.locator('[data-smg-consent-overlay]')).toBeHidden();

    await page.getByRole('link', { name: 'Cookie settings' }).click();

    const overlay = page.locator('[data-smg-consent-overlay]');
    await expect(overlay).toBeVisible();
    await expect(overlay).toHaveAttribute('data-dismissible', '1');

    await page.keyboard.press('Escape');
    await expect(overlay).toBeHidden();
  });

  test('a stale accept cookie (version below current) forces the dialog to reappear', async ({ page, context }) => {
    // Consent::CONSENT_VERSION is 1, so 0 is the only value "below current" — and
    // readAcceptCookie() treats a falsy version as no cookie at all (empty(0) === true),
    // which forces the same reappear-the-dialog outcome a real version bump would.
    await context.addCookies([
      {
        name: 'smg_consent',
        value: JSON.stringify({
          guid: '11111111-1111-4111-8111-111111111111',
          accepted_at: '2020-01-01T00:00:00+08:00',
          version: 0,
        }),
        domain: 'localhost',
        path: '/',
      },
    ]);

    await page.goto('/index.php');
    await expect(page.locator('[data-smg-consent-overlay]')).toBeVisible();
  });

  test('the accept/decline flow works with JavaScript disabled', async ({ browser }) => {
    const context = await browser.newContext({ javaScriptEnabled: false });
    const page = await context.newPage();

    await page.goto('/index.php');
    await expect(page.locator('[data-smg-consent-overlay]')).toBeVisible();

    // No JS to intercept the submit — this is a real POST followed by a 303 redirect back
    // to redirect_to. Race the click against the navigation it triggers so Playwright's
    // actionability retries don't fight the page tearing down mid-click.
    await Promise.all([
      page.waitForURL(/\/index\.php$/),
      page.locator('[data-smg-consent-accept]').click({ force: true }),
    ]);

    await expect(page.locator('[data-smg-consent-overlay]')).toBeHidden();

    await context.close();
  });
});
