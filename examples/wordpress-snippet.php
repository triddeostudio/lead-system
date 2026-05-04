<?php
/**
 * Snippet sencillo para WordPress.
 * Pégalo en un plugin propio o en Code Snippets, no en el core.
 * Shortcode: [triddeo_lead_form]
 */

add_shortcode('triddeo_lead_form', function () {
    ob_start();
    ?>
    <form class="triddeo-lead-form">
        <input type="hidden" name="source_site" value="<?php echo esc_attr(home_url()); ?>">
        <input type="hidden" name="form_name" value="contacto_wordpress">
        <input type="hidden" name="source_url" value="<?php echo esc_attr(get_permalink()); ?>">

        <p style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">
            <label>Website <input name="website" tabindex="-1" autocomplete="off"></label>
        </p>

        <p><label>Nombre<br><input name="name" autocomplete="name"></label></p>
        <p><label>Email<br><input name="email" type="email" autocomplete="email"></label></p>
        <p><label>Teléfono<br><input name="phone" autocomplete="tel"></label></p>
        <p><label>Mensaje<br><textarea name="message"></textarea></label></p>
        <p><label><input type="checkbox" name="consent" value="1" required> Acepto la política de privacidad</label></p>
        <button type="submit">Enviar</button>
        <p class="triddeo-lead-result" aria-live="polite"></p>
    </form>
    <script>
    document.querySelectorAll('.triddeo-lead-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const result = form.querySelector('.triddeo-lead-result');
            result.textContent = 'Enviando...';

            const payload = Object.fromEntries(new FormData(form).entries());
            payload.referrer = document.referrer || '';
            payload.source_url = window.location.href;

            try {
                const response = await fetch('https://leads.tudominio.com/api/lead.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const data = await response.json();
                result.textContent = data.success ? 'Gracias. Hemos recibido tu mensaje.' : (data.message || 'No se ha podido enviar.');
                if (data.success) form.reset();
            } catch (error) {
                result.textContent = 'No se ha podido enviar. Inténtalo más tarde.';
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
});
