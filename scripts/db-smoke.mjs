/**
 * Phase 1 MySQL smoke test (matches PHP DataStore defaults).
 * Usage:
 *   set DB_PASSWORD=... && node scripts/db-smoke.mjs
 * Optional: DB_HOST, DB_PORT (default 3306), DB_NAME, DB_USER, DB_TRY_HOSTS (comma list)
 */
import mysql from 'mysql2/promise';

const DEFAULT_REMOTE_DB_HOST = 'LawDocumentManager.com';

const port = parseInt(process.env.DB_PORT || '3306', 10);
const user = process.env.DB_USER || 'ldm';
/** If DB_PASSWORD is unset: use remote default for `ldm`, no password for local `root` (XAMPP). If set to empty string, no password. */
function resolvePassword() {
	if (Object.prototype.hasOwnProperty.call(process.env, 'DB_PASSWORD')) {
		return process.env.DB_PASSWORD === '' ? undefined : process.env.DB_PASSWORD;
	}
	if (user === 'root') {
		return undefined;
	}
	return '3294459786827563';
}
const password = resolvePassword();
const database = process.env.DB_NAME || 'LawDocumentManager.com';
const rawHost = (process.env.DB_HOST || '').trim();
const tryHosts = (process.env.DB_TRY_HOSTS || `${DEFAULT_REMOTE_DB_HOST},127.0.0.1,localhost`)
	.split(',')
	.map((h) => h.trim())
	.filter(Boolean);

function resolveHost(h) {
	if (!h || /^mysql$/i.test(h)) {
		return DEFAULT_REMOTE_DB_HOST;
	}
	return h;
}

function connectionOptions(hostResolved) {
	const o = {
		host: hostResolved,
		port,
		user,
		database,
		connectTimeout: 8000,
	};
	if (password !== undefined) {
		o.password = password;
	}
	return o;
}

async function tryConnect(host) {
	const h = resolveHost(host);
	const conn = await mysql.createConnection(connectionOptions(h));
	const [rows] = await conn.query('SELECT 1 AS ok, DATABASE() AS db, @@hostname AS server_host');
	await conn.end();
	return { host: h, rows };
}

async function main() {
	const hosts = rawHost ? [rawHost, ...tryHosts] : tryHosts;
	const seen = new Set();
	let lastErr = null;
	for (const candidate of hosts) {
		const key = resolveHost(candidate);
		if (seen.has(key)) {
			continue;
		}
		seen.add(key);
		process.stderr.write(`Trying ${key}:${port} db=${database} user=${user} ...\n`);
		try {
			const out = await tryConnect(candidate);
			console.log(JSON.stringify({ success: true, host: out.host, port, sample: out.rows }, null, 2));
			const conn2 = await mysql.createConnection(connectionOptions(out.host));
			const [tables] = await conn2.query(
				"SHOW TABLES LIKE 'form_%'"
			);
			await conn2.end();
			console.log('Tables matching form_*:', tables);
			return;
		} catch (e) {
			lastErr = e;
			console.error(`  failed: ${e.code || ''} ${e.message}`);
			if (e.code === 'ER_BAD_DB_ERROR') {
				process.stderr.write(
					`  Hint: create the database (e.g. in phpMyAdmin) or set DB_NAME=mysql to test auth only.\n`
				);
			}
		}
	}
	console.error(JSON.stringify({ success: false, message: String(lastErr && lastErr.message) }, null, 2));
	process.exit(1);
}

main();
