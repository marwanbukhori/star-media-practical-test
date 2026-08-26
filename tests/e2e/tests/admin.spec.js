const { test, expect } = require('@playwright/test');
const { truncate, seedConsentRow } = require('../helpers/db');

const ADMIN_USER = 'e2e_admin';
const ADMIN_PASS = 'E2ETestPass123';

async function login(page, username, password) {
  await page.goto('/admin/login.php');
  await page.fill('#username', username);
  await page.fill('#password', password);
  await page.click('[data-smg-login-submit]');
}

test.describe('admin login', () => {
  test.beforeEach(() => {
    truncate('login_attempts');
  });

  test('rejects an invalid password', async ({ page }) => {
    await login(page, ADMIN_USER, 'wrong-password');

    await expect(page).toHaveURL(/login\.php$/);
    await expect(page.locator('.smg-form-banner--error')).toHaveText(/Invalid username or password/);
  });

  test('logs in with valid credentials and reaches the dashboard', async ({ page }) => {
    await login(page, ADMIN_USER, ADMIN_PASS);

    await expect(page).toHaveURL(/admin\/index\.php$/);
    await expect(page.locator('.smg-admin-topbar__user')).toHaveText(ADMIN_USER);
  });

  test('locks out after 5 failed attempts within a minute, even for the correct password', async ({ page }) => {
    for (let i = 0; i < 5; i++) {
      await login(page, ADMIN_USER, 'wrong-password');
    }

    await login(page, ADMIN_USER, ADMIN_PASS);

    await expect(page.locator('.smg-form-banner--error')).toHaveText(/Too many attempts/);
  });
});

test.describe('admin dashboard (logged in)', () => {
  test.beforeEach(async ({ page }) => {
    truncate('login_attempts');
    truncate('consent_log');
    seedConsentRow('55555555-5555-4555-8555-500000000005', 'accepted', '2026-08-20 04:00:00', '2030-01-01 00:00:00');
    seedConsentRow('66666666-6666-4666-8666-600000000006', 'declined', '2026-08-21 04:00:00', '2030-01-01 00:00:00');
    await login(page, ADMIN_USER, ADMIN_PASS);
  });

  test('lists seeded consent records and filters by status', async ({ page }) => {
    await expect(page.locator('table tbody tr')).toHaveCount(2);

    await page.selectOption('select[name="status"]', 'declined');
    await page.locator('button:has-text("Filter")').click();

    await expect(page.locator('table tbody tr')).toHaveCount(1);
    await expect(page.locator('table tbody tr')).toContainText('66666666');
  });

  test('sorting by "Accepted at" toggles direction', async ({ page }) => {
    // Default order is newest-first (declined 08-21, then accepted 08-20). Clicking the
    // column header once flips it to oldest-first.
    await expect(page.locator('table tbody tr').first()).toContainText('66666666');

    await page.locator('thead a:has-text("Accepted at")').click();

    await expect(page.locator('table tbody tr').first()).toContainText('55555555');
  });

  test('clicking a GUID opens its record detail page', async ({ page }) => {
    await page.locator('text=55555555-5555-4555-8555-500000000005').click();

    await expect(page).toHaveURL(/record\.php\?guid=/);
    await expect(page.locator('h2')).toHaveText('Consent record');
    await expect(page.locator('.smg-pill')).toHaveText('ACCEPTED');
  });

  test('change password rejects an incorrect current password', async ({ page }) => {
    await page.goto('/admin/change-password.php');
    await page.fill('#current_password', 'wrong-current');
    await page.fill('#new_password', 'newpassword123');
    await page.fill('#confirm_password', 'newpassword123');
    await page.locator('button:has-text("Update password")').click();

    await expect(page.locator('.smg-form-banner--error')).toHaveText(/current password is incorrect/);
  });

  test('change password succeeds and the new password can log in', async ({ page }) => {
    await page.goto('/admin/change-password.php');
    await page.fill('#current_password', ADMIN_PASS);
    await page.fill('#new_password', 'RotatedPass456');
    await page.fill('#confirm_password', 'RotatedPass456');
    await page.locator('button:has-text("Update password")').click();

    await expect(page.locator('.smg-form-banner--success')).toHaveText('Password updated.');

    await page.locator('text=Log out').click();
    await login(page, ADMIN_USER, 'RotatedPass456');
    await expect(page).toHaveURL(/admin\/index\.php$/);

    // Revert so the fixed E2E credentials keep working for later runs/tests.
    await page.goto('/admin/change-password.php');
    await page.fill('#current_password', 'RotatedPass456');
    await page.fill('#new_password', ADMIN_PASS);
    await page.fill('#confirm_password', ADMIN_PASS);
    await page.locator('button:has-text("Update password")').click();
    await expect(page.locator('.smg-form-banner--success')).toHaveText('Password updated.');
  });
});
