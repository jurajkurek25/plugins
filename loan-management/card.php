<?php
require_once __DIR__ . '/php/auth.php';

$auth = new Auth();
$auth->requireLogin();

$currentUser = $auth->getCurrentUserWithToken();
$loginToken = $currentUser['login_token'] ?? null;

// Ak token neexistuje, vytvor nový
if (!$loginToken) {
    $tokenResult = $auth->createLoginToken();
    if ($tokenResult['success']) {
        $loginToken = $tokenResult['token'];
    }
}

// Vytvorenie prihlasovacieho URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$loginUrl = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']) . '/login-token.php?token=' . $loginToken;
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prihlasovacia kartička - <?php echo htmlspecialchars($currentUser['full_name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 20px;
            }
            .no-print {
                display: none !important;
            }
            .card-container {
                box-shadow: none !important;
                page-break-after: always;
            }
        }

        body {
            background: #f5f5f5;
            padding: 20px;
        }

        .card-wrapper {
            max-width: 800px;
            margin: 0 auto;
        }

        .card-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin: 20px auto;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .card-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }

        .card-header p {
            margin: 0;
            font-size: 18px;
            opacity: 0.9;
        }

        .user-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .user-info h2 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 28px;
        }

        .user-info p {
            margin: 5px 0;
            color: #666;
            font-size: 16px;
        }

        .qr-section {
            margin: 40px 0;
        }

        .qr-section h3 {
            margin-bottom: 20px;
            color: #333;
        }

        #card-qr-code {
            display: inline-block;
            padding: 20px;
            background: white;
            border: 3px solid #667eea;
            border-radius: 15px;
        }

        .instructions {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            text-align: left;
        }

        .instructions h3 {
            margin-top: 0;
            color: #1976d2;
        }

        .instructions ol {
            margin: 10px 0;
            padding-left: 25px;
        }

        .instructions li {
            margin: 10px 0;
            line-height: 1.6;
        }

        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            text-align: left;
        }

        .warning-box h4 {
            margin-top: 0;
            color: #856404;
        }

        .url-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            word-break: break-all;
            font-size: 14px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="card-wrapper">
        <div class="no-print action-buttons">
            <button class="btn btn-primary" onclick="window.print()">
                🖨️ Vytlačiť kartičku
            </button>
            <button class="btn btn-secondary" onclick="downloadCard()">
                💾 Stiahnuť ako obrázok
            </button>
            <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                ← Späť na dashboard
            </button>
        </div>

        <div class="card-container" id="printable-card">
            <div class="card-header">
                <h1>💰 Systém správy pôžičiek</h1>
                <p>Prihlasovacia kartička</p>
            </div>

            <div class="user-info">
                <h2><?php echo htmlspecialchars($currentUser['full_name']); ?></h2>
                <p><strong>Používateľ:</strong> @<?php echo htmlspecialchars($currentUser['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($currentUser['email']); ?></p>
            </div>

            <div class="qr-section">
                <h3>Naskenujte QR kód na prihlásenie</h3>
                <div id="card-qr-code"></div>
            </div>

            <div class="url-display no-print">
                <strong>Prihlasovací odkaz:</strong><br>
                <span id="login-url-text"><?php echo htmlspecialchars($loginUrl); ?></span>
            </div>

            <div class="instructions no-print">
                <h3>📱 Ako použiť kartičku:</h3>
                <ol>
                    <li>Otvorte fotoaparát alebo QR čítačku na vašom telefóne</li>
                    <li>Naskenujte QR kód na tejto kartičke</li>
                    <li>Otvorte sa vám prihlasovací odkaz</li>
                    <li>Automaticky sa prihlásite do systému</li>
                </ol>
            </div>

            <div class="warning-box no-print">
                <h4>⚠️ Bezpečnostné upozornenie</h4>
                <p>Táto kartička obsahuje váš osobný prihlasovací kód. Neodovzdávajte ju neznámym osobám.</p>
                <p>Ak stratíte kartičku alebo sa domnievate, že ju má niekto neoprávnený, okamžite vygenerujte nový QR kód v dashboarde.</p>
            </div>
        </div>
    </div>

    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // Vygenerovanie QR kódu
        const loginUrl = <?php echo json_encode($loginUrl); ?>;

        // Počkať na načítanie DOM a QRCode knižnice
        window.addEventListener('load', function() {
            if (typeof QRCode !== 'undefined') {
                new QRCode(document.getElementById('card-qr-code'), {
                    text: loginUrl,
                    width: 256,
                    height: 256,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            } else {
                document.getElementById('card-qr-code').innerHTML = '<p style="color: red;">Chyba: QR kód knižnica sa nenačítala.</p>';
            }
        });

        // Funkcia na stiahnutie kartičky ako obrázok
        function downloadCard() {
            // Táto funkcia by vyžadovala html2canvas alebo podobnú knižnicu
            // Pre jednoduchosť momentálne len upozorníme používateľa
            alert('Použite tlačidlo "Vytlačiť kartičku" a vyberte "Uložiť ako PDF" vo vašom tlačovom dialógu.');
        }

        // Kopírovanie URL do schránky
        document.getElementById('login-url-text').addEventListener('click', function() {
            const text = this.textContent;
            navigator.clipboard.writeText(text).then(function() {
                alert('Odkaz bol skopírovaný do schránky!');
            }).catch(function(err) {
                console.error('Chyba pri kopírovaní:', err);
            });
        });
    </script>
</body>
</html>
