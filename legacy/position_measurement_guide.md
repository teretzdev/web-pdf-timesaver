# FL-100 Position Measurement Guide

## Overview
This guide helps you measure field positions from the draft.clio.com reference PDF and update the position data.

## Prerequisites
- Reference PDF from draft.clio.com saved as `fl100_draft_clio_reference.pdf`
- PDF reader with measurement tools (Adobe Acrobat recommended)
- Calculator for coordinate conversion

## Measurement Process

### Step 1: Set Up Your PDF Reader
1. Open `fl100_draft_clio_reference.pdf` in Adobe Acrobat
2. Go to Tools → Measure → select "Measuring Tool"
3. Set units to **Points** (72 points = 1 inch)
4. The origin (0,0) is at the **bottom-left** corner in PDF standard

### Step 2: Measure Each Field
For each field, you need to measure:
- **X position**: Distance from left edge to field start
- **Y position**: Distance from bottom edge to field baseline
- **Width**: Length of the field
- **Height**: Height of the field

### Step 3: Convert Coordinates
Our system uses:
- **Millimeters** instead of points
- **Top-left origin** instead of bottom-left
- Conversion formulas:
  - `mm = points × 0.3528`
  - `y_top_left = 279.4 - y_bottom_left` (for US Letter 11" height)

### Step 4: Record Measurements
Use this template for each field:

```
Field: attorney_name
PDF Measurements (points, bottom-left origin):
  - X: ___ pt
  - Y: ___ pt (from bottom)
  - Width: ___ pt
  - Height: ___ pt

Converted (mm, top-left origin):
  - X: ___ mm
  - Y: ___ mm (from top)
  - Width: ___ mm
  - Height: ___ mm
```

## Field List (30 Fields to Measure)

### Attorney Section (7 fields)
1. attorney_name
2. attorney_bar_number
3. attorney_firm
4. attorney_address
5. attorney_city_state_zip
6. attorney_phone
7. attorney_email

### Court Section (5 fields)
8. case_number
9. court_county
10. court_address
11. case_type
12. filing_date

### Party Section (5 fields)
13. petitioner_name
14. respondent_name
15. petitioner_address
16. petitioner_phone
17. respondent_address

### Marriage Section (5 fields)
18. marriage_date
19. separation_date
20. marriage_location
21. grounds_for_dissolution
22. dissolution_type

### Relief Section (4 fields)
23. property_division
24. spousal_support
25. attorney_fees
26. name_change

### Children Section (2 fields)
27. has_children
28. children_count

### Signature Section (3 fields)
29. additional_info
30. attorney_signature
31. signature_date

## Quick Reference

### US Letter Page Dimensions
- **Points**: 612pt × 792pt
- **Millimeters**: 215.9mm × 279.4mm

### Conversion Factors
- 1 point = 0.3528 mm
- 1 inch = 72 points = 25.4 mm
- Page height for Y conversion: 279.4 mm (792 pt)

### Y-Coordinate Conversion
If PDF shows Y = 700pt from bottom:
```
Y in mm from bottom = 700 × 0.3528 = 246.96 mm
Y in mm from top = 279.4 - 246.96 = 32.44 mm
```

## Tips
- Measure to the **baseline** where text starts, not the field box
- For checkboxes, measure to the center of the checkbox
- Round to 1 decimal place in millimeters
- Double-check measurements by comparing multiple fields in the same row

