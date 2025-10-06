#!/usr/bin/env python3
"""
Generate FL-100 test data and compare with Clio PDF
"""

import json
import os
import subprocess
from datetime import datetime
from pathlib import Path

def generate_fl100_test_data():
    """Generate complete test data for FL-100 form"""
    return {
        # Attorney Information
        'attorney_name': 'John Michael Smith, Esq.',
        'attorney_firm': 'Smith & Associates Family Law',
        'attorney_address': '1234 Legal Plaza, Suite 500',
        'attorney_city_state_zip': 'Los Angeles, CA 90210',
        'attorney_phone': '(555) 123-4567',
        'attorney_email': 'jsmith@smithlaw.com',
        'attorney_bar_number': '123456',
        
        # Court Information
        'case_number': 'FL-2024-001234',
        'court_county': 'Los Angeles',
        'court_address': '111 N Hill St, Los Angeles, CA 90012',
        'case_type': 'Dissolution of Marriage',
        'filing_date': '2024-10-06',
        
        # Parties Information
        'petitioner_name': 'Sarah Elizabeth Johnson',
        'respondent_name': 'Michael David Johnson',
        'petitioner_address': '123 Main Street, Los Angeles, CA 90210',
        'petitioner_phone': '(555) 987-6543',
        'respondent_address': '456 Oak Avenue, Los Angeles, CA 90211',
        
        # Marriage Information
        'marriage_date': '2010-06-15',
        'separation_date': '2024-03-20',
        'marriage_location': 'Las Vegas, Nevada',
        'grounds_for_dissolution': 'Irreconcilable differences',
        'dissolution_type': 'Dissolution of Marriage',
        
        # Relief Requested
        'property_division': '1',  # Checkbox checked
        'spousal_support': '1',    # Checkbox checked
        'attorney_fees': '1',       # Checkbox checked
        'name_change': '0',         # Checkbox unchecked
        
        # Children Information
        'has_children': 'No',
        'children_count': '0',
        
        # Additional Information
        'additional_info': 'Petitioner requests dissolution of marriage based on irreconcilable differences.',
        'attorney_signature': 'John Michael Smith',
        'signature_date': '2024-10-06'
    }

def save_test_data_to_mvp():
    """Save test data to MVP system for PDF generation"""
    # Load existing data
    data_file = '/workspace/data/mvp.json'
    with open(data_file, 'r') as f:
        db = json.load(f)
    
    # Find or create test project
    test_project = None
    for project in db['projects']:
        if project['name'] == 'FL-100 Comparison Test':
            test_project = project
            break
    
    if not test_project:
        test_project = {
            'id': 'p_comparison_test',
            'clientId': '',
            'name': 'FL-100 Comparison Test',
            'status': 'in_progress',
            'createdAt': datetime.now().isoformat(),
            'updatedAt': datetime.now().isoformat()
        }
        db['projects'].append(test_project)
    
    # Find or create FL-100 document
    test_doc = None
    for doc in db['projectDocuments']:
        if doc['projectId'] == test_project['id'] and doc['templateId'] == 't_fl100_gc120':
            test_doc = doc
            break
    
    if not test_doc:
        test_doc = {
            'id': 'pd_fl100_test',
            'projectId': test_project['id'],
            'templateId': 't_fl100_gc120',
            'status': 'in_progress',
            'createdAt': datetime.now().isoformat()
        }
        db['projectDocuments'].append(test_doc)
    
    # Clear old field values for this document
    db['fieldValues'] = [fv for fv in db['fieldValues'] if fv['projectDocumentId'] != test_doc['id']]
    
    # Add new field values
    test_data = generate_fl100_test_data()
    for key, value in test_data.items():
        db['fieldValues'].append({
            'id': f'fv_{key}_{datetime.now().timestamp()}',
            'projectDocumentId': test_doc['id'],
            'key': key,
            'value': str(value),
            'updatedAt': datetime.now().isoformat()
        })
    
    # Save back to file
    with open(data_file, 'w') as f:
        json.dump(db, f, indent=2)
    
    print(f"✅ Test data saved to MVP system")
    print(f"   Project ID: {test_project['id']}")
    print(f"   Document ID: {test_doc['id']}")
    
    return test_doc['id']

def analyze_pdf_content(pdf_path):
    """Analyze PDF content and structure"""
    if not os.path.exists(pdf_path):
        return None
    
    analysis = {
        'path': pdf_path,
        'size': os.path.getsize(pdf_path),
        'exists': True,
        'text_content': '',
        'fields_found': [],
        'has_fl100_structure': False
    }
    
    # Try to extract text
    try:
        # Try pdftotext
        result = subprocess.run(['pdftotext', pdf_path, '-'], 
                              capture_output=True, text=True)
        if result.returncode == 0:
            analysis['text_content'] = result.stdout
    except:
        # Fallback: read as binary and extract readable text
        try:
            with open(pdf_path, 'rb') as f:
                content = f.read()
                # Look for text patterns
                text_chunks = []
                for match in content.split(b'stream'):
                    try:
                        decoded = match.decode('latin-1', errors='ignore')
                        readable = ''.join(c for c in decoded if c.isprintable() or c.isspace())
                        if len(readable) > 20:
                            text_chunks.append(readable)
                    except:
                        pass
                analysis['text_content'] = '\n'.join(text_chunks)
        except Exception as e:
            print(f"Error reading PDF: {e}")
    
    # Check for FL-100 structure
    text = analysis['text_content'].upper()
    fl100_markers = [
        'PETITION', 'MARRIAGE', 'DISSOLUTION', 
        'PETITIONER', 'RESPONDENT', 'FL-100'
    ]
    
    markers_found = sum(1 for marker in fl100_markers if marker in text)
    analysis['has_fl100_structure'] = markers_found >= 3
    
    # Extract field values
    if 'SARAH ELIZABETH JOHNSON' in text or 'JOHN MICHAEL SMITH' in text:
        analysis['fields_found'].append('Test data present')
    
    return analysis

def main():
    print("=" * 60)
    print("FL-100 PDF COMPARISON")
    print("Clio vs Our System")
    print("=" * 60)
    
    # Step 1: Save test data to our system
    print("\n1. Preparing test data...")
    doc_id = save_test_data_to_mvp()
    
    # Step 2: Analyze Clio PDF
    print("\n2. Analyzing Clio PDF...")
    clio_pdf = '/workspace/uploads/fl100_official.pdf'
    clio_analysis = analyze_pdf_content(clio_pdf)
    
    if clio_analysis:
        print(f"   ✓ Clio PDF found: {clio_pdf}")
        print(f"     Size: {clio_analysis['size']:,} bytes")
        print(f"     Has FL-100 structure: {clio_analysis['has_fl100_structure']}")
    else:
        print(f"   ✗ Clio PDF not found at {clio_pdf}")
    
    # Step 3: Instructions for generating our PDF
    print("\n3. Generate Our System PDF:")
    print("   To generate a PDF from our system with the test data:")
    print(f"   1. Open the MVP system in your browser")
    print(f"   2. Go to the 'FL-100 Comparison Test' project")
    print(f"   3. Click 'Edit' on the FL-100 document")
    print(f"   4. Review the pre-filled test data")
    print(f"   5. Save the form")
    print(f"   6. Click 'Generate PDF' or use the actions menu")
    print(f"   7. The PDF will be saved in /workspace/output/")
    
    # Step 4: Check for our PDF
    print("\n4. Looking for our system's FL-100 PDFs...")
    output_pdfs = list(Path('/workspace/output').glob('*.pdf'))
    
    if output_pdfs:
        print(f"   Found {len(output_pdfs)} PDFs in output directory")
        most_recent = max(output_pdfs, key=os.path.getctime)
        print(f"   Most recent: {most_recent.name}")
        
        our_analysis = analyze_pdf_content(str(most_recent))
        if our_analysis:
            print(f"     Size: {our_analysis['size']:,} bytes")
    else:
        print("   No PDFs found in output directory yet")
    
    # Step 5: Comparison summary
    print("\n5. COMPARISON SUMMARY")
    print("-" * 40)
    
    if clio_analysis:
        print("Clio PDF:")
        print(f"  - Size: {clio_analysis['size']:,} bytes")
        print(f"  - Has FL-100 markers: {clio_analysis['has_fl100_structure']}")
        print(f"  - Text length: {len(clio_analysis['text_content'])} chars")
        
        # Show sample of extracted text
        if clio_analysis['text_content']:
            sample = clio_analysis['text_content'][:200].replace('\n', ' ')
            print(f"  - Sample text: {sample}...")
    
    print("\n📋 Test Data Ready:")
    print("The following test data has been loaded into the system:")
    test_data = generate_fl100_test_data()
    for key, value in list(test_data.items())[:5]:  # Show first 5 fields
        print(f"  - {key}: {value}")
    print("  ... and more")
    
    # Save comparison info
    comparison_info = {
        'timestamp': datetime.now().isoformat(),
        'clio_pdf': clio_pdf,
        'clio_analysis': {
            'exists': clio_analysis['exists'] if clio_analysis else False,
            'size': clio_analysis['size'] if clio_analysis else 0,
            'has_fl100_structure': clio_analysis['has_fl100_structure'] if clio_analysis else False
        },
        'test_data': test_data,
        'document_id': doc_id
    }
    
    with open('/workspace/fl100_comparison_info.json', 'w') as f:
        json.dump(comparison_info, f, indent=2)
    
    print(f"\n✅ Comparison info saved to: /workspace/fl100_comparison_info.json")
    print("\n🔍 Next Steps:")
    print("1. Generate a PDF from our system using the test data")
    print("2. Run this script again to see the comparison")
    print("3. Or manually compare the PDFs side by side")

if __name__ == "__main__":
    main()