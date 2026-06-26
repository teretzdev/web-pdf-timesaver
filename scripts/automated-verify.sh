#!/bin/bash
# Automated PDF Verification Script
# Usage: ./automated-verify.sh t_fl100_gc120

TEMPLATE_ID=${1:-t_fl100_gc120}
PHP_BIN=${PHP_BIN:-php}

echo "=== AUTOMATED PDF VERIFICATION ==="
echo "Template: $TEMPLATE_ID"
echo ""

cd "$(dirname "$0")/.."
$PHP_BIN mvp/verify-pdf.php "$TEMPLATE_ID"

EXIT_CODE=$?
if [ $EXIT_CODE -eq 0 ]; then
    echo ""
    echo "✅ VERIFICATION PASSED"
else
    echo ""
    echo "❌ VERIFICATION FAILED"
fi

exit $EXIT_CODE

