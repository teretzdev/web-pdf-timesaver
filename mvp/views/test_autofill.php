<div class="pdftimesaver-card">
    <h2 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">
        Test Auto-Fill with Extracted Positions
    </h2>
    <p style="color: #6c757d; margin-bottom: 24px;">
        This will test the hybrid approach: extract field positions from FL-100.pdf, generate background images,
        and create a filled PDF with test data.
    </p>
</div>

<?php if (isset($error)): ?>
    <div class="pdftimesaver-card" style="background: #f8d7da; color: #721c24; margin-bottom: 16px;">
        ❌ <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="pdftimesaver-card" style="background: #d4edda; color: #155724; margin-bottom: 16px;">
        ✅ <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if (isset($extractionResult)): ?>
    <div class="pdftimesaver-card">
        <h3 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 18px;">Step 1: Field Extraction</h3>
        
        <?php if (!empty($extractionResult['fields'])): ?>
            <div style="padding: 12px; background: #d4edda; border-left: 4px solid #28a745; margin-bottom: 16px;">
                <strong>✅ Extracted <?php echo count($extractionResult['fields']); ?> fields</strong>
            </div>
            
            <details style="margin-bottom: 16px;">
                <summary style="cursor: pointer; font-weight: 600; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                    View Extracted Fields
                </summary>
                <div style="margin-top: 12px; max-height: 300px; overflow-y: auto;">
                    <table class="pdftimesaver-table">
                        <thead>
                            <tr>
                                <th>Field Name</th>
                                <th>Type</th>
                                <th>Page</th>
                                <th>Position</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($extractionResult['fields'] as $name => $data): ?>
                                <tr>
                                    <td style="font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($name); ?></td>
                                    <td><?php echo htmlspecialchars($data['type']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$data['page']); ?></td>
                                    <td style="font-size: 11px;">(<?php echo number_format($data['x'], 1); ?>, <?php echo number_format($data['y'], 1); ?>)</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        <?php else: ?>
            <div style="padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107;">
                ⚠️ No fields extracted (PDF may be encrypted)
            </div>
        <?php endif; ?>
        
        <?php if (!empty($extractionResult['backgrounds'])): ?>
            <div style="padding: 12px; background: #d4edda; border-left: 4px solid #28a745; margin-top: 12px;">
                <strong>✅ Generated <?php echo count($extractionResult['backgrounds']); ?> background images</strong>
                <div style="margin-top: 8px; font-size: 13px;">
                    <?php foreach ($extractionResult['backgrounds'] as $page => $file): ?>
                        <div>Page <?php echo $page; ?>: <?php echo htmlspecialchars(basename($file)); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isset($generatedPdf)): ?>
    <div class="pdftimesaver-card">
        <h3 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 18px;">Step 2: PDF Generation</h3>
        
        <div style="padding: 16px; background: #d4edda; border-left: 4px solid #28a745; margin-bottom: 16px;">
            <strong>✅ PDF Generated Successfully!</strong><br>
            <div style="margin-top: 8px;">
                <strong>File:</strong> <code><?php echo htmlspecialchars($generatedPdf['filename']); ?></code><br>
                <strong>Fields Placed:</strong> <?php echo $generatedPdf['fields_placed'] ?? 'N/A'; ?><br>
                <strong>Pages:</strong> <?php echo $generatedPdf['pages'] ?? 'N/A'; ?>
            </div>
        </div>
        
        <div class="button-group" style="display: flex; gap: 12px;">
            <a href="?route=actions/download-test-pdf&file=<?php echo urlencode($generatedPdf['filename']); ?>" 
               class="pdftimesaver-btn" target="_blank">
                📄 Download Test PDF
            </a>
            <a href="?route=test-autofill" class="pdftimesaver-btn-secondary">
                🔄 Generate Another
            </a>
        </div>
    </div>
<?php endif; ?>

<?php if (!isset($extractionResult) && !isset($generatedPdf)): ?>
    <div class="pdftimesaver-card">
        <h3 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 18px;">Ready to Test</h3>
        
        <div style="padding: 16px; background: #e7f3ff; border-left: 4px solid #007bff; margin-bottom: 20px;">
            <strong>This will:</strong>
            <ol style="margin: 8px 0 0 20px; padding: 0;">
                <li>Extract field positions from <code>uploads/fl100.pdf</code></li>
                <li>Generate background images for each page</li>
                <li>Fill the form with test data</li>
                <li>Create a final PDF with overlaid text</li>
            </ol>
        </div>
        
        <form method="post" action="?route=actions/test-autofill">
            <div class="button-group" style="display: flex; gap: 12px;">
                <button type="submit" class="pdftimesaver-btn">
                    🚀 Run Auto-Fill Test
                </button>
                <a href="?route=dashboard" class="pdftimesaver-btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="pdftimesaver-card">
    <h3 style="margin: 0 0 12px 0; color: #2c3e50; font-size: 16px;">How This Works</h3>
    <div style="color: #6c757d; line-height: 1.8;">
        <strong>Hybrid Approach:</strong>
        <ol style="margin: 8px 0 0 20px; padding: 0;">
            <li><strong>Extract Positions:</strong> Read field metadata from PDF's AcroForm (even if password-protected)</li>
            <li><strong>Generate Backgrounds:</strong> Convert PDF pages to high-quality PNG images using Ghostscript</li>
            <li><strong>Create Position File:</strong> Save field positions as JSON for future use</li>
            <li><strong>Overlay Text:</strong> Use FPDI to place text at detected positions over background images</li>
        </ol>
        
        <div style="margin-top: 16px; padding: 12px; background: #f8f9fa; border-radius: 4px;">
            <strong>Advantages:</strong>
            <ul style="margin: 8px 0 0 20px; padding: 0;">
                <li>✅ Works with password-protected PDFs</li>
                <li>✅ No manual coordinate mapping</li>
                <li>✅ Perfect visual fidelity</li>
                <li>✅ Auto-detects all fields</li>
            </ul>
        </div>
    </div>
</div>

