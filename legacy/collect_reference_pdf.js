/**
 * Browser automation script to collect FL-100 reference PDF from draft.clio.com
 * This script will navigate to draft.clio.com, fill the FL-100 form, and download the PDF
 */

const testData = {
    'attorney_name': 'John Michael Smith, Esq.',
    'attorney_bar_number': '123456',
    'attorney_firm': 'Smith & Associates Family Law',
    'attorney_address': '1234 Legal Plaza, Suite 500',
    'attorney_city_state_zip': 'Los Angeles, CA 90210',
    'attorney_phone': '(555) 123-4567',
    'attorney_email': 'jsmith@smithlaw.com',
    'case_number': 'FL-2024-001234',
    'court_county': 'Los Angeles',
    'court_address': '111 N Hill St, Los Angeles, CA 90012',
    'petitioner_name': 'Sarah Elizabeth Johnson',
    'respondent_name': 'Michael David Johnson',
    'petitioner_address': '123 Main Street, Los Angeles, CA 90210',
    'petitioner_phone': '(555) 987-6543',
    'respondent_address': '456 Oak Avenue, Los Angeles, CA 90211',
    'marriage_date': '06/15/2010',
    'separation_date': '03/20/2024',
    'marriage_location': 'Las Vegas, Nevada',
    'grounds_for_dissolution': 'Irreconcilable differences',
    'dissolution_type': 'Dissolution of Marriage',
    'property_division': '1',
    'spousal_support': '1',
    'attorney_fees': '1',
    'name_change': '0',
    'has_children': 'Yes',
    'children_count': '2',
    'additional_info': 'Request for temporary custody orders.',
    'attorney_signature': 'John M. Smith',
    'signature_date': '10/09/2025'
};

console.log('FL-100 Reference PDF Collection Script');
console.log('=====================================');
console.log('');
console.log('This script will help you collect the reference PDF from draft.clio.com');
console.log('');
console.log('MANUAL STEPS REQUIRED:');
console.log('1. Open Chrome browser');
console.log('2. Navigate to: http://draft.clio.com');
console.log('3. Find and select the FL-100 form (Petition - Marriage/Domestic Partnership)');
console.log('4. Fill out the form with the following test data:');
console.log('');

Object.entries(testData).forEach(([field, value]) => {
    console.log(`   ${field}: ${value}`);
});

console.log('');
console.log('5. Generate/Download the PDF');
console.log('6. Save it as: fl100_draft_clio_reference.pdf in the project folder');
console.log('7. Run: C:\\xampp\\php\\php.exe measure_and_fix_positions.php');
console.log('');
console.log('FIELD MAPPING GUIDE:');
console.log('===================');
console.log('Look for these field labels on the form:');
console.log('• Attorney Name → attorney_name');
console.log('• State Bar No → attorney_bar_number');
console.log('• Firm Name → attorney_firm');
console.log('• Address → attorney_address');
console.log('• City, State, ZIP → attorney_city_state_zip');
console.log('• Telephone → attorney_phone');
console.log('• Email → attorney_email');
console.log('• Case Number → case_number');
console.log('• County → court_county');
console.log('• Court Address → court_address');
console.log('• Petitioner Name → petitioner_name');
console.log('• Respondent Name → respondent_name');
console.log('• Marriage Date → marriage_date');
console.log('• Separation Date → separation_date');
console.log('• Marriage Location → marriage_location');
console.log('• Grounds → grounds_for_dissolution');
console.log('• Additional Info → additional_info');
console.log('• Attorney Signature → attorney_signature');
console.log('• Signature Date → signature_date');
console.log('');
console.log('Ready to collect reference PDF!');


