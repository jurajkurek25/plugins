<?php
require_once __DIR__ . '/php/auth.php';

$auth = new Auth();
$error = null;
$success = null;

// Ak je používateľ už prihlásený, presmeruj na dashboard
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Prihlásenie cez token z GET parametra
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $result = $auth->loginWithToken($token);

    if ($result['success']) {
        $success = $result['message'];
        // Presmerovanie na dashboard po 1 sekunde
        header('refresh:1;url=index.php');
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prihlásenie cez QR kód - Systém správy pôžičiek</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>🔐 Prihlásenie cez QR kód</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="login.php" class="btn btn-primary">Prihlásiť sa klasicky</a>
            </div>
        <?php elseif ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <br><br>
                <div class="spinner"></div>
                <p style="text-align: center; margin-top: 10px;">Presmerovávam...</p>
            </div>
        <?php else: ?>
            <p style="text-align: center;">
                Naskenujte QR kód z vašej prihlasovacej kartičky<br>
                alebo použite odkaz, ktorý ste dostali.
            </p>
            <div style="text-align: center; margin-top: 30px;">
                <div class="spinner"></div>
                <p style="margin-top: 20px;">Čakám na token...</p>
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <a href="login.php" class="btn btn-secondary">Späť na prihlásenie</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
