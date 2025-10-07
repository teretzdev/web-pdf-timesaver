# FL-100 Field Position Visual Verification Instructions

## 🎯 Purpose
This document provides instructions for visually verifying and adjusting the FL-100 form field positions using the browser-based tools.

## 📋 Prerequisites
- ✅ Server running on http://localhost:8080
- ✅ Chrome browser open with fl100_field_verification.html
- ✅ FL-100 background image loaded (uploads/fl100_background.png)
- ✅ Field position data loaded (data/t_fl100_gc120_positions.json)

## 🔍 Visual Inspection Process

### Step 1: Open the Verification Tool
The tool is now running at: **http://localhost:8080/fl100_field_verification.html**

You should see:
- FL-100 form background image
- Red field overlays on top of the form
- Control buttons at the top
- Section filter buttons
- Legend showing field types

### Step 2: Verify Field Alignment by Section

#### Attorney Information Section (Top Left)
**Fields to verify:**
1. **attorney_name** (35, 30) - Should align with "NAME" field
2. **attorney_bar_number** (145, 30) - Should align with "STATE BAR NO." field
3. **attorney_firm** (35, 35) - Should align with firm name line
4. **attorney_address** (35, 40) - Should align with address line
5. **attorney_city_state_zip** (35, 45) - Should align with city/state/zip line
6. **attorney_phone** (35, 50) - Should align with telephone field
7. **attorney_email** (120, 50) - Should align with email field

**To inspect:**
- Click "Attorney" button in section filters
- Verify each red overlay aligns with its corresponding form field
- Check if text would fit within the visible form lines

#### Court Information Section (Center/Right)
**Fields to verify:**
1. **case_number** (150, 28) - Should align with "CASE NUMBER" box (top right)
2. **court_county** (52, 115) - Should align with county field
3. **court_address** (52, 122) - Should align with court address field
4. **case_type** (52, 129) - Should align with branch name field
5. **filing_date** (52, 136) - Should align with date field

**To inspect:**
- Click "Court" button in section filters
- Verify case number box aligns with top-right form box
- Check court information fields align with center-left section

#### Party Information Section
**Fields to verify:**
1. **petitioner_name** (58, 148) - Should align with "PETITIONER" name field
2. **respondent_name** (58, 156) - Should align with "RESPONDENT" name field
3. **petitioner_address** (53, 166) - Should align with petitioner address
4. **petitioner_phone** (53, 175) - Should align with petitioner phone
5. **respondent_address** (53, 185) - Should align with respondent address

**To inspect:**
- Click "Party" button in section filters
- Verify name fields align with party name lines
- Check address fields align properly

#### Marriage Information Section
**Fields to verify:**
1. **marriage_date** (53, 197) - Should align with marriage date field
2. **separation_date** (53, 205) - Should align with separation date field
3. **marriage_location** (53, 213) - Should align with place of marriage
4. **grounds_for_dissolution** (53, 222) - Should align with grounds field

**To inspect:**
- Click "Marriage" button in section filters
- Verify date fields align with form date lines
- Check location field aligns properly

#### Relief Requested Section (Checkboxes)
**Fields to verify:**
1. **dissolution_type** (28, 238) - Should align with dissolution checkbox
2. **property_division** (28, 247) - Should align with property checkbox
3. **spousal_support** (28, 256) - Should align with support checkbox
4. **attorney_fees** (28, 265) - Should align with fees checkbox
5. **name_change** (28, 274) - Should align with name change checkbox

**To inspect:**
- Click "Relief" button in section filters
- Verify small 8x8px red boxes align with form checkboxes
- All should be vertically aligned on left side

#### Children Information Section
**Fields to verify:**
1. **has_children** (28, 286) - Should align with "no children" checkbox
2. **children_count** (53, 295) - Should align with number of children field

**To inspect:**
- Click "Children" button in section filters
- Verify checkbox and text field alignment

#### Signature Section (Bottom)
**Fields to verify:**
1. **additional_info** (53, 305) - Should align with additional info area (larger box)
2. **attorney_signature** (53, 325) - Should align with signature line
3. **signature_date** (53, 336) - Should align with date field

**To inspect:**
- Click "Signature" button in section filters
- Verify signature and date fields at bottom of form

### Step 3: Use Grid Overlay for Precision
1. Click "Show Grid" button
2. A 20px grid will appear over the form
3. Use this to check precise alignment
4. Verify fields align with form grid lines

### Step 4: Adjust Field Positions (If Needed)

**If a field is misaligned:**
1. Note the field name and current position
2. Estimate the correct position visually
3. Update `data/t_fl100_gc120_positions.json` manually with new coordinates
4. Refresh the browser to see updated positions
5. Repeat until aligned

**Adjustment Guidelines:**
- X-axis: Increase to move RIGHT, decrease to move LEFT
- Y-axis: Increase to move DOWN, decrease to move UP
- Units are in pixels
- Small adjustments: ±1-5 pixels
- Medium adjustments: ±5-15 pixels
- Large adjustments: ±15+ pixels

### Step 5: Click Individual Fields for Details
- Click any red field overlay
- A popup appears showing:
  - Field name
  - Field type
  - Section name
  - Exact position (x, y)
  - Field size (width x height)
- Use this to verify exact coordinates

## 🎨 Visual Indicators

### Field Colors
- **Red overlay with red border**: All fields
- **Opacity 10%**: Normal view
- **Opacity 30%**: Hover state

### Field Types
- **Solid border**: Text fields
- **Dashed border**: Text areas
- **Small squares (8x8)**: Checkboxes

## ✅ Verification Checklist

Use this checklist to verify each section:

- [ ] **Attorney Information**: All 7 fields aligned
- [ ] **Court Information**: All 5 fields aligned
- [ ] **Party Information**: All 5 fields aligned
- [ ] **Marriage Information**: All 4 fields aligned
- [ ] **Relief Requested**: All 5 checkboxes aligned
- [ ] **Children Information**: Both fields aligned
- [ ] **Signature Section**: All 3 fields aligned

## 📝 How to Record Adjustments

If you need to adjust positions, record them like this:

```json
{
  "field_name": {
    "old_position": {"x": 35, "y": 30},
    "new_position": {"x": 37, "y": 32},
    "reason": "Field was 2px too far left and 2px too high"
  }
}
```

## 🔄 Testing After Adjustments

After making position adjustments:
1. Update `data/t_fl100_gc120_positions.json`
2. Refresh the browser (F5)
3. Verify the new positions
4. Generate a test PDF to confirm:
   ```bash
   php test_field_alignment.php
   ```
5. Open the generated PDF and verify field positions match the form

## 📸 Taking Screenshots for Documentation

You can take screenshots manually:
1. Press F12 to open DevTools
2. Click "Toggle device toolbar" (Ctrl+Shift+M)
3. Set viewport to 794x1123 (A4 size)
4. Right-click the form container
5. Select "Capture node screenshot"

## 🎯 Expected Results

When properly aligned, you should see:
- Red overlays perfectly centered on form field lines
- Checkboxes centered on form checkboxes
- Text field widths matching form field widths
- No overlapping between adjacent fields
- Fields staying within form boundaries

## 💡 Tips

1. **Use the grid**: The 20px grid helps see alignment patterns
2. **Check one section at a time**: Use section filters to focus
3. **Toggle field visibility**: Hide/show overlays to see form clearly
4. **Compare with generated PDFs**: The ultimate test is the actual PDF output
5. **Start with obvious misalignments**: Fix large issues first, then fine-tune

## 🚀 Next Steps After Verification

Once all fields are verified and adjusted:
1. Export the final positions (click "Export Positions")
2. Save as `data/t_fl100_gc120_positions_verified.json`
3. Replace the original positions file if needed
4. Generate test PDFs to confirm
5. Document any changes made
6. Update the field fillers if positions changed significantly

---

**Current Status:**
✅ Tool loaded and ready
✅ All 31 fields displayed
⏳ Awaiting visual verification
⏳ Adjustments pending (if needed)










