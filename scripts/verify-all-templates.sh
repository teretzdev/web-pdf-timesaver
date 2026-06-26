#!/bin/bash
# Verify all templates in the system
# Usage: ./verify-all-templates.sh

PHP_BIN=${PHP_BIN:-php}
DATA_DIR="data"

echo "=== VERIFYING ALL TEMPLATES ==="
echo ""

cd "$(dirname "$0")/.."

# Find all position files
for pos_file in "$DATA_DIR"/*_positions.json; do
    if [ -f "$pos_file" ]; then
        template_id=$(basename "$pos_file" _positions.json)
        if [[ $template_id == t_* ]]; then
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo "Verifying: $template_id"
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            $PHP_BIN mvp/verify-pdf.php "$template_id" 2>&1 | grep -E "Overall Status|Total Tests|Passed|Failed|PASS|FAIL" | tail -5
            echo ""
        fi
    fi
done

echo "=== VERIFICATION COMPLETE ==="

