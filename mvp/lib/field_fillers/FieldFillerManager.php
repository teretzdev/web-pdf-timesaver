<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

require_once __DIR__ . '/FieldFillerInterface.php';
require_once __DIR__ . '/AttorneyFieldFiller.php';
require_once __DIR__ . '/CourtFieldFiller.php';
require_once __DIR__ . '/PartyFieldFiller.php';
require_once __DIR__ . '/MarriageFieldFiller.php';
require_once __DIR__ . '/ReliefFieldFiller.php';
require_once __DIR__ . '/ChildrenFieldFiller.php';
require_once __DIR__ . '/SignatureFieldFiller.php';

final class FieldFillerManager {
    private array $fillers = [];
    
    public function __construct() {
        $this->initializeFillers();
    }
    
    private function initializeFillers(): void {
        $this->fillers = [
            new AttorneyFieldFiller(),
            new CourtFieldFiller(),
            new PartyFieldFiller(),
            new MarriageFieldFiller(),
            new ReliefFieldFiller(),
            new ChildrenFieldFiller(),
            new SignatureFieldFiller()
        ];
    }
    
    /**
     * Fill all fields using the modular system
     */
    public function fillAllFields($pdf, array $data, \WebPdfTimeSaver\Mvp\Logger $logger): void {
        $logger->debug('Starting modular field filling');
        
        foreach ($this->fillers as $filler) {
            $logger->debug('Processing ' . $filler->getSectionName() . ' section');
            $filler->fillFields($pdf, $data, $logger);
        }
        
        $logger->debug('Completed modular field filling');
    }
    
    /**
     * Get all field fillers
     */
    public function getFillers(): array {
        return $this->fillers;
    }
    
    /**
     * Get a specific filler by section name
     */
    public function getFillerBySection(string $sectionName): ?FieldFillerInterface {
        foreach ($this->fillers as $filler) {
            if ($filler->getSectionName() === $sectionName) {
                return $filler;
            }
        }
        return null;
    }
    
    /**
     * Get all handled fields across all fillers
     */
    public function getAllHandledFields(): array {
        $allFields = [];
        foreach ($this->fillers as $filler) {
            $allFields = array_merge($allFields, $filler->getHandledFields());
        }
        return array_unique($allFields);
    }
    
    /**
     * Get field statistics
     */
    public function getFieldStatistics(): array {
        $stats = [];
        foreach ($this->fillers as $filler) {
            $stats[$filler->getSectionName()] = [
                'fields' => $filler->getHandledFields(),
                'count' => count($filler->getHandledFields())
            ];
        }
        return $stats;
    }
}
