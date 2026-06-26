<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/../lib/dynamic_template_generator.php';

final class TemplateRegistry {
	public static function load(): array {
		// Return empty array - templates are now generated dynamically
		return [];
	}

	private static function toTemplateSummary(string $templateId): array {
		return [
			'id' => $templateId,
			'code' => strtoupper($templateId),
			'name' => ucwords(str_replace(['_', '-'], ' ', $templateId)),
			'version' => '1.0',
			'fields' => [],
			'panels' => [],
		];
	}
	
	/**
	 * Get template dynamically from PDF field extraction
	 */
	public static function getTemplate($templateId): array {
		error_log("TemplateRegistry::getTemplate called with: $templateId");
		
		$generator = new DynamicTemplateGenerator();
		error_log("DynamicTemplateGenerator created");
		
		try {
			error_log("Attempting to generate template from PDF for: $templateId");
			$template = $generator->generateTemplateFromPdf($templateId);
			// Log successful generation
			error_log("Dynamic template generated for $templateId: " . count($template['fields']) . " fields");
			return $template;
		} catch (\Exception $e) {
			// Log the error and fallback to basic template if extraction fails
			error_log("Dynamic template generation failed for $templateId: " . $e->getMessage());
			error_log("Stack trace: " . $e->getTraceAsString());
			return self::getFallbackTemplate($templateId);
		}
	}
	
	/**
	 * Get all available templates by scanning positions files.
	 * This method intentionally returns lightweight summaries to avoid
	 * expensive full-template generation on every page load.
	 */
	public static function getAllTemplates(): array {
		$templates = [];
		$dataDir = __DIR__ . '/../../data';
		
		if (!is_dir($dataDir)) {
			return $templates;
		}
		
		$files = glob($dataDir . '/*_positions.json');
		
		foreach ($files as $file) {
			$filename = basename($file);
			$templateId = str_replace('_positions.json', '', $filename);
			$templates[$templateId] = self::toTemplateSummary((string)$templateId);
		}
		
		return $templates;
	}
	
	/**
	 * Fallback template for when extraction fails
	 */
	private static function getFallbackTemplate($templateId): array {
		return [
			'id' => $templateId,
			'code' => strtoupper($templateId),
			'name' => ucwords(str_replace(['_', '-'], ' ', $templateId)),
			'pageCount' => 1,
			'panels' => [
				['id' => 'general', 'label' => 'General Information']
			],
			'fields' => [
				[
					'key' => 'general_field',
					'label' => 'General Field',
					'type' => 'text',
					'panelId' => 'general',
					'required' => false,
					'placeholder' => 'Enter information',
					'pdfTarget' => ['formField' => 'GENERAL_FIELD']
				]
			]
		];
	}
}


