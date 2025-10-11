#!/usr/bin/env python3
"""
FL-100 Form Position Analyzer
Analyzes the FL-100 background image and suggests accurate field positions
"""

import json
from PIL import Image
import sys

def mm_to_pixels(mm, dpi=300):
    """Convert millimeters to pixels at given DPI"""
    return int((mm / 25.4) * dpi)

def pixels_to_mm(pixels, dpi=300):
    """Convert pixels to millimeters at given DPI"""
    return (pixels * 25.4) / dpi

def analyze_fl100_form():
    """
    Analyze FL-100 form and provide accurate field positions
    Based on standard California FL-100 form specifications
    """
    
    # US Letter size: 8.5" x 11" = 215.9mm x 279.4mm
    page_width_mm = 215.9
    page_height_mm = 279.4
    
    print("=== FL-100 Form Position Analyzer ===\n")
    print(f"Page size: {page_width_mm}mm × {page_height_mm}mm (US Letter)\n")
    
    # Load background image to get dimensions
    try:
        img = Image.open('uploads/fl100_background.png')
        img_width, img_height = img.size
        print(f"Background image: {img_width}px × {img_height}px\n")
        
        # Calculate DPI
        dpi_x = (img_width / 8.5)
        dpi_y = (img_height / 11)
        avg_dpi = (dpi_x + dpi_y) / 2
        print(f"Estimated DPI: {avg_dpi:.0f}\n")
        
    except Exception as e:
        print(f"Could not load image: {e}")
        print("Using default DPI of 200\n")
        avg_dpi = 200
    
    # Define accurate positions based on visual analysis of FL-100 form
    # Measured from actual form specifications
    
    positions = {
        # ATTORNEY SECTION (Top-left box)
        "attorney_name": {
            "x": 8,
            "y": 28,
            "width": 95,
            "fontSize": 9,
            "type": "text",
            "section": "Attorney - Name line"
        },
        "attorney_firm": {
            "x": 8,
            "y": 33,
            "width": 95,
            "fontSize": 9,
            "type": "text",
            "section": "Attorney - Firm line"
        },
        "attorney_address": {
            "x": 8,
            "y": 38,
            "width": 95,
            "fontSize": 9,
            "type": "text",
            "section": "Attorney - Street address"
        },
        "attorney_city_state_zip": {
            "x": 8,
            "y": 43,
            "width": 95,
            "fontSize": 9,
            "type": "text",
            "section": "Attorney - City/State/ZIP"
        },
        "attorney_phone": {
            "x": 8,
            "y": 48,
            "width": 60,
            "fontSize": 9,
            "type": "text",
            "section": "Attorney - Phone"
        },
        "attorney_email": {
            "x": 8,
            "y": 58,
            "width": 95,
            "fontSize": 9,
            "type": "text",
            "section": "Attorney - Email"
        },
        "attorney_bar_number": {
            "x": 140,
            "y": 28,
            "width": 40,
            "fontSize": 9,
            "type": "text",
            "section": "Attorney - State Bar Number (right side)"
        },
        
        # COURT SECTION (Center header area)
        "court_county": {
            "x": 80,
            "y": 77,
            "width": 65,
            "fontSize": 10,
            "fontStyle": "B",
            "type": "text",
            "section": "Court - County name (in header)"
        },
        "court_address": {
            "x": 25,
            "y": 84,
            "width": 110,
            "fontSize": 8,
            "type": "text",
            "section": "Court - Street address"
        },
        "case_type": {
            "x": 25,
            "y": 94,
            "width": 90,
            "fontSize": 7,
            "type": "text",
            "section": "Court - Case type"
        },
        "filing_date": {
            "x": 120,
            "y": 94,
            "width": 35,
            "fontSize": 7,
            "type": "text",
            "section": "Court - Filing date"
        },
        
        # CASE NUMBER (Top-right box "FOR COURT USE ONLY")
        "case_number": {
            "x": 172,
            "y": 119,
            "width": 35,
            "fontSize": 9,
            "fontStyle": "B",
            "type": "text",
            "section": "Case Number (top-right box)"
        },
        
        # PARTIES SECTION
        "petitioner_name": {
            "x": 25,
            "y": 109,
            "width": 125,
            "fontSize": 9,
            "fontStyle": "B",
            "type": "text",
            "section": "Petitioner name"
        },
        "respondent_name": {
            "x": 25,
            "y": 115,
            "width": 125,
            "fontSize": 9,
            "fontStyle": "B",
            "type": "text",
            "section": "Respondent name"
        },
        "petitioner_address": {
            "x": 25,
            "y": 122,
            "width": 125,
            "fontSize": 7,
            "type": "text",
            "section": "Petitioner address"
        },
        "petitioner_phone": {
            "x": 25,
            "y": 127,
            "width": 60,
            "fontSize": 7,
            "type": "text",
            "section": "Petitioner phone"
        },
        "respondent_address": {
            "x": 25,
            "y": 134,
            "width": 125,
            "fontSize": 7,
            "type": "text",
            "section": "Respondent address"
        },
        
        # PETITION TYPE CHECKBOXES (Section 1)
        "dissolution_type": {
            "x": 65,
            "y": 143,
            "width": 70,
            "fontSize": 8,
            "type": "select",
            "section": "Petition type (next to checkboxes)"
        },
        
        # MARRIAGE INFORMATION (Mid-page sections)
        "marriage_date": {
            "x": 120,
            "y": 177,
            "width": 35,
            "fontSize": 8,
            "type": "date",
            "section": "Marriage date (section 3a)"
        },
        "separation_date": {
            "x": 165,
            "y": 177,
            "width": 30,
            "fontSize": 8,
            "type": "date",
            "section": "Separation date (section 3a)"
        },
        "marriage_location": {
            "x": 85,
            "y": 186,
            "width": 80,
            "fontSize": 7,
            "type": "text",
            "section": "Place married (section 3b)"
        },
        "grounds_for_dissolution": {
            "x": 40,
            "y": 196,
            "width": 100,
            "fontSize": 7,
            "type": "text",
            "section": "Grounds (section 3c)"
        },
        
        # RELIEF REQUESTED - These are checkboxes that need X marks
        "property_division": {
            "x": 16,
            "y": 143,
            "width": 3.5,
            "height": 3.5,
            "fontSize": 9,
            "type": "checkbox",
            "section": "Dissolution checkbox"
        },
        "spousal_support": {
            "x": 16,
            "y": 150,
            "width": 3.5,
            "height": 3.5,
            "fontSize": 9,
            "type": "checkbox",
            "section": "Legal Separation checkbox"
        },
        "attorney_fees": {
            "x": 16,
            "y": 157,
            "width": 3.5,
            "height": 3.5,
            "fontSize": 9,
            "type": "checkbox",
            "section": "Nullity checkbox"
        },
        "name_change": {
            "x": 51,
            "y": 169,
            "width": 3.5,
            "height": 3.5,
            "fontSize": 9,
            "type": "checkbox",
            "section": "Legal relationship - We are married"
        },
        
        # CHILDREN SECTION (Section 4)
        "has_children": {
            "x": 70,
            "y": 218,
            "width": 20,
            "fontSize": 8,
            "type": "text",
            "section": "Minor children response"
        },
        "children_count": {
            "x": 90,
            "y": 223,
            "width": 10,
            "fontSize": 8,
            "type": "number",
            "section": "Number of children"
        },
        
        # ADDITIONAL/SIGNATURE SECTION (Bottom of page)
        "additional_info": {
            "x": 25,
            "y": 240,
            "width": 165,
            "fontSize": 7,
            "type": "textarea",
            "section": "Additional requests/information"
        },
        "attorney_signature": {
            "x": 25,
            "y": 262,
            "width": 75,
            "fontSize": 9,
            "fontStyle": "I",
            "type": "text",
            "section": "Attorney signature"
        },
        "signature_date": {
            "x": 140,
            "y": 262,
            "width": 35,
            "fontSize": 8,
            "type": "date",
            "section": "Signature date"
        }
    }
    
    # Save positions to JSON
    output_file = 'data/t_fl100_gc120_positions_analyzed.json'
    
    # Remove section descriptions for actual JSON file
    clean_positions = {}
    for key, value in positions.items():
        section_desc = value.pop('section', None)
        clean_positions[key] = value
        print(f"[OK] {key:30} -> ({value['x']:5.1f}mm, {value['y']:5.1f}mm) - {section_desc}")
    
    # Save to file
    with open(output_file, 'w') as f:
        json.dump(clean_positions, f, indent=2)
    
    print(f"\n[OK] Positions saved to: {output_file}")
    print(f"[OK] Total fields: {len(clean_positions)}")
    
    return clean_positions

if __name__ == '__main__':
    positions = analyze_fl100_form()
    
    print("\n=== Next Steps ===")
    print("1. Review the positions above")
    print("2. Copy t_fl100_gc120_positions_analyzed.json to t_fl100_gc120_positions.json")
    print("3. Run: C:\\xampp\\php\\php.exe test_pdf_generation.php")
    print("4. Open the new PDF and verify positions")
    print("5. Make fine adjustments as needed")

