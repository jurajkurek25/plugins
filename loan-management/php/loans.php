<?php
/**
 * Loans Management API
 * Správa pôžičiek a žiadostí
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

class Loans {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->auth = new Auth();
    }

    /**
     * Vytvorenie žiadosti o pôžičku
     */
    public function createLoanRequest($lenderId, $amount, $description = '') {
        $borrowerId = $this->auth->getUserId();

        if (!$borrowerId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        if ($borrowerId == $lenderId) {
            return ['success' => false, 'message' => 'Nemôžete požičať sami sebe'];
        }

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Suma musí byť väčšia ako 0'];
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO loans (lender_id, borrower_id, amount, remaining_amount, description, status)
                 VALUES (?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([$lenderId, $borrowerId, $amount, $amount, $description]);

            $loanId = $this->db->lastInsertId();

            // Vytvorenie notifikácie pre veriteľa
            $this->createNotification(
                $lenderId,
                'loan_request',
                'Máte novú žiadosť o pôžičku vo výške ' . number_format($amount, 2) . ' €',
                $loanId
            );

            return [
                'success' => true,
                'message' => 'Žiadosť o pôžičku bola odoslaná',
                'loan_id' => $loanId
            ];
        } catch (PDOException $e) {
            error_log("Loan request error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri vytváraní žiadosti'];
        }
    }

    /**
     * Schválenie žiadosti o pôžičku
     */
    public function approveLoan($loanId) {
        $userId = $this->auth->getUserId();

        if (!$userId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        // Kontrola či je používateľ veriteľom
        $stmt = $this->db->prepare("SELECT * FROM loans WHERE id = ? AND lender_id = ? AND status = 'pending'");
        $stmt->execute([$loanId, $userId]);
        $loan = $stmt->fetch();

        if (!$loan) {
            return ['success' => false, 'message' => 'Pôžička nebola nájdená alebo nemáte oprávnenie'];
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE loans SET status = 'active', approved_date = NOW() WHERE id = ?"
            );
            $stmt->execute([$loanId]);

            // Notifikácia pre dlžníka
            $this->createNotification(
                $loan['borrower_id'],
                'loan_approved',
                'Vaša žiadosť o pôžičku vo výške ' . number_format($loan['amount'], 2) . ' € byla schválená',
                $loanId
            );

            return ['success' => true, 'message' => 'Pôžička bola schválená'];
        } catch (PDOException $e) {
            error_log("Loan approval error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri schvaľovaní pôžičky'];
        }
    }

    /**
     * Odmietnutie žiadosti o pôžičku
     */
    public function rejectLoan($loanId) {
        $userId = $this->auth->getUserId();

        if (!$userId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        $stmt = $this->db->prepare("SELECT * FROM loans WHERE id = ? AND lender_id = ? AND status = 'pending'");
        $stmt->execute([$loanId, $userId]);
        $loan = $stmt->fetch();

        if (!$loan) {
            return ['success' => false, 'message' => 'Pôžička nebola nájdená alebo nemáte oprávnenie'];
        }

        try {
            $stmt = $this->db->prepare("UPDATE loans SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$loanId]);

            // Notifikácia pre dlžníka
            $this->createNotification(
                $loan['borrower_id'],
                'loan_rejected',
                'Vaša žiadosť o pôžičku vo výške ' . number_format($loan['amount'], 2) . ' € bola zamietnutá',
                $loanId
            );

            return ['success' => true, 'message' => 'Pôžička bola zamietnutá'];
        } catch (PDOException $e) {
            error_log("Loan rejection error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri zamietaní pôžičky'];
        }
    }

    /**
     * Získanie všetkých pôžičiek pre prihláseného používateľa
     */
    public function getUserLoans() {
        $userId = $this->auth->getUserId();

        if (!$userId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT l.*,
                    lender.username as lender_username, lender.full_name as lender_name,
                    borrower.username as borrower_username, borrower.full_name as borrower_name
                 FROM loans l
                 JOIN users lender ON l.lender_id = lender.id
                 JOIN users borrower ON l.borrower_id = borrower.id
                 WHERE l.lender_id = ? OR l.borrower_id = ?
                 ORDER BY l.created_at DESC"
            );
            $stmt->execute([$userId, $userId]);
            $loans = $stmt->fetchAll();

            return ['success' => true, 'loans' => $loans];
        } catch (PDOException $e) {
            error_log("Get loans error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri načítaní pôžičiek'];
        }
    }

    /**
     * Získanie detailov pôžičky
     */
    public function getLoanDetails($loanId) {
        $userId = $this->auth->getUserId();

        if (!$userId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT l.*,
                    lender.username as lender_username, lender.full_name as lender_name,
                    borrower.username as borrower_username, borrower.full_name as borrower_name
                 FROM loans l
                 JOIN users lender ON l.lender_id = lender.id
                 JOIN users borrower ON l.borrower_id = borrower.id
                 WHERE l.id = ? AND (l.lender_id = ? OR l.borrower_id = ?)"
            );
            $stmt->execute([$loanId, $userId, $userId]);
            $loan = $stmt->fetch();

            if (!$loan) {
                return ['success' => false, 'message' => 'Pôžička nebola nájdená'];
            }

            // Získanie platieb
            $stmt = $this->db->prepare(
                "SELECT p.*, u.username as confirmed_by_username
                 FROM payments p
                 LEFT JOIN users u ON p.confirmed_by = u.id
                 WHERE p.loan_id = ?
                 ORDER BY p.payment_date DESC"
            );
            $stmt->execute([$loanId]);
            $payments = $stmt->fetchAll();

            return [
                'success' => true,
                'loan' => $loan,
                'payments' => $payments
            ];
        } catch (PDOException $e) {
            error_log("Get loan details error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri načítaní detailov pôžičky'];
        }
    }

    /**
     * Získanie všetkých používateľov (pre výber veriteľa)
     */
    public function getAllUsers() {
        $userId = $this->auth->getUserId();

        if (!$userId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, full_name FROM users WHERE id != ? ORDER BY full_name"
            );
            $stmt->execute([$userId]);
            $users = $stmt->fetchAll();

            return ['success' => true, 'users' => $users];
        } catch (PDOException $e) {
            error_log("Get users error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri načítaní používateľov'];
        }
    }

    /**
     * Vytvorenie notifikácie
     */
    private function createNotification($userId, $type, $message, $loanId = null, $paymentId = null) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO notifications (user_id, type, message, related_loan_id, related_payment_id)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $type, $message, $loanId, $paymentId]);
            return true;
        } catch (PDOException $e) {
            error_log("Create notification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Získanie notifikácií pre používateľa
     */
    public function getNotifications($limit = 10) {
        $userId = $this->auth->getUserId();

        if (!$userId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM notifications
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$userId, $limit]);
            $notifications = $stmt->fetchAll();

            return ['success' => true, 'notifications' => $notifications];
        } catch (PDOException $e) {
            error_log("Get notifications error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri načítaní notifikácií'];
        }
    }

    /**
     * Označenie notifikácie ako prečítanej
     */
    public function markNotificationAsRead($notificationId) {
        $userId = $this->auth->getUserId();

        if (!$userId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$notificationId, $userId]);

            return ['success' => true, 'message' => 'Notifikácia označená ako prečítaná'];
        } catch (PDOException $e) {
            error_log("Mark notification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri označení notifikácie'];
        }
    }
}
