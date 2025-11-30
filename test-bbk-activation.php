<?php
/**
 * Plugin Name: Test BBK Activation
 * Description: Spustí BBK aktiváciu a zachytí chyby
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function test_bbk_activation() {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $errors = array();

    try {
        // Načítaj install.php
        $install_file = WP_PLUGIN_DIR . '/buddyboss-kniznica/includes/class-bbk-install.php';

        if (!file_exists($install_file)) {
            $errors[] = 'ERROR: Súbor neexistuje: ' . $install_file;
        } else {
            $errors[] = 'OK: Súbor existuje';

            require_once $install_file;
            $errors[] = 'OK: Súbor načítaný';

            if (!class_exists('BBK_Install')) {
                $errors[] = 'ERROR: Trieda BBK_Install neexistuje';
            } else {
                $errors[] = 'OK: Trieda BBK_Install existuje';

                // Skús zavolať activate
                BBK_Install::activate();
                $errors[] = 'OK: Aktivácia prebehla úspešne';
            }
        }
    } catch (Exception $e) {
        $errors[] = 'EXCEPTION: ' . $e->getMessage();
    } catch (Error $e) {
        $errors[] = 'FATAL ERROR: ' . $e->getMessage();
    }

    update_option('test_bbk_activation_result', $errors);
}

add_action('admin_init', function() {
    if (isset($_GET['test_bbk_activation'])) {
        test_bbk_activation();
    }
});

add_action('admin_notices', function() {
    $results = get_option('test_bbk_activation_result', array());
    if (!empty($results)) {
        echo '<div class="notice notice-info"><h3>BBK Activation Test:</h3><ul>';
        foreach ($results as $result) {
            echo '<li>' . esc_html($result) . '</li>';
        }
        echo '</ul>';
        echo '<p><a href="' . admin_url('index.php?test_bbk_activation=1') . '" class="button">Spustiť test znova</a></p>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-warning"><p><a href="' . admin_url('index.php?test_bbk_activation=1') . '" class="button">Spustiť BBK Activation Test</a></p></div>';
    }
});
