# Fase 13 — Backend de vacantes y postulaciones

## Cambios
- Las postulaciones recibidas se muestran solo cuando la vacante pertenece a la empresa que inició sesión.
- Ver una postulación y descargar su CV exige comprobar tanto el dueño registrado en la postulación como el dueño real de la vacante.
- El detalle público de una vacante requiere que el propietario sea una cuenta con rol empresa.
- El formulario de postulación conserva la validación, el control de duplicados y el estado inicial `pending`.
- Se limita a 5 solicitudes de postulación por minuto mediante middleware.

## Pruebas
1. Estudiante A se postula a una vacante publicada de empresa A.
2. Verificar que la empresa A ve la solicitud y puede consultar el perfil y CV.
3. Verificar que la empresa B no puede abrir la URL de esa solicitud ni descargar el CV (403).
4. Repetir postulación del mismo estudiante: no debe crearse otra.
5. Retirar una vacante: deja de aparecer en el listado de estudiante.
6. Confirmar que la empresa puede cambiar el estado de su solicitud.

## Pendiente
Crear índice único compuesto de `vacancy_id` y `student_user_id` en `applications` después de comprobar duplicados. El control `exists()` por sí solo no impide solicitudes simultáneas. Ejecutar pruebas locales con MongoDB antes de integrar.
