#!/bin/bash
cd "$(dirname "$0")/../.."
mkdir -p memory_backups
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M")
cp MEMORY.md "memory_backups/MEMORY_${TIMESTAMP}.md"
echo "✅ Backup guardado en memory_backups/MEMORY_${TIMESTAMP}.md"
