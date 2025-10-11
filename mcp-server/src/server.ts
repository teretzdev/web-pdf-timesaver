// Enhanced MCP-style stdio JSON-RPC server with PDF analysis capabilities
import { stdin, stdout } from 'process';
import * as fs from 'fs';
import * as path from 'path';
import { PDFAnalyzer } from './pdf-analyzer';
import { PositionComparator } from './position-comparator';

let buffer = Buffer.alloc(0);
const analyzer = new PDFAnalyzer();
const comparator = new PositionComparator();

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

async function handleMessage(msg: any) {
  if (msg && msg.method) {
    try {
      switch (msg.method) {
        case 'initialize':
          send({ jsonrpc: '2.0', id: msg.id, result: { capabilities: {} } });
          return;
          
        case 'mcp/ping':
          send({ jsonrpc: '2.0', id: msg.id, result: 'pong' });
          return;
          
        case 'pdf/read':
          const readResult = await handlePdfRead(msg.params);
          send({ jsonrpc: '2.0', id: msg.id, result: readResult });
          return;
          
        case 'pdf/compare':
          const compareResult = await handlePdfCompare(msg.params);
          send({ jsonrpc: '2.0', id: msg.id, result: compareResult });
          return;
          
        case 'pdf/analyze_positions':
          const analysisResult = await handlePositionAnalysis(msg.params);
          send({ jsonrpc: '2.0', id: msg.id, result: analysisResult });
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
    } catch (error) {
      if (msg.id !== undefined) {
        send({ jsonrpc: '2.0', id: msg.id, error: { code: -32600, message: error instanceof Error ? error.message : String(error) } });
      }
    }
  }
}

async function handlePdfRead(params: any) {
  const { filePath } = params;
  
  if (!filePath || !fs.existsSync(filePath)) {
    throw new Error('Invalid file path or file does not exist');
  }
  
  const result = await analyzer.readPDF(filePath);
  return {
    success: true,
    filePath,
    text: result.text,
    items: result.items,
    itemCount: result.items.length
  };
}

async function handlePdfCompare(params: any) {
  const { ourPdfPath, referencePdfPath, expectedPositionsPath } = params;
  
  if (!ourPdfPath || !fs.existsSync(ourPdfPath)) {
    throw new Error('Our PDF path is invalid or file does not exist');
  }
  
  if (!referencePdfPath || !fs.existsSync(referencePdfPath)) {
    throw new Error('Reference PDF path is invalid or file does not exist');
  }
  
  const expectedPositions = expectedPositionsPath && fs.existsSync(expectedPositionsPath)
    ? JSON.parse(fs.readFileSync(expectedPositionsPath, 'utf-8'))
    : {};
  
  const report = await comparator.compareWithReference(ourPdfPath, referencePdfPath, expectedPositions);
  
  return {
    success: true,
    report,
    summary: {
      overallAccuracy: report.summary.overallAccuracy,
      needsAttention: report.summary.needsAttention,
      recommendations: report.summary.recommendations
    }
  };
}

async function handlePositionAnalysis(params: any) {
  const { ourPdfPath, referencePdfPath, expectedPositionsPath, outputPath } = params;
  
  if (!ourPdfPath || !fs.existsSync(ourPdfPath)) {
    throw new Error('Our PDF path is invalid or file does not exist');
  }
  
  if (!referencePdfPath || !fs.existsSync(referencePdfPath)) {
    throw new Error('Reference PDF path is invalid or file does not exist');
  }
  
  const expectedPositions = expectedPositionsPath && fs.existsSync(expectedPositionsPath)
    ? JSON.parse(fs.readFileSync(expectedPositionsPath, 'utf-8'))
    : {};
  
  const report = await comparator.compareWithReference(ourPdfPath, referencePdfPath, expectedPositions);
  
  if (outputPath) {
    await comparator.saveComparisonReport(report, outputPath);
  }
  
  // Generate position adjustments
  const adjustments = report.results
    .filter(r => r.status === 'misaligned' && r.analysis)
    .map(r => ({
      fieldName: r.fieldName,
      current: r.analysis!.current,
      suggested: r.analysis!.suggested,
      difference: r.analysis!.difference
    }));
  
  return {
    success: true,
    report,
    adjustments,
    adjustmentCount: adjustments.length,
    needsAdjustment: adjustments.length > 0
  };
}

// keep process alive
setInterval(() => {}, 1 << 30);
