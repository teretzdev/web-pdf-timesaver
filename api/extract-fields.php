<?php
/**
 * API endpoint for PDF field extraction dashboard
 * Handles file uploads and calls the universal extractor
 */

declare(strict_types=1);

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
    
    $templateId = trim($_POST['template_id']);
    $uploadedFile = $_FILES['pdf'];
    
    // Validate file type
    $fileInfo = pathinfo($uploadedFile['name']);
    if (strtolower($fileInfo['extension']) !== 'pdf') {
        throw new \Exception('File must be a PDF');
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/uploads';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // Generate unique filename
    $filename = $templateId . '_' . time() . '.pdf';
    $filePath = $uploadsDir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
        throw new \Exception('Failed to save uploaded file');
    }
    
    // Call universal extractor
    require_once __DIR__ . '/mvp/lib/auto_position_extractor.php';
    
    $extractor = new \WebPdfTimeSaver\Mvp\AutoPositionExtractor();
    
    if (!$extractor->isAvailable()) {
        throw new \Exception('Node.js extraction not available');
    }
    
    $result = $extractor->extractPositions($filePath, $templateId);
    
    // Clean up uploaded file
    if (file_exists($filePath)) {
        unlink($filePath);
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
