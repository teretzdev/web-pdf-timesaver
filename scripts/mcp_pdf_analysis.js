#!/usr/bin/env node
/**
 * MCP PDF Analysis Script
 * Uses the MCP server to analyze PDF field positions
 */

const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

class MCPPdfAnalyzer {
    constructor() {
        this.projectRoot = path.join(__dirname, '..');
        this.mcpServerPath = path.join(this.projectRoot, 'mcp-server', 'dist', 'server.js');
    }

    /**
     * Send JSON-RPC message to MCP server
     */
    async sendMCPMessage(message) {
        return new Promise((resolve, reject) => {
            const mcpProcess = spawn('node', [this.mcpServerPath], {
                stdio: ['pipe', 'pipe', 'pipe']
            });

            let output = '';
            let errorOutput = '';

            mcpProcess.stdout.on('data', (data) => {
                output += data.toString();
            });

            mcpProcess.stderr.on('data', (data) => {
                errorOutput += data.toString();
            });

            mcpProcess.on('close', (code) => {
                if (code === 0) {
                    try {
                        // Parse JSON-RPC response
                        const lines = output.split('\n');
                        for (const line of lines) {
                            if (line.trim().startsWith('{')) {
                                const response = JSON.parse(line);
                                resolve(response);
                                return;
                            }
                        }
                        resolve({ error: 'No valid JSON response found' });
                    } catch (error) {
                        reject(new Error(`Failed to parse response: ${error.message}`));
                    }
                } else {
                    reject(new Error(`MCP server exited with code ${code}: ${errorOutput}`));
                }
            });

            // Send the message
            const messageStr = JSON.stringify(message);
            const contentLength = Buffer.byteLength(messageStr, 'utf8');
            const header = `Content-Length: ${contentLength}\r\n\r\n`;
            
            mcpProcess.stdin.write(header + messageStr);
            mcpProcess.stdin.end();
        });
    }

    /**
     * Initialize MCP connection
     */
    async initialize() {
        const initMessage = {
            jsonrpc: '2.0',
            id: 1,
            method: 'initialize',
            params: {
                protocolVersion: '2024-11-05',
                capabilities: {},
                clientInfo: {
                    name: 'pdf-analyzer',
                    version: '1.0.0'
                }
            }
        };

        try {
            const response = await this.sendMCPMessage(initMessage);
            console.log('✓ MCP server initialized');
            return response;
        } catch (error) {
            console.error('❌ Failed to initialize MCP server:', error.message);
            throw error;
        }
    }

    /**
     * Read PDF using MCP server
     */
    async readPDF(pdfPath) {
        const readMessage = {
            jsonrpc: '2.0',
            id: 2,
            method: 'pdf/read',
            params: {
                filePath: pdfPath
            }
        };

        try {
            console.log(`📖 Reading PDF: ${pdfPath}`);
            const response = await this.sendMCPMessage(readMessage);
            
            if (response.error) {
                throw new Error(response.error.message);
            }

            console.log('✓ PDF read successfully');
            console.log(`Text length: ${response.result.text.length} characters`);
            console.log(`Items found: ${response.result.items.length}`);
            
            return response.result;
        } catch (error) {
            console.error('❌ Failed to read PDF:', error.message);
            throw error;
        }
    }

    /**
     * Analyze PDF positions using MCP server
     */
    async analyzePositions(pdfPath, expectedPositionsPath) {
        const analysisMessage = {
            jsonrpc: '2.0',
            id: 3,
            method: 'pdf/analyze_positions',
            params: {
                pdfPath: pdfPath,
                expectedPositionsPath: expectedPositionsPath
            }
        };

        try {
            console.log(`🔍 Analyzing positions in: ${pdfPath}`);
            const response = await this.sendMCPMessage(analysisMessage);
            
            if (response.error) {
                throw new Error(response.error.message);
            }

            console.log('✓ Position analysis completed');
            return response.result;
        } catch (error) {
            console.error('❌ Failed to analyze positions:', error.message);
            throw error;
        }
    }

    /**
     * Compare PDF with reference using MCP server
     */
    async compareWithReference(ourPdfPath, referencePdfPath, expectedPositionsPath) {
        const compareMessage = {
            jsonrpc: '2.0',
            id: 4,
            method: 'pdf/compare',
            params: {
                ourPdfPath: ourPdfPath,
                referencePdfPath: referencePdfPath,
                expectedPositionsPath: expectedPositionsPath
            }
        };

        try {
            console.log(`🔍 Comparing PDFs:`);
            console.log(`  Our PDF: ${ourPdfPath}`);
            console.log(`  Reference: ${referencePdfPath}`);
            
            const response = await this.sendMCPMessage(compareMessage);
            
            if (response.error) {
                throw new Error(response.error.message);
            }

            console.log('✓ PDF comparison completed');
            return response.result;
        } catch (error) {
            console.error('❌ Failed to compare PDFs:', error.message);
            throw error;
        }
    }

    /**
     * Run comprehensive PDF analysis
     */
    async runAnalysis() {
        try {
            console.log('=== MCP PDF Analysis ===\n');

            // Initialize MCP server
            await this.initialize();

            // Paths
            const pdfPath = path.join(this.projectRoot, 'output', 'mvp_20251009_205754_t_fl100_gc120_positioned.pdf');
            const expectedPositionsPath = path.join(this.projectRoot, 'data', 't_fl100_gc120_positions.json');
            const referencePdfPath = path.join(this.projectRoot, 'uploads', 'fl100_official.pdf');

            // Check if files exist
            if (!fs.existsSync(pdfPath)) {
                throw new Error(`PDF not found: ${pdfPath}`);
            }
            if (!fs.existsSync(expectedPositionsPath)) {
                throw new Error(`Expected positions file not found: ${expectedPositionsPath}`);
            }

            // Read PDF
            const pdfData = await this.readPDF(pdfPath);

            // Analyze positions
            console.log(`PDF Path: ${pdfPath}`);
            console.log(`Expected Positions Path: ${expectedPositionsPath}`);
            console.log(`PDF exists: ${fs.existsSync(pdfPath)}`);
            console.log(`Expected positions exists: ${fs.existsSync(expectedPositionsPath)}`);
            
            const analysisResult = await this.analyzePositions(pdfPath, expectedPositionsPath);

            // Compare with reference if available
            let comparisonResult = null;
            if (fs.existsSync(referencePdfPath)) {
                comparisonResult = await this.compareWithReference(pdfPath, referencePdfPath, expectedPositionsPath);
            }

            // Display results
            console.log('\n=== Analysis Results ===');
            console.log(`PDF Text Preview: ${pdfData.text.substring(0, 200)}...`);
            console.log(`Total Text Items: ${pdfData.items.length}`);
            
            if (analysisResult) {
                console.log(`\nPosition Analysis:`);
                console.log(`  Total Fields: ${analysisResult.totalFields || 'N/A'}`);
                console.log(`  Accuracy: ${analysisResult.accuracy || 'N/A'}%`);
                console.log(`  Misaligned: ${analysisResult.misalignedFields || 'N/A'}`);
            }

            if (comparisonResult) {
                console.log(`\nComparison Results:`);
                console.log(`  Overall Accuracy: ${comparisonResult.overallAccuracy || 'N/A'}%`);
                console.log(`  Fields Needing Attention: ${comparisonResult.needsAttention?.length || 0}`);
            }

            return {
                pdfData,
                analysisResult,
                comparisonResult
            };

        } catch (error) {
            console.error('\n❌ Analysis failed:', error.message);
            throw error;
        }
    }
}

// Run if called directly
if (require.main === module) {
    const analyzer = new MCPPdfAnalyzer();
    analyzer.runAnalysis().then(result => {
        console.log('\n🎉 MCP PDF analysis completed successfully!');
        process.exit(0);
    }).catch(error => {
        console.error('\n❌ MCP PDF analysis failed:', error.message);
        process.exit(1);
    });
}

module.exports = MCPPdfAnalyzer;
