import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StdioClientTransport } from '@modelcontextprotocol/sdk/client/stdio.js';

const transport = new StdioClientTransport({
  command: 'npx',
  args: ['-y', '@browsermcp/mcp@latest'],
});
const client = new Client({ name: 'debug', version: '1.0.0' });
await client.connect(transport);
await client.callTool({ name: 'browser_navigate', arguments: { url: 'https://draft.clio.com/clients/active/' } });
await new Promise((r) => setTimeout(r, 3000));
const shot = await client.callTool({ name: 'browser_screenshot', arguments: {} });
console.log(JSON.stringify(shot, (k, v) => (typeof v === 'string' && v.length > 200 ? v.slice(0, 80) + '...[truncated]' : v), 2));
process.exit(0);
