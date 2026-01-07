<?php
/**
 * API Endpoint Handler
 * Spracovanie AJAX požiadaviek
 */

header('Content-Type: application/json');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/loans.php';
require_once __DIR__ . '/payments.php';

// Získanie akcie z POST/GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$auth = new Auth();
$loans = new Loans();
$payments = new Payments();

try {
    switch ($action) {
        // AUTENTIFIKÁCIA
        case 'register':
            $result = $auth->register(
                $_POST['username'] ?? '',
                $_POST['email'] ?? '',
                $_POST['password'] ?? '',
                $_POST['full_name'] ?? ''
            );
            break;

        case 'login':
            $result = $auth->login(
                $_POST['username'] ?? '',
                $_POST['password'] ?? ''
            );
            break;

        case 'logout':
            $result = $auth->logout();
            break;

        case 'get_current_user':
            $user = $auth->getCurrentUser();
            $result = $user ? ['success' => true, 'user' => $user] : ['success' => false];
            break;

        case 'get_login_token':
            $token = $auth->getLoginToken();
            $result = $token ? ['success' => true, 'token' => $token] : ['success' => false, 'message' => 'Token nie je dostupný'];
            break;

        case 'regenerate_login_token':
            $result = $auth->createLoginToken();
            break;

        case 'login_with_token':
            $result = $auth->loginWithToken($_POST['token'] ?? $_GET['token'] ?? '');
            break;

        // PÔŽIČKY
        case 'create_loan_request':
            $result = $loans->createLoanRequest(
                $_POST['lender_id'] ?? 0,
                $_POST['amount'] ?? 0,
                $_POST['description'] ?? ''
            );
            break;

        case 'approve_loan':
            $result = $loans->approveLoan($_POST['loan_id'] ?? 0);
            break;

        case 'reject_loan':
            $result = $loans->rejectLoan($_POST['loan_id'] ?? 0);
            break;

        case 'get_user_loans':
            $result = $loans->getUserLoans();
            break;

        case 'get_loan_details':
            $result = $loans->getLoanDetails($_GET['loan_id'] ?? 0);
            break;

        case 'get_all_users':
            $result = $loans->getAllUsers();
            break;

        case 'get_notifications':
            $result = $loans->getNotifications($_GET['limit'] ?? 10);
            break;

        case 'mark_notification_read':
            $result = $loans->markNotificationAsRead($_POST['notification_id'] ?? 0);
            break;

        // SPLÁTKY
        case 'add_payment':
            $result = $payments->addPayment(
                $_POST['loan_id'] ?? 0,
                $_POST['amount'] ?? 0,
                $_POST['description'] ?? ''
            );
            break;

        case 'confirm_payment':
            $result = $payments->confirmPayment($_POST['payment_id'] ?? 0);
            break;

        case 'reject_payment':
            $result = $payments->rejectPayment(
                $_POST['payment_id'] ?? 0,
                $_POST['reason'] ?? ''
            );
            break;

        case 'get_loan_payments':
            $result = $payments->getLoanPayments($_GET['loan_id'] ?? 0);
            break;

        default:
            $result = ['success' => false, 'message' => 'Neplatná akcia'];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Chyba servera: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
