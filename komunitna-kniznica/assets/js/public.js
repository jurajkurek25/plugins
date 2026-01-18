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

            // Funkcia na pridanie knihy
            function addBook(imageUrl) {
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
                    image_url: imageUrl || ''
                };

                $.ajax({
                    url: kkPublic.ajaxUrl,
                    type: 'POST',
                    data: formDataObj,
                    success: function(response) {
                        if (response.success) {
                            console.log('SUCCESS:', response);
                            $message.html('<div class="kk-success-message" style="background: #4ade80; color: #0f1419; padding: 15px; border-radius: 5px; margin: 15px 0;">' + response.data.message + '</div>');
                            $form[0].reset();
                            $('#kk-image-preview').html('');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            console.error('ERROR:', response);
                            if (response.data.debug) {
                                console.error('DEBUG INFO:', response.data.debug);
                            }
                            $message.html('<div class="kk-error-message" style="background: #f87171; color: #fff; padding: 15px; border-radius: 5px; margin: 15px 0;">' + response.data.message + '</div>');
                            $submitBtn.prop('disabled', false).text('Pridať knihu');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        $message.html('<div class="kk-error-message" style="background: #f87171; color: #fff; padding: 15px; border-radius: 5px; margin: 15px 0;">Nastala chyba pri komunikácii so serverom. Skúste to prosím znova.</div>');
                        $submitBtn.prop('disabled', false).text('Pridať knihu');
                    }
                });
            }

            // Upload obrázka najprv (ak existuje)
            var imageFile = $('#book_image')[0].files[0];

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
                    success: function(response) {
                        if (response.success) {
                            addBook(response.data.image_url);
                        } else {
                            // Pridaj knihu aj bez obrázka
                            addBook('');
                        }
                    },
                    error: function() {
                        // Pridaj knihu aj bez obrázka
                        addBook('');
                    }
                });
            } else {
                // Žiadny obrázok, pridaj knihu priamo
                addBook('');
            }
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

/**
 * ===================================
 * MARURITKA FLASHCARDS JAVASCRIPT
 * ===================================
 */

(function($) {
    'use strict';

    // Flashcards Deck Learning
    const FlashcardsDeck = {
        currentCardIndex: 0,
        cards: [],
        deckId: null,
        stats: null,

        init: function() {
            const $container = $('.maruritka-flashcards-deck');
            if ($container.length === 0) return;

            // Načítanie dát
            const data = $('#flashcard-data').text();
            if (!data) return;

            try {
                const jsonData = JSON.parse(data);
                this.cards = jsonData.cards || [];
                this.deckId = jsonData.deck_id;
                this.stats = jsonData.stats;

                if (this.cards.length > 0) {
                    this.bindEvents();
                    this.showCard(0);
                }
            } catch (e) {
                console.error('Error parsing flashcard data:', e);
            }

            // Reset progress button
            $('#reset-progress-btn').on('click', () => this.resetProgress());
        },

        bindEvents: function() {
            // Show answer
            $('#show-answer-btn').on('click', () => this.showAnswer());

            // Rating buttons
            $('.rating-btn').on('click', (e) => {
                const quality = $(e.currentTarget).data('quality');
                this.submitRating(quality);
            });

            // Show hint
            $('#show-hint-btn').on('click', () => {
                $('#card-hint').slideToggle();
            });

            // Show explanation
            $('#show-explanation-btn').on('click', () => {
                $('#card-explanation').slideToggle();
            });

            // Continue learning
            $('#continue-learning-btn').on('click', () => {
                location.reload();
            });
        },

        showCard: function(index) {
            if (index >= this.cards.length) {
                this.showSessionComplete();
                return;
            }

            this.currentCardIndex = index;
            const card = this.cards[index];

            // Reset card state
            $('#flashcard').removeClass('flipped');
            $('#rating-buttons').hide();
            $('#show-answer-btn').show();
            $('#card-hint').hide();
            $('#card-explanation').hide();

            // Update content
            $('#card-question').html(card.question);
            $('#card-answer').html(card.answer);

            // Hint
            if (card.hint) {
                $('#card-hint-text').html(card.hint);
                $('#show-hint-btn').show();
            } else {
                $('#show-hint-btn').hide();
            }

            // Explanation
            if (card.explanation) {
                $('#card-explanation-text').html(card.explanation);
                $('#show-explanation-btn').show();
            } else {
                $('#show-explanation-btn').hide();
            }

            // Update progress
            this.updateProgress();
        },

        showAnswer: function() {
            $('#flashcard').addClass('flipped');
            $('#show-answer-btn').hide();
            $('#rating-buttons').fadeIn();
        },

        submitRating: function(quality) {
            const card = this.cards[this.currentCardIndex];

            // Disable buttons
            $('.rating-btn').prop('disabled', true);

            // AJAX request
            $.ajax({
                url: kkPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kk_submit_card_review',
                    nonce: kkPublic.nonces.flashcards,
                    card_id: card.id,
                    quality: quality
                },
                success: (response) => {
                    if (response.success) {
                        // Move to next card
                        setTimeout(() => {
                            $('.rating-btn').prop('disabled', false);
                            this.showCard(this.currentCardIndex + 1);
                        }, 300);
                    } else {
                        alert(response.data.message || 'Chyba pri ukladaní odpovede');
                        $('.rating-btn').prop('disabled', false);
                    }
                },
                error: () => {
                    alert('Chyba pripojenia');
                    $('.rating-btn').prop('disabled', false);
                }
            });
        },

        updateProgress: function() {
            const current = this.currentCardIndex + 1;
            const total = this.cards.length;
            const percent = (current / total) * 100;

            $('#current-card-num').text(current);
            $('#total-cards-num').text(total);
            $('#session-progress-fill').css('width', percent + '%');
        },

        showSessionComplete: function() {
            $('.flashcard-container').hide();
            $('#session-complete').fadeIn();

            // Stats
            const sessionStats = `
                <div class="stat-box">
                    <span class="stat-number">${this.cards.length}</span>
                    <span class="stat-label">Kartičiek precvičených</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number">${this.stats.reviewing_cards}</span>
                    <span class="stat-label">Kartičiek naučených</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number">${this.stats.accuracy}%</span>
                    <span class="stat-label">Úspešnosť</span>
                </div>
            `;

            $('#session-stats').html(sessionStats);
            $('#completed-count').text(this.cards.length);
        },

        resetProgress: function() {
            if (!confirm('Naozaj chceš resetovať celý progress pre tento balíček? Táto akcia je nevratná!')) {
                return;
            }

            $.ajax({
                url: kkPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kk_reset_deck_progress',
                    nonce: kkPublic.nonces.flashcards,
                    deck_id: this.deckId
                },
                success: (response) => {
                    if (response.success) {
                        alert('Progress bol resetovaný');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Chyba pri resetovaní');
                    }
                },
                error: () => {
                    alert('Chyba pripojenia');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        FlashcardsDeck.init();
    });

})(jQuery);
