<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

final class TemplateRegistry {
	public static function load(): array {
		// Seed with a single FL-105/GC-120 example matching screenshots
		return [
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
					[ 'key' => 'attorney.name', 'label' => 'Attorney Name', 'type' => 'text', 'panelId' => 'attorney' ],
					[ 'key' => 'attorney.firm', 'label' => 'Law Firm Name', 'type' => 'text', 'panelId' => 'attorney' ],
					[ 'key' => 'attorney.bar', 'label' => 'State Bar Number', 'type' => 'text', 'panelId' => 'attorney' ],
					[ 'key' => 'court.branch', 'label' => 'Court Branch', 'type' => 'text', 'panelId' => 'court' ],
					[ 'key' => 'petitioner.name', 'label' => 'Petitioner', 'type' => 'text', 'panelId' => 'parties' ],
					[ 'key' => 'respondent.name', 'label' => 'Respondent', 'type' => 'text', 'panelId' => 'parties' ]
				]
			]
		];
	}
}


