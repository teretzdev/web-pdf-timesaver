#!/usr/bin/env python3
"""
FL-100 Field Position Analyzer

This tool analyzes the current field positions and provides
visual feedback on alignment accuracy.
"""

import json
import os
import math
from typing import Dict, List, Tuple, Any

class FieldPositionAnalyzer:
    def __init__(self):
        self.field_positions = {}
        self.load_field_positions()
    
    def load_field_positions(self):
        """Load field positions from JSON file"""
        positions_file = os.path.join(os.path.dirname(__file__), 'data', 't_fl100_gc120_positions.json')
        if os.path.exists(positions_file):
            with open(positions_file, 'r') as f:
                data = json.load(f)
                self.field_positions = data.get('t_fl100_gc120', {})
        else:
            self.field_positions = {}
    
    def analyze_field_positions(self):
        """Main analysis function"""
        print("🎯 FL-100 Field Position Analysis")
        print("================================\n")
        
        if not self.field_positions:
            print("❌ No field positions found. Please run the field editor first.\n")
            return
        
        # Group fields by section
        sections = {
            'Attorney Information': ['attorney_name', 'attorney_bar_number', 'attorney_firm', 
                                   'attorney_address', 'attorney_city_state_zip', 'attorney_phone', 'attorney_email'],
            'Court Information': ['case_number', 'court_county', 'court_address', 'case_type', 'filing_date'],
            'Party Information': ['petitioner_name', 'respondent_name', 'petitioner_address', 
                                'petitioner_phone', 'respondent_address'],
            'Marriage Information': ['marriage_date', 'separation_date', 'marriage_location', 'grounds_for_dissolution'],
            'Relief Requested': ['dissolution_type', 'property_division', 'spousal_support', 
                               'attorney_fees', 'name_change'],
            'Children Information': ['has_children', 'children_count'],
            'Signature Section': ['additional_info', 'attorney_signature', 'signature_date']
        }
        
        for section_name, field_ids in sections.items():
            print(f"📋 {section_name}")
            print("-" * (len(section_name) + 4))
            
            for field_id in field_ids:
                if field_id in self.field_positions:
                    field = self.field_positions[field_id]
                    status = self.analyze_field(field)
                    print(f"  {status['icon']} {field['label']:<25} ({field['x']:3d}, {field['y']:3d}) "
                          f"{field['width']}×{field['height']} {status['message']}")
                else:
                    print(f"  ❌ {field_id} - Missing field definition")
            print()
        
        self.generate_alignment_report()
    
    def analyze_field(self, field: Dict[str, Any]) -> Dict[str, str]:
        """Analyze individual field for issues"""
        issues = []
        
        # Check for reasonable positioning
        if field['x'] < 0 or field['x'] > 200:
            issues.append("X position out of bounds")
        
        if field['y'] < 0 or field['y'] > 400:
            issues.append("Y position out of bounds")
        
        # Check for reasonable sizing
        if field['width'] < 5 or field['width'] > 150:
            issues.append("Width unusual")
        
        if field['height'] < 5 or field['height'] > 50:
            issues.append("Height unusual")
        
        # Check field type specific issues
        if field['type'] == 'checkbox' and (field['width'] != 8 or field['height'] != 8):
            issues.append("Checkbox size should be 8×8")
        
        if field['type'] == 'textarea' and field['height'] < 10:
            issues.append("Textarea height too small")
        
        if not issues:
            return {'icon': '✅', 'message': 'OK'}
        else:
            return {'icon': '⚠️', 'message': ', '.join(issues)}
    
    def generate_alignment_report(self):
        """Generate comprehensive alignment report"""
        print("📊 Alignment Analysis Report")
        print("============================\n")
        
        # Check for overlapping fields
        overlaps = self.find_overlapping_fields()
        if overlaps:
            print("⚠️  Overlapping Fields Detected:")
            for overlap in overlaps:
                print(f"  - {overlap['field1']} overlaps with {overlap['field2']}")
            print()
        else:
            print("✅ No overlapping fields detected\n")
        
        # Check for fields too close together
        too_close = self.find_fields_too_close()
        if too_close:
            print("⚠️  Fields Too Close Together:")
            for close in too_close:
                print(f"  - {close['field1']} and {close['field2']} (distance: {close['distance']}px)")
            print()
        else:
            print("✅ Field spacing looks good\n")
        
        # Generate recommendations
        self.generate_recommendations()
    
    def find_overlapping_fields(self) -> List[Dict[str, str]]:
        """Find fields that overlap"""
        overlaps = []
        field_list = list(self.field_positions.values())
        
        for i in range(len(field_list)):
            for j in range(i + 1, len(field_list)):
                field1 = field_list[i]
                field2 = field_list[j]
                
                if self.fields_overlap(field1, field2):
                    overlaps.append({
                        'field1': field1['label'],
                        'field2': field2['label']
                    })
        
        return overlaps
    
    def fields_overlap(self, field1: Dict[str, Any], field2: Dict[str, Any]) -> bool:
        """Check if two fields overlap"""
        return not (field1['x'] + field1['width'] < field2['x'] or
                   field2['x'] + field2['width'] < field1['x'] or
                   field1['y'] + field1['height'] < field2['y'] or
                   field2['y'] + field2['height'] < field1['y'])
    
    def find_fields_too_close(self) -> List[Dict[str, Any]]:
        """Find fields that are too close together"""
        too_close = []
        field_list = list(self.field_positions.values())
        
        for i in range(len(field_list)):
            for j in range(i + 1, len(field_list)):
                field1 = field_list[i]
                field2 = field_list[j]
                
                distance = self.calculate_distance(field1, field2)
                if 0 < distance < 10:
                    too_close.append({
                        'field1': field1['label'],
                        'field2': field2['label'],
                        'distance': round(distance, 1)
                    })
        
        return too_close
    
    def calculate_distance(self, field1: Dict[str, Any], field2: Dict[str, Any]) -> float:
        """Calculate distance between field centers"""
        center1 = {
            'x': field1['x'] + field1['width'] / 2,
            'y': field1['y'] + field1['height'] / 2
        }
        
        center2 = {
            'x': field2['x'] + field2['width'] / 2,
            'y': field2['y'] + field2['height'] / 2
        }
        
        return math.sqrt((center1['x'] - center2['x'])**2 + (center1['y'] - center2['y'])**2)
    
    def generate_recommendations(self):
        """Generate recommendations for field alignment"""
        print("💡 Recommendations:")
        print("===================\n")
        
        # Check attorney section alignment
        attorney_fields = [f for f in self.field_positions.values() 
                          if f['label'] in ['Attorney Name', 'State Bar Number', 'Law Firm Name', 
                                          'Attorney Address', 'City, State, ZIP', 'Phone', 'Email']]
        
        if attorney_fields:
            print("📝 Attorney Section:")
            y_positions = [f['y'] for f in attorney_fields]
            min_y, max_y = min(y_positions), max(y_positions)
            
            if max_y - min_y > 25:
                print(f"  - Consider aligning attorney fields vertically (current spread: {max_y - min_y}px)")
            else:
                print("  - ✅ Attorney fields are well-aligned")
            print()
        
        # Check checkbox alignment
        checkboxes = [f for f in self.field_positions.values() if f['type'] == 'checkbox']
        
        if checkboxes:
            print("☑️  Checkboxes:")
            x_positions = [f['x'] for f in checkboxes]
            unique_x = list(set(x_positions))
            
            if len(unique_x) > 2:
                print(f"  - Consider aligning checkboxes vertically (multiple X positions: {', '.join(map(str, unique_x))})")
            else:
                print("  - ✅ Checkboxes are well-aligned")
            print()
        
        print("🎯 Next Steps:")
        print("1. Open the field alignment tool at http://localhost:8080/field_alignment_tool.html")
        print("2. Use the MCP field editor at http://localhost:3001")
        print("3. Fine-tune field positions based on visual inspection")
        print("4. Test PDF generation with updated positions\n")
    
    def export_positions_for_mcp(self):
        """Export positions in MCP format"""
        mcp_positions = {}
        
        for field_id, field in self.field_positions.items():
            mcp_positions[field_id] = {
                'x': field['x'],
                'y': field['y'],
                'width': field['width'],
                'height': field['height'],
                'type': field['type']
            }
        
        output_file = os.path.join(os.path.dirname(__file__), 'field_positions_for_mcp.json')
        with open(output_file, 'w') as f:
            json.dump(mcp_positions, f, indent=2)
        
        print(f"📄 MCP positions exported to: {output_file}")

def main():
    """Main function"""
    analyzer = FieldPositionAnalyzer()
    analyzer.analyze_field_positions()
    analyzer.export_positions_for_mcp()

if __name__ == "__main__":
    main()