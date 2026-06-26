/**
 * Save a viewport PNG after Browser MCP has navigated to the target page.
 * Usage: node scripts/save-clio-screenshot.mjs <filename.png>
 * Requires: Chrome window titled "Clio Draft" visible; Browser MCP tab connected.
 */
import { writeFileSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import screenshot from 'screenshot-desktop';

const OUT_DIR = join(dirname(fileURLToPath(import.meta.url)), '..', 'DESIGN SPECS', 'clio-screenshots');
const file = process.argv[2];
if (!file) {
  console.error('Usage: node scripts/save-clio-screenshot.mjs <filename.png>');
  process.exit(1);
}

mkdirSync(OUT_DIR, { recursive: true });
const outPath = join(OUT_DIR, file);
const img = await screenshot({ format: 'png' });
writeFileSync(outPath, img);
console.log('Saved:', outPath);
