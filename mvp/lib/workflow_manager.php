<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

/**
 * Workflow Manager - Manages form filling workflows similar to Clio
 * Provides step-by-step form completion, validation, and progress tracking
 */
class WorkflowManager {
    private DataStore $dataStore;
    private array $templates;
    private string $logFile;
    
    public function __construct(DataStore $dataStore, array $templates = []) {
        $this->dataStore = $dataStore;
        $this->templates = $templates;
        $this->logFile = __DIR__ . '/../../logs/workflow.log';
    }
    
    /**
     * Get workflow status for a project document
     */
    public function getWorkflowStatus(string $projectDocumentId): array {
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
     * Get current step in workflow
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
     * Get next step in workflow
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
     * Create a workflow instance for a project document
     */
    public function createWorkflow(string $projectDocumentId, array $options = []): array {
        $projDoc = $this->dataStore->getProjectDocumentById($projectDocumentId);
        if (!$projDoc) {
            return ['error' => 'Document not found'];
        }
        
        $template = $this->templates[$projDoc['templateId']] ?? null;
        if (!$template) {
            return ['error' => 'Template not found'];
        }
        
        // Initialize workflow state
        $workflow = [
            'id' => uniqid('workflow_'),
            'projectDocumentId' => $projectDocumentId,
            'templateId' => $projDoc['templateId'],
            'status' => 'active',
            'currentPanelIndex' => 0,
            'completedPanels' => [],
            'skipPanels' => $options['skipPanels'] ?? [],
            'createdAt' => date(DATE_ATOM),
            'updatedAt' => date(DATE_ATOM)
        ];
        
        // Save workflow state
        $this->saveWorkflowState($workflow);
        
        return $workflow;
    }
    
    /**
     * Save workflow state
     */
    private function saveWorkflowState(array $workflow): void {
        $workflowFile = __DIR__ . '/../../data/workflows/' . $workflow['id'] . '.json';
        $workflowDir = dirname($workflowFile);
        
        if (!is_dir($workflowDir)) {
            mkdir($workflowDir, 0777, true);
        }
        
        file_put_contents($workflowFile, json_encode($workflow, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * Load workflow state
     */
    public function loadWorkflowState(string $workflowId): ?array {
        $workflowFile = __DIR__ . '/../../data/workflows/' . $workflowId . '.json';
        if (!file_exists($workflowFile)) {
            return null;
        }
        
        return json_decode(file_get_contents($workflowFile), true);
    }
    
    /**
     * Get workflow by project document
     */
    public function getWorkflowByDocument(string $projectDocumentId): ?array {
        $workflowDir = __DIR__ . '/../../data/workflows/';
        if (!is_dir($workflowDir)) {
            return null;
        }
        
        $files = glob($workflowDir . '*.json');
        foreach ($files as $file) {
            $workflow = json_decode(file_get_contents($file), true);
            if ($workflow && ($workflow['projectDocumentId'] ?? '') === $projectDocumentId) {
                return $workflow;
            }
        }
        
        return null;
    }
    
    /**
     * Complete a panel in the workflow
     */
    public function completePanel(string $workflowId, string $panelId): array {
        $workflow = $this->loadWorkflowState($workflowId);
        if (!$workflow) {
            return ['error' => 'Workflow not found'];
        }
        
        if (!in_array($panelId, $workflow['completedPanels'])) {
            $workflow['completedPanels'][] = $panelId;
            $workflow['updatedAt'] = date(DATE_ATOM);
            
            // Update current panel index
            $template = $this->templates[$workflow['templateId']] ?? null;
            if ($template) {
                $panels = $template['panels'] ?? [];
                foreach ($panels as $index => $panel) {
                    if ($panel['id'] === $panelId) {
                        $workflow['currentPanelIndex'] = min($index + 1, count($panels) - 1);
                        break;
                    }
                }
            }
            
            $this->saveWorkflowState($workflow);
        }
        
        return $workflow;
    }
    
    /**
     * Skip a panel in the workflow
     */
    public function skipPanel(string $workflowId, string $panelId): array {
        $workflow = $this->loadWorkflowState($workflowId);
        if (!$workflow) {
            return ['error' => 'Workflow not found'];
        }
        
        if (!in_array($panelId, $workflow['skipPanels'])) {
            $workflow['skipPanels'][] = $panelId;
            $workflow['updatedAt'] = date(DATE_ATOM);
            $this->saveWorkflowState($workflow);
        }
        
        return $workflow;
    }
    
    /**
     * Get workflow analytics
     */
    public function getWorkflowAnalytics(string $workflowId): array {
        $workflow = $this->loadWorkflowState($workflowId);
        if (!$workflow) {
            return ['error' => 'Workflow not found'];
        }
        
        $status = $this->getWorkflowStatus($workflow['projectDocumentId']);
        
        // Calculate time metrics
        $createdTime = strtotime($workflow['createdAt']);
        $currentTime = time();
        $elapsedTime = $currentTime - $createdTime;
        
        // Estimate completion time based on current progress
        $progressRate = $status['overallProgress'] > 0 ? $elapsedTime / $status['overallProgress'] : 0;
        $estimatedTotalTime = $progressRate * 100;
        $estimatedRemainingTime = max(0, $estimatedTotalTime - $elapsedTime);
        
        return [
            'workflowId' => $workflowId,
            'elapsedTime' => $this->formatDuration($elapsedTime),
            'estimatedRemainingTime' => $this->formatDuration($estimatedRemainingTime),
            'completionRate' => $status['overallProgress'] . '%',
            'panelsCompleted' => count($workflow['completedPanels']),
            'panelsSkipped' => count($workflow['skipPanels']),
            'averageTimePerPanel' => count($workflow['completedPanels']) > 0 ? 
                $this->formatDuration($elapsedTime / count($workflow['completedPanels'])) : 'N/A',
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
     * Identify bottlenecks in the workflow
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
     * Generate workflow report
     */
    public function generateWorkflowReport(string $workflowId): array {
        $workflow = $this->loadWorkflowState($workflowId);
        if (!$workflow) {
            return ['error' => 'Workflow not found'];
        }
        
        $status = $this->getWorkflowStatus($workflow['projectDocumentId']);
        $analytics = $this->getWorkflowAnalytics($workflowId);
        
        return [
            'workflow' => $workflow,
            'status' => $status,
            'analytics' => $analytics,
            'recommendations' => $this->getWorkflowRecommendations($status, $analytics),
            'exportedAt' => date(DATE_ATOM)
        ];
    }
    
    /**
     * Get workflow recommendations
     */
    private function getWorkflowRecommendations(array $status, array $analytics): array {
        $recommendations = [];
        
        // Check for low completion rate
        if ($status['overallProgress'] < 50) {
            $recommendations[] = [
                'type' => 'progress',
                'priority' => 'high',
                'message' => 'Workflow is less than 50% complete. Focus on completing required fields first.'
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