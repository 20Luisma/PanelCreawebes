#!/bin/bash
cd "$(dirname "$0")/../.."
mkdir -p .agents/reports
OUTPUT_FILE=".agents/reports/raw_metrics.json"
echo "{" > "$OUTPUT_FILE"
TOTAL_FILES=$(find . -type f \( -name "*.js" -o -name "*.jsx" -o -name "*.ts" -o -name "*.tsx" -o -name "*.html" -o -name "*.css" -o -name "*.py" -o -name "*.php" \) -not -path "*/node_modules/*" -not -path "*/.git/*" | wc -l | tr -d ' ')
echo "  \"total_code_files\": $TOTAL_FILES," >> "$OUTPUT_FILE"
TOTAL_LOC=$(find . -type f \( -name "*.js" -o -name "*.jsx" -o -name "*.ts" -o -name "*.tsx" -o -name "*.html" -o -name "*.css" \) -not -path "*/node_modules/*" -not -path "*/.git/*" | xargs wc -l 2>/dev/null | tail -n 1 | awk '{print $1}')
if [ -z "$TOTAL_LOC" ]; then TOTAL_LOC=0; fi
echo "  \"total_lines_of_code\": $TOTAL_LOC," >> "$OUTPUT_FILE"
echo "  \"dependencies_count\": 0" >> "$OUTPUT_FILE"
echo "}" >> "$OUTPUT_FILE"
echo "✅ Métricas generadas en $OUTPUT_FILE"
