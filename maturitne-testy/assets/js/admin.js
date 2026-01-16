jQuery(document).ready(function($) {

    // Prepínanie medzi typmi otázok
    $('#question_type').on('change', function() {
        const type = $(this).val();

        if (type === 'multiple_choice') {
            $('#multiple-choice-section').show();
            $('#text-answer-section').hide();
            $('#correct_answer').prop('required', false);
        } else {
            $('#multiple-choice-section').hide();
            $('#text-answer-section').show();
            $('#correct_answer').prop('required', true);
        }
    });

    // Pridanie novej možnosti
    $('#add-option-btn').on('click', function() {
        const container = $('#options-container');
        const currentOptions = container.find('.mt-option-row').length;
        const newIndex = currentOptions;

        const optionHtml = `
            <div class="mt-option-row" data-index="${newIndex}">
                <input type="text" name="options[${newIndex}][text]" placeholder="Text možnosti" class="regular-text">
                <label>
                    <input type="radio" name="correct_option" value="${newIndex}">
                    Správna odpoveď
                </label>
                <button type="button" class="button remove-option-btn">Odstrániť</button>
            </div>
        `;

        container.append(optionHtml);
    });

    // Odstránenie možnosti
    $(document).on('click', '.remove-option-btn', function() {
        if ($('#options-container .mt-option-row').length > 2) {
            $(this).closest('.mt-option-row').remove();
            reindexOptions();
        } else {
            alert('Musíte mať aspoň 2 možnosti.');
        }
    });

    function reindexOptions() {
        $('#options-container .mt-option-row').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('input[type="text"]').attr('name', 'options[' + index + '][text]');
            $(this).find('input[type="radio"]').attr('value', index);

            const optionId = $(this).find('input[type="hidden"]').val();
            if (optionId) {
                $(this).find('input[type="hidden"]').attr('name', 'options[' + index + '][id]');
            }
        });
    }

    // Upload obrázka
    let imageUploader;

    $('#mt-upload-image-btn').on('click', function(e) {
        e.preventDefault();

        if (imageUploader) {
            imageUploader.open();
            return;
        }

        imageUploader = wp.media({
            title: 'Vybrať obrázok',
            button: {
                text: 'Použiť tento obrázok'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        imageUploader.on('select', function() {
            const attachment = imageUploader.state().get('selection').first().toJSON();

            $('#image_url').val(attachment.url);
            $('#mt-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;">');
            $('#mt-remove-image-btn').show();
        });

        imageUploader.open();
    });

    // Odstránenie obrázka
    $('#mt-remove-image-btn').on('click', function(e) {
        e.preventDefault();

        $('#image_url').val('');
        $('#mt-image-preview').html('');
        $(this).hide();
    });

    // Validácia formulára pred odoslaním
    $('#mt-question-form').on('submit', function(e) {
        const questionType = $('#question_type').val();

        if (questionType === 'multiple_choice') {
            const options = $('#options-container .mt-option-row input[type="text"]');
            let hasEmptyOption = false;
            let filledOptionsCount = 0;

            options.each(function() {
                if ($(this).val().trim() === '') {
                    hasEmptyOption = true;
                } else {
                    filledOptionsCount++;
                }
            });

            if (filledOptionsCount < 2) {
                alert('Musíte vyplniť aspoň 2 možnosti.');
                e.preventDefault();
                return false;
            }

            const correctOption = $('input[name="correct_option"]:checked');
            if (correctOption.length === 0) {
                alert('Musíte vybrať správnu odpoveď.');
                e.preventDefault();
                return false;
            }

            // Kontrola, či je správna odpoveď vyplnená
            const correctIndex = correctOption.val();
            const correctText = $('input[name="options[' + correctIndex + '][text]"]').val();
            if (!correctText || correctText.trim() === '') {
                alert('Správna odpoveď musí byť vyplnená.');
                e.preventDefault();
                return false;
            }
        }

        return true;
    });

    // Potvrdenie pred vymazaním
    $('.delete-link').on('click', function(e) {
        if (!confirm('Naozaj chcete vymazať túto položku?')) {
            e.preventDefault();
            return false;
        }
    });
});
