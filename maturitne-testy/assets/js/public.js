jQuery(document).ready(function($) {
    let testStartTime = null;
    let testTimeLimit = 0;
    let timerInterval = null;
    let cheatingCount = 0;
    let testActive = false;
    let originalTitle = document.title;

    // Detekcia podvádzania - blur event (prepnutie okna/karty)
    $(window).on('blur', function() {
        if (testActive) {
            cheatingCount++;
            recordCheating();
            showCheatingWarning();
        }
    });

    // Detekcia podvádzania - visibility change
    $(document).on('visibilitychange', function() {
        if (document.hidden && testActive) {
            cheatingCount++;
            recordCheating();
            showCheatingWarning();
        }
    });

    // Detekcia podvádzania - focus loss
    $(window).on('focus', function() {
        if (testActive) {
            // Kontrola, či sa používateľ vrátil z iného okna/karty
            const timeDiff = Date.now() - lastActivityTime;
            if (timeDiff > 1000) { // Viac ako 1 sekunda
                // Už zaznamená blur event
            }
        }
    });

    // Blokovanie pravého tlačidla myši
    $('.mt-test-content').on('contextmenu', function(e) {
        if (testActive) {
            e.preventDefault();
            return false;
        }
    });

    // Blokovanie klávesových skratiek
    $(document).on('keydown', function(e) {
        if (testActive) {
            // Ctrl+Shift+I (DevTools)
            // Ctrl+Shift+J (Console)
            // Ctrl+Shift+C (Inspect)
            // Ctrl+U (View Source)
            // F12 (DevTools)
            if ((e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) ||
                (e.ctrlKey && e.key === 'u') ||
                e.key === 'F12') {
                e.preventDefault();
                cheatingCount++;
                recordCheating();
                showCheatingWarning();
                return false;
            }
        }
    });

    let lastActivityTime = Date.now();
    $(document).on('click keypress', function() {
        lastActivityTime = Date.now();
    });

    function recordCheating() {
        // Nemusíme volať AJAX, stačí zvýšiť počítadlo
        console.log('Cheating detected. Count: ' + cheatingCount);
    }

    function showCheatingWarning() {
        $('.mt-anti-cheat-warning').slideDown();

        // Blikanie nadpisu stránky
        let blinkCount = 0;
        let blinkInterval = setInterval(function() {
            document.title = (blinkCount % 2 === 0) ? '⚠️ PODVÁDZANIE!' : originalTitle;
            blinkCount++;
            if (blinkCount >= 10) {
                clearInterval(blinkInterval);
                document.title = originalTitle;
            }
        }, 500);
    }

    // Začatie testu
    $('#mt-start-test-btn').on('click', function() {
        const userName = $('#mt-user-name').val();
        const userEmail = $('#mt-user-email').val();

        if ($('#mt-user-name').length > 0) {
            if (!userName || !userEmail) {
                alert('Prosím vyplňte všetky povinné polia.');
                return;
            }

            // Validácia emailu
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(userEmail)) {
                alert('Prosím zadajte platný email.');
                return;
            }
        }

        $('#mt-test-start').hide();
        $('#mt-test-content').show();

        testStartTime = new Date();
        testActive = true;

        // Resetovanie počítadla podvádzania
        cheatingCount = 0;
        $('.mt-anti-cheat-warning').hide();

        // Spustenie časovača ak je nastavený
        const container = $('.mt-test-container');
        const timeLimitMinutes = container.data('time-limit');

        if (timeLimitMinutes && timeLimitMinutes > 0) {
            testTimeLimit = timeLimitMinutes * 60; // Konverzia na sekundy
            startTimer(testTimeLimit);
        }

        // Scroll na začiatok testu
        $('html, body').animate({
            scrollTop: $('#mt-test-content').offset().top - 100
        }, 500);
    });

    function startTimer(seconds) {
        let remainingTime = seconds;

        updateTimerDisplay(remainingTime);

        timerInterval = setInterval(function() {
            remainingTime--;
            updateTimerDisplay(remainingTime);

            if (remainingTime <= 0) {
                clearInterval(timerInterval);
                alert('Čas vypršal! Test bude automaticky odoslaný.');
                submitTest();
            }
        }, 1000);
    }

    function updateTimerDisplay(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        const display = String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        $('#mt-timer-display').text(display);

        // Zmena farby pri malom čase
        if (seconds <= 60) {
            $('#mt-timer-display').css('color', 'red');
        } else if (seconds <= 300) {
            $('#mt-timer-display').css('color', 'orange');
        }
    }

    // Odoslanie testu
    $('#mt-test-form').on('submit', function(e) {
        e.preventDefault();

        if (!confirm('Naozaj chcete odoslať test? Po odoslaní už nebude možné meniť odpovede.')) {
            return;
        }

        submitTest();
    });

    function submitTest() {
        testActive = false;

        if (timerInterval) {
            clearInterval(timerInterval);
        }

        const container = $('.mt-test-container');
        const testId = container.data('test-id');
        const answers = {};

        // Získanie odpovedí
        $('.mt-question').each(function() {
            const questionId = $(this).data('question-id');
            const input = $(this).find('input[type="radio"]:checked, input[type="text"]');

            if (input.length > 0) {
                answers[questionId] = input.val();
            }
        });

        // Výpočet času
        const timeTaken = Math.floor((new Date() - testStartTime) / 1000);

        // Získanie údajov používateľa
        const userName = $('#mt-user-name').val() || '';
        const userEmail = $('#mt-user-email').val() || '';

        // Zobrazenie loadingu
        $('#mt-test-content').hide();
        $('#mt-loading').show();

        // AJAX odoslanie
        $.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_submit_test',
                nonce: mt_ajax.nonce,
                test_id: testId,
                answers: answers,
                time_taken: timeTaken,
                cheating_count: cheatingCount,
                started_at: formatDateTime(testStartTime),
                user_name: userName,
                user_email: userEmail
            },
            success: function(response) {
                $('#mt-loading').hide();

                if (response.success) {
                    displayResults(response.data);
                } else {
                    alert('Chyba: ' + (response.data.message || 'Neznáma chyba'));
                    $('#mt-test-content').show();
                }
            },
            error: function() {
                $('#mt-loading').hide();
                alert('Chyba pri odosielaní testu. Skúste to prosím znova.');
                $('#mt-test-content').show();
            }
        });
    }

    function formatDateTime(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');

        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    function displayResults(data) {
        $('#mt-test-results').show();

        // Skóre
        $('#mt-result-score').text(data.score);
        $('#mt-result-max-score').text(data.max_score);
        $('#mt-result-percentage').text(data.percentage);
        $('#mt-score-percentage').text(data.percentage + '%');

        // Čas
        const minutes = Math.floor(data.time_taken / 60);
        const seconds = data.time_taken % 60;
        $('#mt-result-time').text(String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0'));

        // Úspech/neúspech
        if (data.passed) {
            $('#mt-result-passed').show();
            $('#mt-result-failed').hide();
            $('#mt-score-circle').addClass('mt-passed');
        } else {
            $('#mt-result-passed').hide();
            $('#mt-result-failed').show();
            $('#mt-score-circle').addClass('mt-failed');
        }

        // Podvádzanie
        if (data.cheating_detected) {
            $('#mt-result-cheating').show();
        }

        // Detailné výsledky
        let resultsHtml = '';
        $.each(data.results, function(index, result) {
            const isCorrect = result.is_correct;
            const statusClass = isCorrect ? 'mt-correct' : 'mt-incorrect';
            const statusIcon = isCorrect ? '✓' : '✗';

            resultsHtml += '<div class="mt-result-item ' + statusClass + '">';
            resultsHtml += '<div class="mt-result-question">';
            resultsHtml += '<strong>' + (index + 1) + '.</strong> ' + result.question_text;
            resultsHtml += '</div>';
            resultsHtml += '<div class="mt-result-answer">';
            resultsHtml += '<span class="mt-status-icon">' + statusIcon + '</span> ';
            resultsHtml += '<strong>Vaša odpoveď:</strong> ' + (result.user_answer || '(bez odpovede)');
            resultsHtml += '</div>';
            if (!isCorrect) {
                resultsHtml += '<div class="mt-result-correct-answer">';
                resultsHtml += '<strong>Správna odpoveď:</strong> ' + result.correct_answer;
                resultsHtml += '</div>';
            }
            resultsHtml += '<div class="mt-result-points">';
            resultsHtml += 'Body: ' + result.points + ' / ' + result.max_points;
            resultsHtml += '</div>';
            resultsHtml += '</div>';
        });

        $('#mt-results-list').html(resultsHtml);

        // Scroll na výsledky
        $('html, body').animate({
            scrollTop: $('#mt-test-results').offset().top - 100
        }, 500);
    }

    // Opakovanie testu
    $('#mt-retake-test-btn').on('click', function() {
        location.reload();
    });

    // Detekcia pokusov o otvorenie nového okna pomocou Ctrl+N, Ctrl+T
    $(document).on('keydown', function(e) {
        if (testActive) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'n' || e.key === 't')) {
                e.preventDefault();
                cheatingCount++;
                recordCheating();
                showCheatingWarning();
                return false;
            }
        }
    });

    // Detekcia fullscreen exit
    $(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange msfullscreenchange', function() {
        if (testActive && !document.fullscreenElement && !document.webkitFullscreenElement &&
            !document.mozFullScreenElement && !document.msFullscreenElement) {
            // Používateľ opustil fullscreen počas testu
            // Môžeme to považovať za podozrivé správanie, ale nemusí to byť podvádzanie
        }
    });
});
