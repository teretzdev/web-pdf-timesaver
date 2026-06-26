/**
 * Capture Clio screenshots via Browser MCP (stdio client).
 * Prereqs: Chrome tab connected in Browser MCP extension (signed into draft.clio.com).
 * Note: Starting this MCP server resets the WS on port 9009; Cursor may need to reconnect after.
 */
import { writeFileSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StdioClientTransport } from '@modelcontextprotocol/sdk/client/stdio.js';

const OUT_DIR = join(dirname(fileURLToPath(import.meta.url)), '..', 'DESIGN SPECS', 'clio-screenshots');

const PAGES = [
  { file: '01-clients-active.png', url: 'https://draft.clio.com/clients/active/' },
  { file: '02-clients-projects.png', url: 'https://draft.clio.com/clients/projects/' },
  { file: '03-clients-create.png', url: 'https://draft.clio.com/clients/create/' },
  { file: '04-project-overview.png', url: 'https://draft.clio.com/clients/project/info/' },
  { file: '05-panels-populate.png', url: 'https://draft.clio.com/panels/populate/' },
  { file: '06-panels-edit.png', url: 'https://draft.clio.com/panels/edit/' },
  { file: '07-account-profile.png', url: 'https://draft.clio.com/clients/settings/account/profile/' },
  { file: '08-account-organization.png', url: 'https://draft.clio.com/clients/settings/account/organization/' },
  { file: '09-form-libraries.png', url: 'https://draft.clio.com/clients/settings/form-libraries/' },
  { file: '10-support.png', url: 'https://draft.clio.com/clients/support/' },
  { file: '11-project-signatures.png', url: 'https://draft.clio.com/clients/project/signatures/' },
  { file: '12-clients-archived.png', url: 'https://draft.clio.com/clients/archived/' },
];

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

async function main() {
  mkdirSync(OUT_DIR, { recursive: true });

  const transport = new StdioClientTransport({
    command: 'npx',
    args: ['-y', '@browsermcp/mcp@latest'],
  });

  const client = new Client({ name: 'clio-screenshot-capture', version: '1.0.0' });
  await client.connect(transport);
  console.log('Browser MCP connected. Ensure Chrome extension tab is linked.');

  let ok = 0;
  for (const page of PAGES) {
    try {
      console.log(`→ ${page.file}`);
      await client.callTool({ name: 'browser_navigate', arguments: { url: page.url } });
      await sleep(3000);
      const shot = await client.callTool({ name: 'browser_screenshot', arguments: {} });
      const image = shot.content?.find((c) => c.type === 'image');
      if (!image?.data) throw new Error('No image data in screenshot response');
      writeFileSync(join(OUT_DIR, page.file), Buffer.from(image.data, 'base64'));
      console.log(`  ✓ ${page.file}`);
      ok++;
    } catch (err) {
      console.error(`  ✗ ${page.file}: ${err.message}`);
    }
  }

  await client.close();
  console.log(`\nSaved ${ok}/${PAGES.length} → ${OUT_DIR}`);
  process.exit(ok === PAGES.length ? 0 : 1);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
