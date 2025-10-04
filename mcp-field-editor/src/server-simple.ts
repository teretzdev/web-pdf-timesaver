#!/usr/bin/env node

import express from 'express';
import cors from 'cors';
import fs from 'fs-extra';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

interface FieldPosition {
  x: number;
  y: number;
  width?: number;
  height?: number;
}

interface FieldPositions {
  [fieldKey: string]: FieldPosition;
}

class FieldEditorServer {
  private app: express.Application;
  private port: number = 3002;

  constructor() {
    this.app = express();
    this.setupMiddleware();
    this.setupRoutes();
  }

  private setupMiddleware() {
    this.app.use(cors());
    this.app.use(express.json());
    this.app.use(express.static(path.join(__dirname, 'public')));
    // Expose uploads so the background image (e.g., fl100_background.png) can be displayed
    this.app.use('/uploads', express.static(path.join(process.cwd(), 'uploads')));
  }

  private setupRoutes() {
    // Serve the main HTML file
    this.app.get('/', (req, res) => {
      res.sendFile(path.join(__dirname, 'public', 'index.html'));
    });

    // Get template fields
    this.app.get('/api/fields/:template', async (req, res) => {
      try {
        const template = req.params.template;
        const fields = await this.getTemplateFields(template);
        res.json(fields);
      } catch (error) {
        res.status(500).json({ error: (error as Error).message });
      }
    });

    // Get field positions
    this.app.get('/api/positions/:template', async (req, res) => {
      try {
        const template = req.params.template;
        const positions = await this.getFieldPositions(template);
        res.json(positions);
      } catch (error) {
        res.status(500).json({ error: (error as Error).message });
      }
    });

    // Save field positions
    this.app.post('/api/positions/:template', async (req, res) => {
      try {
        const template = req.params.template;
        const positions = req.body;
        await this.saveFieldPositions(template, positions);
        res.json({ success: true, message: `Saved ${Object.keys(positions).length} field positions` });
      } catch (error) {
        res.status(500).json({ error: (error as Error).message });
      }
    });

    // Set individual field position
    this.app.post('/api/position/:template/:fieldKey', async (req, res) => {
      try {
        const { template, fieldKey } = req.params;
        const { x, y, width, height } = req.body;
        await this.setFieldPosition(template, fieldKey, x, y, width, height);
        res.json({ success: true, message: `Field ${fieldKey} positioned at (${x}, ${y})` });
      } catch (error) {
        res.status(500).json({ error: (error as Error).message });
      }
    });
  }

  private async getTemplateFields(template: string): Promise<any[]> {
    const templateFile = path.join(process.cwd(), 'mvp', 'templates', 'registry.php');
    
    if (!await fs.pathExists(templateFile)) {
      throw new Error('Template registry not found');
    }

    const phpContent = await fs.readFile(templateFile, 'utf-8');
    
    // Extract template fields using regex
    const templateRegex = new RegExp(`'${template}'\\s*=>\\s*\\[([\\s\\S]*?)\\]`, 'g');
    const match = templateRegex.exec(phpContent);
    
    if (!match) {
      throw new Error(`Template ${template} not found`);
    }

    const fields: any[] = [];
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

    return fields;
  }

  private async getFieldPositions(template: string): Promise<FieldPositions> {
    const positionsFile = path.join(process.cwd(), 'data', `${template}_positions.json`);
    
    if (await fs.pathExists(positionsFile)) {
      return await fs.readJson(positionsFile);
    }

    return {};
  }

  private async saveFieldPositions(template: string, positions: FieldPositions): Promise<void> {
    const positionsFile = path.join(process.cwd(), 'data', `${template}_positions.json`);
    
    await fs.ensureDir(path.dirname(positionsFile));
    await fs.writeJson(positionsFile, positions, { spaces: 2 });
  }

  private async setFieldPosition(
    template: string,
    fieldKey: string,
    x: number,
    y: number,
    width?: number,
    height?: number
  ): Promise<void> {
    const positionsFile = path.join(process.cwd(), 'data', `${template}_positions.json`);
    
    let positions: FieldPositions = {};
    if (await fs.pathExists(positionsFile)) {
      positions = await fs.readJson(positionsFile);
    }

    positions[fieldKey] = { x, y, width, height };

    await fs.ensureDir(path.dirname(positionsFile));
    await fs.writeJson(positionsFile, positions, { spaces: 2 });
  }

  public start(port: number = 3002) {
    this.port = port;
    this.app.listen(port, () => {
      console.log(`🎯 Field Editor Server running on http://localhost:${port}`);
      console.log(`📋 Drag and drop interface available at http://localhost:${port}`);
    });
  }
}

// Start the server if this file is run directly
if (import.meta.url === `file://${process.argv[1]}`) {
  const server = new FieldEditorServer();
  server.start();
}

export default FieldEditorServer;

