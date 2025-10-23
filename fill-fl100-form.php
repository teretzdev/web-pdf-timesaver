<?php
/**
 * FL-100 Form Filler - Hybrid Approach
 * Uses FPDF + manual positions for encrypted PDF
 */

header('Content-Type: application/json');

require_once __DIR__ . '/mvp/lib/pdf_form_filler.php';
require_once __DIR__ . '/mvp/lib/field_position_loader.php';
require_once __DIR__ . '/mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\FieldPositionLoader;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

try {
    // Get form data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid form data');
    }
    
    // Use qpdf-decrypted FL-100 PDF
    $qpdfPath = __DIR__ . '/bin/qpdf/bin/qpdf.bat';
    $decryptedFile = __DIR__ . '/temp/fl100_decrypted_' . time() . '.pdf';
    $originalPdfFile = __DIR__ . '/uploads/fl100.pdf';
    
    // Try to decrypt PDF first using qpdf
    if (file_exists($qpdfPath)) {
        $decryptCmd = escapeshellcmd($qpdfPath) . ' --decrypt ' . escapeshellarg($originalPdfFile) . ' ' . escapeshellarg($decryptedFile);
        $decryptResult = shell_exec($decryptCmd . ' 2>&1');
        
        if (file_exists($decryptedFile) && filesize($decryptedFile) > 0) {
            $originalPdfFile = $decryptedFile; // Use decrypted version
        }
    }
    
    // Map form data to FL-100 fields
    $fl100Data = [
        'attorney_name' => $data['attorney_name'] ?? 'John Michael Smith, Esq.',
        'attorney_firm' => $data['attorney_firm'] ?? 'Smith & Associates Family Law',
        'attorney_bar' => $data['attorney_bar'] ?? '123456',
        'attorney_street' => $data['attorney_street'] ?? '1234 Legal Plaza, Suite 500',
        'attorney_city' => $data['attorney_city'] ?? 'Los Angeles',
        'attorney_state' => $data['attorney_state'] ?? 'CA',
        'attorney_zip' => $data['attorney_zip'] ?? '90210',
        'attorney_phone' => $data['attorney_phone'] ?? '(555) 123-4567',
        'attorney_email' => $data['attorney_email'] ?? 'jsmith@smithlaw.com',
        'petitioner_name' => $data['petitioner_name'] ?? 'Sarah Elizabeth Johnson',
        'respondent_name' => $data['respondent_name'] ?? 'Michael David Johnson',
        'marriage_date' => $data['marriage_date'] ?? '06/15/2010',
        'separation_date' => $data['separation_date'] ?? '03/20/2024',
        'minor_children' => $data['minor_children'] ?? 'yes',
        'children_count' => $data['children_count'] ?? '2',
        'case_number' => $data['case_number'] ?? 'FL-2025-001234'
    ];
    
    // Load template
    $templates = TemplateRegistry::load();
    $templateId = 't_fl100_gc120';
    
    if (!isset($templates[$templateId])) {
        throw new Exception('FL-100 template not found');
    }
    
    $template = $templates[$templateId];
    
    // Initialize form filler
    $filler = new PdfFormFiller();
    
    // Fill the form
    $result = $filler->fillPdfFormWithPositions($template, $fl100Data, 't_fl100_gc120');
    
    if (!$result['success']) {
        throw new Exception('Failed to generate PDF: ' . ($result['error'] ?? 'Unknown error'));
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'FL-100 form generated successfully!',
        'pdf_url' => 'output/' . $result['filename'],
        'preview_url' => 'uploads/fl100_page1_background.png',
        'fields_filled' => $result['fields_placed'] ?? count(array_filter($fl100Data)),
        'method' => 'FPDF + Manual Positions (Hybrid)',
        'file_size' => round(filesize(__DIR__ . '/output/' . $result['filename']) / 1024, 2) . ' KB'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}