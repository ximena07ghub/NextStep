# Mejoras de estabilidad: pagos, membresías y valoraciones

Este cambio parte de `main` posterior a las fases 21–23. No modifica datos ni migra colecciones.

## Archivos modificados

- `routes/web.php`: rutas web de consulta de pagos y mantenimiento del catálogo de membresías para administración.
- `resources/views/layouts/admin.blade.php`: navegación a los módulos nuevos.
- `app/Http/Controllers/CompanyMembershipController.php`: bloquea capturas de órdenes no aprobadas, permite volver a consultar órdenes pendientes y conserva comprobaciones de empresa, plan, importe y moneda.
- `resources/views/company/membership.blade.php`: muestra últimas órdenes pendientes y un enlace de comprobación manual.
- `app/Http/Controllers/StudentCompanyReviewController.php`: mensajes de validación consistentes.
- `resources/views/student/company-profile.blade.php`: texto de valoraciones uniforme.
- `tests/Feature/NextStepRegressionTest.php`: escenarios de regresión.

## Garantías y alcance

- Se conservan los flujos de empresa y estudiante existentes; los cambios de cobro son incrementales.
- Solo estudiantes autenticados pueden valorar empresas desde la web, y cada estudiante puede hacerlo una vez por empresa. No se amplió el grupo de usuarios elegibles.
- La comprobación de órdenes pendientes exige sesión de la empresa propietaria y una orden vinculada a ella.
- Una orden creada o aún no aprobada permanece pendiente; una orden aprobada puede capturarse; solo una captura completada y con datos coincidentes habilita beneficios.
- Los pagos de PayPal siguen siendo pagos únicos y la renovación de membresía no es automática.
- La administración conserva el historial: desactivar un plan no elimina compras anteriores.

## Pruebas

No ejecutar pruebas de MongoDB contra la base habitual ni contra datos compartidos. Usar una instancia MongoDB de pruebas con `TEST_MONGODB_URI` distinta de `DB_URI`.

```bash
APP_ENV=testing TEST_MONGODB_URI='mongodb://127.0.0.1:27018' php artisan test tests/Feature/NextStepRegressionTest.php
```

La suite usa exclusivamente la base `nextstep_regression_test` en ese servidor y la borra antes/después de las pruebas. Confirmar que esa base no contiene datos que deban conservarse. Para una ejecución completa, tener instaladas las dependencias con Composer y un servidor MongoDB de pruebas.

## Pendiente de validar en entorno local

- Ejecución real de la suite, flujo de pago ficticio PayPal Sandbox y concurrencia de publicaciones.
- Recuperación de órdenes si el usuario no vuelve a entrar a NextStep: existe consulta manual, pero falta webhook con verificación o reconciliación programada para producción.
- Los límites numéricos exactos de Basic, Plus y Premium deben corresponder a los documentos de membresía configurados en MongoDB; el código usa `membership_vacancy_limit` de la compra.
