<?php
/**
 * Copy this file to db.local.php (same folder). Gitignored — safe for tunnel passwords.
 *
 * Values here apply only when the matching env var is unset (server env always wins).
 * Typical dev: SSH local forward 127.0.0.1:3307 → remote MySQL (see DESIGN SPECS/db_connection.txt).
 */
return [
	'DB_HOST' => '127.0.0.1',
	'DB_PORT' => '3307',
	// 'DB_NAME' => 'LawDocumentManager.com',
	// 'DB_USER' => 'ldm',
	// 'DB_PASSWORD' => 'your-password',
];
