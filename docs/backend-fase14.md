# Fase 14 — Límite de vacantes por membresía

Antes de crear una vacante se comprueba la última suscripción pagada, activa y vigente de la empresa.
Si no existe, se redirige a la página de membresía sin guardar la vacante.
Si `membership_vacancy_limit` es un número, se compara con las vacantes actualmente publicadas.
Si el límite es `null`, el plan permite vacantes ilimitadas.

## Pruebas manuales
1. Empresa sin suscripción: no puede publicar.
2. Empresa con suscripción pendiente o vencida: no puede publicar.
3. Empresa con plan pagado y activo: puede publicar hasta el límite.
4. Al alcanzar el límite, una publicación adicional se rechaza.
5. Retirar una vacante debe liberar un espacio.

## Importante
Stripe sigue pendiente: **las empresas sin pagos confirmados no podrán publicar** con esta fase.
No se crean pagos ni suscripciones falsas para superar el control.
Revisar la semántica de `membership_vacancy_limit` para planes ilimitados.
La comprobación de conteo no protege frente a publicaciones simultáneas; requiere control de concurrencia antes de producción.
