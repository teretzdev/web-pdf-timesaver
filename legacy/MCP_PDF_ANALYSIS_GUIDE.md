# MCP PDF Analysis Guide

## Overview
This guide covers the MCP (Model Context Protocol) PDF analysis system for automatically comparing and adjusting FL-100 field positions.

## Architecture

### Components
1. **MCP Server** (`mcp-server/`) - PDF analysis engine
2. **Position Extractor** (`lib/position-extractor.js`) - Automated position extraction
3. **Position Comparator** (`mcp-server/src/position-comparator.ts`) - Comparison logic
4. **Interactive Editor** (`position-editor.html`) - Visual adjustment tool
5. **API Endpoints** (`mvp/index.php`) - Position update interface

### Data Flow
```
PDF Generation → Position Extraction → Comparison → Analysis → Adjustments → Update
```

## MCP Server

### Installation
```bash
cd mcp-server
npm install
npm run build
```

### Dependencies
- `pdf-parse` - PDF text extraction
- `pdf-lib` - PDF manipulation
- `canvas` - Image processing

### JSON-RPC Methods

#### `pdf/read`
Extract text and positions from PDF
```json
{
  "method": "pdf/read",
  "params": {
    "filePath": "output/test.pdf"
  }
}
```

#### `pdf/compare`
Compare two PDFs
```json
{
  "method": "pdf/compare",
  "params": {
    "ourPdfPath": "output/our-fl100.pdf",
    "referencePdfPath": "downloads/clio-fl100.pdf",
    "expectedPositionsPath": "data/t_fl100_gc120_positions.json"
  }
}
```

#### `pdf/analyze_positions`
Analyze field positions and suggest adjustments
```json
{
  "method": "pdf/analyze_positions",
  "params": {
    "ourPdfPath": "output/our-fl100.pdf",
    "referencePdfPath": "downloads/clio-fl100.pdf",
    "expectedPositionsPath": "data/t_fl100_gc120_positions.json",
    "outputPath": "output/analysis-report.json"
  }
}
```

## Position Analysis Workflow

### 1. Automated Analysis
```bash
# Run the automated position analysis
node scripts/auto_position_analysis.js
```

This script:
- Generates FL-100 PDF with test data
- Extracts field positions
- Compares with expected positions
- Generates analysis report
- Suggests adjustments

### 2. MCP Integration
```javascript
// Connect to MCP server
const mcpClient = new MCPClient();

// Analyze positions
const result = await mcpClient.analyzePositions(
  'output/our-fl100.pdf',
  'downloads/reference-fl100.pdf',
  'data/t_fl100_gc120_positions.json'
);

// Apply suggested adjustments
if (result.adjustments.length > 0) {
  console.log('Adjustments needed:', result.adjustments);
}
```

### 3. Interactive Editor
Open `position-editor.html` in browser:
- Load generated PDF
- View field markers
- Click to adjust positions
- Save changes via API

## Field Position Format

### Current Format
```json
{
  "attorney_name": {
    "page": 1,
    "x": 4.4,
    "y": 3.8,
    "width": 12.7,
    "fontSize": 9,
    "type": "text",
    "fontStyle": "B"
  }
}
```

### Analysis Result Format
```json
{
  "fieldName": "attorney_name",
  "current": {"x": 4.4, "y": 3.8},
  "suggested": {"x": 5.2, "y": 3.8},
  "difference": {"x": 0.8, "y": 0},
  "confidence": 0.8
}
```

## Comparison with Reference Implementations

### Draft Clio (draft.clio.com)
```javascript
// Use MCP Chrome Bridge
mcp_chrome-bridge_chrome_navigate({
  url: "http://draft.clio.com"
});

// Fill form with test data
mcp_chrome-bridge_chrome_fill_or_select({
  selector: "[name='attorney_name']",
  value: "John Michael Smith, Esq."
});

// Generate PDF and download
mcp_chrome-bridge_chrome_screenshot({
  name: "clio-draft-fl100",
  savePng: true,
  fullPage: true
});
```

### PDF TimeSavers (pdftimesavers.desktopmasters.com)
```javascript
// Navigate and fill form
mcp_chrome-bridge_chrome_navigate({
  url: "https://pdftimesavers.desktopmasters.com"
});

// Fill form fields
// Generate PDF
// Take screenshot
```

## API Endpoints

### Update Positions
```bash
POST /api/positions/update
Content-Type: application/json

{
  "attorney_name": {
    "page": 1,
    "x": 5.2,
    "y": 3.8,
    "width": 12.7,
    "fontSize": 9,
    "type": "text",
    "fontStyle": "B"
  }
}
```

### Response
```json
{
  "success": true,
  "message": "Positions updated successfully",
  "fieldCount": 1,
  "timestamp": "2024-01-15 10:30:00"
}
```

## Test Data

### Standard Test Data
```json
{
  "attorney_name": "John Michael Smith, Esq.",
  "attorney_firm": "Smith & Associates Family Law",
  "attorney_address": "1234 Legal Plaza, Suite 500",
  "attorney_city_state_zip": "Los Angeles, CA 90210",
  "attorney_phone": "(555) 123-4567",
  "attorney_email": "jsmith@smithlaw.com",
  "attorney_bar_number": "123456",
  "case_number": "FL-2024-001234",
  "court_county": "Los Angeles",
  "petitioner_name": "Sarah Elizabeth Johnson",
  "respondent_name": "Michael David Johnson",
  "petitioner_address": "123 Main Street, Los Angeles, CA 90210",
  "petitioner_phone": "(555) 987-6543",
  "respondent_address": "456 Oak Avenue, Los Angeles, CA 90211"
}
```

## Analysis Reports

### Report Structure
```json
{
  "timestamp": "2024-01-15T10:30:00Z",
  "ourPdf": "output/our-fl100.pdf",
  "referencePdf": "downloads/clio-fl100.pdf",
  "totalFields": 25,
  "misalignedFields": 3,
  "accuracy": 88.0,
  "analysis": [
    {
      "fieldName": "attorney_name",
      "current": {"x": 4.4, "y": 3.8},
      "suggested": {"x": 5.2, "y": 3.8},
      "difference": {"x": 0.8, "y": 0},
      "confidence": 0.8
    }
  ],
  "summary": {
    "overallAccuracy": 88.0,
    "needsAttention": ["attorney_name", "case_number", "petitioner_name"],
    "recommendations": [
      "Adjust 3 misaligned fields: attorney_name, case_number, petitioner_name",
      "Priority: Fix 1 critical misalignment (>5mm difference)"
    ]
  }
}
```

## Troubleshooting

### Common Issues

#### MCP Server Won't Start
```bash
# Check dependencies
cd mcp-server
npm install

# Build TypeScript
npm run build

# Check for errors
node dist/server.js
```

#### PDF Analysis Fails
- Verify PDF files exist
- Check file permissions
- Ensure PDF is not password protected
- Verify PDF contains text (not just images)

#### Position Extraction Issues
- Check field mappings in `pdf-analyzer.ts`
- Verify test data matches expected format
- Ensure PDF generation is working correctly

#### Interactive Editor Issues
- Check browser console for errors
- Verify API endpoint is accessible
- Ensure positions JSON file is valid

### Debug Mode
```bash
# Enable debug logging
export MVP_DEBUG_LOG=1

# Run analysis with verbose output
node scripts/auto_position_analysis.js
```

## Performance Optimization

### MCP Server
- Use connection pooling
- Implement caching for repeated analyses
- Optimize PDF parsing algorithms

### Position Extraction
- Cache extracted positions
- Use background processing for large PDFs
- Implement incremental updates

### Interactive Editor
- Lazy load PDF pages
- Implement virtual scrolling for large field lists
- Use Web Workers for heavy computations

## Security Considerations

### API Security
- Validate input data
- Implement rate limiting
- Use HTTPS in production
- Sanitize file paths

### File Access
- Restrict access to sensitive directories
- Validate file types
- Implement file size limits
- Use secure file upload handling

## Future Enhancements

### Planned Features
- Machine learning-based position detection
- Automated field recognition
- Real-time collaboration
- Version control for positions
- Batch processing capabilities

### Integration Opportunities
- CI/CD pipeline integration
- Automated testing
- Performance monitoring
- Error tracking and reporting

## Success Criteria

The MCP PDF analysis system is successful when:
- ✅ MCP server reads and analyzes PDFs correctly
- ✅ Position extraction identifies field locations accurately
- ✅ Comparison with reference PDFs works reliably
- ✅ Interactive editor allows visual adjustments
- ✅ API endpoints update positions successfully
- ✅ Analysis reports provide actionable insights
- ✅ Automated workflow reduces manual effort
- ✅ Field alignment accuracy exceeds 95%
