<?php
// Test PdfParser specifically with W-9
require_once __DIR__ . '/vendor/autoload.php';

$pdfFile = __DIR__ . '/uploads/w9.pdf';
echo "Testing PdfParser with W-9...\n";
echo "File: $pdfFile\n";
echo "File exists: " . (file_exists($pdfFile) ? 'YES' : 'NO') . "\n";
echo "File size: " . number_format(filesize($pdfFile)) . " bytes\n\n";

try {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($pdfFile);
    
    echo "PDF parsed successfully\n";
    echo "Pages: " . count($pdf->getPages()) . "\n\n";
    
    // Get annotations from all pages
    $allFields = [];
    foreach ($pdf->getPages() as $pageIndex => $page) {
        echo "Page " . ($pageIndex + 1) . ":\n";
        
        $objects = $page->get('Annots');
        if ($objects) {
            echo "  Found Annots object\n";
            $objectsContent = $objects->getContent();
            echo "  Annots content type: " . gettype($objectsContent) . "\n";
            
            if (is_array($objectsContent)) {
                echo "  Annots array length: " . count($objectsContent) . "\n";
                
                foreach ($objectsContent as $annotIndex => $annot) {
                    echo "    Annotation $annotIndex:\n";
                    echo "      Type: " . gettype($annot) . "\n";
                    
                    if (is_object($annot)) {
                        echo "      Class: " . get_class($annot) . "\n";
                        
                        // Try to get annotation properties
                        try {
                            $subtype = $annot->get('Subtype');
                            if ($subtype) {
                                echo "      Subtype: " . $subtype->getContent() . "\n";
                            }
                            
                            $rect = $annot->get('Rect');
                            if ($rect) {
                                $rectContent = $rect->getContent();
                                echo "      Rect: " . print_r($rectContent, true) . "\n";
                            }
                            
                            $t = $annot->get('T');
                            if ($t) {
                                echo "      Name: " . $t->getContent() . "\n";
                            }
                            
                        } catch (Exception $e) {
                            echo "      Error accessing properties: " . $e->getMessage() . "\n";
                        }
                    }
                }
            }
        } else {
            echo "  No Annots found\n";
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
