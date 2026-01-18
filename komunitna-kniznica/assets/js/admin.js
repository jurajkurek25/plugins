/**
 * Komunitná Knižnica - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // === ADMIN DASHBOARD ===
        console.log('Komunitná Knižnica Admin JS loaded');

        // Môžu byť pridané ďalšie admin funkcie podľa potreby
        // Napríklad: grafické štatistiky, filtrovanie tabuliek, atď.

    });

})(jQuery);

/**
 * ===================================
 * FLASHCARDS ADMIN JAVASCRIPT
 * ===================================
 */

(function($) {
    'use strict';

    // Flashcards Admin
    const FlashcardsAdmin = {
        init: function() {
            this.bindDeckEvents();
            this.bindCardEvents();
            this.bindModalEvents();
        },

        // Deck Events
        bindDeckEvents: function() {
            // Add deck
            $('#add-deck-btn').on('click', () => {
                this.openDeckModal();
            });

            // Edit deck
            $(document).on('click', '.edit-deck-btn', function() {
                const deckId = $(this).data('deck-id');
                FlashcardsAdmin.editDeck(deckId);
            });

            // Delete deck
            $(document).on('click', '.delete-deck-btn', function() {
                const deckId = $(this).data('deck-id');
                FlashcardsAdmin.deleteDeck(deckId);
            });

            // Deck form submit
            $('#deck-form').on('submit', (e) => {
                e.preventDefault();
                this.saveDeck();
            });
        },

        // Card Events
        bindCardEvents: function() {
            // Add card
            $('#add-card-btn').on('click', function() {
                const deckId = $(this).data('deck-id');
                FlashcardsAdmin.openCardModal(deckId);
            });

            // Edit card
            $(document).on('click', '.edit-card-btn', function() {
                const cardId = $(this).data('card-id');
                FlashcardsAdmin.editCard(cardId);
            });

            // Delete card
            $(document).on('click', '.delete-card-btn', function() {
                const cardId = $(this).data('card-id');
                FlashcardsAdmin.deleteCard(cardId);
            });

            // Card form submit
            $('#card-form').on('submit', (e) => {
                e.preventDefault();
                this.saveCard();
            });
        },

        // Modal Events
        bindModalEvents: function() {
            $('.kk-modal-close').on('click', function() {
                $(this).closest('.kk-modal').hide();
            });

            // Close on outside click
            $('.kk-modal').on('click', function(e) {
                if ($(e.target).hasClass('kk-modal')) {
                    $(this).hide();
                }
            });
        },

        // Open Deck Modal
        openDeckModal: function(deckData = null) {
            if (deckData) {
                $('#deck-modal-title').text('Upraviť balíček');
                $('#deck-id').val(deckData.id);
                $('#deck-title').val(deckData.title);
                $('#deck-description').val(deckData.description);
                $('#deck-category').val(deckData.category);
                $('#deck-subject').val(deckData.subject);
                $('#deck-difficulty').val(deckData.difficulty);
                $('#deck-is-public').prop('checked', deckData.is_public == 1);
            } else {
                $('#deck-modal-title').text('Pridať balíček');
                $('#deck-form')[0].reset();
                $('#deck-id').val('');
            }
            $('#deck-modal').show();
        },

        // Save Deck
        saveDeck: function() {
            const deckId = $('#deck-id').val();
            const action = deckId ? 'kk_update_deck' : 'kk_create_deck';

            const data = {
                action: action,
                nonce: kkAdmin.nonce,
                deck_id: deckId,
                title: $('#deck-title').val(),
                description: $('#deck-description').val(),
                category: $('#deck-category').val(),
                subject: $('#deck-subject').val(),
                difficulty: $('#deck-difficulty').val(),
                is_public: $('#deck-is-public').is(':checked') ? 1 : 0
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Balíček uložený');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Chyba pri ukladaní');
                    }
                },
                error: function() {
                    alert('Chyba pripojenia');
                }
            });
        },

        // Edit Deck
        editDeck: function(deckId) {
            // V production verzii by sme načítali data cez AJAX
            // Pre teraz len otvoríme modal a používateľ musí opäť zadať data
            // V budúcnosti pridáme AJAX endpoint na získanie deck detail
            this.openDeckModal();
        },

        // Delete Deck
        deleteDeck: function(deckId) {
            if (!confirm('Naozaj chceš zmazať tento balíček? Zmažú sa aj všetky jeho kartičky a progress používateľov!')) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kk_delete_deck',
                    nonce: kkAdmin.nonce,
                    deck_id: deckId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Balíček zmazaný');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Chyba pri mazaní');
                    }
                },
                error: function() {
                    alert('Chyba pripojenia');
                }
            });
        },

        // Open Card Modal
        openCardModal: function(deckId, cardData = null) {
            $('#card-deck-id').val(deckId);

            if (cardData) {
                $('#card-modal-title').text('Upraviť kartičku');
                $('#card-id').val(cardData.id);
                $('#card-question').val(cardData.question);
                $('#card-answer').val(cardData.answer);
                $('#card-hint').val(cardData.hint);
                $('#card-explanation').val(cardData.explanation);
                $('#card-order').val(cardData.card_order);
            } else {
                $('#card-modal-title').text('Pridať kartičku');
                $('#card-form')[0].reset();
                $('#card-id').val('');
                $('#card-deck-id').val(deckId);
            }
            $('#card-modal').show();
        },

        // Save Card
        saveCard: function() {
            const cardId = $('#card-id').val();
            const action = cardId ? 'kk_update_card' : 'kk_create_card';

            const data = {
                action: action,
                nonce: kkAdmin.nonce,
                card_id: cardId,
                deck_id: $('#card-deck-id').val(),
                question: $('#card-question').val(),
                answer: $('#card-answer').val(),
                hint: $('#card-hint').val(),
                explanation: $('#card-explanation').val(),
                card_order: $('#card-order').val()
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Kartička uložená');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Chyba pri ukladaní');
                    }
                },
                error: function() {
                    alert('Chyba pripojenia');
                }
            });
        },

        // Edit Card
        editCard: function(cardId) {
            // V production verzii by sme načítali data cez AJAX
            this.openCardModal($('#card-deck-id').val());
        },

        // Delete Card
        deleteCard: function(cardId) {
            if (!confirm('Naozaj chceš zmazať túto kartičku?')) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kk_delete_card',
                    nonce: kkAdmin.nonce,
                    card_id: cardId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Kartička zmazaná');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Chyba pri mazaní');
                    }
                },
                error: function() {
                    alert('Chyba pripojenia');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.kk-decks-admin, .kk-cards-admin').length > 0) {
            FlashcardsAdmin.init();
        }
    });

})(jQuery);
