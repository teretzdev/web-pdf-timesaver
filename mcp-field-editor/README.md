# MCP Field Editor Server

A Model Context Protocol (MCP) server for drag-and-drop field positioning on PDF forms, specifically designed for the FL-100 form editor.

## Features

- 🎯 **Drag & Drop Interface**: Intuitive web-based field positioning
- 💾 **Save/Load Positions**: Persistent storage of field positions
- 📤 **Import/Export**: Share field configurations
- 🔧 **MCP Integration**: Full MCP server with tool support
- 🎨 **Modern UI**: Clean, responsive interface
- 📐 **Grid Overlay**: Precise positioning assistance
- 🔍 **Zoom Controls**: Detailed field placement

## Installation

1. Install dependencies:
```bash
npm install
```

2. Build the TypeScript code:
```bash
npm run build
```

3. Start the MCP server:
```bash
npm start
```

## Development

For development with auto-rebuild:
```bash
npm run dev
```

## MCP Tools

The server provides the following MCP tools:

### `get_field_positions`
Get current field positions for a template.

### `set_field_position`
Set position for a specific field with coordinates.

### `save_field_positions`
Save all field positions to file.

### `load_field_positions`
Load field positions from file.

### `get_template_fields`
Get available fields for a template.

### `start_field_editor_web`
Start the web-based field editor interface.

### `generate_pdf_with_positions`
Generate PDF using current field positions.

## Web Interface

The web interface runs on port 3001 by default and provides:

- **Field Palette**: Drag fields from the left panel
- **PDF Canvas**: Drop fields onto the form layout
- **Position Controls**: Fine-tune field placement
- **Save/Load**: Persistent position storage
- **Grid Overlay**: Precise positioning assistance
- **Zoom Controls**: Detailed field placement

## Usage

1. Start the MCP server
2. Use the `start_field_editor_web` tool to launch the web interface
3. Open http://localhost:3001 in your browser
4. Drag fields from the palette to position them on the form
5. Save your positions for future use

## File Structure

```
mcp-field-editor/
├── src/
│   ├── server.ts          # MCP server implementation
│   └── public/
│       └── index.html     # Web interface
├── package.json
├── tsconfig.json
└── README.md
```

## Integration

This MCP server integrates with the existing PDF form filler by:

1. Storing field positions in JSON format
2. Providing API endpoints for position management
3. Supporting the existing template system
4. Enabling visual field positioning

## API Endpoints

- `GET /api/fields/:template` - Get template fields
- `GET /api/positions/:template` - Get field positions
- `POST /api/positions/:template` - Save field positions
- `POST /api/position/:template/:fieldKey` - Set individual field position

