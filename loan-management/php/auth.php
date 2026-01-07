<?php
/**
 * Authentication System
 * Správa autentifikácie používateľov
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/config.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->startSession();
    }

    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'secure' => SESSION_SECURE,
                'httponly' => SESSION_HTTPONLY,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
    }

    /**
     * Registrácia nového používateľa
     */
    public function register($username, $email, $password, $fullName) {
        // Validácia
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return [
                'success' => false,
                'message' => 'Heslo musí mať aspoň ' . PASSWORD_MIN_LENGTH . ' znakov'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Neplatný formát emailu'
            ];
        }

        // Kontrola či používateľ už existuje
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Používateľské meno alebo email už existuje'
            ];
        }

        // Vytvorenie používateľa
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$username, $email, $passwordHash, $fullName]);

            return [
                'success' => true,
                'message' => 'Registrácia úspešná',
                'user_id' => $this->db->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Chyba pri registrácii'
            ];
        }
    }

    /**
     * Prihlásenie používateľa
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Nesprávne používateľské meno alebo heslo'
            ];
        }

        // Nastavenie session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['logged_in'] = true;

        return [
            'success' => true,
            'message' => 'Prihlásenie úspešné',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email']
            ]
        ];
    }

    /**
     * Odhlásenie používateľa
     */
    public function logout() {
        session_destroy();
        return [
            'success' => true,
            'message' => 'Odhlásenie úspešné'
        ];
    }

    /**
     * Kontrola či je používateľ prihlásený
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Získanie ID prihláseného používateľa
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Získanie údajov prihláseného používateľa
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT id, username, email, full_name, created_at FROM users WHERE id = ?");
        $stmt->execute([$this->getUserId()]);
        return $stmt->fetch();
    }

    /**
     * Vyžaduje prihlásenie - presmeruje ak používateľ nie je prihlásený
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
}
