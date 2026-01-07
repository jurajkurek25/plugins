<?php
/**
 * Inštalačný skript pre systém správy pôžičiek
 * Tento skript vytvorí databázu a tabuľky
 */

// Načítanie konfigurácie
require_once __DIR__ . '/php/config.php';

$errors = [];
$success = [];

// Spracovanie formulára
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? DB_HOST;
    $dbName = $_POST['db_name'] ?? DB_NAME;
    $dbUser = $_POST['db_user'] ?? DB_USER;
    $dbPass = $_POST['db_pass'] ?? DB_PASS;

    try {
        // Pripojenie k MySQL serveru (bez databázy)
        $dsn = "mysql:host=$dbHost;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Vytvorenie databázy
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $success[] = "Databáza '$dbName' bola vytvorená";

        // Pripojenie k vytvorenej databáze
        $pdo->exec("USE `$dbName`");

        // Načítanie SQL schémy
        $sqlFile = __DIR__ . '/sql/schema.sql';
        $sql = file_get_contents($sqlFile);

        // Vykonanie SQL príkazov
        $pdo->exec($sql);
        $success[] = "Tabuľky boli úspešne vytvorené";

        // Vytvorenie konfiguračného súboru
        $configContent = "<?php\n";
        $configContent .= "/**\n";
        $configContent .= " * Lokálna konfigurácia\n";
        $configContent .= " * Automaticky vygenerované: " . date('Y-m-d H:i:s') . "\n";
        $configContent .= " */\n\n";
        $configContent .= "define('DB_HOST', '$dbHost');\n";
        $configContent .= "define('DB_NAME', '$dbName');\n";
        $configContent .= "define('DB_USER', '$dbUser');\n";
        $configContent .= "define('DB_PASS', '$dbPass');\n";
        $configContent .= "define('DB_CHARSET', 'utf8mb4');\n";

        $configFile = __DIR__ . '/php/config.local.php';
        file_put_contents($configFile, $configContent);
        $success[] = "Konfiguračný súbor bol vytvorený";

        $success[] = "<strong>Inštalácia bola úspešná!</strong>";
        $success[] = "Teraz môžete prejsť na <a href='register.php'>registráciu</a>";

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
    <title>Inštalácia - Systém správy pôžičiek</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>🔧 Inštalácia systému</h2>

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
            <p>Vyplňte databázové údaje pre inštaláciu systému.</p>

            <form method="POST">
                <div class="form-group">
                    <label for="db_host">Databázový host</label>
                    <input type="text" id="db_host" name="db_host" value="<?php echo DB_HOST; ?>" required>
                </div>

                <div class="form-group">
                    <label for="db_name">Názov databázy</label>
                    <input type="text" id="db_name" name="db_name" value="<?php echo DB_NAME; ?>" required>
                </div>

                <div class="form-group">
                    <label for="db_user">Databázový používateľ</label>
                    <input type="text" id="db_user" name="db_user" value="<?php echo DB_USER; ?>" required>
                </div>

                <div class="form-group">
                    <label for="db_pass">Databázové heslo</label>
                    <input type="password" id="db_pass" name="db_pass" value="<?php echo DB_PASS; ?>">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Spustiť inštaláciu
                </button>
            </form>

            <div class="auth-links" style="margin-top: 20px;">
                <p><strong>Poznámka:</strong> Uistite sa, že databázový používateľ má oprávnenia na vytváranie databáz a tabuliek.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
