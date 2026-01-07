<?php
require_once __DIR__ . '/php/auth.php';

$auth = new Auth();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Systém správy pôžičiek</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Systém správy pôžičiek</h1>
            <div class="user-info">
                <span>👤 <?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                <button id="logout-btn" class="btn btn-secondary">Odhlásiť sa</button>
            </div>
        </div>

        <div class="dashboard">
            <aside class="sidebar">
                <nav>
                    <ul>
                        <li><a href="#" class="nav-link active" data-section="overview">Prehľad</a></li>
                        <li><a href="#" class="nav-link" data-section="my-loans">Moje pôžičky</a></li>
                        <li><a href="#" class="nav-link" data-section="borrowed">Požičal som si</a></li>
                        <li><a href="#" class="nav-link" data-section="lent">Požičal som</a></li>
                        <li><a href="#" class="nav-link" data-section="pending">Čakajúce žiadosti</a></li>
                        <li><a href="#" class="nav-link" data-section="notifications">Notifikácie</a></li>
                    </ul>
                </nav>
            </aside>

            <main class="main-content">
                <div id="alert-container"></div>

                <!-- PREHĽAD SEKCIA -->
                <div id="section-overview" class="content-section">
                    <h2>Prehľad</h2>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Celkom požičané</h3>
                            <div class="value" id="stat-lent">0 €</div>
                        </div>
                        <div class="stat-card">
                            <h3>Celkom požičal som si</h3>
                            <div class="value" id="stat-borrowed">0 €</div>
                        </div>
                        <div class="stat-card">
                            <h3>Aktívne pôžičky</h3>
                            <div class="value" id="stat-active">0</div>
                        </div>
                        <div class="stat-card">
                            <h3>Čakajúce žiadosti</h3>
                            <div class="value" id="stat-pending">0</div>
                        </div>
                    </div>

                    <button class="btn btn-primary" onclick="openNewLoanModal()">
                        + Nová žiadosť o pôžičku
                    </button>
                </div>

                <!-- MOJE PÔŽIČKY SEKCIA -->
                <div id="section-my-loans" class="content-section hidden">
                    <h2>Moje pôžičky</h2>
                    <button class="btn btn-primary" onclick="openNewLoanModal()">
                        + Nová žiadosť o pôžičku
                    </button>
                    <div id="all-loans-container"></div>
                </div>

                <!-- POŽIČAL SOM SI SEKCIA -->
                <div id="section-borrowed" class="content-section hidden">
                    <h2>Požičal som si</h2>
                    <div id="borrowed-loans-container"></div>
                </div>

                <!-- POŽIČAL SOM SEKCIA -->
                <div id="section-lent" class="content-section hidden">
                    <h2>Požičal som</h2>
                    <div id="lent-loans-container"></div>
                </div>

                <!-- ČAKAJÚCE ŽIADOSTI SEKCIA -->
                <div id="section-pending" class="content-section hidden">
                    <h2>Čakajúce žiadosti</h2>
                    <div id="pending-loans-container"></div>
                </div>

                <!-- NOTIFIKÁCIE SEKCIA -->
                <div id="section-notifications" class="content-section hidden">
                    <h2>Notifikácie</h2>
                    <div id="notifications-container" class="notifications"></div>
                </div>
            </main>
        </div>
    </div>

    <!-- MODAL: NOVÁ PÔŽIČKA -->
    <div id="new-loan-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Nová žiadosť o pôžičku</h2>
                <span class="close" onclick="closeNewLoanModal()">&times;</span>
            </div>
            <form id="new-loan-form">
                <div class="form-group">
                    <label for="lender-select">Komu žiadam pôžičku?</label>
                    <select id="lender-select" name="lender_id" required>
                        <option value="">Vyberte používateľa...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="loan-amount">Suma (€)</label>
                    <input type="number" id="loan-amount" name="amount" step="0.01" min="0.01" required>
                </div>

                <div class="form-group">
                    <label for="loan-description">Popis (voliteľné)</label>
                    <textarea id="loan-description" name="description" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Odoslať žiadosť</button>
            </form>
        </div>
    </div>

    <!-- MODAL: DETAILY PÔŽIČKY -->
    <div id="loan-details-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detaily pôžičky</h2>
                <span class="close" onclick="closeLoanDetailsModal()">&times;</span>
            </div>
            <div id="loan-details-content"></div>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo $currentUser['id']; ?>;
    </script>
    <script src="js/app.js"></script>
</body>
</html>
