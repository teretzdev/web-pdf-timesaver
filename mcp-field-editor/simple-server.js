const express = require('express');
const cors = require('cors');
const fs = require('fs-extra');
const path = require('path');

const app = express();
const port = 3001;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname, 'dist', 'public')));
app.use('/uploads', express.static(path.join(__dirname, '..', 'uploads')));

// Routes
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'dist', 'public', 'index.html'));
});

// Get template fields
app.get('/api/fields/:template', async (req, res) => {
  try {
    const template = req.params.template;
    const templateFile = path.join(__dirname, '..', 'mvp', 'templates', 'registry.php');
    
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

    const fields = [];
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

    res.json(fields);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Get field positions
app.get('/api/positions/:template', async (req, res) => {
  try {
    const template = req.params.template;
    const positionsFile = path.join(__dirname, '..', 'data', `${template}_positions.json`);
    
    if (await fs.pathExists(positionsFile)) {
      const positions = await fs.readJson(positionsFile);
      res.json(positions);
    } else {
      res.json({});
    }
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Save field positions
app.post('/api/positions/:template', async (req, res) => {
  try {
    const template = req.params.template;
    const positions = req.body;
    const positionsFile = path.join(__dirname, '..', 'data', `${template}_positions.json`);
    
    await fs.ensureDir(path.dirname(positionsFile));
    await fs.writeJson(positionsFile, positions, { spaces: 2 });
    
    res.json({ success: true, message: `Saved ${Object.keys(positions).length} field positions` });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Start server
app.listen(port, () => {
  console.log(`🎯 Field Editor Server running on http://localhost:${port}`);
  console.log(`📋 Drag and drop interface available at http://localhost:${port}`);
});
