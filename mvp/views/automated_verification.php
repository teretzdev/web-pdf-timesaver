<!DOCTYPE html>
<html>
<head>
    <title>Automated PDF Verification</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .status { font-size: 24px; font-weight: bold; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .status.pass { background: #d4edda; color: #155724; }
        .status.fail { background: #f8d7da; color: #721c24; }
        .test { border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .test.passed { background: #d4edda; }
        .test.failed { background: #f8d7da; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .loading { display: none; }
        .loading.show { display: block; }
    </style>
</head>
<body>
    <h1>Automated PDF Verification Pipeline</h1>
    
    <form id="verify-form">
        <label>Template ID: 
            <input type="text" name="template_id" value="t_fl100_gc120" required>
        </label>
        <button type="submit" class="btn">Run Verification</button>
    </form>
    
    <div id="loading" class="loading">
        <p>Running verification pipeline... This may take a minute.</p>
    </div>
    
    <div id="results"></div>
    
    <script>
        document.getElementById('verify-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const templateId = formData.get('template_id');
            
            document.getElementById('loading').classList.add('show');
            document.getElementById('results').innerHTML = '';
            
            try {
                const response = await fetch('?route=automated-verify&template_id=' + encodeURIComponent(templateId));
                const results = await response.json();
                
                displayResults(results);
            } catch (error) {
                document.getElementById('results').innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
            } finally {
                document.getElementById('loading').classList.remove('show');
            }
        });
        
        function displayResults(results) {
            const statusClass = results.overall_status === 'PASS' ? 'pass' : 'fail';
            const statusIcon = results.overall_status === 'PASS' ? '✅' : '❌';
            
            let html = `<div class="status ${statusClass}">${statusIcon} Overall Status: ${results.overall_status}</div>`;
            
            html += '<h2>Summary</h2>';
            html += `<p>Total Tests: ${results.summary.total_tests}</p>`;
            html += `<p>Passed: ${results.summary.passed}</p>`;
            html += `<p>Failed: ${results.summary.failed}</p>`;
            
            html += '<h2>Test Results</h2>';
            for (const [testName, testResult] of Object.entries(results.tests)) {
                const testClass = testResult.passed ? 'passed' : 'failed';
                const testIcon = testResult.passed ? '✅' : '❌';
                html += `<div class="test ${testClass}">`;
                html += `<h3>${testIcon} ${testName.replace(/_/g, ' ').toUpperCase()}</h3>`;
                
                if (testResult.message) {
                    html += `<p><strong>Message:</strong> ${testResult.message}</p>`;
                }
                
                if (testResult.issues && testResult.issues.length > 0) {
                    html += '<div><strong>Issues:</strong><ul>';
                    testResult.issues.forEach(issue => {
                        html += `<li>${issue}</li>`;
                    });
                    html += '</ul></div>';
                }
                
                if (testResult.statistics) {
                    html += '<table><tr><th>Metric</th><th>Value</th></tr>';
                    for (const [key, value] of Object.entries(testResult.statistics)) {
                        html += `<tr><td>${key.replace(/_/g, ' ')}</td><td>${value}</td></tr>`;
                    }
                    html += '</table>';
                }
                
                html += '</div>';
            }
            
            if (results.report && results.report.html_path) {
                html += `<p><a href="${results.report.html_path}" target="_blank" class="btn">View Full Report</a></p>`;
            }
            
            document.getElementById('results').innerHTML = html;
        }
    </script>
</body>
</html>

