# Fase 12 — Backend de valoraciones

- La ruta de publicación requiere una sesión de estudiante.
- La empresa se busca por su identificador y rol.
- El estudiante se obtiene de la sesión; el formulario no puede elegir otro autor.
- Calificación de 1 a 5, título de 3 a 100 caracteres y descripción de 10 a 1500.
- Mensajes de validación en español y atributos HTML minlength.
- La interfaz muestra valoraciones de MongoDB, no ejemplos inventados.
- La aplicación comprueba si ya existe una valoración del estudiante para la empresa.

## Pruebas manuales
1. Intentar enviar un comentario de 8 caracteres: debe mostrarse un error en español.
2. Enviar una valoración válida: debe verse en el perfil público y en /company/ratings.
3. Volver a publicar desde el mismo estudiante: no debe duplicarse.
4. Probar el POST con una cuenta de empresa: debe rechazarse.
5. Confirmar que un estudiante distinto sí puede valorar.

## Pendiente antes de producción
Crear un índice único compuesto de `company_user_id` y `student_user_id` en `company_reviews` después de revisar posibles duplicados existentes. La comprobación de la aplicación por sí sola no impide dos solicitudes simultáneas.
