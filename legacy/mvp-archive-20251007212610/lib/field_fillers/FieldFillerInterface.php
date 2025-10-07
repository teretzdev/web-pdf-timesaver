<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

interface FieldFillerInterface {
    /**
     * Fill fields for this section
     */
    public function fillFields($pdf, array $data, string $logFile): void;
    
    /**
     * Get the section name
     */
    public function getSectionName(): string;
    
    /**
     * Get the fields handled by this filler
     */
    public function getHandledFields(): array;
}
