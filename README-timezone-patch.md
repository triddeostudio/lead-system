# Parche zona horaria Europe/Madrid

Este parche mantiene PostgreSQL guardando fechas en UTC y convierte las fechas al mostrarlas en el panel/admin y en el CSV.

## Archivos incluidos

- `app/Time.php`
- `app/bootstrap.php`
- `public/admin/index.php`
- `public/admin/lead.php`
- `public/admin/export.php`
- `public/admin/update-lead.php`

## Variable opcional

En Coolify puedes añadir:

```env
APP_TIMEZONE=Europe/Madrid
```

Si no la añades, el sistema usa `Europe/Madrid` por defecto.

## Despliegue

Sustituye los archivos, haz commit/push y redeploy.
