<?php
/**
 * API endpoint for PDF field extraction dashboard
 * Handles file uploads and calls the universal extractor
 */

declare(strict_types=1);

define('PROJECT_ROOT', dirname(__DIR__));
define('UPLOADS_DIR', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'uploads');
define('AUTO_EXTRACTOR_FILE', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'mvp' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'auto_position_extractor.php');
define('PDF_FIELD_EXTRACTOR_FILE', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'mvp' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pdf_field_extractor.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
        throw new \Exception('No PDF file uploaded or upload error occurred');
    }
    
    // Check if template ID was provided
    if (!isset($_POST['template_id']) || empty(trim($_POST['template_id']))) {
        throw new \Exception('Template ID is required');
    }
    
    $templateId = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$_POST['template_id']));
    if ($templateId === '') {
        throw new \Exception('Template ID contains no valid characters');
    }
    $uploadedFile = $_FILES['pdf'];
    $backgroundModeRaw = strtolower(trim((string)($_POST['background_conversion_mode'] ?? 'raster')));
    $backgroundConversionMode = $backgroundModeRaw === 'selectable_full' ? 'selectable_full' : 'raster';
    
    // Validate file type
    $fileInfo = pathinfo($uploadedFile['name']);
    if (strtolower($fileInfo['extension']) !== 'pdf') {
        throw new \Exception('File must be a PDF');
    }
    
    // Create uploads directory if it doesn't exist
    if (!is_dir(UPLOADS_DIR)) {
        if (!mkdir(UPLOADS_DIR, 0755, true) && !is_dir(UPLOADS_DIR)) {
            throw new \Exception('Failed to create uploads directory');
        }
    }
    
    // Generate unique filename
    $filename = $templateId . '_' . time() . '.pdf';
    $filePath = UPLOADS_DIR . DIRECTORY_SEPARATOR . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
        throw new \Exception('Failed to save uploaded file');
    }
    
    // Call universal extractor
    if (!file_exists(AUTO_EXTRACTOR_FILE)) {
        throw new \Exception('Auto extractor bridge not found');
    }
    require_once AUTO_EXTRACTOR_FILE;
    
    $extractor = new \WebPdfTimeSaver\Mvp\AutoPositionExtractor();
    
    if (!$extractor->isAvailable()) {
        throw new \Exception('Node.js extraction not available');
    }
    
    $result = $extractor->extractPositions($filePath, $templateId);
    $backgroundConversion = [
        'requestedMode' => $backgroundConversionMode,
        'selectedStrategy' => 'raster_background_png',
        'selectedPdfPath' => null,
        'attempts' => []
    ];
    if (file_exists(PDF_FIELD_EXTRACTOR_FILE)) {
        require_once PDF_FIELD_EXTRACTOR_FILE;
        try {
            $pdfExtractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
            $backgroundConversion = $pdfExtractor->prepareBackgroundConversionArtifacts(
                $filePath,
                $templateId,
                UPLOADS_DIR,
                $backgroundConversionMode
            );
        } catch (\Throwable $e) {
            $backgroundConversion['attempts'][] = [
                'strategy' => 'background_conversion_metadata',
                'success' => false,
                'reason' => $e->getMessage(),
                'path' => null,
            ];
        }
    }
    
    // Ensure result has the expected structure for browser
    if (!isset($result['success'])) {
        $result['success'] = !empty($result['fields']);
    }
    
    // If we have fields but success is false, set it to true
    if (!empty($result['fields']) && !$result['success']) {
        $result['success'] = true;
    }
    $result['backgroundConversionMode'] = $backgroundConversionMode;
    $result['backgroundConversion'] = $backgroundConversion;
    
    // Clean up uploaded file AFTER extraction completes
    // Don't delete immediately - Node.js might still be processing
    if (file_exists($filePath)) {
        // Use a small delay to ensure Node.js has finished with the file
        sleep(1);
        @unlink($filePath);
    }
    
    // Return results
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'fields' => [],
        'pageCount' => 0
    ]);
}
