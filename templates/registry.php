<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

final class TemplateRegistry {
	public static function load(): array {
		// Seed with FL-100 and FL-105/GC-120 templates
		return [
			't_fl100_gc120' => [
				'id' => 't_fl100_gc120',
				'code' => 'FL-100',
				'name' => 'Petition—Marriage/Domestic Partnership',
				'panels' => [
					[ 'id' => 'attorney', 'label' => 'Attorney' ],
					[ 'id' => 'court', 'label' => 'Court' ],
					[ 'id' => 'parties', 'label' => 'Parties' ],
					[ 'id' => 'marriage', 'label' => 'Marriage Information' ],
					[ 'id' => 'relief', 'label' => 'Relief Requested' ],
					[ 'id' => 'children', 'label' => 'Children' ],
					[ 'id' => 'additional', 'label' => 'Additional Information' ],
				],
				'fields' => [
					// Attorney Information
					[ 
						'key' => 'attorney_name', 
						'label' => 'Attorney Name', 
						'type' => 'text', 
						'panelId' => 'attorney',
						'required' => true,
						'placeholder' => 'Enter attorney full name',
						'pdfTarget' => [ 'formField' => 'ATTORNEY_NAME' ]
					],
					[ 
						'key' => 'attorney_firm', 
						'label' => 'Law Firm Name', 
						'type' => 'text', 
						'panelId' => 'attorney',
						'placeholder' => 'Enter law firm name',
						'pdfTarget' => [ 'formField' => 'FIRM_NAME' ]
					],
					[ 
						'key' => 'attorney_address', 
						'label' => 'Address', 
						'type' => 'text', 
						'panelId' => 'attorney',
						'placeholder' => 'Enter street address',
						'pdfTarget' => [ 'formField' => 'ADDRESS' ]
					],
					[ 
						'key' => 'attorney_city_state_zip', 
						'label' => 'City, State, ZIP', 
						'type' => 'text', 
						'panelId' => 'attorney',
						'placeholder' => 'Enter city, state, ZIP',
						'pdfTarget' => [ 'formField' => 'CITY_STATE_ZIP' ]
					],
					[ 
						'key' => 'attorney_phone', 
						'label' => 'Phone Number', 
						'type' => 'text', 
						'panelId' => 'attorney',
						'placeholder' => 'Enter phone number',
						'pdfTarget' => [ 'formField' => 'PHONE' ]
					],
					[ 
						'key' => 'attorney_email', 
						'label' => 'Email', 
						'type' => 'text', 
						'panelId' => 'attorney',
						'placeholder' => 'Enter email address',
						'pdfTarget' => [ 'formField' => 'EMAIL' ]
					],
					[ 
						'key' => 'attorney_bar_number', 
						'label' => 'State Bar Number', 
						'type' => 'text', 
						'panelId' => 'attorney',
						'placeholder' => 'Enter state bar number',
						'pdfTarget' => [ 'formField' => 'BAR_NUMBER' ]
					],
					// Court Information
					[ 
						'key' => 'case_number', 
						'label' => 'Case Number', 
						'type' => 'text', 
						'panelId' => 'court',
						'placeholder' => 'Enter case number',
						'pdfTarget' => [ 'formField' => 'CASE_NUMBER' ]
					],
					[ 
						'key' => 'court_county', 
						'label' => 'County', 
						'type' => 'text', 
						'panelId' => 'court',
						'placeholder' => 'Enter county name',
						'pdfTarget' => [ 'formField' => 'COUNTY' ]
					],
					[ 
						'key' => 'court_address', 
						'label' => 'Court Address', 
						'type' => 'text', 
						'panelId' => 'court',
						'placeholder' => 'Enter court address',
						'pdfTarget' => [ 'formField' => 'COURT_ADDRESS' ]
					],
					[ 
						'key' => 'case_type', 
						'label' => 'Case Type', 
						'type' => 'text', 
						'panelId' => 'court',
						'placeholder' => 'Enter case type',
						'pdfTarget' => [ 'formField' => 'CASE_TYPE' ]
					],
					[ 
						'key' => 'filing_date', 
						'label' => 'Filing Date', 
						'type' => 'date', 
						'panelId' => 'court',
						'placeholder' => 'Enter filing date',
						'pdfTarget' => [ 'formField' => 'FILING_DATE' ]
					],
					// Parties Information
					[ 
						'key' => 'petitioner_name', 
						'label' => 'Petitioner', 
						'type' => 'text', 
						'panelId' => 'parties',
						'required' => true,
						'placeholder' => 'Enter petitioner name',
						'pdfTarget' => [ 'formField' => 'PETITIONER' ]
					],
					[ 
						'key' => 'respondent_name', 
						'label' => 'Respondent', 
						'type' => 'text', 
						'panelId' => 'parties',
						'placeholder' => 'Enter respondent name',
						'pdfTarget' => [ 'formField' => 'RESPONDENT' ]
					],
					[ 
						'key' => 'petitioner_address', 
						'label' => 'Petitioner Address', 
						'type' => 'text', 
						'panelId' => 'parties',
						'placeholder' => 'Enter petitioner address',
						'pdfTarget' => [ 'formField' => 'PETITIONER_ADDRESS' ]
					],
					[ 
						'key' => 'petitioner_phone', 
						'label' => 'Petitioner Phone', 
						'type' => 'text', 
						'panelId' => 'parties',
						'placeholder' => 'Enter petitioner phone',
						'pdfTarget' => [ 'formField' => 'PETITIONER_PHONE' ]
					],
					[ 
						'key' => 'respondent_address', 
						'label' => 'Respondent Address', 
						'type' => 'text', 
						'panelId' => 'parties',
						'placeholder' => 'Enter respondent address',
						'pdfTarget' => [ 'formField' => 'RESPONDENT_ADDRESS' ]
					],
					// Marriage Information
					[ 
						'key' => 'marriage_date', 
						'label' => 'Marriage Date', 
						'type' => 'date', 
						'panelId' => 'marriage',
						'placeholder' => 'Enter marriage date',
						'pdfTarget' => [ 'formField' => 'MARRIAGE_DATE' ]
					],
					[ 
						'key' => 'separation_date', 
						'label' => 'Separation Date', 
						'type' => 'date', 
						'panelId' => 'marriage',
						'placeholder' => 'Enter separation date',
						'pdfTarget' => [ 'formField' => 'SEPARATION_DATE' ]
					],
					[ 
						'key' => 'marriage_location', 
						'label' => 'Marriage Location', 
						'type' => 'text', 
						'panelId' => 'marriage',
						'placeholder' => 'Enter marriage location',
						'pdfTarget' => [ 'formField' => 'MARRIAGE_LOCATION' ]
					],
					[ 
						'key' => 'grounds_for_dissolution', 
						'label' => 'Grounds for Dissolution', 
						'type' => 'select', 
						'panelId' => 'marriage',
						'options' => ['Irreconcilable differences', 'Incapacity to consent', 'Fraud', 'Force', 'Physical incapacity', 'Mental incapacity'],
						'placeholder' => 'Select grounds',
						'pdfTarget' => [ 'formField' => 'GROUNDS' ]
					],
					[ 
						'key' => 'dissolution_type', 
						'label' => 'Type of Dissolution', 
						'type' => 'select', 
						'panelId' => 'marriage',
						'options' => ['Dissolution of Marriage', 'Nullity of Marriage', 'Legal Separation'],
						'placeholder' => 'Select dissolution type',
						'pdfTarget' => [ 'formField' => 'DISSOLUTION_TYPE' ]
					],
					// Relief Requested
					[ 
						'key' => 'property_division', 
						'label' => 'Property Division', 
						'type' => 'checkbox', 
						'panelId' => 'relief',
						'pdfTarget' => [ 'formField' => 'PROPERTY_DIVISION' ]
					],
					[ 
						'key' => 'spousal_support', 
						'label' => 'Spousal Support', 
						'type' => 'checkbox', 
						'panelId' => 'relief',
						'pdfTarget' => [ 'formField' => 'SPOUSAL_SUPPORT' ]
					],
					[ 
						'key' => 'attorney_fees', 
						'label' => 'Attorney Fees', 
						'type' => 'checkbox', 
						'panelId' => 'relief',
						'pdfTarget' => [ 'formField' => 'ATTORNEY_FEES' ]
					],
					[ 
						'key' => 'name_change', 
						'label' => 'Name Change', 
						'type' => 'checkbox', 
						'panelId' => 'relief',
						'pdfTarget' => [ 'formField' => 'NAME_CHANGE' ]
					],
					// Children Information
					[ 
						'key' => 'has_children', 
						'label' => 'Has Children', 
						'type' => 'select', 
						'panelId' => 'children',
						'options' => ['Yes', 'No'],
						'placeholder' => 'Select if has children',
						'pdfTarget' => [ 'formField' => 'HAS_CHILDREN' ]
					],
					[ 
						'key' => 'children_count', 
						'label' => 'Number of Children', 
						'type' => 'number', 
						'panelId' => 'children',
						'placeholder' => 'Enter number of children',
						'pdfTarget' => [ 'formField' => 'CHILDREN_COUNT' ]
					],
					// Additional Information
					[ 
						'key' => 'additional_info', 
						'label' => 'Additional Information', 
						'type' => 'textarea', 
						'panelId' => 'additional',
						'placeholder' => 'Enter any additional information',
						'pdfTarget' => [ 'formField' => 'ADDITIONAL_INFO' ]
					],
					[ 
						'key' => 'attorney_signature', 
						'label' => 'Attorney Signature', 
						'type' => 'text', 
						'panelId' => 'additional',
						'placeholder' => 'Enter attorney signature',
						'pdfTarget' => [ 'formField' => 'ATTORNEY_SIGNATURE' ]
					],
					[ 
						'key' => 'signature_date', 
						'label' => 'Signature Date', 
						'type' => 'date', 
						'panelId' => 'additional',
						'placeholder' => 'Enter signature date',
						'pdfTarget' => [ 'formField' => 'SIGNATURE_DATE' ]
					],
				]
			],
			't_fl105_gc120' => [
				'id' => 't_fl105_gc120',
				'code' => 'FL-105/GC-120',
				'name' => 'Declaration Under UCCJEA',
				'panels' => [
					[ 'id' => 'attorney', 'label' => 'Attorney' ],
					[ 'id' => 'court', 'label' => 'Court' ],
					[ 'id' => 'parties', 'label' => 'Parties' ],
				],
				'fields' => [
					[ 
						'key' => 'attorney_name', 
						'label' => 'Attorney Name', 
						'type' => 'text', 
						'panelId' => 'attorney',
						'required' => true,
						'placeholder' => 'Enter attorney full name',
						'pdfTarget' => [ 'formField' => 'ATTORNEY_NAME' ]
					],
					[ 
						'key' => 'attorney_firm', 
						'label' => 'Law Firm Name', 
						'type' => 'text', 
						'panelId' => 'attorney',
						'placeholder' => 'Enter law firm name',
						'pdfTarget' => [ 'formField' => 'FIRM_NAME' ]
					],
					[ 
						'key' => 'attorney_bar', 
						'label' => 'State Bar Number', 
						'type' => 'text', 
						'panelId' => 'attorney',
						'placeholder' => 'Enter state bar number',
						'pdfTarget' => [ 'formField' => 'BAR_NUMBER' ]
					],
					[ 
						'key' => 'court_branch', 
						'label' => 'Court Branch', 
						'type' => 'select', 
						'panelId' => 'court',
						'options' => ['Superior Court', 'Family Court', 'Probate Court', 'Municipal Court'],
						'pdfTarget' => [ 'formField' => 'COURT_BRANCH' ]
					],
					[ 
						'key' => 'petitioner_name', 
						'label' => 'Petitioner', 
						'type' => 'text', 
						'panelId' => 'parties',
						'required' => true,
						'placeholder' => 'Enter petitioner name',
						'pdfTarget' => [ 'formField' => 'PETITIONER_NAME' ]
					],
					[ 
						'key' => 'respondent_name', 
						'label' => 'Respondent', 
						'type' => 'text', 
						'panelId' => 'parties',
						'placeholder' => 'Enter respondent name',
						'pdfTarget' => [ 'formField' => 'RESPONDENT_NAME' ]
					]
				]
			]
		];
	}
}


