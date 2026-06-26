<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/logger.php';

/**
 * Custom Field Manager
 * Handles dynamic field creation, positioning, and persistence for templates
 */
final class CustomFieldManager {
    private DataStore $store;
    private Logger $logger;

    public function __construct(DataStore $store, ?Logger $logger = null) {
        $this->store = $store;
        $this->logger = $logger ?? new Logger();
    }

    /**
     * Add a custom field to a template
     */
    public function addCustomField(string $templateId, array $fieldConfig): ?array {
        $field = [
            'key' => $fieldConfig['key'] ?? 'custom_field_' . bin2hex(random_bytes(4)),
            'label' => $fieldConfig['label'] ?? 'Custom Field',
            'type' => $fieldConfig['type'] ?? 'text',
            'x' => (int)($fieldConfig['x'] ?? 100),
            'y' => (int)($fieldConfig['y'] ?? 100),
            'width' => (int)($fieldConfig['width'] ?? 200),
            'height' => (int)($fieldConfig['height'] ?? 25),
            'page' => (int)($fieldConfig['page'] ?? 1),
            'isCustom' => true,
            'createdAt' => date(DATE_ATOM),
            'panelId' => $fieldConfig['panelId'] ?? 'custom_panel'
        ];

        // Get template registry to update
        $templateRegistry = TemplateRegistry::getTemplate($templateId);
        if (!$templateRegistry) {
            $this->logger->error('Template not found for custom field', ['templateId' => $templateId]);
            return null;
        }

        // Add custom field to template
        if (!isset($templateRegistry['customFields'])) {
            $templateRegistry['customFields'] = [];
        }
        $templateRegistry['customFields'][] = $field;

        // Add to main fields array for rendering
        if (!isset($templateRegistry['fields'])) {
            $templateRegistry['fields'] = [];
        }
        $templateRegistry['fields'][] = $field;

        // Save updated template
        $this->saveTemplate($templateId, $templateRegistry);

        $this->logger->info('Custom field added', [
            'templateId' => $templateId,
            'fieldKey' => $field['key'],
            'label' => $field['label']
        ]);

        return $field;
    }

    /**
     * Remove a custom field from a template
     */
    public function removeCustomField(string $templateId, string $fieldKey): bool {
        $templateRegistry = TemplateRegistry::getTemplate($templateId);
        if (!$templateRegistry) {
            return false;
        }

        $removed = false;

        // Remove from customFields array
        if (isset($templateRegistry['customFields'])) {
            $templateRegistry['customFields'] = array_values(array_filter(
                $templateRegistry['customFields'],
                function($field) use ($fieldKey, &$removed) {
                    if ($field['key'] === $fieldKey) {
                        $removed = true;
                        return false;
                    }
                    return true;
                }
            ));
        }

        // Remove from main fields array
        if (isset($templateRegistry['fields'])) {
            $templateRegistry['fields'] = array_values(array_filter(
                $templateRegistry['fields'],
                function($field) use ($fieldKey) {
                    return $field['key'] !== $fieldKey;
                }
            ));
        }

        if ($removed) {
            $this->saveTemplate($templateId, $templateRegistry);
            $this->logger->info('Custom field removed', [
                'templateId' => $templateId,
                'fieldKey' => $fieldKey
            ]);
        }

        return $removed;
    }

    /**
     * Update field position and dimensions
     */
    public function updateFieldPosition(string $templateId, string $fieldKey, int $x, int $y, int $width, int $height): bool {
        $templateRegistry = TemplateRegistry::getTemplate($templateId);
        if (!$templateRegistry) {
            return false;
        }

        $updated = false;

        // Update in customFields array
        if (isset($templateRegistry['customFields'])) {
            foreach ($templateRegistry['customFields'] as &$field) {
                if ($field['key'] === $fieldKey) {
                    $field['x'] = $x;
                    $field['y'] = $y;
                    $field['width'] = $width;
                    $field['height'] = $height;
                    $updated = true;
                    break;
                }
            }
        }

        // Update in main fields array
        if (isset($templateRegistry['fields'])) {
            foreach ($templateRegistry['fields'] as &$field) {
                if ($field['key'] === $fieldKey) {
                    $field['x'] = $x;
                    $field['y'] = $y;
                    $field['width'] = $width;
                    $field['height'] = $height;
                    break;
                }
            }
        }

        if ($updated) {
            $this->saveTemplate($templateId, $templateRegistry);
            $this->logger->info('Field position updated', [
                'templateId' => $templateId,
                'fieldKey' => $fieldKey,
                'position' => ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]
            ]);
        }

        return $updated;
    }

    /**
     * Get all custom fields for a template
     */
    public function getCustomFields(string $templateId): array {
        $templateRegistry = TemplateRegistry::getTemplate($templateId);
        if (!$templateRegistry) {
            return [];
        }

        return $templateRegistry['customFields'] ?? [];
    }

    /**
     * Get all fields (including custom) for a template
     */
    public function getAllFields(string $templateId): array {
        $templateRegistry = TemplateRegistry::getTemplate($templateId);
        if (!$templateRegistry) {
            return [];
        }

        return $templateRegistry['fields'] ?? [];
    }

    /**
     * Save template to registry
     */
    private function saveTemplate(string $templateId, array $template): void {
        // This would typically save to the template registry file
        // For now, we'll use a simple file-based approach
        $templateFile = __DIR__ . '/../templates/' . $templateId . '.json';
        
        if (!is_dir(dirname($templateFile))) {
            mkdir(dirname($templateFile), 0755, true);
        }

        file_put_contents($templateFile, json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Generate a unique field key
     */
    public function generateFieldKey(string $label): string {
        $baseKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $label));
        $key = $baseKey;
        $counter = 1;

        // Ensure uniqueness by appending numbers if needed
        while ($this->fieldKeyExists($key)) {
            $key = $baseKey . '_' . $counter;
            $counter++;
        }

        return $key;
    }

    /**
     * Check if a field key already exists
     */
    private function fieldKeyExists(string $key): bool {
        // This would check against all templates
        // For now, return false to allow any key
        return false;
    }
}
