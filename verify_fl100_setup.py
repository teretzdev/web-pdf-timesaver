#!/usr/bin/env python3
"""
FL-100 Form Setup Verification Script
Checks that the FL-100 form is properly configured in the workflow system
as a 1:1 clone of the Clio form without any improvements
"""

import json
import os
import re
from pathlib import Path

def load_json_file(filepath):
    """Load and parse a JSON file"""
    try:
        with open(filepath, 'r') as f:
            return json.load(f)
    except FileNotFoundError:
        return None
    except json.JSONDecodeError as e:
        print(f"Error parsing {filepath}: {e}")
        return None

def check_file_exists(filepath, description):
    """Check if a file exists and report status"""
    exists = os.path.exists(filepath)
    status = "✓" if exists else "✗"
    print(f"  {status} {description}: {filepath}")
    return exists

def verify_directory_structure():
    """Verify all required directories and files exist"""
    print("\n1. DIRECTORY STRUCTURE CHECK")
    print("=" * 50)
    
    required_paths = {
        "/workspace/mvp": "MVP directory",
        "/workspace/mvp/index.php": "Main router file",
        "/workspace/mvp/lib": "Library directory",
        "/workspace/mvp/views": "Views directory",
        "/workspace/mvp/templates": "Templates directory",
        "/workspace/mvp/templates/registry.php": "Template registry",
        "/workspace/mvp/views/populate.php": "Original populate view",
        "/workspace/mvp/views/populate_simple.php": "Simple populate view (1:1 clone)",
        "/workspace/mvp/lib/data.php": "Data store",
        "/workspace/mvp/lib/fl100_test_data_generator.php": "FL-100 test data generator",
        "/workspace/data": "Data directory",
        "/workspace/output": "Output directory for PDFs",
    }
    
    all_exist = True
    for path, desc in required_paths.items():
        if not check_file_exists(path, desc):
            all_exist = False
    
    # Create missing directories if needed
    for dir_path in ["/workspace/data", "/workspace/output", "/workspace/logs"]:
        if not os.path.exists(dir_path):
            os.makedirs(dir_path, exist_ok=True)
            print(f"  → Created missing directory: {dir_path}")
    
    return all_exist

def extract_template_from_php():
    """Extract FL-100 template configuration from PHP registry file"""
    print("\n2. FL-100 TEMPLATE CONFIGURATION CHECK")
    print("=" * 50)
    
    registry_file = "/workspace/mvp/templates/registry.php"
    if not os.path.exists(registry_file):
        print("  ✗ Template registry file not found")
        return None
    
    with open(registry_file, 'r') as f:
        content = f.read()
    
    # Check if FL-100 template exists
    if "'t_fl100_gc120'" not in content:
        print("  ✗ FL-100 template (t_fl100_gc120) not found in registry")
        return None
    
    print("  ✓ FL-100 template found in registry")
    
    # Extract template structure using regex
    panels = re.findall(r"\[ 'id' => '(\w+)', 'label' => '([^']+)' \]", content)
    fields = re.findall(r"'key' => '(\w+)',\s*'label' => '([^']+)',\s*'type' => '(\w+)'", content)
    
    print(f"  ✓ Found {len(panels)} panels")
    print(f"  ✓ Found {len(fields)} fields")
    
    # Expected panels for FL-100
    expected_panels = [
        ('attorney', 'Attorney'),
        ('court', 'Court'),
        ('parties', 'Parties'),
        ('marriage', 'Marriage Information'),
        ('relief', 'Relief Requested'),
        ('children', 'Children'),
        ('additional', 'Additional Information')
    ]
    
    print("\n  Panel verification:")
    for exp_id, exp_label in expected_panels:
        found = any(p[0] == exp_id and p[1] == exp_label for p in panels)
        status = "✓" if found else "✗"
        print(f"    {status} {exp_label} panel")
    
    # Key fields that must exist
    key_fields = [
        'attorney_name', 'petitioner_name', 'respondent_name',
        'marriage_date', 'case_number', 'court_county'
    ]
    
    print("\n  Key field verification:")
    field_keys = [f[0] for f in fields]
    for field in key_fields:
        status = "✓" if field in field_keys else "✗"
        print(f"    {status} {field}")
    
    return {'panels': panels, 'fields': fields}

def verify_data_store():
    """Verify data store is working"""
    print("\n3. DATA STORE CHECK")
    print("=" * 50)
    
    # Check if data files exist
    data_files = [
        "/workspace/data/mvp.json",
        "/workspace/data/mvp_test.json"
    ]
    
    for filepath in data_files:
        if os.path.exists(filepath):
            data = load_json_file(filepath)
            if data:
                print(f"  ✓ {filepath} exists and is valid JSON")
                if 'projects' in data:
                    print(f"    - Projects: {len(data.get('projects', []))}")
                if 'projectDocuments' in data:
                    print(f"    - Documents: {len(data.get('projectDocuments', []))}")
        else:
            print(f"  ℹ {filepath} does not exist (will be created on first use)")

def check_simple_form():
    """Check if the simple form view exists (1:1 clone without improvements)"""
    print("\n4. SIMPLE FORM VIEW CHECK (1:1 CLIO CLONE)")
    print("=" * 50)
    
    simple_form = "/workspace/mvp/views/populate_simple.php"
    if os.path.exists(simple_form):
        with open(simple_form, 'r') as f:
            content = f.read()
        
        # Check it doesn't have fancy features
        has_revert = "revert-btn" in content
        has_custom_fields = "custom-field" in content
        has_animations = "transition" in content or "animation" in content
        has_drag_drop = "drag" in content.lower()
        
        print(f"  ✓ Simple form view exists")
        print(f"  {'✗' if has_revert else '✓'} No revert buttons (Clio doesn't have this)")
        print(f"  {'✗' if has_custom_fields else '✓'} No custom fields section (Clio doesn't have this)")
        print(f"  {'✗' if has_animations else '✓'} No animations/transitions (plain like Clio)")
        print(f"  {'✗' if has_drag_drop else '✓'} No drag-drop functionality (Clio doesn't have this)")
        
        if not any([has_revert, has_custom_fields, has_animations, has_drag_drop]):
            print("\n  ✓✓ PERFECT: Simple form is a true 1:1 Clio clone!")
        else:
            print("\n  ⚠ WARNING: Simple form has extra features not in Clio")
    else:
        print("  ✗ Simple form view not found")

def update_router_for_simple_view():
    """Update the router to use the simple view"""
    print("\n5. ROUTER CONFIGURATION CHECK")
    print("=" * 50)
    
    router_file = "/workspace/mvp/index.php"
    if not os.path.exists(router_file):
        print("  ✗ Router file not found")
        return
    
    with open(router_file, 'r') as f:
        content = f.read()
    
    # Check if router has option for simple view
    if "populate_simple" in content:
        print("  ✓ Router already supports simple view")
    else:
        print("  ℹ Router needs update to support simple view")
        print("    Add route case for 'populate_simple' to use populate_simple.php")

def main():
    print("=" * 60)
    print("FL-100 FORM SETUP VERIFICATION")
    print("Ensuring 1:1 Clio clone without improvements")
    print("=" * 60)
    
    # Run all checks
    dir_ok = verify_directory_structure()
    template = extract_template_from_php()
    verify_data_store()
    check_simple_form()
    update_router_for_simple_view()
    
    # Summary
    print("\n" + "=" * 60)
    print("VERIFICATION SUMMARY")
    print("=" * 60)
    
    if dir_ok and template:
        print("✓ Basic setup is complete")
        print("✓ FL-100 template is configured")
        print("\nRECOMMENDATION:")
        print("  Use populate_simple.php view for true 1:1 Clio experience")
        print("  The original populate.php has extra features not in Clio:")
        print("    - Revert buttons")
        print("    - Custom fields section")  
        print("    - Drag and drop")
        print("    - Animations and transitions")
        print("\n  To use the simple Clio-like form:")
        print("    1. Navigate to your project")
        print("    2. Add an FL-100 document")
        print("    3. Use the simple populate view (no extra features)")
    else:
        print("✗ Setup incomplete - check errors above")
    
    print("\n" + "=" * 60)

if __name__ == "__main__":
    main()