/**
 * Komunitná Knižnica - Public JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // === TABS ===
        $('.kk-tab-btn').on('click', function() {
            var tabId = $(this).data('tab');

            // Odstránenie active z všetkých
            $('.kk-tab-btn').removeClass('active');
            $('.kk-tab-content').removeClass('active');

            // Pridanie active na kliknutý
            $(this).addClass('active');
            $('#tab-' + tabId).addClass('active');
        });

        // === PRIDANIE DO KOŠÍKA (Platené požičanie) ===
        $('.kk-add-to-cart').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var bookId = $btn.data('book-id');

            $btn.prop('disabled', true).text('Pridávam...');

            $.ajax({
                url: kkPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kk_add_to_cart',
                    nonce: kkPublic.nonces.add_to_cart,
                    book_id: bookId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        // Presmerovanie na košík
                        window.location.href = response.data.cart_url;
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text('Pridať do košíka');
                    }
                },
                error: function() {
                    alert('Nastala chyba. Skúste to prosím znova.');
                    $btn.prop('disabled', false).text('Pridať do košíka');
                }
            });
        });

        // === POŽIČANIE ZADARMO ===
        $('.kk-borrow-free').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var bookId = $btn.data('book-id');

            if (!confirm('Naozaj si chcete požičať túto knihu?')) {
                return;
            }

            $btn.prop('disabled', true).text('Požičiavam...');

            $.ajax({
                url: kkPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kk_borrow_free_book',
                    nonce: kkPublic.nonces.borrow_free,
                    book_id: bookId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text('Požičať knihu');
                    }
                },
                error: function() {
                    alert('Nastala chyba. Skúste to prosím znova.');
                    $btn.prop('disabled', false).text('Požičať knihu');
                }
            });
        });

        // === VRÁTENIE KNIHY ===
        $('.kk-return-book').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var lendingId = $btn.data('lending-id');

            if (!confirm('Naozaj chcete vrátiť túto knihu?')) {
                return;
            }

            $btn.prop('disabled', true).text('Vraciam...');

            $.ajax({
                url: kkPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kk_return_book',
                    nonce: kkPublic.nonces.return_book,
                    lending_id: lendingId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text('Vrátiť knihu');
                    }
                },
                error: function() {
                    alert('Nastala chyba. Skúste to prosím znova.');
                    $btn.prop('disabled', false).text('Vrátiť knihu');
                }
            });
        });

        // === PRIDANIE KNIHY ===
        $('#kk-add-book-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $message = $('#kk-form-message');

            $submitBtn.prop('disabled', true).text('Pridávam...');
            $message.html('');

            // Upload obrázka najprv (ak existuje)
            var imageFile = $('#book_image')[0].files[0];
            var imageUrl = '';

            if (imageFile) {
                var formData = new FormData();
                formData.append('action', 'kk_upload_book_image');
                formData.append('nonce', kkPublic.nonces.upload_image);
                formData.append('image', imageFile);

                $.ajax({
                    url: kkPublic.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    async: false,
                    success: function(response) {
                        if (response.success) {
                            imageUrl = response.data.image_url;
                        }
                    }
                });
            }

            // Pridanie knihy
            var formDataObj = {
                action: 'kk_add_book',
                nonce: kkPublic.nonces.add_book,
                title: $('#book_title').val(),
                author: $('#book_author').val(),
                isbn: $('#book_isbn').val(),
                genre: $('#book_genre').val(),
                description: $('#book_description').val(),
                book_condition: $('#book_condition').val(),
                lending_price: $('#book_lending_price').val(),
                image_url: imageUrl
            };

            $.ajax({
                url: kkPublic.ajaxUrl,
                type: 'POST',
                data: formDataObj,
                success: function(response) {
                    if (response.success) {
                        $message.html('<div class="kk-success-message">' + response.data.message + '</div>');
                        $form[0].reset();
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $message.html('<div class="kk-error-message">' + response.data.message + '</div>');
                        $submitBtn.prop('disabled', false).text('Pridať knihu');
                    }
                },
                error: function() {
                    $message.html('<div class="kk-error-message">Nastala chyba. Skúste to prosím znova.</div>');
                    $submitBtn.prop('disabled', false).text('Pridať knihu');
                }
            });
        });

        // === NÁHĽAD OBRÁZKA ===
        $('#book_image').on('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#kk-image-preview').html('<img src="' + e.target.result + '" style="max-width: 200px; margin-top: 10px; border-radius: 5px;">');
                };
                reader.readAsDataURL(file);
            }
        });

        // === ZMAZANIE KNIHY ===
        $('.kk-btn-delete').on('click', function(e) {
            e.preventDefault();
            var bookId = $(this).data('book-id');

            if (!confirm('Naozaj chcete zmazať túto knihu?')) {
                return;
            }

            $.ajax({
                url: kkPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kk_delete_book',
                    nonce: kkPublic.nonces.delete_book,
                    book_id: bookId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Nastala chyba. Skúste to prosím znova.');
                }
            });
        });

        // === HODNOTENIE KNIHY ===
        $('.kk-rate-book').on('click', function(e) {
            e.preventDefault();
            var lendingId = $(this).data('lending-id');

            // Jednoduchý prompt (môže byť nahradený modálnym oknom)
            var rating = prompt('Hodnotenie (1-5):');
            var review = prompt('Recenzia (voliteľné):');

            if (rating && rating >= 1 && rating <= 5) {
                $.ajax({
                    url: kkPublic.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'kk_add_rating',
                        nonce: kkPublic.nonces.add_rating,
                        lending_id: lendingId,
                        rating: rating,
                        review: review || ''
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('Nastala chyba. Skúste to prosím znova.');
                    }
                });
            }
        });

    });

})(jQuery);
