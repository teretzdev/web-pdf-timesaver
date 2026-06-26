/**
 * Capture Clio Draft screenshots via Browser MCP extension (ws://127.0.0.1:9009).
 * Requires: Chrome tab connected in Browser MCP extension, signed into draft.clio.com
 */
import { writeFileSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const WS_URL = 'ws://127.0.0.1:9009';
const OUT_DIR = join(dirname(fileURLToPath(import.meta.url)), '..', 'DESIGN SPECS', 'clio-screenshots');

const PAGES = [
  { file: '01-clients-active.png', url: 'https://draft.clio.com/clients/active/', label: 'Clients (Active)' },
  { file: '02-clients-projects.png', url: 'https://draft.clio.com/clients/projects/', label: 'Projects list' },
  { file: '03-clients-create.png', url: 'https://draft.clio.com/clients/create/', label: 'Add client' },
  { file: '04-project-overview.png', url: 'https://draft.clio.com/clients/project/info/', label: 'Project overview' },
  { file: '05-panels-populate.png', url: 'https://draft.clio.com/panels/populate/', label: 'Populate', fullPage: true },
  { file: '06-panels-edit.png', url: 'https://draft.clio.com/panels/edit/', label: 'Edit / drafting' },
  { file: '07-account-profile.png', url: 'https://draft.clio.com/clients/settings/account/profile/', label: 'Account profile' },
  { file: '08-account-organization.png', url: 'https://draft.clio.com/clients/settings/account/organization/', label: 'Organization' },
  { file: '09-form-libraries.png', url: 'https://draft.clio.com/clients/settings/form-libraries/', label: 'Form libraries' },
  { file: '10-support.png', url: 'https://draft.clio.com/clients/support/', label: 'Support' },
  { file: '11-project-signatures.png', url: 'https://draft.clio.com/clients/project/signatures/', label: 'Signed documents' },
];

function generateId() {
  return crypto.randomUUID();
}

function connectWs() {
  return new Promise((resolve, reject) => {
    const ws = new WebSocket(WS_URL);
    ws.addEventListener('open', () => resolve(ws));
    ws.addEventListener('error', () => reject(new Error('Cannot connect to Browser MCP on port 9009. Connect a tab in the extension.')));
    setTimeout(() => reject(new Error('WebSocket connect timeout')), 5000);
  });
}

function sendMessage(ws, type, payload = {}, timeoutMs = 45000) {
  return new Promise((resolve, reject) => {
    const id = generateId();
    const message = { id, type, payload };

    const timeoutId = setTimeout(() => {
      cleanup();
      reject(new Error(`${type} timed out after ${timeoutMs}ms`));
    }, timeoutMs);

    const onMessage = (event) => {
      let data;
      try {
        data = JSON.parse(event.data.toString());
      } catch {
        return;
      }
      if (data.type !== 'messageResponse') return;
      if (data.payload?.requestId !== id) return;
      cleanup();
      if (data.payload.error) reject(new Error(data.payload.error));
      else resolve(data.payload.result);
    };

    const cleanup = () => {
      clearTimeout(timeoutId);
      ws.removeEventListener('message', onMessage);
    };

    ws.addEventListener('message', onMessage);
    ws.send(JSON.stringify(message));
  });
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

async function main() {
  mkdirSync(OUT_DIR, { recursive: true });
  const ws = await connectWs();
  console.log('Connected to Browser MCP');

  const saved = [];
  const failed = [];

  for (const page of PAGES) {
    try {
      console.log(`→ ${page.label}: ${page.url}`);
      await sendMessage(ws, 'browser_navigate', { url: page.url });
      await sleep(page.fullPage ? 3500 : 2500);
      const b64 = await sendMessage(ws, 'browser_screenshot', {});
      const outPath = join(OUT_DIR, page.file);
      writeFileSync(outPath, Buffer.from(b64, 'base64'));
      saved.push(outPath);
      console.log(`  ✓ ${page.file}`);
    } catch (err) {
      failed.push({ page: page.label, error: err.message });
      console.error(`  ✗ ${page.label}: ${err.message}`);
    }
  }

  ws.close();
  console.log(`\nSaved ${saved.length}/${PAGES.length} screenshots to:\n${OUT_DIR}`);
  if (failed.length) {
    console.log('Failed:', failed);
    process.exit(1);
  }
}

main().catch((err) => {
  console.error(err.message);
  process.exit(1);
});
