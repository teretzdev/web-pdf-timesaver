# Draft.clio.com - UI Components Reference
## Quick Reference Guide

> **Live audit (June 2026):** See `DRAFT_CLIO_UI_DEEP_DIVE.md` §11–17 for design tokens, autopopulate chain, per-page functionality, and PDFTimeSaver style crosswalk (`UI_STYLES_REFERENCE.md`). This file retains Jan 2025 sizing notes and component inventory.

## Button Components

### Primary Action Buttons
1. **"Go to drafting"**
   - Action: Navigate from populate to edit/drafting interface
   - Style: Primary button appearance
   - Location: Bottom/center of populate interface

2. **"← Back to populate"**
   - Action: Return from drafting to populate stage
   - Style: Secondary/link button
   - Location: Left side of header bar in drafting interface

3. **"Download"**
   - Action: Generate and download final PDF documents
   - Style: Prominent action button
   - Location: Header bar (top-right area, before Sign button)

4. **"Sign"**
   - Action: Initiate document signing workflow
   - Style: Prominent action button
   - Location: Header bar (rightmost position)

5. **"Insert"**
   - Action: Add custom fields or content to document
   - Style: Toolbar button
   - Location: Header bar (left area)

### Secondary Buttons
- **"Add custom field"** - Field addition functionality
- **"Add/Remove documents"** - Document management
- **Browse** - File selection
- **Upload** - File upload action

---

## Form Input Components

### Text Input Fields
- **Single-line text inputs**
  - Width: Variable (220px - 547px typical)
  - Height: ~15-16px
  - Use cases: Names, addresses, dates, amounts, IDs

### Text Areas
- **Multi-line text inputs**
  - Width: Variable (460px - 696px)
  - Height: Variable (25px - 72px)
  - Use cases: Descriptions, comments, detailed entries

### Interactive Elements
- **Checkboxes**
  - Size: ~25px x 13-15px
  - Purpose: Boolean field selection
  
- **Radio buttons**
  - Similar sizing to checkboxes
  - Purpose: Single-choice selection

### Dropdown Selectors
- **Status dropdown**
  - Options: "In progress", "Review", "Completed"
  - Location: Top-right of interface
  - Width: ~123px
  - Height: ~36px

---

## Navigation Components

### Header Bar
**Components:**
- Logo/Branding (left)
- Client name link (breadcrumb style)
- Project name link (breadcrumb style)
- Insert button
- Status selector dropdown
- Download button
- Sign button
- Back button

**Layout:**
- Fixed top positioning
- Horizontal layout
- Consistent spacing

### Link/Breadcrumb Navigation
**Structured as:** Client → Project → Current View

**Elements:**
- Clickable client name
- Clickable project name
- Current page indicator
- Back navigation arrows

---

## Layout Components

### Document Preview Area
**Characteristics:**
- Full-width content area
- Scrollable interface
- PDF-rendered form display
- Interactive field overlay
- Responsive coordinate mapping

### Sidebar/Panel Areas
**Components:**
- Client vault section
- File upload area
- Document list
- "No client files" placeholder
- "Browse" link
- Add/remove document controls

### File Upload Interface
**Components:**
- "To upload files drag them here" prompt
- Browse button
- File list display
- Drag-and-drop support
- Document management controls

---

## Document Display Components

### Document List
**Format:**
- List of document titles
- "Add/Remove" prefix for each item
- Clear document type labels
- Toggle functionality

**Examples:**
- Add/Remove FL-150 Income and Expense Declaration
- Add/Remove FL-142 Schedule of Assets and Debts
- Add/Remove FL-141 Declaration Regarding Service...
- Add/Remove LOE UPDATED
- Add/Remove Pleading Paper
- Add/Remove Third Party Payor Addendum

### Status Indicators
**Options:**
- "In progress" (active work)
- "Review" (awaiting approval)
- "Completed" (finalized)

**Appearance:** Dropdown selector in header

---

## Content Organization

### Field Grouping
- **Sections**: Logical groupings of related fields
- **Multi-page support**: Long forms split across views
- **Section headers**: Clear visual separation

### Form Structure
- **266+ interactive elements** per document
- **Mixed input types**: Text, textarea, checkbox, radio
- **Consistent spacing**: Uniform field positioning
- **Label placement**: Consistent relative to inputs

---

## Spacing & Typography

### Field Coordinates Observed
- **X-axis range**: 23px - 1536px
- **Y-axis range**: 168px - 4231px (scrollable)
- **Field widths**: 38px - 696px
- **Field heights**: 12px - 75px

### Text Sizing
- **Button text**: ~14-17px
- **Input text**: Standard browser default (~14px)
- **Labels**: Readable with appropriate sizing
- **Instructions**: Clear instructional text

---

## Interactive Features

### Clickable Elements
- Client names (navigate to details)
- Project names (navigate to project)
- Document titles (navigate to document)
- Buttons (trigger actions)
- Links (navigation)
- Form fields (data entry)

### Drag-and-Drop
- File upload support
- Document organization
- Reordering capability (implied)

### Auto-complete/Suggestions
- Status dropdown options
- Document type selection
- Navigation options

---

## Color & Visual Design (Inferred)

### Button Hierarchy
1. **Primary Actions** (Download, Sign)
   - Most prominent
   - Right-aligned
   - Larger visual weight

2. **Navigation Actions** (Back, Insert)
   - Secondary prominence
   - Left or center-aligned
   - Medium visual weight

3. **Tertiary Actions** (Status selector)
   - Dropdown style
   - Minimal visual weight
   - Functional placement

### Visual States
- **Default**: Standard appearance
- **Hover**: Interaction feedback (implied)
- **Active**: Selected state
- **Disabled**: Grayed out (implied)

---

## Responsive Design Elements

### Scrolling Interface
- Vertical scrolling for long forms
- Fixed header during scroll
- Multiple page view support
- Continuous document rendering

### Viewport Handling
- Coordinate-based positioning
- Absolute positioning support
- Scroll-aware calculations
- Window resize handling (implied)

---

## Data Entry Enhancements

### Field Insertion
- "Insert" button for adding fields
- "Add custom field" functionality
- Dynamic form building
- Real-time field addition

### Field Management
- Field deletion (implied)
- Field editing
- Field repositioning (implied)
- Field duplication (implied)

---

## User Guidance Components

### Instructions
- **Populate page**: "Use the 'Download' button to download/print..."
- **Drafting interface**: Field labels and hints
- **Buttons**: Self-explanatory labels
- **Actions**: Clear call-to-action text

### Visual Feedback
- **Status changes**: Immediate UI updates
- **Field activation**: Visual indication
- **Form submission**: Success feedback (implied)

---

## Security & Access Control

### Client Vault
- Secured file storage
- Client-specific document access
- Upload restrictions (implied)
- File management controls

### Authentication
- Login/logout functionality
- Profile dropdown menu
- Session management
- Access control (implied)

---

## Integration Points

### Signature Service
- Electronic signature capability
- Send for external signing
- Sign directly option
- Signature tracking

### File Management
- Upload functionality
- Document storage
- Organization tools
- Version control (implied)

### PDF Generation
- Form field extraction
- PDF overlay placement
- Document combination
- Export functionality

---

## Component Summary Table

| Component Type | Count Observed | Primary Use |
|---------------|----------------|-------------|
| Text Inputs | ~200+ | Data entry fields |
| Text Areas | ~10+ | Multi-line content |
| Checkboxes/Radios | ~50+ | Boolean/Multiple choice |
| Buttons | 6-8 | Actions |
| Links | Multiple | Navigation |
| Dropdowns | 1 | Status selection |
| File Upload | 1 | Document management |

---

## Implementation Priorities

### Must-Have Components
1. ✅ Client list/grid view
2. ✅ Project detail view
3. ✅ Document management interface
4. ✅ Form editing interface
5. ✅ Download functionality
6. ✅ Sign functionality

### Should-Have Components
1. ⚡ Real-time auto-save
2. ⚡ Field validation
3. ⚡ Document preview
4. ⚡ Status tracking
5. ⚡ File drag-and-drop

### Nice-to-Have Components
1. ⭐ Field autocomplete
2. ⭐ Undo/redo functionality
3. ⭐ Document templates
4. ⭐ Advanced search
5. ⭐ Bulk operations

---

*End of Reference*

