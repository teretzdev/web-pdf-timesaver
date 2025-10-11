<?php
require 'vendor/autoload.php';
use setasign\Fpdi\Fpdi;

$pdf = new Fpdi();
try {
    $pages = $pdf->setSourceFile('uploads/fl100.pdf');
    echo "FL-100 PDF has $pages pages\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check official PDF
try {
    $pdf2 = new Fpdi();
    $pages2 = $pdf2->setSourceFile('uploads/fl100_official.pdf');
    echo "FL-100 Official PDF has $pages2 pages\n";
} catch (Exception $e) {
    echo "Official PDF Error: " . $e->getMessage() . "\n";
}

