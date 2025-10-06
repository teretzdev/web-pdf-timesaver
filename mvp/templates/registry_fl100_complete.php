<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

/**
 * COMPLETE FL-100 Template with ALL fields from official form
 * Based on California Judicial Council Form FL-100 (Rev. 1/1/2023)
 */
final class FL100CompleteRegistry {
	public static function getCompleteTemplate(): array {
		return [
			'id' => 't_fl100_complete',
			'code' => 'FL-100',
			'name' => 'Petition—Marriage/Domestic Partnership (Family Law)',
			'panels' => [
				[ 'id' => 'attorney', 'label' => 'Attorney or Party Without Attorney', 'order' => 1 ],
				[ 'id' => 'court', 'label' => 'Superior Court of California', 'order' => 2 ],
				[ 'id' => 'parties', 'label' => 'Marriage or Partnership of', 'order' => 3 ],
				[ 'id' => 'petition_type', 'label' => 'Petition For', 'order' => 4 ],
				[ 'id' => 'legal_relationship', 'label' => 'Legal Relationship', 'order' => 5 ],
				[ 'id' => 'residence', 'label' => 'Residence Requirements', 'order' => 6 ],
				[ 'id' => 'statistical', 'label' => 'Statistical Facts', 'order' => 7 ],
				[ 'id' => 'minor_children', 'label' => 'Minor Children', 'order' => 8 ],
				[ 'id' => 'property_rights', 'label' => 'Legal Grounds', 'order' => 9 ],
				[ 'id' => 'requests', 'label' => 'Petitioner Requests', 'order' => 10 ],
			],
			'fields' => [
				// ATTORNEY OR PARTY WITHOUT ATTORNEY
				[
					'key' => 'attorney_name',
					'label' => 'Name',
					'type' => 'text',
					'panelId' => 'attorney',
					'required' => true,
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].AttyInfo[0].AttyName[0]']
				],
				[
					'key' => 'attorney_bar_number',
					'label' => 'State Bar Number',
					'type' => 'text',
					'panelId' => 'attorney',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].AttyInfo[0].SBN[0]']
				],
				[
					'key' => 'attorney_firm',
					'label' => 'Firm Name',
					'type' => 'text',
					'panelId' => 'attorney',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].AttyInfo[0].FirmName[0]']
				],
				[
					'key' => 'attorney_street',
					'label' => 'Street Address',
					'type' => 'text',
					'panelId' => 'attorney',
					'required' => true,
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].AttyInfo[0].Street[0]']
				],
				[
					'key' => 'attorney_city',
					'label' => 'City',
					'type' => 'text',
					'panelId' => 'attorney',
					'required' => true,
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].AttyInfo[0].City[0]']
				],
				[
					'key' => 'attorney_state',
					'label' => 'State',
					'type' => 'text',
					'panelId' => 'attorney',
					'required' => true,
					'placeholder' => 'CA',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].AttyInfo[0].State[0]']
				],
				[
					'key' => 'attorney_zip',
					'label' => 'Zip Code',
					'type' => 'text',
					'panelId' => 'attorney',
					'required' => true,
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].AttyInfo[0].Zip[0]']
				],
				[
					'key' => 'attorney_phone',
					'label' => 'Telephone Number',
					'type' => 'text',
					'panelId' => 'attorney',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].AttyInfo[0].Phone[0]']
				],
				[
					'key' => 'attorney_fax',
					'label' => 'Fax Number',
					'type' => 'text',
					'panelId' => 'attorney',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].AttyInfo[0].Fax[0]']
				],
				[
					'key' => 'attorney_email',
					'label' => 'Email Address',
					'type' => 'text',
					'panelId' => 'attorney',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].AttyInfo[0].Email[0]']
				],
				[
					'key' => 'attorney_for',
					'label' => 'Attorney for (name)',
					'type' => 'text',
					'panelId' => 'attorney',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].AttyInfo[0].AttyFor[0]']
				],

				// SUPERIOR COURT OF CALIFORNIA
				[
					'key' => 'court_county',
					'label' => 'County of',
					'type' => 'text',
					'panelId' => 'court',
					'required' => true,
					'placeholder' => 'Los Angeles',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].CourtInfo[0].County[0]']
				],
				[
					'key' => 'court_street',
					'label' => 'Street Address',
					'type' => 'text',
					'panelId' => 'court',
					'required' => true,
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].CourtInfo[0].CourtStreet[0]']
				],
				[
					'key' => 'court_mailing',
					'label' => 'Mailing Address (if different)',
					'type' => 'text',
					'panelId' => 'court',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].CourtInfo[0].MailingAdd[0]']
				],
				[
					'key' => 'court_city_zip',
					'label' => 'City and Zip Code',
					'type' => 'text',
					'panelId' => 'court',
					'required' => true,
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].CourtInfo[0].CityZip[0]']
				],
				[
					'key' => 'court_branch',
					'label' => 'Branch Name',
					'type' => 'text',
					'panelId' => 'court',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].CourtInfo[0].Branch[0]']
				],

				// MARRIAGE OR PARTNERSHIP OF
				[
					'key' => 'petitioner_name',
					'label' => 'Petitioner',
					'type' => 'text',
					'panelId' => 'parties',
					'required' => true,
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].PetitionerRespondent[0].Petitioner[0]']
				],
				[
					'key' => 'respondent_name',
					'label' => 'Respondent',
					'type' => 'text',
					'panelId' => 'parties',
					'required' => true,
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].PetitionerRespondent[0].Respondent[0]']
				],
				[
					'key' => 'case_number',
					'label' => 'Case Number',
					'type' => 'text',
					'panelId' => 'parties',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].CaseNumber[0]']
				],

				// PETITION FOR (checkboxes)
				[
					'key' => 'petition_dissolution_marriage',
					'label' => 'Dissolution (divorce) of Marriage',
					'type' => 'checkbox',
					'panelId' => 'petition_type',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].PetitionFor[0].Dissolution_Marriage[0]']
				],
				[
					'key' => 'petition_dissolution_partnership',
					'label' => 'Dissolution (divorce) of Domestic Partnership',
					'type' => 'checkbox',
					'panelId' => 'petition_type',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].PetitionFor[0].Dissolution_Partnership[0]']
				],
				[
					'key' => 'petition_legal_separation_marriage',
					'label' => 'Legal Separation of Marriage',
					'type' => 'checkbox',
					'panelId' => 'petition_type',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].PetitionFor[0].Separation_Marriage[0]']
				],
				[
					'key' => 'petition_legal_separation_partnership',
					'label' => 'Legal Separation of Domestic Partnership',
					'type' => 'checkbox',
					'panelId' => 'petition_type',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].PetitionFor[0].Separation_Partnership[0]']
				],
				[
					'key' => 'petition_nullity_marriage',
					'label' => 'Nullity of Marriage',
					'type' => 'checkbox',
					'panelId' => 'petition_type',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].PetitionFor[0].Nullity_Marriage[0]']
				],
				[
					'key' => 'petition_nullity_partnership',
					'label' => 'Nullity of Domestic Partnership',
					'type' => 'checkbox',
					'panelId' => 'petition_type',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].PetitionFor[0].Nullity_Partnership[0]']
				],

				// LEGAL RELATIONSHIP
				[
					'key' => 'we_are_married',
					'label' => 'We are married',
					'type' => 'checkbox',
					'panelId' => 'legal_relationship',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].LegalRelationship[0].Married[0]']
				],
				[
					'key' => 'we_are_domestic_partners',
					'label' => 'We are domestic partners and our domestic partnership was established in California',
					'type' => 'checkbox',
					'panelId' => 'legal_relationship',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].LegalRelationship[0].DomesticPartners[0]']
				],
				[
					'key' => 'we_are_same_sex_married',
					'label' => 'We are the same sex, were married in California, and wish to file for divorce in California',
					'type' => 'checkbox',
					'panelId' => 'legal_relationship',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].LegalRelationship[0].SameSexMarried[0]']
				],

				// RESIDENCE REQUIREMENTS
				[
					'key' => 'petitioner_resident',
					'label' => 'Petitioner has been a resident of this state for at least six months and of this county for at least three months immediately preceding the filing of this Petition',
					'type' => 'checkbox',
					'panelId' => 'residence',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].Residence[0].PetitionerResident[0]']
				],
				[
					'key' => 'respondent_resident',
					'label' => 'Respondent has been a resident of this state for at least six months and of this county for at least three months immediately preceding the filing of this Petition',
					'type' => 'checkbox',
					'panelId' => 'residence',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].Residence[0].RespondentResident[0]']
				],
				[
					'key' => 'same_sex_not_resident',
					'label' => 'We are the same sex and not residents of California but were married in California',
					'type' => 'checkbox',
					'panelId' => 'residence',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].Residence[0].SameSexNotResident[0]']
				],
				[
					'key' => 'our_partnership_established',
					'label' => 'Our domestic partnership was established in California. Neither of us has to be a resident or have lived in California for any specific length of time',
					'type' => 'checkbox',
					'panelId' => 'residence',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].Residence[0].PartnershipEstablished[0]']
				],

				// STATISTICAL FACTS
				[
					'key' => 'date_married_month',
					'label' => 'Date Married (Month)',
					'type' => 'text',
					'panelId' => 'statistical',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].StatisticalFacts[0].DateMarried_Month[0]']
				],
				[
					'key' => 'date_married_day',
					'label' => 'Date Married (Day)',
					'type' => 'text',
					'panelId' => 'statistical',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].StatisticalFacts[0].DateMarried_Day[0]']
				],
				[
					'key' => 'date_married_year',
					'label' => 'Date Married (Year)',
					'type' => 'text',
					'panelId' => 'statistical',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].StatisticalFacts[0].DateMarried_Year[0]']
				],
				[
					'key' => 'date_separated_month',
					'label' => 'Date Separated (Month)',
					'type' => 'text',
					'panelId' => 'statistical',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].StatisticalFacts[0].DateSeparated_Month[0]']
				],
				[
					'key' => 'date_separated_day',
					'label' => 'Date Separated (Day)',
					'type' => 'text',
					'panelId' => 'statistical',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].StatisticalFacts[0].DateSeparated_Day[0]']
				],
				[
					'key' => 'date_separated_year',
					'label' => 'Date Separated (Year)',
					'type' => 'text',
					'panelId' => 'statistical',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].StatisticalFacts[0].DateSeparated_Year[0]']
				],
				[
					'key' => 'time_from_marriage_years',
					'label' => 'Time from date of marriage to date of separation (Years)',
					'type' => 'text',
					'panelId' => 'statistical',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].StatisticalFacts[0].TimeMarriage_Years[0]']
				],
				[
					'key' => 'time_from_marriage_months',
					'label' => 'Time from date of marriage to date of separation (Months)',
					'type' => 'text',
					'panelId' => 'statistical',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].StatisticalFacts[0].TimeMarriage_Months[0]']
				],

				// MINOR CHILDREN
				[
					'key' => 'no_minor_children',
					'label' => 'There are no minor children',
					'type' => 'checkbox',
					'panelId' => 'minor_children',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].MinorChildren[0].NoMinorChildren[0]']
				],
				[
					'key' => 'minor_children_of_petitioner_respondent',
					'label' => 'The minor children are',
					'type' => 'checkbox',
					'panelId' => 'minor_children',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page1[0].MinorChildren[0].HasMinorChildren[0]']
				],
				[
					'key' => 'child1_name',
					'label' => 'Child 1 - Name',
					'type' => 'text',
					'panelId' => 'minor_children',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Child1_Name[0]']
				],
				[
					'key' => 'child1_birthdate',
					'label' => 'Child 1 - Birthdate',
					'type' => 'text',
					'panelId' => 'minor_children',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Child1_Birthdate[0]']
				],
				[
					'key' => 'child1_age',
					'label' => 'Child 1 - Age',
					'type' => 'text',
					'panelId' => 'minor_children',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Child1_Age[0]']
				],
				[
					'key' => 'child1_sex',
					'label' => 'Child 1 - Sex',
					'type' => 'text',
					'panelId' => 'minor_children',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Child1_Sex[0]']
				],
				[
					'key' => 'child2_name',
					'label' => 'Child 2 - Name',
					'type' => 'text',
					'panelId' => 'minor_children',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Child2_Name[0]']
				],
				[
					'key' => 'child2_birthdate',
					'label' => 'Child 2 - Birthdate',
					'type' => 'text',
					'panelId' => 'minor_children',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Child2_Birthdate[0]']
				],
				[
					'key' => 'continued_attachment',
					'label' => 'Continued on Attachment 6c',
					'type' => 'checkbox',
					'panelId' => 'minor_children',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].ContinuedAttachment[0]']
				],
				[
					'key' => 'pregnant_no',
					'label' => 'Petitioner is not pregnant',
					'type' => 'checkbox',
					'panelId' => 'minor_children',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].PregnantNo[0]']
				],
				[
					'key' => 'pregnant_yes',
					'label' => 'Petitioner is pregnant',
					'type' => 'checkbox',
					'panelId' => 'minor_children',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].PregnantYes[0]']
				],

				// LEGAL GROUNDS
				[
					'key' => 'grounds_divorce',
					'label' => 'Dissolution (Divorce) or Legal Separation based on',
					'type' => 'select',
					'panelId' => 'property_rights',
					'options' => ['', 'irreconcilable differences', 'incurable insanity'],
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].LegalGrounds[0]']
				],
				[
					'key' => 'grounds_nullity',
					'label' => 'Nullity based on',
					'type' => 'select',
					'panelId' => 'property_rights',
					'options' => ['', 'void marriage/domestic partnership', 'voidable marriage/domestic partnership'],
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].NullityGrounds[0]']
				],

				// PETITIONER REQUESTS (section 8)
				[
					'key' => 'child_custody_to_petitioner',
					'label' => 'Child custody to Petitioner',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Request_Custody_Petitioner[0]']
				],
				[
					'key' => 'child_custody_to_respondent',
					'label' => 'Child custody to Respondent',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Request_Custody_Respondent[0]']
				],
				[
					'key' => 'child_custody_other',
					'label' => 'Child custody to Other',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Request_Custody_Other[0]']
				],
				[
					'key' => 'child_visitation_granted',
					'label' => 'Child visitation be granted to',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Request_Visitation[0]']
				],
				[
					'key' => 'child_visitation_petitioner',
					'label' => 'Visitation to Petitioner',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Visitation_Petitioner[0]']
				],
				[
					'key' => 'child_visitation_respondent',
					'label' => 'Visitation to Respondent',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Visitation_Respondent[0]']
				],
				[
					'key' => 'determine_parentage',
					'label' => 'Determination of parentage of any children born to Petitioner and Respondent prior to or during this marriage or domestic partnership',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Request_Parentage[0]']
				],
				[
					'key' => 'spousal_support_petitioner',
					'label' => 'Spousal or domestic partner support payable to Petitioner',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Support_Petitioner[0]']
				],
				[
					'key' => 'spousal_support_respondent',
					'label' => 'Spousal or domestic partner support payable to Respondent',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Support_Respondent[0]']
				],
				[
					'key' => 'terminate_support',
					'label' => 'Terminate the court\'s ability to award support to Petitioner/Respondent',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Terminate_Support[0]']
				],
				[
					'key' => 'property_rights_determination',
					'label' => 'Property rights be determined',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Property_Rights[0]']
				],
				[
					'key' => 'attorney_fees_petitioner',
					'label' => 'Attorney fees and costs payable by Respondent',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Attorney_Fees_Petitioner[0]']
				],
				[
					'key' => 'attorney_fees_respondent',
					'label' => 'Attorney fees and costs payable by Petitioner',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Attorney_Fees_Respondent[0]']
				],
				[
					'key' => 'restore_name',
					'label' => 'Petitioner\'s former name be restored',
					'type' => 'checkbox',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Restore_Name[0]']
				],
				[
					'key' => 'former_name',
					'label' => 'Former name to be restored',
					'type' => 'text',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Former_Name[0]']
				],
				[
					'key' => 'other_relief',
					'label' => 'Other relief (specify)',
					'type' => 'textarea',
					'panelId' => 'requests',
					'pdfTarget' => ['formField' => 'topmostSubform[0].Page2[0].Other_Relief[0]']
				],
			]
		];
	}

	public static function getCompleteTestData(): array {
		return [
			// Attorney Information
			'attorney_name' => 'John Michael Smith, Esq.',
			'attorney_bar_number' => '123456',
			'attorney_firm' => 'Smith & Associates Family Law',
			'attorney_street' => '1234 Legal Plaza, Suite 500',
			'attorney_city' => 'Los Angeles',
			'attorney_state' => 'CA',
			'attorney_zip' => '90210',
			'attorney_phone' => '(555) 123-4567',
			'attorney_fax' => '(555) 123-4568',
			'attorney_email' => 'jsmith@smithlaw.com',
			'attorney_for' => 'Petitioner Sarah Elizabeth Johnson',

			// Court Information
			'court_county' => 'Los Angeles',
			'court_street' => '111 N Hill St',
			'court_mailing' => '111 N Hill St, Room 118',
			'court_city_zip' => 'Los Angeles, CA 90012',
			'court_branch' => 'Stanley Mosk Courthouse',

			// Parties
			'petitioner_name' => 'Sarah Elizabeth Johnson',
			'respondent_name' => 'Michael David Johnson',
			'case_number' => 'FL-2024-001234',

			// Petition For (checking dissolution of marriage)
			'petition_dissolution_marriage' => '1',
			'petition_dissolution_partnership' => '0',
			'petition_legal_separation_marriage' => '0',
			'petition_legal_separation_partnership' => '0',
			'petition_nullity_marriage' => '0',
			'petition_nullity_partnership' => '0',

			// Legal Relationship (we are married)
			'we_are_married' => '1',
			'we_are_domestic_partners' => '0',
			'we_are_same_sex_married' => '0',

			// Residence Requirements
			'petitioner_resident' => '1',
			'respondent_resident' => '1',
			'same_sex_not_resident' => '0',
			'our_partnership_established' => '0',

			// Statistical Facts
			'date_married_month' => '06',
			'date_married_day' => '15',
			'date_married_year' => '2010',
			'date_separated_month' => '03',
			'date_separated_day' => '20',
			'date_separated_year' => '2024',
			'time_from_marriage_years' => '13',
			'time_from_marriage_months' => '9',

			// Minor Children
			'no_minor_children' => '0',
			'minor_children_of_petitioner_respondent' => '1',
			'child1_name' => 'Emma Rose Johnson',
			'child1_birthdate' => '08/12/2012',
			'child1_age' => '12',
			'child1_sex' => 'F',
			'child2_name' => 'James Michael Johnson',
			'child2_birthdate' => '04/23/2015',
			'continued_attachment' => '0',
			'pregnant_no' => '1',
			'pregnant_yes' => '0',

			// Legal Grounds
			'grounds_divorce' => 'irreconcilable differences',
			'grounds_nullity' => '',

			// Petitioner Requests
			'child_custody_to_petitioner' => '1',
			'child_custody_to_respondent' => '0',
			'child_custody_other' => '0',
			'child_visitation_granted' => '1',
			'child_visitation_petitioner' => '0',
			'child_visitation_respondent' => '1',
			'determine_parentage' => '0',
			'spousal_support_petitioner' => '1',
			'spousal_support_respondent' => '0',
			'terminate_support' => '0',
			'property_rights_determination' => '1',
			'attorney_fees_petitioner' => '1',
			'attorney_fees_respondent' => '0',
			'restore_name' => '1',
			'former_name' => 'Sarah Elizabeth Martinez',
			'other_relief' => 'Petitioner requests exclusive use and possession of the family residence at 789 Maple Street, Los Angeles, CA 90210, and that Respondent be ordered to contribute to the mortgage payments.',
		];
	}
}