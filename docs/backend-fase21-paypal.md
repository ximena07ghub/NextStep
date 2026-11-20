# Fase 21 — PayPal manual (Sandbox)

- Cada compra es un pago único en MXN, sin renovación automática.
- El precio se calcula en Laravel usando el plan activo; no se acepta un importe enviado por el navegador.
- Se crea una orden con PayPal Orders v2 y se guarda una compra pendiente.
- Tras regresar de PayPal, Laravel captura o consulta la orden y verifica estado, empresa, plan, importe y moneda.
- Solo una compra confirmada se marca pagada y activa por un mes. El historial existente se conserva.
- La orden se asocia al usuario autenticado; el mismo token no activa dos compras.

## Configuración

1. Una persona adulta autorizada debe gestionar la cuenta PayPal Developer/Business y sus credenciales.
2. Configurar `PAYPAL_MODE=sandbox`, `PAYPAL_CLIENT_ID` y `PAYPAL_CLIENT_SECRET` en el `.env` local.
3. Ejecutar `php artisan config:clear` y abrir NextStep con una URL accesible para el navegador.
4. Iniciar sesión como empresa, seleccionar un plan y completar un pago ficticio con una cuenta compradora Sandbox.
5. Comprobar la vigencia de un mes, el historial y que regresar dos veces no duplique la compra.

## Limitaciones antes de producción

- La confirmación depende del retorno autenticado a NextStep. Si el comprador no regresa, la orden puede quedar pendiente aunque se haya cobrado. Se necesita reconciliación o webhook verificado antes de usar dinero real.
- Probar rechazos, cancelaciones, fallos de red, pagos repetidos y concurrencia con un entorno de pruebas aislado.
- No se implementan límites de publicación en esta fase (pendiente fase 22).
- No se habilitan pagos reales ni se almacenan claves en GitHub.
