#!/usr/bin/env python3
"""
PDF Comparison Tool - Compare Clio PDF with Our System PDF
Analyzes structure, fields, and content differences
"""

import os
import json
import re
from pathlib import Path
import subprocess

def check_pdf_tools():
    """Check if PDF tools are available"""
    tools_available = {
        'pdftotext': False,
        'pdftk': False,
        'pdfinfo': False
    }
    
    for tool in tools_available.keys():
        try:
            result = subprocess.run(['which', tool], capture_output=True, text=True)
            tools_available[tool] = result.returncode == 0
        except:
            pass
    
    return tools_available

def extract_pdf_text(pdf_path):
    """Extract text from PDF using pdftotext or basic Python"""
    text_content = ""
    
    # Try pdftotext first
    try:
        result = subprocess.run(['pdftotext', pdf_path, '-'], 
                              capture_output=True, text=True)
        if result.returncode == 0:
            text_content = result.stdout
    except:
        # Fallback: try to read PDF as binary and extract readable text
        try:
            with open(pdf_path, 'rb') as f:
                pdf_bytes = f.read()
                # Extract ASCII text between stream markers
                text_matches = re.findall(b'stream\s*\n(.*?)\nendstream', 
                                         pdf_bytes, re.DOTALL)
                for match in text_matches:
                    try:
                        decoded = match.decode('latin-1', errors='ignore')
                        # Filter out binary noise
                        readable = ''.join(c for c in decoded if c.isprintable() or c.isspace())
                        text_content += readable + "\n"
                    except:
                        pass
        except Exception as e:
            print(f"Error reading PDF: {e}")
    
    return text_content

def get_pdf_info(pdf_path):
    """Get PDF metadata and structure info"""
    info = {
        'exists': os.path.exists(pdf_path),
        'size': 0,
        'pages': 'unknown',
        'producer': 'unknown',
        'creation_date': 'unknown'
    }
    
    if not info['exists']:
        return info
    
    info['size'] = os.path.getsize(pdf_path)
    
    # Try to get PDF info using pdfinfo
    try:
        result = subprocess.run(['pdfinfo', pdf_path], 
                              capture_output=True, text=True)
        if result.returncode == 0:
            output = result.stdout
            # Parse pages
            pages_match = re.search(r'Pages:\s+(\d+)', output)
            if pages_match:
                info['pages'] = pages_match.group(1)
            # Parse producer
            producer_match = re.search(r'Producer:\s+(.+)', output)
            if producer_match:
                info['producer'] = producer_match.group(1).strip()
            # Parse creation date
            date_match = re.search(r'CreationDate:\s+(.+)', output)
            if date_match:
                info['creation_date'] = date_match.group(1).strip()
    except:
        pass
    
    return info

def extract_form_fields(text):
    """Extract form field values from PDF text"""
    fields = {}
    
    # Common FL-100 field patterns
    patterns = {
        'petitioner': r'PETITIONER[:\s]+([^\n]+)',
        'respondent': r'RESPONDENT[:\s]+([^\n]+)',
        'case_number': r'CASE NUMBER[:\s]+([^\n]+)',
        'attorney_name': r'ATTORNEY.*NAME[:\s]+([^\n]+)',
        'bar_number': r'BAR.*NUMBER[:\s]+([^\n]+)',
        'marriage_date': r'Date of marriage[:\s]+([^\n]+)',
        'separation_date': r'Date of separation[:\s]+([^\n]+)',
    }
    
    for field_name, pattern in patterns.items():
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            fields[field_name] = match.group(1).strip()
    
    # Check for checkboxes (look for X or ✓ marks)
    checkbox_patterns = {
        'dissolution': r'[\[X\]✓]\s*Dissolution',
        'legal_separation': r'[\[X\]✓]\s*Legal Separation',
        'property_division': r'[\[X\]✓]\s*Property',
        'spousal_support': r'[\[X\]✓]\s*Spousal',
    }
    
    for field_name, pattern in checkbox_patterns.items():
        if re.search(pattern, text, re.IGNORECASE):
            fields[field_name] = 'checked'
    
    return fields

def compare_pdfs(clio_pdf, our_pdf):
    """Compare two PDFs and report differences"""
    print("=" * 60)
    print("PDF COMPARISON REPORT")
    print("=" * 60)
    
    # Check available tools
    tools = check_pdf_tools()
    print("\nAvailable PDF Tools:")
    for tool, available in tools.items():
        status = "✓" if available else "✗"
        print(f"  {status} {tool}")
    
    # Get PDF info
    print("\n1. PDF METADATA COMPARISON")
    print("-" * 40)
    
    clio_info = get_pdf_info(clio_pdf)
    our_info = get_pdf_info(our_pdf)
    
    print(f"\nClio PDF: {clio_pdf}")
    for key, value in clio_info.items():
        print(f"  {key}: {value}")
    
    print(f"\nOur System PDF: {our_pdf}")
    for key, value in our_info.items():
        print(f"  {key}: {value}")
    
    # Extract and compare text
    print("\n2. TEXT CONTENT COMPARISON")
    print("-" * 40)
    
    clio_text = extract_pdf_text(clio_pdf) if clio_info['exists'] else ""
    our_text = extract_pdf_text(our_pdf) if our_info['exists'] else ""
    
    print(f"\nClio PDF text length: {len(clio_text)} characters")
    print(f"Our PDF text length: {len(our_text)} characters")
    
    # Extract form fields
    print("\n3. FORM FIELDS COMPARISON")
    print("-" * 40)
    
    clio_fields = extract_form_fields(clio_text)
    our_fields = extract_form_fields(our_text)
    
    print(f"\nFields found in Clio PDF: {len(clio_fields)}")
    for field, value in clio_fields.items():
        print(f"  {field}: {value[:50]}...")
    
    print(f"\nFields found in Our PDF: {len(our_fields)}")
    for field, value in our_fields.items():
        print(f"  {field}: {value[:50]}...")
    
    # Find differences
    print("\n4. DIFFERENCES")
    print("-" * 40)
    
    all_fields = set(clio_fields.keys()) | set(our_fields.keys())
    differences = []
    
    for field in all_fields:
        clio_val = clio_fields.get(field, 'NOT FOUND')
        our_val = our_fields.get(field, 'NOT FOUND')
        
        if clio_val != our_val:
            differences.append({
                'field': field,
                'clio': clio_val,
                'ours': our_val
            })
    
    if differences:
        print(f"\nFound {len(differences)} differences:")
        for diff in differences:
            print(f"\n  Field: {diff['field']}")
            print(f"    Clio: {diff['clio'][:50]}")
            print(f"    Ours: {diff['ours'][:50]}")
    else:
        print("\n✅ No significant differences found in extracted fields!")
    
    # Visual structure check
    print("\n5. VISUAL STRUCTURE CHECK")
    print("-" * 40)
    
    # Check for common FL-100 sections
    sections = [
        'PETITION', 'MARRIAGE', 'DISSOLUTION', 'LEGAL SEPARATION',
        'NULLITY', 'PROPERTY', 'SPOUSAL SUPPORT', 'CHILD CUSTODY',
        'ATTORNEY', 'PETITIONER', 'RESPONDENT'
    ]
    
    print("\nSection presence in Clio PDF:")
    for section in sections:
        present = section.upper() in clio_text.upper()
        status = "✓" if present else "✗"
        print(f"  {status} {section}")
    
    print("\nSection presence in Our PDF:")
    for section in sections:
        present = section.upper() in our_text.upper()
        status = "✓" if present else "✗"
        print(f"  {status} {section}")
    
    # Summary
    print("\n" + "=" * 60)
    print("SUMMARY")
    print("=" * 60)
    
    issues = []
    
    if not clio_info['exists']:
        issues.append("Clio PDF not found - please provide a Clio-generated PDF")
    if not our_info['exists']:
        issues.append("Our system PDF not found - please generate one first")
    if len(differences) > 0:
        issues.append(f"{len(differences)} field differences detected")
    
    if issues:
        print("\n⚠ Issues found:")
        for issue in issues:
            print(f"  - {issue}")
    else:
        print("\n✅ PDFs appear to be structurally similar!")
    
    return {
        'clio_info': clio_info,
        'our_info': our_info,
        'differences': differences,
        'issues': issues
    }

def main():
    # Default paths
    clio_pdf = "/workspace/uploads/fl100_official.pdf"  # Assuming this is from Clio
    our_pdf = None
    
    # Find our most recent FL-100 PDF
    output_dir = Path("/workspace/output")
    fl100_pdfs = list(output_dir.glob("*fl100*.pdf"))
    if fl100_pdfs:
        # Get the most recent one
        our_pdf = str(max(fl100_pdfs, key=os.path.getctime))
    
    if not our_pdf:
        print("No FL-100 PDF found from our system.")
        print("Please generate one first using the form interface.")
        return
    
    # Check if user wants to specify different files
    print("PDF Comparison Tool")
    print("-" * 40)
    print(f"Clio PDF: {clio_pdf}")
    print(f"Our PDF: {our_pdf}")
    print()
    
    # Run comparison
    results = compare_pdfs(clio_pdf, our_pdf)
    
    # Save results
    results_file = "/workspace/pdf_comparison_results.json"
    with open(results_file, 'w') as f:
        # Convert to JSON-serializable format
        json_results = {
            'clio_pdf': clio_pdf,
            'our_pdf': our_pdf,
            'comparison_date': str(Path(our_pdf).stat().st_mtime) if our_pdf else 'N/A',
            'differences_count': len(results['differences']),
            'issues': results['issues']
        }
        json.dump(json_results, f, indent=2)
    
    print(f"\n📋 Results saved to: {results_file}")

if __name__ == "__main__":
    main()