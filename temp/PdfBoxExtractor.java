import org.apache.pdfbox.pdmodel.PDDocument;
import org.apache.pdfbox.pdmodel.interactive.form.PDAcroForm;
import org.apache.pdfbox.pdmodel.interactive.form.PDField;
import org.apache.pdfbox.pdmodel.interactive.annotation.PDAnnotationWidget;
import org.apache.pdfbox.pdmodel.PDPage;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.File;
import java.util.List;

public class PdfBoxExtractor {
    private static final double MM_PER_POINT = 0.352778;
    
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
                fieldObj.put("fontSize", Math.max(7, Math.round(height * 0.7 * 10.0) / 10.0));
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
}