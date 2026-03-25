---
description: Analiza la escalabilidad, arquitectura y código espagueti del proyecto
---
# Analizando Escalabilidad del Proyecto (God Mode)
1. Ejecuta el script de recolección de métricas: `bash .agents/scripts/metricas_escalabilidad.sh`.
// turbo
2. Lee silenciosamente los resultados crudos en `.agents/reports/raw_metrics.json`.
3. Escanea superficialmente con `list_dir` y `view_file` los posibles "God Objects".
4. Si encontrás inconsistencias o bloqueos para escalar, identificalos.
5. Generá un reporte premium en `.agents/reports/Auditoria_Escalabilidad_$(date +"%Y-%m-%d").md`.
6. Notificá a Martín con el link al archivo del reporte.
