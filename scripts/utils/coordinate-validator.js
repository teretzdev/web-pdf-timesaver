const fieldMetrics = require('./field-metrics');
/**
 * Coordinate Validation Utility
 * Normalizes and validates PDF field positions
 */

class CoordinateValidator {
    constructor() {
        this.mmPerPoint = fieldMetrics.MM_PER_PT; // Convert points to mm
        // Use US Letter as default (most common in US legal forms like FL-100)
        // A4: 595x842 points, US Letter: 612x792 points
        this.defaultPageWidth = 612; // US Letter width in points (8.5 inches)
        this.defaultPageHeight = 792; // US Letter height in points (11 inches)
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
     * Full normalization pipeline: coordinate system, units, snapping
     * CRITICAL: Fields from pdf-lib-extractor are already in mm with top-left origin
     * DO NOT modify coordinates - they are already correct!
     */
    normalizeFields(fields, pageSize = { widthPoints: this.defaultPageWidth, heightPoints: this.defaultPageHeight }) {
        if (!Array.isArray(fields) || fields.length === 0) return [];

        // CRITICAL FIX: Fields from pdf-lib-extractor are already correctly converted
        // DO NOT modify coordinates - just ensure font size is set
        return fields.map(f => ({ 
            ...f, 
            fontSize: f.fontSize || this.estimateFontSize(f) 
        }));
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
     * CRITICAL: Fields from pdf-lib-extractor are already in mm with top-left origin
     * Only convert if coordinates are clearly in PDF points (bottom-left origin)
     */
    detectAndConvertCoordinateSystem(fields, pageHeight) {
        // Check if coordinates look like PDF coordinate system (bottom-left origin)
        // PDF points are typically > 200 (for US Letter height of 792 points, y values near top are 600-700)
        // mm values are typically < 300 (for US Letter height of 279.4mm, y values are 0-279)
        const pageHeightMm = pageHeight * this.mmPerPoint;
        const hasHighYValues = fields.some(f => {
            // If y is in points (large values), it needs conversion
            // If y is already in mm (small values), it's already converted
            return f.y > pageHeightMm * 0.8; // If y > 80% of page height in mm, likely needs conversion
        });
        
        if (hasHighYValues) {
            // Convert from PDF coordinate system (bottom-left origin, in points) to top-left (in mm)
            return fields.map(field => {
                // If field.y is in points, convert to mm first, then flip
                const yInPoints = field.y > 100 ? field.y : field.y / this.mmPerPoint;
                const heightInPoints = field.height > 100 ? field.height : field.height / this.mmPerPoint;
                const yInMm = (pageHeight - yInPoints - heightInPoints) * this.mmPerPoint;
                return {
                    ...field,
                    y: yInMm
                };
            });
        }
        
        // Fields are already in correct coordinate system (top-left, mm)
        return fields;
    }

    /**
     * Snap fields to nearby horizontal baselines and vertical columns to improve alignment
     */
    snapToGrid(fields) {
        const Y_TOL_MM = 1.5; // snap within 1.5mm vertically
        const X_TOL_MM = 1.5; // snap within 1.5mm horizontally

        // Group by page for snapping
        const byPage = new Map();
        for (const f of fields) {
            const key = f.page || 1;
            if (!byPage.has(key)) byPage.set(key, []);
            byPage.get(key).push(f);
        }

        const snapped = [];

        for (const [page, list] of byPage.entries()) {
            // Build candidate baselines from y positions
            const baselines = this.clusterValues(list.map(f => f.y), Y_TOL_MM);
            const columns = this.clusterValues(list.map(f => f.x), X_TOL_MM);

            for (const f of list) {
                const ySnap = this.closestClusterCenter(baselines, f.y, Y_TOL_MM);
                const xSnap = this.closestClusterCenter(columns, f.x, X_TOL_MM);

                snapped.push({
                    ...f,
                    y: typeof ySnap === 'number' ? Number(ySnap.toFixed(2)) : f.y,
                    x: typeof xSnap === 'number' ? Number(xSnap.toFixed(2)) : f.x
                });
            }
        }

        return snapped;
    }

    /**
     * Cluster scalar values within tolerance and return cluster centers
     */
    clusterValues(values, tol) {
        const sorted = Array.from(new Set(values.map(v => Number(v.toFixed(2))))).sort((a, b) => a - b);
        const clusters = [];
        for (const v of sorted) {
            const last = clusters[clusters.length - 1];
            if (!last || Math.abs(v - last.center) > tol) {
                clusters.push({ center: v, count: 1 });
            } else {
                // incremental average
                last.center = (last.center * last.count + v) / (last.count + 1);
                last.count += 1;
            }
        }
        return clusters.map(c => c.center);
    }

    closestClusterCenter(centers, value, tol) {
        let best = null;
        let bestDist = Infinity;
        for (const c of centers) {
            const d = Math.abs(c - value);
            if (d < bestDist) {
                bestDist = d;
                best = c;
            }
        }
        return bestDist <= tol ? best : null;
    }

    /**
     * Estimate font size based on field height
     */
    estimateFontSize(field) {
        if (!field.height) return 10;
        
        // Convert height to font size (rough estimate)
        const fontSize = fieldMetrics.estimateFontPtFromHeightMm(field.height, fieldMetrics.DEFAULT_FONT_PT);
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
