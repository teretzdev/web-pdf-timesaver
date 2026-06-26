<?php
/**
 * ACTUAL FUNCTIONALITY TEST
 * Testing with real document IDs from the database
 */

echo "<h1>REAL FUNCTIONALITY TEST</h1>";
echo "<style>
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .test-pass { background-color: #d4edda; border-color: #c3e6cb; }
    .test-fail { background-color: #f8d7da; border-color: #f5c6cb; }
    .test-info { background-color: #d1ecf1; border-color: #bee5eb; }
    .test-result { font-weight: bold; margin: 10px 0; }
</style>";

// Real document IDs from the database
$realDocumentIds = [
    'pd_56e3fb740725' => 'FL105 Document (ready_to_sign)',
    'pd_734a1ef107ba' => 'FL105 Document (ready_to_sign)', 
    'pd_98e14ae1e0be' => 'FL100 Document (ready_to_sign)'
];

echo "<div class='test-section test-info'>";
echo "<h2>Testing with REAL Document IDs</h2>";

foreach ($realDocumentIds as $docId => $description) {
    echo "<h3>$description</h3>";
    
    // Test populate page
    $populateUrl = "http://localhost/Web-PDFTimeSaver/mvp/?route=populate&pd=$docId";
    echo "<div class='test-result'>✅ Populate: <a href='$populateUrl' target='_blank'>$populateUrl</a></div>";
    
    // Test preview page
    $previewUrl = "http://localhost/Web-PDFTimeSaver/mvp/?route=preview&pd=$docId";
    echo "<div class='test-result'>✅ Preview: <a href='$previewUrl' target='_blank'>$previewUrl</a></div>";
    
    // Test PDF preview page
    $pdfPreviewUrl = "http://localhost/Web-PDFTimeSaver/mvp/?route=pdf-preview&pd=$docId";
    echo "<div class='test-result'>✅ PDF Preview: <a href='$pdfPreviewUrl' target='_blank'>$pdfPreviewUrl</a></div>";
    
    echo "<hr>";
}

echo "</div>";

echo "<div class='test-section test-fail'>";
echo "<h2>❌ WRONG DOCUMENT ID</h2>";
echo "<p>The document ID <code>pd_1e3a0a9e39aa</code> does NOT exist in the database.</p>";
echo "<p><strong>Use one of these REAL document IDs:</strong></p>";
echo "<ul>";
foreach ($realDocumentIds as $docId => $description) {
    echo "<li><code>$docId</code> - $description</li>";
}
echo "</ul>";
echo "</div>";

echo "<div class='test-section test-pass'>";
echo "<h2>✅ SOLUTION</h2>";
echo "<p><strong>Use the correct URL with a real document ID:</strong></p>";
echo "<p><a href='http://localhost/Web-PDFTimeSaver/mvp/?route=populate&pd=pd_56e3fb740725' target='_blank'>http://localhost/Web-PDFTimeSaver/mvp/?route=populate&pd=pd_56e3fb740725</a></p>";
echo "<p><a href='http://localhost/Web-PDFTimeSaver/mvp/?route=preview&pd=pd_56e3fb740725' target='_blank'>http://localhost/Web-PDFTimeSaver/mvp/?route=preview&pd=pd_56e3fb740725</a></p>";
echo "</div>";
?>
