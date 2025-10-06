<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

/**
 * Drafting Manager - Manages form drafting sessions similar to Clio
 * Provides step-by-step form drafting, validation, and progress tracking
 */
class DraftingManager {
    private DataStore $dataStore;
    private array $templates;
    private string $logFile;
    
    public function __construct(DataStore $dataStore, array $templates = []) {
        $this->dataStore = $dataStore;
        $this->templates = $templates;
        $this->logFile = __DIR__ . '/../../logs/drafting.log';
    }
    
    /**
     * Get drafting status for a project document
     */
    public function getDraftingStatus(string $projectDocumentId): array {
        $projDoc = $this->dataStore->getProjectDocumentById($projectDocumentId);
        if (!$projDoc) {
            return ['error' => 'Document not found'];
        }
        
        $template = $this->templates[$projDoc['templateId']] ?? null;
        if (!$template) {
            return ['error' => 'Template not found'];
        }
        
        $values = $this->dataStore->getFieldValues($projectDocumentId);
        $customFields = $this->dataStore->getCustomFields($projectDocumentId);
        
        // Calculate completion status for each panel
        $panels = [];
        foreach ($template['panels'] ?? [] as $panel) {
            $panelFields = array_filter($template['fields'] ?? [], function($field) use ($panel) {
                return ($field['panelId'] ?? '') === $panel['id'];
            });
            
            $totalFields = count($panelFields);
            $completedFields = 0;
            $requiredComplete = true;
            $errors = [];
            
            foreach ($panelFields as $field) {
                $value = $values[$field['key']] ?? '';
                
                // Check if field has a value
                if (!empty($value)) {
                    $completedFields++;
                    
                    // Validate field value
                    $validation = $this->validateField($field, $value);
                    if (!$validation['valid']) {
                        $errors[] = [
                            'field' => $field['key'],
                            'message' => $validation['message']
                        ];
                    }
                } elseif (!empty($field['required'])) {
                    $requiredComplete = false;
                    $errors[] = [
                        'field' => $field['key'],
                        'message' => $field['label'] . ' is required'
                    ];
                }
            }
            
            $panels[] = [
                'id' => $panel['id'],
                'label' => $panel['label'],
                'totalFields' => $totalFields,
                'completedFields' => $completedFields,
                'progress' => $totalFields > 0 ? round(($completedFields / $totalFields) * 100) : 0,
                'requiredComplete' => $requiredComplete,
                'errors' => $errors,
                'status' => $this->getPanelStatus($completedFields, $totalFields, $requiredComplete)
            ];
        }
        
        // Calculate overall progress
        $totalPanels = count($panels);
        $completedPanels = count(array_filter($panels, fn($p) => $p['status'] === 'complete'));
        $overallProgress = $totalPanels > 0 ? round(($completedPanels / $totalPanels) * 100) : 0;
        
        return [
            'projectDocumentId' => $projectDocumentId,
            'templateId' => $projDoc['templateId'],
            'panels' => $panels,
            'overallProgress' => $overallProgress,
            'totalPanels' => $totalPanels,
            'completedPanels' => $completedPanels,
            'canGenerate' => $this->canGeneratePdf($panels),
            'currentStep' => $this->getCurrentStep($panels),
            'nextStep' => $this->getNextStep($panels)
        ];
    }
    
    /**
     * Validate a field value
     */
    public function validateField(array $field, $value): array {
        // Required field validation
        if (!empty($field['required']) && empty($value)) {
            return [
                'valid' => false,
                'message' => 'This field is required'
            ];
        }
        
        // Type-specific validation
        switch ($field['type'] ?? 'text') {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return [
                        'valid' => false,
                        'message' => 'Invalid email address'
                    ];
                }
                break;
                
            case 'number':
                if (!is_numeric($value)) {
                    return [
                        'valid' => false,
                        'message' => 'Must be a valid number'
                    ];
                }
                break;
                
            case 'date':
                if (!strtotime($value)) {
                    return [
                        'valid' => false,
                        'message' => 'Invalid date format'
                    ];
                }
                break;
                
            case 'select':
                if (!empty($field['options']) && !in_array($value, $field['options'])) {
                    return [
                        'valid' => false,
                        'message' => 'Invalid selection'
                    ];
                }
                break;
        }
        
        // Pattern validation
        if (!empty($field['pattern']) && !empty($value)) {
            if (!preg_match('/' . $field['pattern'] . '/', $value)) {
                return [
                    'valid' => false,
                    'message' => 'Value does not match required format'
                ];
            }
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get panel status
     */
    private function getPanelStatus(int $completedFields, int $totalFields, bool $requiredComplete): string {
        if ($totalFields === 0) {
            return 'empty';
        }
        
        if ($completedFields === 0) {
            return 'not_started';
        }
        
        if ($completedFields === $totalFields && $requiredComplete) {
            return 'complete';
        }
        
        if ($requiredComplete) {
            return 'in_progress';
        }
        
        return 'incomplete';
    }
    
    /**
     * Check if PDF can be generated
     */
    private function canGeneratePdf(array $panels): bool {
        // All panels with required fields must be complete
        foreach ($panels as $panel) {
            if (!$panel['requiredComplete']) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get current step in drafting
     */
    private function getCurrentStep(array $panels): ?array {
        // Find first incomplete panel
        foreach ($panels as $index => $panel) {
            if ($panel['status'] !== 'complete' && $panel['status'] !== 'empty') {
                return [
                    'index' => $index,
                    'panelId' => $panel['id'],
                    'label' => $panel['label'],
                    'progress' => $panel['progress']
                ];
            }
        }
        return null;
    }
    
    /**
     * Get next step in drafting
     */
    private function getNextStep(array $panels): ?array {
        $foundCurrent = false;
        foreach ($panels as $index => $panel) {
            if ($foundCurrent && $panel['status'] !== 'empty') {
                return [
                    'index' => $index,
                    'panelId' => $panel['id'],
                    'label' => $panel['label']
                ];
            }
            if ($panel['status'] !== 'complete' && $panel['status'] !== 'empty') {
                $foundCurrent = true;
            }
        }
        return null;
    }
    
    /**
     * Create a drafting session for a project document
     */
    public function createDraftSession(string $projectDocumentId, array $options = []): array {
        $projDoc = $this->dataStore->getProjectDocumentById($projectDocumentId);
        if (!$projDoc) {
            return ['error' => 'Document not found'];
        }
        
        $template = $this->templates[$projDoc['templateId']] ?? null;
        if (!$template) {
            return ['error' => 'Template not found'];
        }
        
        // Initialize drafting state
        $drafting = [
            'id' => uniqid('draft_'),
            'projectDocumentId' => $projectDocumentId,
            'templateId' => $projDoc['templateId'],
            'status' => 'active',
            'currentPanelIndex' => 0,
            'completedPanels' => [],
            'skipPanels' => $options['skipPanels'] ?? [],
            'createdAt' => date(DATE_ATOM),
            'updatedAt' => date(DATE_ATOM)
        ];
        
        // Save drafting state
        $this->saveDraftSessionState($drafting);
        
        return $drafting;
    }
    
    /**
     * Save drafting state
     */
    private function saveDraftSessionState(array $drafting): void {
        $draftingFile = __DIR__ . '/../../data/draftings/' . $drafting['id'] . '.json';
        $draftingDir = dirname($draftingFile);
        
        if (!is_dir($draftingDir)) {
            mkdir($draftingDir, 0777, true);
        }
        
        file_put_contents($draftingFile, json_encode($drafting, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * Load drafting state
     */
    public function loadDraftSessionState(string $draftingId): ?array {
        $draftingFile = __DIR__ . '/../../data/draftings/' . $draftingId . '.json';
        if (!file_exists($draftingFile)) {
            return null;
        }
        
        return json_decode(file_get_contents($draftingFile), true);
    }
    
    /**
     * Get drafting by project document
     */
    public function getDraftSessionByDocument(string $projectDocumentId): ?array {
        $draftingDir = __DIR__ . '/../../data/draftings/';
        if (!is_dir($draftingDir)) {
            return null;
        }
        
        $files = glob($draftingDir . '*.json');
        foreach ($files as $file) {
            $drafting = json_decode(file_get_contents($file), true);
            if ($drafting && ($drafting['projectDocumentId'] ?? '') === $projectDocumentId) {
                return $drafting;
            }
        }
        
        return null;
    }
    
    /**
     * Complete a panel in the drafting
     */
    public function completePanel(string $draftingId, string $panelId): array {
        $drafting = $this->loadDraftSessionState($draftingId);
        if (!$drafting) {
            return ['error' => 'Drafting not found'];
        }
        
        if (!in_array($panelId, $drafting['completedPanels'])) {
            $drafting['completedPanels'][] = $panelId;
            $drafting['updatedAt'] = date(DATE_ATOM);
            
            // Update current panel index
            $template = $this->templates[$drafting['templateId']] ?? null;
            if ($template) {
                $panels = $template['panels'] ?? [];
                foreach ($panels as $index => $panel) {
                    if ($panel['id'] === $panelId) {
                        $drafting['currentPanelIndex'] = min($index + 1, count($panels) - 1);
                        break;
                    }
                }
            }
            
            $this->saveDraftSessionState($drafting);
        }
        
        return $drafting;
    }
    
    /**
     * Skip a panel in the drafting
     */
    public function skipPanel(string $draftingId, string $panelId): array {
        $drafting = $this->loadDraftSessionState($draftingId);
        if (!$drafting) {
            return ['error' => 'Drafting not found'];
        }
        
        if (!in_array($panelId, $drafting['skipPanels'])) {
            $drafting['skipPanels'][] = $panelId;
            $drafting['updatedAt'] = date(DATE_ATOM);
            $this->saveDraftSessionState($drafting);
        }
        
        return $drafting;
    }
    
    /**
     * Get drafting analytics
     */
    public function getDraftingAnalytics(string $draftingId): array {
        $drafting = $this->loadDraftSessionState($draftingId);
        if (!$drafting) {
            return ['error' => 'Drafting not found'];
        }
        
        $status = $this->getDraftingStatus($drafting['projectDocumentId']);
        
        // Calculate time metrics
        $createdTime = strtotime($drafting['createdAt']);
        $currentTime = time();
        $elapsedTime = $currentTime - $createdTime;
        
        // Estimate completion time based on current progress
        $progressRate = $status['overallProgress'] > 0 ? $elapsedTime / $status['overallProgress'] : 0;
        $estimatedTotalTime = $progressRate * 100;
        $estimatedRemainingTime = max(0, $estimatedTotalTime - $elapsedTime);
        
        return [
            'draftingId' => $draftingId,
            'elapsedTime' => $this->formatDuration($elapsedTime),
            'estimatedRemainingTime' => $this->formatDuration($estimatedRemainingTime),
            'completionRate' => $status['overallProgress'] . '%',
            'panelsCompleted' => count($drafting['completedPanels']),
            'panelsSkipped' => count($drafting['skipPanels']),
            'averageTimePerPanel' => count($drafting['completedPanels']) > 0 ? 
                $this->formatDuration($elapsedTime / count($drafting['completedPanels'])) : 'N/A',
            'bottlenecks' => $this->identifyBottlenecks($status['panels'])
        ];
    }
    
    /**
     * Format duration in human-readable format
     */
    private function formatDuration(int $seconds): string {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        }
        
        $minutes = floor($seconds / 60);
        if ($minutes < 60) {
            return $minutes . ' minutes';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . ' hours ' . $remainingMinutes . ' minutes';
    }
    
    /**
     * Identify bottlenecks in the drafting
     */
    private function identifyBottlenecks(array $panels): array {
        $bottlenecks = [];
        
        foreach ($panels as $panel) {
            if ($panel['status'] === 'incomplete' && count($panel['errors']) > 0) {
                $bottlenecks[] = [
                    'panel' => $panel['label'],
                    'issues' => count($panel['errors']),
                    'type' => 'validation_errors'
                ];
            } elseif ($panel['progress'] > 0 && $panel['progress'] < 50) {
                $bottlenecks[] = [
                    'panel' => $panel['label'],
                    'progress' => $panel['progress'] . '%',
                    'type' => 'partial_completion'
                ];
            }
        }
        
        return $bottlenecks;
    }
    
    /**
     * Generate drafting report
     */
    public function generateDraftingReport(string $draftingId): array {
        $drafting = $this->loadDraftSessionState($draftingId);
        if (!$drafting) {
            return ['error' => 'Drafting not found'];
        }
        
        $status = $this->getDraftingStatus($drafting['projectDocumentId']);
        $analytics = $this->getDraftingAnalytics($draftingId);
        
        return [
            'drafting' => $drafting,
            'status' => $status,
            'analytics' => $analytics,
            'recommendations' => $this->getDraftingRecommendations($status, $analytics),
            'exportedAt' => date(DATE_ATOM)
        ];
    }
    
    /**
     * Get drafting recommendations
     */
    private function getDraftingRecommendations(array $status, array $analytics): array {
        $recommendations = [];
        
        // Check for low completion rate
        if ($status['overallProgress'] < 50) {
            $recommendations[] = [
                'type' => 'progress',
                'priority' => 'high',
                'message' => 'Drafting is less than 50% complete. Focus on completing required fields first.'
            ];
        }
        
        // Check for validation errors
        $totalErrors = 0;
        foreach ($status['panels'] as $panel) {
            $totalErrors += count($panel['errors']);
        }
        
        if ($totalErrors > 0) {
            $recommendations[] = [
                'type' => 'validation',
                'priority' => 'high',
                'message' => "There are {$totalErrors} validation errors that need to be resolved."
            ];
        }
        
        // Check for bottlenecks
        if (count($analytics['bottlenecks']) > 0) {
            $recommendations[] = [
                'type' => 'bottleneck',
                'priority' => 'medium',
                'message' => 'Some panels have partial completion. Consider focusing on one panel at a time.'
            ];
        }
        
        // Check if ready to generate
        if ($status['canGenerate']) {
            $recommendations[] = [
                'type' => 'ready',
                'priority' => 'low',
                'message' => 'All required fields are complete. The document is ready to generate.'
            ];
        }
        
        return $recommendations;
    }
}