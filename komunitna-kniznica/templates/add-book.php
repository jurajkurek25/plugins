<?php
/**
 * Template: Pridanie knihy
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="kk-add-book-wrapper">
    <h2><?php _e('Pridať novú knihu', 'komunitna-kniznica'); ?></h2>

    <form id="kk-add-book-form" class="kk-form" enctype="multipart/form-data">
        <div class="kk-form-group">
            <label for="book_title"><?php _e('Názov knihy *', 'komunitna-kniznica'); ?></label>
            <input type="text" id="book_title" name="title" required class="kk-input">
        </div>

        <div class="kk-form-group">
            <label for="book_author"><?php _e('Autor *', 'komunitna-kniznica'); ?></label>
            <input type="text" id="book_author" name="author" required class="kk-input">
        </div>

        <div class="kk-form-group">
            <label for="book_isbn"><?php _e('ISBN', 'komunitna-kniznica'); ?></label>
            <input type="text" id="book_isbn" name="isbn" class="kk-input">
        </div>

        <div class="kk-form-group">
            <label for="book_genre"><?php _e('Žáner *', 'komunitna-kniznica'); ?></label>
            <select id="book_genre" name="genre" required class="kk-select">
                <option value=""><?php _e('Vyberte žáner', 'komunitna-kniznica'); ?></option>
                <option value="Beletria"><?php _e('Beletria', 'komunitna-kniznica'); ?></option>
                <option value="Sci-Fi"><?php _e('Sci-Fi', 'komunitna-kniznica'); ?></option>
                <option value="Fantasy"><?php _e('Fantasy', 'komunitna-kniznica'); ?></option>
                <option value="Detektívka"><?php _e('Detektívka', 'komunitna-kniznica'); ?></option>
                <option value="Thriller"><?php _e('Thriller', 'komunitna-kniznica'); ?></option>
                <option value="Romantika"><?php _e('Romantika', 'komunitna-kniznica'); ?></option>
                <option value="Historický román"><?php _e('Historický román', 'komunitna-kniznica'); ?></option>
                <option value="Biografie"><?php _e('Biografie', 'komunitna-kniznica'); ?></option>
                <option value="Poézia"><?php _e('Poézia', 'komunitna-kniznica'); ?></option>
                <option value="Odborná literatúra"><?php _e('Odborná literatúra', 'komunitna-kniznica'); ?></option>
                <option value="Detská literatúra"><?php _e('Detská literatúra', 'komunitna-kniznica'); ?></option>
                <option value="Iné"><?php _e('Iné', 'komunitna-kniznica'); ?></option>
            </select>
        </div>

        <div class="kk-form-group">
            <label for="book_description"><?php _e('Popis', 'komunitna-kniznica'); ?></label>
            <textarea id="book_description" name="description" rows="5" class="kk-textarea"></textarea>
        </div>

        <div class="kk-form-group">
            <label for="book_condition"><?php _e('Stav knihy (1-10) *', 'komunitna-kniznica'); ?></label>
            <input type="number" id="book_condition" name="book_condition" min="1" max="10" value="8" required class="kk-input">
            <small><?php _e('1 = veľmi opotrebovaná, 10 = ako nová', 'komunitna-kniznica'); ?></small>
        </div>

        <div class="kk-form-group">
            <label for="book_image"><?php _e('Obrázok knihy', 'komunitna-kniznica'); ?></label>
            <input type="file" id="book_image" name="image" accept="image/*" class="kk-input-file">
            <div id="kk-image-preview"></div>
        </div>

        <div class="kk-form-group">
            <label for="book_lending_price"><?php _e('Cena požičania (€) *', 'komunitna-kniznica'); ?></label>
            <input type="number" id="book_lending_price" name="lending_price" min="0" step="0.01" value="0" required class="kk-input">
            <small><?php _e('Nastavte 0 pre bezplatné požičanie', 'komunitna-kniznica'); ?></small>
        </div>

        <div class="kk-form-actions">
            <button type="submit" class="kk-btn kk-btn-primary kk-btn-large">
                <?php _e('Pridať knihu', 'komunitna-kniznica'); ?>
            </button>
        </div>
    </form>

    <div id="kk-form-message"></div>
</div>
