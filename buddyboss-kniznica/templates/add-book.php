<?php
/**
 * Template: Pridanie knihy
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="bbk-add-book-wrapper">
    <div class="bbk-add-book-header">
        <h2>📖 <?php _e('Pridaj knihu', 'buddyboss-kniznica'); ?></h2>
        <p><?php _e('Nahraj knihu zo svojej knižnice, ktorú chceš požičiavať. Nastav, či ju zdieľaš zadarmo alebo za malý poplatok.', 'buddyboss-kniznica'); ?></p>
    </div>

    <form id="bbk-add-book-form" class="bbk-form">
        <!-- Základné informácie -->
        <div class="bbk-form-section">
            <h3><?php _e('Základné informácie', 'buddyboss-kniznica'); ?></h3>

            <div class="bbk-form-group">
                <label for="title"><?php _e('Názov knihy', 'buddyboss-kniznica'); ?> *</label>
                <input type="text" id="title" name="title" class="bbk-input" required>
            </div>

            <div class="bbk-form-group">
                <label for="author"><?php _e('Autor', 'buddyboss-kniznica'); ?> *</label>
                <input type="text" id="author" name="author" class="bbk-input" required>
            </div>

            <div class="bbk-form-row">
                <div class="bbk-form-group">
                    <label for="isbn"><?php _e('ISBN', 'buddyboss-kniznica'); ?></label>
                    <input type="text" id="isbn" name="isbn" class="bbk-input">
                    <small><?php _e('Voliteľné', 'buddyboss-kniznica'); ?></small>
                </div>

                <div class="bbk-form-group">
                    <label for="genre"><?php _e('Žáner', 'buddyboss-kniznica'); ?> *</label>
                    <select id="genre" name="genre" class="bbk-select" required>
                        <option value=""><?php _e('Vyberte žáner', 'buddyboss-kniznica'); ?></option>
                        <option value="Beletria">Beletria</option>
                        <option value="Non-fiction">Non-fiction</option>
                        <option value="Osobný rozvoj">Osobný rozvoj</option>
                        <option value="Spiritualita">Spiritualita</option>
                        <option value="Psychológia">Psychológia</option>
                        <option value="Biznis">Biznis</option>
                        <option value="História">História</option>
                        <option value="Filozofia">Filozofia</option>
                        <option value="Sci-Fi">Sci-Fi</option>
                        <option value="Fantasy">Fantasy</option>
                        <option value="Detektívka">Detektívka</option>
                        <option value="Romantika">Romantika</option>
                        <option value="Iné">Iné</option>
                    </select>
                </div>
            </div>

            <div class="bbk-form-group">
                <label for="description"><?php _e('Popis knihy', 'buddyboss-kniznica'); ?></label>
                <textarea id="description" name="description" class="bbk-textarea" rows="5"></textarea>
                <small><?php _e('Popíšte knihu, jej obsah alebo prečo ju odporúčate', 'buddyboss-kniznica'); ?></small>
            </div>
        </div>

        <!-- Stav a cena -->
        <div class="bbk-form-section">
            <h3><?php _e('Stav a cena', 'buddyboss-kniznica'); ?></h3>

            <div class="bbk-form-group">
                <label for="book_condition"><?php _e('Stav knihy', 'buddyboss-kniznica'); ?> * (1-10)</label>
                <input type="range" id="book_condition" name="book_condition" min="1" max="10" value="7" class="bbk-range">
                <output for="book_condition" id="condition-value">7</output> / 10
                <small><?php _e('1 = veľmi opotrebovaná, 10 = ako nová', 'buddyboss-kniznica'); ?></small>
            </div>

            <div class="bbk-form-group">
                <label for="lending_price"><?php _e('Cena požičania', 'buddyboss-kniznica'); ?> *</label>
                <select id="lending_price_type" class="bbk-select">
                    <option value="free"><?php _e('🎁 Zadarmo (0 €)', 'buddyboss-kniznica'); ?></option>
                    <option value="paid"><?php _e('💰 S poplatkom (2-10 €)', 'buddyboss-kniznica'); ?></option>
                </select>
            </div>

            <div id="bbk-price-input-wrapper" style="display: none;">
                <div class="bbk-form-group">
                    <label for="lending_price"><?php _e('Výška poplatku (€)', 'buddyboss-kniznica'); ?></label>
                    <input type="number" id="lending_price" name="lending_price" class="bbk-input" min="2" max="10" step="0.5" value="2">
                    <small>
                        <?php _e('Cena musí byť medzi 2-10 €. Rozdelenie: 70% vám, 30% komunite.', 'buddyboss-kniznica'); ?>
                        <br>
                        <strong id="bbk-price-breakdown"></strong>
                    </small>
                </div>
            </div>
        </div>

        <!-- Obrázok -->
        <div class="bbk-form-section">
            <h3><?php _e('Obrázok knihy', 'buddyboss-kniznica'); ?></h3>

            <div class="bbk-form-group">
                <label for="image"><?php _e('Nahrať obrázok', 'buddyboss-kniznica'); ?></label>
                <input type="file" id="image" name="image" accept="image/*" class="bbk-input-file">
                <div id="image-preview" class="bbk-image-preview"></div>
                <input type="hidden" id="image_url" name="image_url">
            </div>
        </div>

        <!-- Tlačidlá -->
        <div class="bbk-form-actions">
            <button type="submit" class="bbk-btn bbk-btn-primary bbk-btn-large">
                <?php _e('Pridať knihu', 'buddyboss-kniznica'); ?>
            </button>
            <a href="<?php echo esc_url(wp_get_referer() ?: home_url()); ?>" class="bbk-btn bbk-btn-secondary">
                <?php _e('Zrušiť', 'buddyboss-kniznica'); ?>
            </a>
        </div>

        <div id="bbk-form-message"></div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Range slider pre stav knihy
    $('#book_condition').on('input', function() {
        $('#condition-value').text($(this).val());
    });

    // Prepínanie medzi zadarmo/platené
    $('#lending_price_type').on('change', function() {
        if ($(this).val() === 'paid') {
            $('#bbk-price-input-wrapper').slideDown();
            $('#lending_price').prop('required', true);
            updatePriceBreakdown();
        } else {
            $('#bbk-price-input-wrapper').slideUp();
            $('#lending_price').prop('required', false).val(0);
            $('#bbk-price-breakdown').text('');
        }
    });

    // Výpočet rozdelenia ceny
    $('#lending_price').on('input', function() {
        updatePriceBreakdown();
    });

    function updatePriceBreakdown() {
        var price = parseFloat($('#lending_price').val()) || 0;
        var owner = (price * 0.7).toFixed(2);
        var community = (price * 0.3).toFixed(2);
        $('#bbk-price-breakdown').text('Vy: ' + owner + ' € (70%), Komunita: ' + community + ' € (30%)');
    }

    // Upload obrázka
    $('#image').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;

        var formData = new FormData();
        formData.append('action', 'bbk_upload_image');
        formData.append('nonce', bbkAjax.nonce);
        formData.append('image', file);

        $.ajax({
            url: bbkAjax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#image_url').val(response.data.image_url);
                    $('#image-preview').html('<img src="' + response.data.image_url + '" style="max-width: 200px;">');
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Submit formulára
    $('#bbk-add-book-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        formData += '&action=bbk_add_book&nonce=' + bbkAjax.nonce;

        // Ak je zadarmo, nastav cenu na 0
        if ($('#lending_price_type').val() === 'free') {
            formData += '&lending_price=0';
        }

        $.ajax({
            url: bbkAjax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#bbk-form-message').html('<div class="bbk-success">' + response.data.message + '</div>');
                    $('#bbk-add-book-form')[0].reset();
                    setTimeout(function() {
                        window.location.href = '<?php echo home_url('/dashboard'); ?>';
                    }, 1500);
                } else {
                    $('#bbk-form-message').html('<div class="bbk-error">' + response.data.message + '</div>');
                }
            }
        });
    });
});
</script>
