#!/usr/bin/env node

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';
import express from 'express';
import cors from 'cors';
import multer from 'multer';
import fs from 'fs-extra';
import path from 'path';

interface FieldPosition {
  x: number;
  y: number;
  width?: number;
  height?: number;
}

interface FieldPositions {
  [fieldKey: string]: FieldPosition;
}

interface TemplateField {
  key: string;
  label: string;
  type: string;
  required?: boolean;
}

class FieldEditorMCPServer {
  private server: Server;
  private fieldPositions: FieldPositions = {};
  private positionsFile: string;
  private webServer!: express.Application;
  private webPort: number = 3001;
  private webServerInstance: any = null;

  constructor() {
    this.server = new Server(
      {
        name: 'field-editor-mcp',
        version: '1.0.0',
      }
    );

    this.positionsFile = path.join(process.cwd(), 'field_positions.json');
    this.setupToolHandlers();
    this.setupWebServer();
  }

  private setupToolHandlers() {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => {
      return {
        tools: [
          {
            name: 'get_field_positions',
            description: 'Get current field positions for a template',
            inputSchema: {
              type: 'object',
              properties: {
                template: {
                  type: 'string',
                  description: 'Template name (e.g., t_fl100_gc120)',
                },
              },
              required: ['template'],
            },
          },
          {
            name: 'set_field_position',
            description: 'Set position for a specific field',
            inputSchema: {
              type: 'object',
              properties: {
                template: {
                  type: 'string',
                  description: 'Template name',
                },
                fieldKey: {
                  type: 'string',
                  description: 'Field key to position',
                },
                x: {
                  type: 'number',
                  description: 'X coordinate',
                },
                y: {
                  type: 'number',
                  description: 'Y coordinate',
                },
                width: {
                  type: 'number',
                  description: 'Field width (optional)',
                },
                height: {
                  type: 'number',
                  description: 'Field height (optional)',
                },
              },
              required: ['template', 'fieldKey', 'x', 'y'],
            },
          },
          {
            name: 'save_field_positions',
            description: 'Save all field positions to file',
            inputSchema: {
              type: 'object',
              properties: {
                template: {
                  type: 'string',
                  description: 'Template name',
                },
                positions: {
                  type: 'object',
                  description: 'Field positions object',
                },
              },
              required: ['template', 'positions'],
            },
          },
          {
            name: 'load_field_positions',
            description: 'Load field positions from file',
            inputSchema: {
              type: 'object',
              properties: {
                template: {
                  type: 'string',
                  description: 'Template name',
                },
              },
              required: ['template'],
            },
          },
          {
            name: 'get_template_fields',
            description: 'Get available fields for a template',
            inputSchema: {
              type: 'object',
              properties: {
                template: {
                  type: 'string',
                  description: 'Template name',
                },
              },
              required: ['template'],
            },
          },
          {
            name: 'start_field_editor_web',
            description: 'Start the web-based field editor interface',
            inputSchema: {
              type: 'object',
              properties: {
                port: {
                  type: 'number',
                  description: 'Port to run the web server on',
                  default: 3001,
                },
              },
            },
          },
          {
            name: 'generate_pdf_with_positions',
            description: 'Generate PDF using current field positions',
            inputSchema: {
              type: 'object',
              properties: {
                template: {
                  type: 'string',
                  description: 'Template name',
                },
                testData: {
                  type: 'object',
                  description: 'Test data to fill the form',
                },
              },
              required: ['template'],
            },
          },
        ],
      };
    });

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        switch (name) {
          case 'get_field_positions':
            return await this.getFieldPositions((args as any).template);
          
          case 'set_field_position':
            return await this.setFieldPosition(
              (args as any).template,
              (args as any).fieldKey,
              (args as any).x,
              (args as any).y,
              (args as any).width,
              (args as any).height
            );
          
          case 'save_field_positions':
            return await this.saveFieldPositions((args as any).template, (args as any).positions);
          
          case 'load_field_positions':
            return await this.loadFieldPositions((args as any).template);
          
          case 'get_template_fields':
            return await this.getTemplateFields((args as any).template);
          
          case 'start_field_editor_web':
            return await this.startFieldEditorWeb((args as any).port || 3001);
          
          case 'generate_pdf_with_positions':
            return await this.generatePdfWithPositions((args as any).template, (args as any).testData);
          
          default:
            throw new Error(`Unknown tool: ${name}`);
        }
      } catch (error) {
        return {
          content: [
            {
              type: 'text',
              text: `Error: ${error instanceof Error ? error.message : String(error)}`,
            },
          ],
        };
      }
    });
  }

  private async getFieldPositions(template: string): Promise<any> {
    const positionsFile = path.join(process.cwd(), 'data', `${template}_positions.json`);
    
    if (await fs.pathExists(positionsFile)) {
      const positions = await fs.readJson(positionsFile);
      return {
        content: [
          {
            type: 'text',
            text: JSON.stringify(positions, null, 2),
          },
        ],
      };
    }

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({}, null, 2),
        },
      ],
    };
  }

  private async setFieldPosition(
    template: string,
    fieldKey: string,
    x: number,
    y: number,
    width?: number,
    height?: number
  ): Promise<any> {
    const positionsFile = path.join(process.cwd(), 'data', `${template}_positions.json`);
    
    let positions: FieldPositions = {};
    if (await fs.pathExists(positionsFile)) {
      positions = await fs.readJson(positionsFile);
    }

    positions[fieldKey] = { x, y, width, height };

    await fs.ensureDir(path.dirname(positionsFile));
    await fs.writeJson(positionsFile, positions, { spaces: 2 });

    return {
      content: [
        {
          type: 'text',
          text: `Field ${fieldKey} positioned at (${x}, ${y})`,
        },
      ],
    };
  }

  private async saveFieldPositions(template: string, positions: FieldPositions): Promise<any> {
    const positionsFile = path.join(process.cwd(), 'data', `${template}_positions.json`);
    
    await fs.ensureDir(path.dirname(positionsFile));
    await fs.writeJson(positionsFile, positions, { spaces: 2 });

    return {
      content: [
        {
          type: 'text',
          text: `Saved ${Object.keys(positions).length} field positions for template ${template}`,
        },
      ],
    };
  }

  private async loadFieldPositions(template: string): Promise<any> {
    const positionsFile = path.join(process.cwd(), 'data', `${template}_positions.json`);
    
    if (await fs.pathExists(positionsFile)) {
      const positions = await fs.readJson(positionsFile);
      return {
        content: [
          {
            type: 'text',
            text: JSON.stringify(positions, null, 2),
          },
        ],
      };
    }

    return {
      content: [
        {
          type: 'text',
          text: 'No saved positions found',
        },
      ],
    };
  }

  private async getTemplateFields(template: string): Promise<any> {
    const templateFile = path.join(process.cwd(), 'mvp', 'templates', 'registry.php');
    
    if (!await fs.pathExists(templateFile)) {
      throw new Error('Template registry not found');
    }

    // Read and parse PHP template file
    const phpContent = await fs.readFile(templateFile, 'utf-8');
    
    // Extract template fields using regex (simplified approach)
    const templateRegex = new RegExp(`'${template}'\\s*=>\\s*\\[([\\s\\S]*?)\\]`, 'g');
    const match = templateRegex.exec(phpContent);
    
    if (!match) {
      throw new Error(`Template ${template} not found`);
    }

    const fields: TemplateField[] = [];
    const fieldRegex = /'([^']+)'\s*=>\s*\[([^\]]+)\]/g;
    let fieldMatch;
    
    while ((fieldMatch = fieldRegex.exec(match[1])) !== null) {
      const fieldKey = fieldMatch[1];
      const fieldContent = fieldMatch[2];
      
      // Extract label
      const labelMatch = fieldContent.match(/'label'\s*=>\s*'([^']+)'/);
      const label = labelMatch ? labelMatch[1] : fieldKey;
      
      // Extract type
      const typeMatch = fieldContent.match(/'type'\s*=>\s*'([^']+)'/);
      const type = typeMatch ? typeMatch[1] : 'text';
      
      fields.push({
        key: fieldKey,
        label: label,
        type: type,
      });
    }

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify(fields, null, 2),
        },
      ],
    };
  }

  private async startFieldEditorWeb(port: number): Promise<any> {
    this.webPort = port;
    
    // Start web server if not already running
    if (!this.webServerInstance) {
      this.webServerInstance = this.webServer.listen(port, () => {
        console.log(`Field editor web interface running on http://localhost:${port}`);
      });
    }

    return {
      content: [
        {
          type: 'text',
          text: `Field editor web interface started on http://localhost:${port}`,
        },
      ],
    };
  }

  private async generatePdfWithPositions(template: string, testData?: any): Promise<any> {
    // This would integrate with the existing PDF form filler
    // For now, return a message indicating the integration point
    return {
      content: [
        {
          type: 'text',
          text: `PDF generation with positions for template ${template} - integration with existing PDF form filler needed`,
        },
      ],
    };
  }

  private setupWebServer() {
    this.webServer = express();
    this.webServer.use(cors());
    this.webServer.use(express.json());
    this.webServer.use(express.static(path.join(__dirname, 'public')));

    // API endpoints
    this.webServer.get('/api/fields/:template', async (req, res) => {
      try {
        const result = await this.getTemplateFields(req.params.template);
        res.json(JSON.parse(result.content[0].text));
      } catch (error) {
        res.status(500).json({ error: (error as Error).message });
      }
    });

    this.webServer.get('/api/positions/:template', async (req, res) => {
      try {
        const result = await this.getFieldPositions(req.params.template);
        res.json(JSON.parse(result.content[0].text));
      } catch (error) {
        res.status(500).json({ error: (error as Error).message });
      }
    });

    this.webServer.post('/api/positions/:template', async (req, res) => {
      try {
        const result = await this.saveFieldPositions(req.params.template, req.body);
        res.json({ success: true, message: result.content[0].text });
      } catch (error) {
        res.status(500).json({ error: (error as Error).message });
      }
    });

    this.webServer.post('/api/position/:template/:fieldKey', async (req, res) => {
      try {
        const { x, y, width, height } = req.body;
        const result = await this.setFieldPosition(
          req.params.template,
          req.params.fieldKey,
          x,
          y,
          width,
          height
        );
        res.json({ success: true, message: result.content[0].text });
      } catch (error) {
        res.status(500).json({ error: (error as Error).message });
      }
    });
  }

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.error('Field Editor MCP server running on stdio');
  }
}

const server = new FieldEditorMCPServer();
server.run().catch(console.error);
