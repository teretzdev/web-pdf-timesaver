<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

use WebPdfTimeSaver\Mvp\Logger;

interface FieldFillerInterface {
    /**
     * Fill fields for this section
     */
    public function fillFields($pdf, array $data, Logger $logger): void;
    
    /**
     * Get the section name
     */
    public function getSectionName(): string;
    
    /**
     * Get the fields handled by this filler
     */
    public function getHandledFields(): array;
}
