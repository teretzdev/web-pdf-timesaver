# MCP Browser Automation - FL-100 Verification Guide

## Overview

This guide shows you how to use MCP Chrome Bridge tools to automatically compare FL-100 PDFs from your system with reference implementations.

## Prerequisites

- MCP Chrome Bridge extension installed
- Chrome browser running
- Test data prepared (see below)

## Test Data

Use this consistent data across all systems:

```json
{
  "attorney_name": "John Michael Smith, Esq.",
  "attorney_firm": "Smith & Associates Family Law",
  "attorney_bar_number": "123456",
  "case_number": "FL-2024-001234",
  "court_county": "Los Angeles",
  "petitioner_name": "Sarah Elizabeth Johnson",
  "respondent_name": "Michael David Johnson"
}
```

## Step-by-Step MCP Automation

### 1. Generate PDF from Our System

```javascript
// Navigate to your local MVP
mcp_chrome-bridge_chrome_navigate({
  url: "http://localhost/Web-PDFTimeSaver/mvp/"
})

// Wait a moment, then get current page
mcp_chrome-bridge_chrome_get_web_content({
  textContent: true
})

// Navigate to projects
mcp_chrome-bridge_chrome_click_element({
  selector: "a[href*='route=projects']",
  waitForNavigation: true
})

// Create new project or select existing one
// Click on a project link
mcp_chrome-bridge_chrome_click_element({
  selector: "a[href*='route=project']",
  waitForNavigation: true
})

// If FL-100 document exists, click Populate
// Otherwise, add FL-100 document first

// Fill attorney name field
mcp_chrome-bridge_chrome_fill_or_select({
  selector: "[name='attorney_name']",  // or [name='attorney.name']
  value: "John Michael Smith, Esq."
})

// Fill other fields similarly...

// Save and generate PDF
mcp_chrome-bridge_chrome_click_element({
  selector: "button[type='submit']",
  waitForNavigation: true
})

// Click Generate
mcp_chrome-bridge_chrome_click_element({
  selector: "a[href*='generate']",
  waitForNavigation: true
})

// Screenshot the result
mcp_chrome-bridge_chrome_screenshot({
  name: "our-fl100-generated",
  savePng: true,
  fullPage: true
})
```

### 2. Test Draft Clio (draft.clio.com)

```javascript
// Navigate to Draft Clio
mcp_chrome-bridge_chrome_navigate({
  url: "http://draft.clio.com",
  newWindow: true
})

// Wait for page load
mcp_chrome-bridge_chrome_get_web_content({
  textContent: true
})

// Look for FL-100 form or document selector
mcp_chrome-bridge_chrome_get_interactive_elements({
  textQuery: "FL-100"
})

// Fill the form - EXAMPLE selectors (adjust based on actual page):
mcp_chrome-bridge_chrome_fill_or_select({
  selector: "[name='attorney_name']",  // Adjust selector as needed
  value: "John Michael Smith, Esq."
})

mcp_chrome-bridge_chrome_fill_or_select({
  selector: "[name='attorney_firm']",
  value: "Smith & Associates Family Law"
})

// Continue filling all fields with same test data...

// Generate/Submit the form
mcp_chrome-bridge_chrome_click_element({
  selector: "button[type='submit']",  // Adjust as needed
  waitForNavigation: true
})

// Take screenshot of result
mcp_chrome-bridge_chrome_screenshot({
  name: "clio-fl100-generated",
  savePng: true,
  fullPage: true
})

// If PDF is generated, look for download link
mcp_chrome-bridge_chrome_get_interactive_elements({
  textQuery: "download"
})

// Click download if available
mcp_chrome-bridge_chrome_click_element({
  selector: "a[href*='download']"  // Adjust as needed
})
```

### 3. Test PDF TimeSavers (pdftimesavers.desktopmasters.com)

```javascript
// Navigate to PDF TimeSavers
mcp_chrome-bridge_chrome_navigate({
  url: "https://pdftimesavers.desktopmasters.com",
  newWindow: true
})

// Get page content
mcp_chrome-bridge_chrome_get_web_content({
  textContent: true
})

// Find FL-100 form
mcp_chrome-bridge_chrome_get_interactive_elements({
  textQuery: "FL-100"
})

// Fill form fields with same test data
mcp_chrome-bridge_chrome_fill_or_select({
  selector: "[name='attorney_name']",  // Adjust as needed
  value: "John Michael Smith, Esq."
})

// Continue with all fields...

// Generate PDF
mcp_chrome-bridge_chrome_click_element({
  selector: "button[type='submit']",
  waitForNavigation: true
})

// Screenshot result
mcp_chrome-bridge_chrome_screenshot({
  name: "pdftimesavers-fl100-generated",
  savePng: true,
  fullPage: true
})

// Download PDF if available
mcp_chrome-bridge_chrome_get_interactive_elements({
  textQuery: "download"
})
```

### 4. Compare Screenshots

After generating all three PDFs and taking screenshots:

1. **Our System**: `our-fl100-generated.png`
2. **Draft Clio**: `clio-fl100-generated.png`
3. **PDF TimeSavers**: `pdftimesavers-fl100-generated.png`

**Visual Comparison Checklist**:
- [ ] Attorney name position matches
- [ ] Case number position matches
- [ ] Petitioner/Respondent names align
- [ ] Checkbox positions are correct
- [ ] Court information placement matches
- [ ] Font sizes appear similar
- [ ] Overall layout is consistent

### 5. Adjust Positions (If Needed)

If you find misalignments, edit `data/t_fl100_gc120_positions.json`:

```json
{
  "attorney_name": {
    "x": 10,      // Move left/right (increase to move right)
    "y": 18,      // Move up/down (increase to move down)
    "width": 85,
    "fontSize": 9
  }
}
```

Coordinate system:
- Origin (0, 0) is top-left corner
- X increases going right
- Y increases going down
- Units are millimeters
- Page size: 215.9mm × 279.4mm (US Letter)

## Troubleshooting MCP Commands

### If element not found:
```javascript
// First, get all interactive elements
mcp_chrome-bridge_chrome_get_interactive_elements({})

// Then find the specific selector you need
```

### If form fields won't fill:
```javascript
// Try injecting JavaScript directly
mcp_chrome-bridge_chrome_inject_script({
  type: "MAIN",
  jsScript: `
    document.querySelector('[name="attorney_name"]').value = "John Michael Smith, Esq.";
  `
})
```

### If navigation fails:
```javascript
// Check current page first
mcp_chrome-bridge_chrome_get_web_content({
  textContent: true
})

// Verify URL
mcp_chrome-bridge_chrome_get_windows_and_tabs()
```

## Quick Reference: Essential MCP Commands

### Navigation
```javascript
mcp_chrome-bridge_chrome_navigate({ url: "..." })
```

### Get Page Info
```javascript
mcp_chrome-bridge_chrome_get_web_content({ textContent: true })
```

### Find Elements
```javascript
mcp_chrome-bridge_chrome_get_interactive_elements({ textQuery: "..." })
```

### Fill Form
```javascript
mcp_chrome-bridge_chrome_fill_or_select({ 
  selector: "...", 
  value: "..." 
})
```

### Click Element
```javascript
mcp_chrome-bridge_chrome_click_element({ 
  selector: "...",
  waitForNavigation: true 
})
```

### Screenshot
```javascript
mcp_chrome-bridge_chrome_screenshot({ 
  name: "...",
  savePng: true 
})
```

### Inject Code
```javascript
mcp_chrome-bridge_chrome_inject_script({
  type: "MAIN",
  jsScript: "..."
})
```

## Automated Workflow Script

For a fully automated comparison, you can chain all commands:

```javascript
async function verifyFL100() {
  // 1. Our System
  await chrome_navigate({ url: "http://localhost/Web-PDFTimeSaver/mvp/" });
  // ... fill form, generate, screenshot
  
  // 2. Draft Clio
  await chrome_navigate({ url: "http://draft.clio.com", newWindow: true });
  // ... fill form, generate, screenshot
  
  // 3. PDF TimeSavers  
  await chrome_navigate({ url: "https://pdftimesavers.desktopmasters.com", newWindow: true });
  // ... fill form, generate, screenshot
  
  // 4. Compare
  console.log("Screenshots saved. Please compare manually:");
  console.log("- our-fl100-generated.png");
  console.log("- clio-fl100-generated.png");
  console.log("- pdftimesavers-fl100-generated.png");
}
```

## Expected Results

After verification, you should see:

### ✅ Success Criteria:
- All three systems generate FL-100 PDFs
- Field positions align within 2-3mm tolerance
- Text appears in correct locations
- Checkboxes render in same positions
- Font sizes are comparable
- Overall form layout matches

### 📝 Minor Adjustments:
If small discrepancies exist:
1. Note the field name
2. Measure pixel difference
3. Convert to millimeters (divide by ~3.78 for 96 DPI)
4. Adjust position in JSON file
5. Regenerate and re-test

## Completion Checklist

- [ ] Generated PDF from our system
- [ ] Took screenshot of our PDF
- [ ] Tested Draft Clio FL-100
- [ ] Took screenshot of Clio PDF
- [ ] Tested PDF TimeSavers FL-100
- [ ] Took screenshot of TimeSavers PDF
- [ ] Compared all three visually
- [ ] Documented any position differences
- [ ] Made adjustments to positions.json (if needed)
- [ ] Re-tested after adjustments
- [ ] Verified final PDF accuracy

---

**Once all checkboxes are complete, your FL-100 PDF system is verified and production-ready!**

