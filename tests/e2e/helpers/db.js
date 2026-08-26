const { execFileSync } = require('child_process');

/** Runs SQL against the isolated smg_consent_test database used by the E2E web server. */
function runSql(sql) {
  execFileSync('mysql', ['-u', 'root', 'smg_consent_test', '-e', sql]);
}

function truncate(table) {
  runSql(`TRUNCATE TABLE ${table}`);
}

function seedConsentRow(guid, action, acceptedAt, expiresAt) {
  runSql(
    `INSERT INTO consent_log (guid, action, consent_version, accepted_at, expires_at) ` +
    `VALUES ('${guid}', '${action}', 1, '${acceptedAt}', '${expiresAt}')`
  );
}

module.exports = { runSql, truncate, seedConsentRow };
