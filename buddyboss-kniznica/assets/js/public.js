/**
 * BuddyBoss Komunitná Knižnica - Public JS
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Požičanie bezplatnej knihy
         */
        $('.bbk-borrow-book').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var bookId = $btn.data('book-id');
            var originalText = $btn.text();

            if (!confirm('Naozaj si chcete požičať túto knihu?')) {
                return;
            }

            $btn.prop('disabled', true).text(bbkAjax.strings.loading);

            $.ajax({
                url: bbkAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbk_borrow_book',
                    nonce: bbkAjax.nonce,
                    book_id: bookId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Nastala chyba. Skúste to znova.');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });

        /**
         * Pridanie knihy do košíka (platené požičanie)
         */
        $('.bbk-add-to-cart').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var bookId = $btn.data('book-id');
            var originalText = $btn.text();

            $btn.prop('disabled', true).text(bbkAjax.strings.loading);

            $.ajax({
                url: bbkAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbk_add_to_cart',
                    nonce: bbkAjax.nonce,
                    book_id: bookId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        if (response.data.cart_url) {
                            window.location.href = response.data.cart_url;
                        }
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Nastala chyba. Skúste to znova.');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });

        /**
         * Vrátenie knihy
         */
        $('.bbk-return-book').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var lendingId = $btn.data('lending-id');
            var originalText = $btn.text();

            if (!confirm(bbkAjax.strings.confirm_return)) {
                return;
            }

            $btn.prop('disabled', true).text(bbkAjax.strings.loading);

            $.ajax({
                url: bbkAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbk_return_book',
                    nonce: bbkAjax.nonce,
                    lending_id: lendingId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Nastala chyba. Skúste to znova.');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });

        /**
         * Zmazanie knihy
         */
        $('.bbk-delete-book').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var bookId = $btn.data('book-id');

            if (!confirm(bbkAjax.strings.confirm_delete)) {
                return;
            }

            $btn.prop('disabled', true).text(bbkAjax.strings.loading);

            $.ajax({
                url: bbkAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbk_delete_book',
                    nonce: bbkAjax.nonce,
                    book_id: bookId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text('🗑️ Zmazať');
                    }
                },
                error: function() {
                    alert('Nastala chyba. Skúste to znova.');
                    $btn.prop('disabled', false).text('🗑️ Zmazať');
                }
            });
        });

        /**
         * Smooth scroll pre odkazy
         */
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 1000);
            }
        });

    });

})(jQuery);
