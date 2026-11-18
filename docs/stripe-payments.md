# Pagos de membresías con Stripe Checkout

Esta integración usa Stripe Checkout (pago único por **un mes de acceso**). **No crea una suscripción recurrente en Stripe**: la renovación se realiza mediante un nuevo pago. El administrador de NextStep no aprueba ni modifica pagos.

## Configuración local

1. Crea una cuenta de Stripe y utiliza **claves de prueba**.
2. Agrega a tu archivo local `.env` (no lo subas a GitHub):

```env
STRIPE_SECRET_KEY=sk_test_REEMPLAZAR
STRIPE_WEBHOOK_SECRET=whsec_REEMPLAZAR
```

3. Ejecuta `php artisan optimize:clear`.
4. Configura Stripe para enviar el evento `checkout.session.completed` a `https://TU_DOMINIO/api/stripe/webhook`. En local necesitas un túnel HTTPS o Stripe CLI, que puede reenviar eventos a `http://127.0.0.1:8000/api/stripe/webhook`. Usa el secreto de firma correspondiente al destino.
5. Inicia sesión como empresa y abre `/company/membership/change`. Selecciona un plan y continúa a Stripe Checkout.

## Verificaciones

- Cancelar el checkout no registra compras.
- Volver a NextStep no activa el plan: únicamente un webhook firmado con `payment_status=paid` crea una suscripción activa.
- Los eventos duplicados se omiten al encontrar la referencia del checkout.
- Un evento con firma incorrecta se rechaza.
- El importe y la moneda deben coincidir con el plan.
- Verifica que la empresa vea el plan activo en `/company/membership` después del webhook.

**Antes de producción:** crear índice único para `subscriptions.reference` y revisar concurrencia, reembolsos, renovación y cambios de precio. La protección de duplicados a nivel de aplicación no sustituye un índice único. No habilites cobros reales hasta completar esas verificaciones.
