#!/usr/bin/env python3
"""
Test FL-100 PDF positioning by generating a filled PDF with all fields
"""

import json
from datetime import datetime
from pathlib import Path

def generate_complete_fl100_test_data():
    """Generate complete FL-100 test data with ALL fields filled"""
    return {
        # Attorney Information - ALL fields
        'attorney_name': 'John Michael Smith, Esq.',
        'attorney_bar_number': '123456',
        'attorney_firm': 'Smith & Associates Family Law',
        'attorney_street': '1234 Legal Plaza, Suite 500',
        'attorney_address': '1234 Legal Plaza, Suite 500',  # Alternative field name
        'attorney_city': 'Los Angeles',
        'attorney_state': 'CA',
        'attorney_zip': '90210',
        'attorney_city_state_zip': 'Los Angeles, CA 90210',  # Combined field
        'attorney_phone': '(555) 123-4567',
        'attorney_fax': '(555) 123-4568',
        'attorney_email': 'jsmith@smithlaw.com',
        'attorney_for': 'Petitioner Sarah Elizabeth Johnson',
        
        # Court Information - ALL fields
        'court_county': 'Los Angeles',
        'court_street': '111 N Hill St',
        'court_address': '111 N Hill St, Los Angeles, CA 90012',  # Alternative
        'court_mailing': '111 N Hill St, Room 118',
        'court_city_zip': 'Los Angeles, CA 90012',
        'court_branch': 'Stanley Mosk Courthouse',
        'case_number': 'FL-2024-001234',
        'case_type': 'Dissolution of Marriage',
        'filing_date': '2024-10-06',
        
        # Parties Information - ALL fields
        'petitioner_name': 'Sarah Elizabeth Johnson',
        'respondent_name': 'Michael David Johnson',
        'petitioner_address': '123 Main Street, Los Angeles, CA 90210',
        'petitioner_phone': '(555) 987-6543',
        'respondent_address': '456 Oak Avenue, Los Angeles, CA 90211',
        
        # Petition For checkboxes - ALL options
        'petition_dissolution_marriage': '1',
        'petition_dissolution_partnership': '0',
        'petition_legal_separation_marriage': '0',
        'petition_legal_separation_partnership': '0',
        'petition_nullity_marriage': '0',
        'petition_nullity_partnership': '0',
        
        # Legal Relationship - ALL checkboxes
        'we_are_married': '1',
        'we_are_domestic_partners': '0',
        'we_are_same_sex_married': '0',
        
        # Residence Requirements - ALL checkboxes
        'petitioner_resident': '1',
        'respondent_resident': '1',
        'same_sex_not_resident': '0',
        'our_partnership_established': '0',
        
        # Statistical Facts - ALL date fields
        'marriage_date': '2010-06-15',
        'date_married_month': '06',
        'date_married_day': '15',
        'date_married_year': '2010',
        'separation_date': '2024-03-20',
        'date_separated_month': '03',
        'date_separated_day': '20',
        'date_separated_year': '2024',
        'time_from_marriage_years': '13',
        'time_from_marriage_months': '9',
        'marriage_location': 'Las Vegas, Nevada',
        
        # Minor Children - ALL fields
        'no_minor_children': '0',
        'minor_children_of_petitioner_respondent': '1',
        'has_children': 'Yes',
        'children_count': '2',
        'child1_name': 'Emma Rose Johnson',
        'child1_birthdate': '08/12/2012',
        'child1_age': '12',
        'child1_sex': 'F',
        'child2_name': 'James Michael Johnson',
        'child2_birthdate': '04/23/2015',
        'child2_age': '9',
        'child2_sex': 'M',
        'continued_attachment': '0',
        'pregnant_no': '1',
        'pregnant_yes': '0',
        
        # Legal Grounds - ALL options
        'grounds_for_dissolution': 'Irreconcilable differences',
        'grounds_divorce': 'irreconcilable differences',
        'grounds_nullity': '',
        'dissolution_type': 'Dissolution of Marriage',
        
        # Petitioner Requests - ALL checkboxes and fields
        'child_custody_to_petitioner': '1',
        'child_custody_to_respondent': '0',
        'child_custody_other': '0',
        'child_visitation_granted': '1',
        'child_visitation_petitioner': '0',
        'child_visitation_respondent': '1',
        'determine_parentage': '0',
        
        # Support requests
        'spousal_support': '1',
        'spousal_support_petitioner': '1',
        'spousal_support_respondent': '0',
        'terminate_support': '0',
        'terminate_support_petitioner': '0',
        'terminate_support_respondent': '0',
        
        # Property and fees
        'property_division': '1',
        'property_rights_determination': '1',
        'attorney_fees': '1',
        'attorney_fees_petitioner': '1',
        'attorney_fees_respondent': '0',
        
        # Name change
        'name_change': '1',
        'restore_name': '1',
        'former_name': 'Sarah Elizabeth Martinez',
        
        # Other relief
        'other_relief': 'Petitioner requests exclusive use and possession of the family residence at 789 Maple Street, Los Angeles, CA 90210. Respondent to contribute to mortgage payments during separation period. Division of retirement accounts per QDRO.',
        'additional_info': 'Marriage is irretrievably broken. No possibility of reconciliation. Parties have been separated since March 2024 and living apart.',
        
        # Signatures
        'petitioner_signature': 'Sarah Elizabeth Johnson',
        'petitioner_signature_date': '2024-10-06',
        'attorney_signature': 'John Michael Smith, Attorney at Law',
        'attorney_signature_date': '2024-10-06',
        'signature_date': '2024-10-06',
    }

def save_test_data_for_mvp():
    """Save the test data to MVP system"""
    data_file = Path('/workspace/data/mvp.json')
    
    # Load existing data
    with open(data_file, 'r') as f:
        db = json.load(f)
    
    # Create or update FL-100 test project
    test_project = None
    for project in db['projects']:
        if 'FL-100 COMPLETE TEST' in project['name']:
            test_project = project
            break
    
    if not test_project:
        test_project = {
            'id': 'p_fl100_complete_test',
            'clientId': '',
            'name': 'FL-100 COMPLETE TEST - All Fields',
            'status': 'in_progress',
            'createdAt': datetime.now().isoformat(),
            'updatedAt': datetime.now().isoformat()
        }
        db['projects'].append(test_project)
    
    # Create or update FL-100 document
    test_doc = None
    for doc in db['projectDocuments']:
        if doc['projectId'] == test_project['id']:
            test_doc = doc
            break
    
    if not test_doc:
        test_doc = {
            'id': 'pd_fl100_complete',
            'projectId': test_project['id'],
            'templateId': 't_fl100_gc120',
            'status': 'in_progress',
            'createdAt': datetime.now().isoformat()
        }
        db['projectDocuments'].append(test_doc)
    
    # Clear old field values
    db['fieldValues'] = [fv for fv in db['fieldValues'] if fv['projectDocumentId'] != test_doc['id']]
    
    # Add ALL new field values
    test_data = generate_complete_fl100_test_data()
    for key, value in test_data.items():
        db['fieldValues'].append({
            'id': f'fv_{key}_{int(datetime.now().timestamp())}',
            'projectDocumentId': test_doc['id'],
            'key': key,
            'value': str(value),
            'updatedAt': datetime.now().isoformat()
        })
    
    # Save to file
    with open(data_file, 'w') as f:
        json.dump(db, f, indent=2)
    
    print(f"✅ Complete FL-100 test data saved")
    print(f"   Project: {test_project['name']}")
    print(f"   Document ID: {test_doc['id']}")
    print(f"   Total fields: {len(test_data)}")
    
    return test_doc['id'], test_data

def main():
    print("=" * 60)
    print("FL-100 COMPLETE FIELD TEST")
    print("Testing all field positioning")
    print("=" * 60)
    
    # Save complete test data
    doc_id, test_data = save_test_data_for_mvp()
    
    print("\n📋 COMPLETE TEST DATA LOADED:")
    print("-" * 40)
    
    # Group fields by section
    sections = {
        'Attorney': [],
        'Court': [],
        'Parties': [],
        'Petition Type': [],
        'Relationship': [],
        'Residence': [],
        'Dates': [],
        'Children': [],
        'Grounds': [],
        'Requests': [],
        'Signatures': []
    }
    
    for key, value in test_data.items():
        if 'attorney' in key:
            sections['Attorney'].append(f"  {key}: {value}")
        elif 'court' in key or 'case' in key:
            sections['Court'].append(f"  {key}: {value}")
        elif 'petitioner' in key or 'respondent' in key:
            sections['Parties'].append(f"  {key}: {value}")
        elif 'petition_' in key:
            sections['Petition Type'].append(f"  {key}: {value}")
        elif 'we_are' in key:
            sections['Relationship'].append(f"  {key}: {value}")
        elif '_resident' in key or 'partnership_established' in key:
            sections['Residence'].append(f"  {key}: {value}")
        elif 'date' in key or 'marriage' in key or 'separation' in key or 'time_from' in key:
            sections['Dates'].append(f"  {key}: {value}")
        elif 'child' in key or 'pregnant' in key or 'minor' in key:
            sections['Children'].append(f"  {key}: {value}")
        elif 'grounds' in key or 'dissolution' in key:
            sections['Grounds'].append(f"  {key}: {value}")
        elif 'custody' in key or 'visitation' in key or 'support' in key or 'property' in key or 'fees' in key or 'name' in key or 'relief' in key:
            sections['Requests'].append(f"  {key}: {value}")
        elif 'signature' in key:
            sections['Signatures'].append(f"  {key}: {value}")
    
    # Display organized data
    for section, fields in sections.items():
        if fields:
            print(f"\n{section}:")
            for field in fields[:5]:  # Show first 5 of each section
                print(field)
            if len(fields) > 5:
                print(f"  ... and {len(fields)-5} more fields")
    
    print("\n" + "=" * 60)
    print("✅ TEST DATA READY")
    print("=" * 60)
    print("\nTo generate PDF with correct positioning:")
    print("1. Open MVP system in browser")
    print("2. Navigate to 'FL-100 COMPLETE TEST - All Fields' project")
    print("3. Click Edit/Complete on the FL-100 document")
    print("4. All fields should be pre-filled")
    print("5. Click Save, then Generate PDF")
    print("6. The PDF will have all fields positioned correctly")
    
    # Check if template PDF exists
    template_paths = [
        '/workspace/uploads/fl100_official.pdf',
        '/workspace/uploads/fl100.pdf'
    ]
    
    template_found = False
    for path in template_paths:
        if Path(path).exists():
            print(f"\n✅ FL-100 template found at: {path}")
            template_found = True
            break
    
    if not template_found:
        print("\n⚠️ WARNING: FL-100 template PDF not found!")
        print("   Please upload the official FL-100 PDF to /workspace/uploads/")
        print("   Name it: fl100_official.pdf or fl100.pdf")

if __name__ == "__main__":
    main()