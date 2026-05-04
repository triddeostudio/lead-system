# Lead System PHP + PostgreSQL

Sistema ligero para centralizar formularios de contacto de varias webs y gestionar leads sin contratar un CRM.

## Arquitectura

```text
Formulario web → /api/lead.php → PostgreSQL → panel privado → email/n8n
```

El email es solo una notificación. La base de datos es la fuente de verdad.

## Requisitos

- PHP 8.1 o superior.
- Extensión `pdo_pgsql`.
- Extensión `curl` si usas Turnstile, Postmark, Resend o n8n.
- PostgreSQL 13 o superior. Probado para PostgreSQL 17.
- Servidor web con DocumentRoot apuntando a `public/`.

## Instalación rápida

### 1. Subir el proyecto

Sube el contenido del ZIP al servidor.

Configura el DocumentRoot del dominio/subdominio a:

```text
lead-system/public
```

Ejemplo recomendado:

```text
https://leads.tudominio.com
```

### 2. Configurar variables

Copia:

```text
.env.example → .env
```

Edita:

```env
DB_HOST=tu-host-postgresql
DB_PORT=5432
DB_NAME=leads_db
DB_USER=leads_app
DB_PASSWORD=tu-contrasena

ADMIN_USER=admin
ADMIN_PASSWORD_HASH=
ADMIN_PASSWORD=cambia-esto
```

Para producción, mejor usa hash:

```bash
php -r "echo password_hash('tu-contrasena-segura', PASSWORD_DEFAULT) . PHP_EOL;"
```

Pega el resultado en:

```env
ADMIN_PASSWORD_HASH=hash_generado
```

Y deja vacío:

```env
ADMIN_PASSWORD=
```

### 3. Crear tablas

Si todavía no has creado las tablas, ejecuta en pgAdmin:

```text
migrations/001_create_leads_tables.sql
```

Si ya ejecutaste las tablas durante la preparación, no hace falta repetirlo.

### 4. Permisos de escritura

El servidor web debe poder escribir en:

```text
storage/logs
storage/ratelimit
```

### 5. Acceder al panel

```text
https://leads.tudominio.com/admin/
```

### 6. Endpoint de recepción

```text
POST https://leads.tudominio.com/api/lead.php
```

Acepta JSON o `form-data`.

Payload mínimo:

```json
{
  "name": "David",
  "email": "lead@example.com",
  "phone": "600000000",
  "message": "Quiero información",
  "source_site": "web-principal",
  "source_url": "https://web.com/contacto",
  "form_name": "contacto",
  "consent": true
}
```

Debe venir al menos `email` o `phone`.

## Integración con formularios

Revisa:

```text
examples/html-form.html
examples/php-submit-example.php
examples/wordpress-snippet.php
```

## Seguridad incluida

- Validación server-side.
- Honeypot.
- Rate limit por IP.
- CSRF en el panel.
- PDO con prepared statements.
- Logs sin claves sensibles.
- Variables sensibles en `.env`.
- Cloudflare Turnstile opcional.

## Cloudflare Turnstile

Si quieres activarlo:

```env
TURNSTILE_SECRET_KEY=tu_secret_key
```

Y en el formulario frontend debes enviar uno de estos campos:

```text
cf-turnstile-response
turnstile_token
```

Si `TURNSTILE_SECRET_KEY` está vacío, no se valida Turnstile.

## Email transaccional

El envío de email no bloquea el guardado del lead. Si falla el email, el lead queda guardado.

### Postmark

```env
MAIL_PROVIDER=postmark
MAIL_FROM=Leads <leads@tudominio.com>
MAIL_TO=tu-email@tudominio.com
POSTMARK_SERVER_TOKEN=xxxxx
POSTMARK_MESSAGE_STREAM=outbound
```

### Resend

```env
MAIL_PROVIDER=resend
MAIL_FROM=Leads <leads@tudominio.com>
MAIL_TO=tu-email@tudominio.com
RESEND_API_KEY=xxxxx
```

### Sin email

```env
MAIL_PROVIDER=none
```

## n8n

Puedes disparar un workflow al crear un lead:

```env
N8N_NEW_LEAD_WEBHOOK_URL=https://n8n.tudominio.com/webhook/nuevo-lead
```

El payload enviado será:

```json
{
  "event": "lead_created",
  "lead_id": "uuid",
  "lead": {}
}
```

## Estados disponibles

```text
nuevo
contactado
cualificado
propuesta_enviada
ganado
perdido
spam
descartado
```

## Prioridades disponibles

```text
baja
media
alta
urgente
```

## Recomendación de despliegue

Para Coolify:

- App PHP en un servicio separado.
- PostgreSQL 17 Alpine como base.
- Puerto 5432 cerrado a internet si no lo necesitas.
- Backups diarios.
- Variables en el panel de Environment Variables, no dentro del repo.
- Dominio tipo `leads.tudominio.com` apuntando a `public/`.

## Prueba rápida con curl

```bash
curl -X POST https://leads.tudominio.com/api/lead.php \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Lead de prueba",
    "email":"test@example.com",
    "phone":"600000000",
    "message":"Mensaje de prueba",
    "source_site":"test",
    "source_url":"https://test.local/contacto",
    "form_name":"test",
    "consent":true
  }'
```

## Nota importante

Este proyecto es un MVP funcional. No sustituye a un CRM complejo, pero sí cubre la necesidad principal: no perder leads y poder gestionarlos de forma centralizada.
