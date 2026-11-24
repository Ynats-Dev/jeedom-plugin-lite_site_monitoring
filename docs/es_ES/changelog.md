---
layout: default
lang: es_ES
---

# Registro de cambios de Lite Site Monitoring

# 24/11/2026 - 1.0.0

- Frecuencia de comprobación configurable por dispositivo (expresión cron, cada 5 minutos por defecto)
- Sitios comprobados en paralelo, una sola petición por comprobación, límites de 10 s (conexión) y 20 s (petición)
- Se siguen las redirecciones; éxito si el código HTTP final es 2xx o 3xx, o forma parte de los códigos esperados configurados
- Palabra clave esperada opcional en la página
- Reintento inmediato en caso de fallo y número configurable de fallos consecutivos antes de alerta
- Sin acceso a Internet, los sitios ya no pasan a fuera de línea: se conserva su último estado
- Opción «Sitio interno» para los sitios de la red local
- Nuevos comandos: Código HTTP, Error, Caducidad SSL, Acceso a Internet
- Tiempos numéricos en milisegundos, historizables; el historial anterior de estos comandos, en segundos, se vacía durante la actualización
- «Latencia» corresponde al tiempo total de la petición (el valor anterior sumaba tiempos acumulados)
- Nuevo widget (desactivable), indicador de estado en la página del plugin y resumen de la última comprobación en el dispositivo
- Validación de la URL al guardar
- Interfaz en francés, inglés, alemán, español e italiano
- Corrección de seguridad: la URL ya no se pasa a un comando del sistema
- Limpieza del código y eliminación de archivos no utilizados

# 17/06/2021 - Versión beta

- Lanzamiento de la beta
- Funciones «OnLine/OffLine» y «Latencia»
