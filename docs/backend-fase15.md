# Fase 15 — Validación de actualizaciones API

Las rutas `/api/company_reviews/{id}` y `/api/applications/{id}` están restringidas a administradores mediante `routes/api.php`.

Antes se aplicaba `$request->all()` directamente a los modelos; esto permitía cambiar campos sensibles como identificadores de empresa, estudiante y vacante.

Ahora:
- En valoraciones solo se pueden modificar calificación, título y descripción, con validación.
- En postulaciones solo se puede modificar el estado a `pending`, `accepted` o `rejected`.
- No se pueden reasignar los registros a otros usuarios a través de estos endpoints.

## Pruebas
1. Como administrador, intentar cambiar `company_user_id` de una valoración: debe permanecer igual.
2. Intentar cambiar `student_user_id` o `vacancy_id` de una postulación: deben permanecer iguales.
3. Cambiar un estado válido: debe guardarse.
4. Enviar un estado inválido: debe devolver 422.
5. Como estudiante o empresa, los endpoints administrativos deben rechazar acceso.

## Pendiente
Auditar el resto de endpoints, datos privados devueltos por la API y reglas de eliminación. Requiere pruebas locales.
