/**
 * Coordinate Validation Utility
 * Normalizes and validates PDF field positions
 */

class CoordinateValidator {
    constructor() {
        this.mmPerPoint = 0.352778; // Convert points to mm
        this.defaultPageWidth = 595; // A4 width in points
        this.defaultPageHeight = 842; // A4 height in points
    }

    /**
     * Validate extracted fields and normalize coordinates
     */
    validateFields(fields, pageCount) {
        const warnings = [];
        const errors = [];
        let validFields = 0;

        for (const field of fields) {
            const validation = this.validateField(field, pageCount);
            
            if (validation.isValid) {
                validFields++;
            } else {
                warnings.push(...validation.warnings);
                errors.push(...validation.errors);
            }
        }

        return {
            isValid: errors.length === 0,
            warnings,
            errors,
            validFields,
            totalFields: fields.length
        };
    }

    /**
     * Validate a single field
     */
    validateField(field, pageCount) {
        const warnings = [];
        const errors = [];

        // Check required properties
        if (!field.name) {
            errors.push('Field missing name');
        }
        if (!field.type) {
            warnings.push(`Field ${field.name} missing type`);
        }

        // Validate coordinates
        if (typeof field.x !== 'number' || typeof field.y !== 'number') {
            errors.push(`Field ${field.name} has invalid coordinates`);
        } else {
            // Check for suspicious coordinates
            if (field.x === 0 && field.y === 0) {
                warnings.push(`Field ${field.name} at origin (0,0) - may be invalid`);
            }
            
            if (field.x < 0 || field.y < 0) {
                errors.push(`Field ${field.name} has negative coordinates`);
            }
        }

        // Validate dimensions
        if (typeof field.width !== 'number' || typeof field.height !== 'number') {
            errors.push(`Field ${field.name} has invalid dimensions`);
        } else {
            if (field.width <= 0 || field.height <= 0) {
                errors.push(`Field ${field.name} has zero or negative dimensions`);
            }
            
            if (field.width > 200 || field.height > 50) {
                warnings.push(`Field ${field.name} has unusually large dimensions`);
            }
        }

        // Validate page number
        if (field.page && (field.page < 1 || field.page > pageCount)) {
            errors.push(`Field ${field.name} on invalid page ${field.page}`);
        }

        return {
            isValid: errors.length === 0,
            warnings,
            errors
        };
    }

    /**
     * Normalize coordinates to standard format
     */
    normalizeCoordinates(field, pageHeight = this.defaultPageHeight) {
        const normalized = { ...field };

        // Convert points to mm if needed
        if (normalized.x && normalized.x > 100) {
            normalized.x = normalized.x * this.mmPerPoint;
        }
        if (normalized.y && normalized.y > 100) {
            normalized.y = normalized.y * this.mmPerPoint;
        }
        if (normalized.width && normalized.width > 100) {
            normalized.width = normalized.width * this.mmPerPoint;
        }
        if (normalized.height && normalized.height > 100) {
            normalized.height = normalized.height * this.mmPerPoint;
        }

        // Ensure coordinates are reasonable
        normalized.x = Math.max(0, Math.min(normalized.x, 300)); // Max 300mm width
        normalized.y = Math.max(0, Math.min(normalized.y, 400)); // Max 400mm height
        normalized.width = Math.max(5, Math.min(normalized.width, 100)); // 5-100mm width
        normalized.height = Math.max(5, Math.min(normalized.height, 20)); // 5-20mm height

        return normalized;
    }

    /**
     * Detect coordinate system and convert if needed
     */
    detectAndConvertCoordinateSystem(fields, pageHeight) {
        // Check if coordinates look like PDF coordinate system (bottom-left origin)
        const hasHighYValues = fields.some(f => f.y > pageHeight * 0.5);
        
        if (hasHighYValues) {
            // Convert from PDF coordinate system (bottom-left) to top-left
            return fields.map(field => ({
                ...field,
                y: pageHeight - field.y - field.height
            }));
        }
        
        return fields;
    }

    /**
     * Estimate font size based on field height
     */
    estimateFontSize(field) {
        if (!field.height) return 10;
        
        // Convert height to font size (rough estimate)
        const fontSize = Math.max(6, Math.min(16, field.height * 0.7));
        return Math.round(fontSize * 10) / 10; // Round to 1 decimal
    }

    /**
     * Check if field positions are reasonable for a form
     */
    validateFormLayout(fields) {
        const warnings = [];
        
        // Check for overlapping fields
        for (let i = 0; i < fields.length; i++) {
            for (let j = i + 1; j < fields.length; j++) {
                if (this.fieldsOverlap(fields[i], fields[j])) {
                    warnings.push(`Fields ${fields[i].name} and ${fields[j].name} may overlap`);
                }
            }
        }

        // Check for fields too close to page edges
        fields.forEach(field => {
            if (field.x < 5) {
                warnings.push(`Field ${field.name} very close to left edge`);
            }
            if (field.y < 5) {
                warnings.push(`Field ${field.name} very close to top edge`);
            }
        });

        return warnings;
    }

    /**
     * Check if two fields overlap
     */
    fieldsOverlap(field1, field2) {
        const x1 = field1.x;
        const y1 = field1.y;
        const w1 = field1.width;
        const h1 = field1.height;
        
        const x2 = field2.x;
        const y2 = field2.y;
        const w2 = field2.width;
        const h2 = field2.height;

        return !(x1 + w1 < x2 || x2 + w2 < x1 || y1 + h1 < y2 || y2 + h2 < y1);
    }

    /**
     * Generate confidence score for field extraction
     */
    calculateConfidence(field, method) {
        let confidence = 0.5; // Base confidence

        // Method-specific confidence adjustments
        switch (method) {
            case 'pdf-lib-direct':
                confidence = 0.95; // High confidence for native extraction
                break;
            case 'qpdf-decrypt-pdf-lib':
                confidence = 0.85; // Good confidence for decrypted extraction
                break;
            case 'pdfjs-text-extraction':
                confidence = 0.70; // Medium confidence for text-based estimation
                break;
            case 'ocr-field-detection':
                confidence = 0.60; // Lower confidence for visual detection
                break;
        }

        // Adjust based on field properties
        if (field.width > 0 && field.height > 0) {
            confidence += 0.1;
        }
        
        if (field.x > 0 && field.y > 0) {
            confidence += 0.1;
        }

        if (field.type && field.type !== 'unknown') {
            confidence += 0.05;
        }

        return Math.min(1.0, Math.max(0.0, confidence));
    }
}

module.exports = CoordinateValidator;
