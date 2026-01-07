<?php
/**
 * Migračný skript pre systém správy pôžičiek
 * Aktualizuje databázu na najnovšiu verziu
 */

require_once __DIR__ . '/php/config.php';

$errors = [];
$success = [];

// Kontrola či je používateľ admin (pre produkciu by ste mali mať lepšiu autentifikáciu)
// Pre jednoduchosť akceptujeme heslo z formulára

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        // Pripojenie k databáze
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Kontrola či stĺpec už existuje
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'login_token'");
        $columnExists = $stmt->fetch();

        if ($columnExists) {
            $errors[] = "Stĺpec 'login_token' už existuje v tabuľke users. Migrácia nie je potrebná.";
        } else {
            // Spustenie migrácie
            $migrationSQL = file_get_contents(__DIR__ . '/sql/add_login_token.sql');

            // Odstránenie komentárov a rozdelenie na príkazy
            $sqlCommands = array_filter(
                array_map('trim', explode(';', $migrationSQL)),
                function($cmd) {
                    return !empty($cmd) && !preg_match('/^--/', $cmd);
                }
            );

            foreach ($sqlCommands as $command) {
                if (!empty(trim($command))) {
                    $pdo->exec($command);
                }
            }

            $success[] = "Stĺpec 'login_token' bol úspešne pridaný do tabuľky users";

            // Vygenerovanie tokenov pre existujúcich používateľov
            $stmt = $pdo->query("SELECT id FROM users WHERE login_token IS NULL");
            $usersWithoutToken = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($usersWithoutToken) > 0) {
                $updateStmt = $pdo->prepare("UPDATE users SET login_token = ? WHERE id = ?");
                $tokenCount = 0;

                foreach ($usersWithoutToken as $userId) {
                    $token = bin2hex(random_bytes(32));
                    $updateStmt->execute([$token, $userId]);
                    $tokenCount++;
                }

                $success[] = "Vygenerované tokeny pre $tokenCount existujúcich používateľov";
            }

            $success[] = "<strong>Migrácia bola úspešná!</strong>";
            $success[] = "Teraz môžete prejsť na <a href='index.php'>dashboard</a>";
        }

    } catch (PDOException $e) {
        $errors[] = "Chyba databázy: " . $e->getMessage();
    } catch (Exception $e) {
        $errors[] = "Chyba: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrácia databázy - Systém správy pôžičiek</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>🔄 Migrácia databázy</h2>

        <p>Táto migrácia pridá podporu pre QR kód prihlásenie.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <ul style="margin: 0; padding-left: 20px; list-style: none;">
                    <?php foreach ($success as $msg): ?>
                        <li>✅ <?php echo $msg; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($success)): ?>
            <div class="alert alert-info">
                <strong>Čo sa pridá:</strong>
                <ul style="margin: 10px 0; padding-left: 25px;">
                    <li>Nový stĺpec <code>login_token</code> v tabuľke users</li>
                    <li>Automatické generovanie tokenov pre existujúcich používateľov</li>
                    <li>Index pre rýchlejšie vyhľadávanie podľa tokenu</li>
                </ul>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="confirm" value="1" required>
                        Potvrdz ujem, že chcem spustiť migráciu
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Spustiť migráciu
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <a href="index.php" class="btn btn-secondary">Zrušiť</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
