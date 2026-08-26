// @ts-check
const path = require('path');
const { defineConfig, devices } = require('@playwright/test');

const repoRoot = path.resolve(__dirname, '..', '..');
const prependFile = path.join(__dirname, 'prepend.php');

// A port unlikely to collide with a developer's own `php -S localhost:8000` session —
// reusing that server by accident would silently point every test at the real
// smg_consent database instead of the isolated smg_consent_test one.
const PORT = 8098;

module.exports = defineConfig({
  testDir: './tests',
  fullyParallel: false,
  workers: 1,
  reporter: 'list',
  use: {
    baseURL: `http://localhost:${PORT}`,
    trace: 'retain-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
  webServer: {
    command: `php -d auto_prepend_file="${prependFile}" -S localhost:${PORT} -t public`,
    cwd: repoRoot,
    url: `http://localhost:${PORT}/index.php`,
    // Always start our own server — reusing whatever else happens to be listening
    // would skip the auto_prepend_file that redirects the app at the test database.
    reuseExistingServer: false,
    timeout: 10_000,
  },
});
