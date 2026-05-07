# Parche: formularios con campos personalizables

Este parche permite que diferentes formularios de distintas webs envíen campos distintos sin modificar la base de datos cada vez.

## Qué cambia

- Los campos estándar siguen guardándose en columnas SQL: nombre, email, teléfono, empresa, web, mensaje, origen, UTMs, estado, prioridad, etc.
- Cualquier campo adicional del formulario se guarda en `raw_payload` y se muestra automáticamente en la ficha del lead.
- El email de aviso incluye los campos adicionales.
- El webhook de n8n recibe `extra_fields` además del lead completo.
- El buscador del panel también busca dentro de `raw_payload`.
- El CSV exporta `campos_adicionales_json`.

## Archivos incluidos

Sustituir/añadir estos archivos:

```text
app/LeadFields.php
app/bootstrap.php
app/LeadValidator.php
app/LeadRepository.php
app/MailService.php
app/Time.php
public/api/lead.php
public/admin/index.php
public/admin/lead.php
public/admin/export.php
public/admin/update-lead.php
```

## Base de datos

No hace falta migración nueva.

Se reutiliza la columna existente:

```sql
raw_payload JSONB
```

## Campos estándar

No se mostrarán como “campos adicionales” porque ya tienen sitio propio:

```text
name, nombre, email, phone, telefono, company, empresa, website, web,
message, mensaje, source_site, source_url, source_path, page_title,
form_name, referrer, utm_source, utm_medium, utm_campaign, utm_term,
utm_content, consent, privacidad, priority, status
```

## Campos internos excluidos

No se guardan ni se muestran:

```text
_hp_website, hp_field, cf-turnstile-response, turnstile_token, csrf_token
```

## Ejemplo de formulario con campos extra

```html
<input name="process" value="Atención al cliente">
<input name="budget" value="500-1000 €">
<input name="city" value="Zaragoza">
```

Aparecerán en la ficha del lead dentro de “Campos adicionales”.
