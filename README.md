# PDF Form Filler - Time Saver

A web-based application that allows you to upload PDF files, fill in form fields, and download the completed document.

## App

- Entry point: `index.php`
- Data: JSON store at `data/mvp.json`
- Templates: `templates/registry.php`
- Generate & Download: from Project → Generate → Download

## Tests

Run with XAMPP PHP:

```
C:\xampp\php\php.exe tests\run_all.php
```

To remove leftover sync-conflict files (Windows PowerShell):

```powershell
.\scripts\cleanup-sync-conflicts.ps1
```

Includes:
- Datastore CRUD
- Template registry
- PDF generation
- PDF export content verification

## Features

- 📄 **PDF Upload**: Upload PDF files up to 10MB
- 👁️ **PDF Preview**: View the uploaded PDF in the browser
- 📝 **Form Filling**: Fill in form fields with a user-friendly interface
- 💾 **Download**: Download the completed PDF with filled form data
- 🎨 **Modern UI**: Beautiful, responsive design with drag-and-drop upload
- ⚡ **Fast Processing**: Quick upload and processing

## Requirements

- PHP 7.4 or higher
- Web server (Apache, Nginx, or PHP built-in server)
- Write permissions for `uploads/` and `output/` directories

## Installation

1. Clone or download this repository
2. Ensure PHP is installed on your system
3. Make sure the `uploads/` and `output/` directories are writable
4. Start a web server pointing to this directory

### Using PHP Built-in Server (Development)

```bash
php -S localhost:8000
```

Then open your browser and go to `http://localhost:8000`

### Using Apache/Nginx

1. Copy the files to your web server directory
2. Ensure mod_rewrite is enabled (for Apache) or proper Nginx config
3. Set proper permissions for the `uploads/` and `output/` directories
4. Access the application through your web browser

## Usage

1. **Upload PDF**: Click the upload area or drag and drop a PDF file
2. **Preview**: The PDF will be displayed in the preview area
3. **Fill Form**: Use the form fields on the left to enter your data
4. **Download**: Click "Fill PDF & Download" to get your completed document

## File Structure

```
Web-PDFTimeSaver/
├── index.php               # Router (projects, project, populate)
├── lib/                    # App libraries and services
├── templates/              # Template registry
├── views/                  # Views (layout, pages)
├── legacy/                 # Archived older code
├── tests/                  # Test suite (run_all.php)
├── uploads/                # Directory for uploaded PDFs
├── output/                 # Directory for filled PDFs
├── .htaccess               # Apache configuration
└── README.md               # This file
```

## Configuration

The application can be configured by modifying the following files:

- `.htaccess`: Server configuration and security settings
- `simple_pdf_processor.php`: PDF processing settings and form field definitions

## Security Notes

- Uploaded files are validated for type and size
- File names are sanitized to prevent directory traversal
- Sensitive configuration files are protected from direct access
- Session-based file management prevents unauthorized access

## Troubleshooting

### Common Issues

1. **Upload fails**: Check file size (max 10MB) and file type (PDF only)
2. **Permission errors**: Ensure `uploads/` and `output/` directories are writable
3. **PDF not displaying**: Check if the PDF file is valid and not corrupted
4. **Download not working**: Verify that the filled PDF was created successfully

### Error Messages

- "Only PDF files are allowed": Upload a valid PDF file
- "File size must be less than 10MB": Reduce the file size or compress the PDF
- "Failed to upload file": Check directory permissions
- "No PDF file in session": Upload a PDF file first before trying to fill it

## Development

This application uses a simplified PDF processing approach for demonstration purposes. For production use with actual PDF form filling, consider integrating with libraries like:

- FPDI (setasign/fpdi)
- TCPDF
- mPDF
- DomPDF

## License

This project is open source and available under the MIT License.

## Support

For issues or questions, please check the troubleshooting section above or create an issue in the project repository.

