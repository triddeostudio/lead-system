# Patch campo website real

Este parche cambia el uso del campo `website`:

- `website` pasa a ser la web real del lead.
- `_hp_website` pasa a ser el honeypot antispam.

## Pasos

1. Ejecuta `migrations/002_add_client_website.sql` en la base `leads_db`.
2. Sustituye estos archivos en el repo:
   - app/LeadValidator.php
   - app/LeadRepository.php
   - public/api/lead.php
   - public/admin/index.php
   - public/admin/lead.php
   - public/admin/export.php
3. Haz commit, push y redeploy en Coolify.
4. Prueba el formulario.
5. La respuesta del endpoint debe incluir `lead_id`.
