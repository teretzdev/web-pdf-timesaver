/**
 * Apache PDFBox Extractor
 * Uses Apache PDFBox Java library for excellent widget annotation extraction
 * Requires Java 8+ and PDFBox JAR file
 */

const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');
const https = require('https');
const http = require('http');
const fieldMetrics = require('../utils/field-metrics');

class PdfBoxExtractor {
    constructor() {
        this.name = 'pdfbox-extractor';
        this.mmPerPoint = fieldMetrics.MM_PER_PT;
        this.javaAvailable = this.checkJavaAvailability();
        this.pdfboxJarPath = this.findPdfBoxJar();
    }

    getName() {
        return this.name;
    }

    getStatus() {
        return {
            name: this.name,
            available: this.javaAvailable && this.pdfboxJarPath !== null,
            description: 'Apache PDFBox widget annotation extraction via Java',
            requirements: ['Java 8+', 'PDFBox JAR file']
        };
    }

    checkJavaAvailability() {
        try {
            const { execSync } = require('child_process');
            execSync('java -version', { stdio: 'ignore' });
            return true;
        } catch {
            return false;
        }
    }

    findPdfBoxJar() {
        // Check common locations for PDFBox JAR
        const candidates = [
            path.join(__dirname, '../../bin/pdfbox/pdfbox-app-3.0.1.jar'),
            path.join(__dirname, '../../bin/pdfbox/pdfbox-app-2.0.29.jar'),
            path.join(__dirname, '../../bin/pdfbox/pdfbox-app.jar'),
            'pdfbox-app.jar' // System PATH
        ];

        for (const candidate of candidates) {
            if (fs.existsSync(candidate)) {
                return candidate;
            }
        }

        return null;
    }

    async extract(pdfPath) {
        if (!this.javaAvailable) {
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: 'Java not available'
            };
        }

        if (!this.pdfboxJarPath) {
            console.log('   ⚠️  PDFBox JAR not found, attempting to download...');
            const downloaded = await this.downloadPdfBox();
            if (!downloaded) {
                return {
                    success: false,
                    fields: [],
                    pageCount: 0,
                    error: 'PDFBox JAR not found and download failed'
                };
            }
        }

        try {
            console.log('   ☕ Starting PDFBox extraction...');
            
            // Create Java extraction script
            const javaScript = this.createExtractionScript();
            const scriptPath = path.join(__dirname, '../../temp/PdfBoxExtractor.java');
            const classPath = path.join(__dirname, '../../temp');
            
            // Ensure temp directory exists
            const tempDir = path.dirname(scriptPath);
            if (!fs.existsSync(tempDir)) {
                fs.mkdirSync(tempDir, { recursive: true });
            }
            
            fs.writeFileSync(scriptPath, javaScript);
            
            // Compile Java class
            const compiled = await this.compileJavaClass(scriptPath, classPath);
            if (!compiled) {
                return {
                    success: false,
                    fields: [],
                    pageCount: 0,
                    error: 'Failed to compile Java extraction class'
                };
            }
            
            // Run extraction
            const result = await this.runExtraction(pdfPath, classPath);
            
            // Cleanup
            try {
                if (fs.existsSync(scriptPath)) fs.unlinkSync(scriptPath);
                const classFile = path.join(classPath, 'PdfBoxExtractor.class');
                if (fs.existsSync(classFile)) fs.unlinkSync(classFile);
            } catch (e) {
                // Ignore cleanup errors
            }
            
            if (result.success) {
                console.log(`   ✅ PDFBox extracted ${result.fields.length} fields`);
            } else {
                console.log(`   ❌ PDFBox extraction failed: ${result.error}`);
            }
            
            return result;

        } catch (error) {
            console.log(`   ❌ PDFBox extraction failed: ${error.message}`);
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: error.message
            };
        }
    }

    createExtractionScript() {
        return `import org.apache.pdfbox.pdmodel.PDDocument;
import org.apache.pdfbox.pdmodel.interactive.form.PDAcroForm;
import org.apache.pdfbox.pdmodel.interactive.form.PDField;
import org.apache.pdfbox.pdmodel.interactive.annotation.PDAnnotationWidget;
import org.apache.pdfbox.pdmodel.PDPage;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.File;
import java.util.List;

public class PdfBoxExtractor {
    private static final double MM_PER_POINT = ${fieldMetrics.MM_PER_PT};
    
    public static void main(String[] args) {
        if (args.length < 1) {
            System.err.println("Usage: PdfBoxExtractor <pdf-path>");
            System.exit(1);
        }
        
        String pdfPath = args[0];
        JSONObject result = new JSONObject();
        JSONArray fields = new JSONArray();
        
        try {
            File file = new File(pdfPath);
            PDDocument document = PDDocument.load(file);
            
            PDAcroForm acroForm = document.getDocumentCatalog().getAcroForm();
            if (acroForm == null) {
                result.put("success", false);
                result.put("error", "No AcroForm found in PDF");
                result.put("fields", new JSONArray());
                result.put("pageCount", document.getNumberOfPages());
                System.out.println(result.toString());
                document.close();
                return;
            }
            
            List<PDField> formFields = acroForm.getFields();
            
            for (PDField field : formFields) {
                processField(field, fields, document);
            }
            
            result.put("success", fields.length() > 0);
            result.put("fields", fields);
            result.put("pageCount", document.getNumberOfPages());
            
            document.close();
            System.out.println(result.toString());
            
        } catch (Exception e) {
            result.put("success", false);
            result.put("error", e.getMessage());
            result.put("fields", new JSONArray());
            result.put("pageCount", 0);
            System.out.println(result.toString());
        }
    }
    
    private static void processField(PDField field, JSONArray fields, PDDocument document) {
        try {
            String fieldName = field.getFullyQualifiedName();
            String fieldType = field.getFieldType();
            
            List<PDAnnotationWidget> widgets = field.getWidgets();
            if (widgets == null || widgets.isEmpty()) {
                return;
            }
            
            for (PDAnnotationWidget widget : widgets) {
                PDPage page = widget.getPage();
                if (page == null) {
                    continue;
                }
                
                int pageNum = document.getPages().indexOf(page) + 1;
                org.apache.pdfbox.geometry.PDRectangle rect = widget.getRectangle();
                
                if (rect == null) {
                    continue;
                }
                
                double x = rect.getLowerLeftX() * MM_PER_POINT;
                double y = rect.getUpperRightY() * MM_PER_POINT; // PDF uses bottom-left origin
                double width = (rect.getUpperRightX() - rect.getLowerLeftX()) * MM_PER_POINT;
                double height = (rect.getUpperRightY() - rect.getLowerLeftY()) * MM_PER_POINT;
                
                // Convert Y coordinate (PDF uses bottom-left, we need top-left)
                double pageHeight = page.getMediaBox().getHeight() * MM_PER_POINT;
                y = pageHeight - y - height;
                
                JSONObject fieldObj = new JSONObject();
                fieldObj.put("name", fieldName);
                fieldObj.put("type", mapFieldType(fieldType));
                fieldObj.put("page", pageNum);
                fieldObj.put("x", Math.round(x * 100.0) / 100.0);
                fieldObj.put("y", Math.round(y * 100.0) / 100.0);
                fieldObj.put("width", Math.round(width * 100.0) / 100.0);
                fieldObj.put("height", Math.round(height * 100.0) / 100.0);
                fieldObj.put("fontSize", Math.max(7, Math.min(16, Math.round(height * 0.7 * 10.0) / 10.0)));
                fieldObj.put("confidence", 0.95);
                fieldObj.put("method", "pdfbox-extractor");
                
                fields.put(fieldObj);
            }
        } catch (Exception e) {
            // Skip fields that cause errors
        }
    }
    
    private static String mapFieldType(String pdfType) {
        if (pdfType == null) return "text";
        switch (pdfType) {
            case "Tx": return "text";
            case "Btn": return "checkbox";
            case "Ch": return "dropdown";
            case "Sig": return "signature";
            default: return "text";
        }
    }
}`;
    }

    async compileJavaClass(scriptPath, classPath) {
        return new Promise((resolve) => {
            const javac = spawn('javac', [
                '-cp', this.pdfboxJarPath + (process.platform === 'win32' ? ';' : ':') + path.join(__dirname, '../../temp/json-20230618.jar'),
                '-d', classPath,
                scriptPath
            ]);
            
            let error = '';
            javac.stderr.on('data', (data) => {
                error += data.toString();
            });
            
            javac.on('close', (code) => {
                if (code === 0) {
                    resolve(true);
                } else {
                    console.log(`   ⚠️  Java compilation failed: ${error}`);
                    // Try simpler approach - use PDFBox command-line tool directly
                    resolve(false);
                }
            });
        });
    }

    async runExtraction(pdfPath, classPath) {
        // Alternative: Use PDFBox's command-line form extraction
        // This is simpler and doesn't require compilation
        return new Promise((resolve) => {
            // Use PDFBox's ExtractText or a Python/Node.js wrapper
            // For now, return a placeholder that indicates PDFBox is available
            // but needs the actual extraction logic implemented
            resolve({
                success: false,
                fields: [],
                pageCount: 0,
                error: 'PDFBox extraction needs PDFBox command-line tools or JSON library'
            });
        });
    }

    async downloadPdfBox() {
        // Download PDFBox JAR from Maven Central
        const pdfboxVersion = '3.0.1';
        const url = `https://repo1.maven.org/maven2/org/apache/pdfbox/pdfbox-app/${pdfboxVersion}/pdfbox-app-${pdfboxVersion}.jar`;
        const targetDir = path.join(__dirname, '../../bin/pdfbox');
        const targetPath = path.join(targetDir, `pdfbox-app-${pdfboxVersion}.jar`);

        if (!fs.existsSync(targetDir)) {
            fs.mkdirSync(targetDir, { recursive: true });
        }

        return new Promise((resolve) => {
            const file = fs.createWriteStream(targetPath);
            const protocol = url.startsWith('https') ? https : http;
            
            protocol.get(url, (response) => {
                if (response.statusCode === 200) {
                    response.pipe(file);
                    file.on('finish', () => {
                        file.close();
                        this.pdfboxJarPath = targetPath;
                        console.log(`   ✅ Downloaded PDFBox to ${targetPath}`);
                        resolve(true);
                    });
                } else {
                    file.close();
                    fs.unlinkSync(targetPath);
                    resolve(false);
                }
            }).on('error', (err) => {
                file.close();
                if (fs.existsSync(targetPath)) {
                    fs.unlinkSync(targetPath);
                }
                resolve(false);
            });
        });
    }
}

module.exports = PdfBoxExtractor;

