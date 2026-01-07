<?php
/**
 * Payments Management API
 * Správa splátok pôžičiek
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

class Payments {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->auth = new Auth();
    }

    /**
     * Pridanie splátky
     */
    public function addPayment($loanId, $amount, $description = '') {
        $userId = $this->auth->getUserId();

        if (!$userId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Suma musí byť väčšia ako 0'];
        }

        // Kontrola či existuje pôžička a či je používateľ dlžníkom
        $stmt = $this->db->prepare(
            "SELECT * FROM loans WHERE id = ? AND borrower_id = ? AND status = 'active'"
        );
        $stmt->execute([$loanId, $userId]);
        $loan = $stmt->fetch();

        if (!$loan) {
            return ['success' => false, 'message' => 'Pôžička nebola nájdená alebo nemáte oprávnenie'];
        }

        if ($amount > $loan['remaining_amount']) {
            return [
                'success' => false,
                'message' => 'Suma splátky presahuje zostávajúcu čiastku (' .
                            number_format($loan['remaining_amount'], 2) . ' €)'
            ];
        }

        try {
            $this->db->beginTransaction();

            // Pridanie splátky
            $stmt = $this->db->prepare(
                "INSERT INTO payments (loan_id, amount, description, status)
                 VALUES (?, ?, ?, 'pending')"
            );
            $stmt->execute([$loanId, $amount, $description]);
            $paymentId = $this->db->lastInsertId();

            // Notifikácia pre veriteľa
            $this->createNotification(
                $loan['lender_id'],
                'payment_added',
                'Bola pridaná nová splátka vo výške ' . number_format($amount, 2) . ' € na pôžičku',
                $loanId,
                $paymentId
            );

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Splátka bola pridaná a čaká na potvrdenie',
                'payment_id' => $paymentId
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Add payment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri pridávaní splátky'];
        }
    }

    /**
     * Potvrdenie splátky veriteľom
     */
    public function confirmPayment($paymentId) {
        $userId = $this->auth->getUserId();

        if (!$userId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        // Získanie platby a pôžičky
        $stmt = $this->db->prepare(
            "SELECT p.*, l.lender_id, l.borrower_id, l.remaining_amount
             FROM payments p
             JOIN loans l ON p.loan_id = l.id
             WHERE p.id = ? AND p.status = 'pending'"
        );
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            return ['success' => false, 'message' => 'Platba nebola nájdená'];
        }

        // Kontrola či je používateľ veriteľom
        if ($payment['lender_id'] != $userId) {
            return ['success' => false, 'message' => 'Nemáte oprávnenie potvrdiť túto platbu'];
        }

        try {
            $this->db->beginTransaction();

            // Potvrdenie platby
            $stmt = $this->db->prepare(
                "UPDATE payments SET status = 'confirmed', confirmed_date = NOW(), confirmed_by = ? WHERE id = ?"
            );
            $stmt->execute([$userId, $paymentId]);

            // Aktualizácia zostávajúcej sumy pôžičky
            $newRemainingAmount = $payment['remaining_amount'] - $payment['amount'];
            $newStatus = $newRemainingAmount <= 0 ? 'completed' : 'active';

            $stmt = $this->db->prepare(
                "UPDATE loans SET remaining_amount = ?, status = ?, completed_date = ? WHERE id = ?"
            );
            $completedDate = $newStatus === 'completed' ? date('Y-m-d H:i:s') : null;
            $stmt->execute([$newRemainingAmount, $newStatus, $completedDate, $payment['loan_id']]);

            // Notifikácia pre dlžníka
            $message = 'Vaša splátka vo výške ' . number_format($payment['amount'], 2) . ' € bola potvrdená';
            if ($newStatus === 'completed') {
                $message .= '. Pôžička je kompletne splatená!';
            }

            $this->createNotification(
                $payment['borrower_id'],
                'payment_confirmed',
                $message,
                $payment['loan_id'],
                $paymentId
            );

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Splátka bola potvrdená',
                'loan_completed' => $newStatus === 'completed'
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Confirm payment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri potvrdzovaní splátky'];
        }
    }

    /**
     * Zamietnutie splátky veriteľom
     */
    public function rejectPayment($paymentId, $reason = '') {
        $userId = $this->auth->getUserId();

        if (!$userId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        // Získanie platby a pôžičky
        $stmt = $this->db->prepare(
            "SELECT p.*, l.lender_id, l.borrower_id
             FROM payments p
             JOIN loans l ON p.loan_id = l.id
             WHERE p.id = ? AND p.status = 'pending'"
        );
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            return ['success' => false, 'message' => 'Platba nebola nájdená'];
        }

        // Kontrola či je používateľ veriteľom
        if ($payment['lender_id'] != $userId) {
            return ['success' => false, 'message' => 'Nemáte oprávnenie zamietnuť túto platbu'];
        }

        try {
            $this->db->beginTransaction();

            // Zamietnutie platby
            $stmt = $this->db->prepare(
                "UPDATE payments SET status = 'rejected', confirmed_by = ? WHERE id = ?"
            );
            $stmt->execute([$userId, $paymentId]);

            // Notifikácia pre dlžníka
            $message = 'Vaša splátka vo výške ' . number_format($payment['amount'], 2) . ' € bola zamietnutá';
            if ($reason) {
                $message .= '. Dôvod: ' . $reason;
            }

            $this->createNotification(
                $payment['borrower_id'],
                'payment_rejected',
                $message,
                $payment['loan_id'],
                $paymentId
            );

            $this->db->commit();

            return ['success' => true, 'message' => 'Splátka bola zamietnutá'];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Reject payment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri zamietaní splátky'];
        }
    }

    /**
     * Získanie všetkých splátok pre pôžičku
     */
    public function getLoanPayments($loanId) {
        $userId = $this->auth->getUserId();

        if (!$userId) {
            return ['success' => false, 'message' => 'Nie ste prihlásený'];
        }

        // Kontrola prístupu k pôžičke
        $stmt = $this->db->prepare(
            "SELECT * FROM loans WHERE id = ? AND (lender_id = ? OR borrower_id = ?)"
        );
        $stmt->execute([$loanId, $userId, $userId]);
        $loan = $stmt->fetch();

        if (!$loan) {
            return ['success' => false, 'message' => 'Pôžička nebola nájdená alebo nemáte oprávnenie'];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT p.*, u.username as confirmed_by_username, u.full_name as confirmed_by_name
                 FROM payments p
                 LEFT JOIN users u ON p.confirmed_by = u.id
                 WHERE p.loan_id = ?
                 ORDER BY p.payment_date DESC"
            );
            $stmt->execute([$loanId]);
            $payments = $stmt->fetchAll();

            return ['success' => true, 'payments' => $payments];
        } catch (PDOException $e) {
            error_log("Get payments error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chyba pri načítaní splátok'];
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
}
