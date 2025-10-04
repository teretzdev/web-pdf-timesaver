// Minimal MCP-style stdio JSON-RPC server (TypeScript)
import { stdin, stdout } from 'process';

let buffer = Buffer.alloc(0);
stdin.on('data', chunk => { buffer = Buffer.concat([buffer, chunk]); processBuffer(); });
stdin.on('end', () => process.exit(0));

function processBuffer() {
  while (true) {
    const str = buffer.toString('utf8');
    const headerEnd = str.indexOf('\r\n\r\n');
    if (headerEnd === -1) break;
    const header = str.slice(0, headerEnd);
    const m = header.match(/Content-Length: (\d+)/i);
    if (!m) {
      // skip malformed header
      buffer = buffer.slice(headerEnd + 4);
      continue;
    }
    const len = parseInt(m[1], 10);
    const total = headerEnd + 4 + len;
    if (buffer.length < total) break; // wait for more data
    const content = buffer.slice(headerEnd + 4, total).toString('utf8');
    buffer = buffer.slice(total);
    try {
      const msg = JSON.parse(content);
      handleMessage(msg);
    } catch (e) {
      // ignore parse errors
    }
  }
}

function send(obj: any) {
  const s = JSON.stringify(obj);
  const header = `Content-Length: ${Buffer.byteLength(s, 'utf8')}\r\n\r\n`;
  stdout.write(header + s, 'utf8');
}

function handleMessage(msg: any) {
  // simple request/notification handler
  if (msg && msg.method) {
    switch (msg.method) {
      case 'initialize':
        send({ jsonrpc: '2.0', id: msg.id, result: { capabilities: {} } });
        return;
      case 'mcp/ping':
        send({ jsonrpc: '2.0', id: msg.id, result: 'pong' });
        return;
      case 'shutdown':
        send({ jsonrpc: '2.0', id: msg.id, result: null });
        return;
      case 'exit':
        process.exit(0);
        return;
      default:
        if (msg.id !== undefined) {
          send({ jsonrpc: '2.0', id: msg.id, error: { code: -32601, message: 'Method not found' } });
        }
        return;
    }
  }
}

// keep process alive
setInterval(() => {}, 1 << 30);
