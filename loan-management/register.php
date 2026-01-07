<?php
require_once __DIR__ . '/php/auth.php';

$auth = new Auth();

// Ak je používateľ už prihlásený, presmeruj na dashboard
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrácia - Systém správy pôžičiek</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Registrácia</h2>

        <div id="alert-container"></div>

        <form id="register-form">
            <div class="form-group">
                <label for="full_name">Celé meno</label>
                <input type="text" id="full_name" name="full_name" required autocomplete="name">
            </div>

            <div class="form-group">
                <label for="username">Používateľské meno</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Heslo (min. 8 znakov)</label>
                <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8">
            </div>

            <div class="form-group">
                <label for="password_confirm">Potvrdenie hesla</label>
                <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password" minlength="8">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Zaregistrovať sa
            </button>
        </form>

        <div class="auth-links">
            <p>Už máte účet? <a href="login.php">Prihláste sa</a></p>
        </div>
    </div>

    <script src="js/auth.js"></script>
</body>
</html>
