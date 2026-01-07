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
    <title>Prihlásenie - Systém správy pôžičiek</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Prihlásenie</h2>

        <div id="alert-container"></div>

        <form id="login-form">
            <div class="form-group">
                <label for="username">Používateľské meno alebo email</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Heslo</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Prihlásiť sa
            </button>
        </form>

        <div class="auth-links">
            <p>Nemáte účet? <a href="register.php">Zaregistrujte sa</a></p>
        </div>
    </div>

    <script src="js/auth.js"></script>
</body>
</html>
