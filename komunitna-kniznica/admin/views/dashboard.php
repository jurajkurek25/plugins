<?php
/**
 * Admin Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}

// Získanie štatistík
$stats = KK_Admin_Lendings::get_stats();
$books_count = KK_Database::get_count('books');
?>

<div class="wrap kk-admin-wrap">
    <h1><?php _e('Komunitná Knižnica - Dashboard', 'komunitna-kniznica'); ?></h1>

    <div class="kk-admin-stats">
        <div class="kk-stat-box">
            <div class="kk-stat-icon">📚</div>
            <div class="kk-stat-content">
                <h3><?php echo esc_html($books_count); ?></h3>
                <p><?php _e('Celkový počet kníh', 'komunitna-kniznica'); ?></p>
            </div>
        </div>

        <div class="kk-stat-box">
            <div class="kk-stat-icon">🔄</div>
            <div class="kk-stat-content">
                <h3><?php echo esc_html($stats['active_lendings']); ?></h3>
                <p><?php _e('Aktívne požičania', 'komunitna-kniznica'); ?></p>
            </div>
        </div>

        <div class="kk-stat-box">
            <div class="kk-stat-icon">📖</div>
            <div class="kk-stat-content">
                <h3><?php echo esc_html($stats['total_lendings']); ?></h3>
                <p><?php _e('Celkový počet požičaní', 'komunitna-kniznica'); ?></p>
            </div>
        </div>

        <div class="kk-stat-box">
            <div class="kk-stat-icon">💰</div>
            <div class="kk-stat-content">
                <h3><?php echo esc_html(number_format($stats['total_community_income'], 2)); ?> €</h3>
                <p><?php _e('Celkové príjmy komunity', 'komunitna-kniznica'); ?></p>
            </div>
        </div>

        <div class="kk-stat-box">
            <div class="kk-stat-icon">💵</div>
            <div class="kk-stat-content">
                <h3><?php echo esc_html(number_format($stats['total_owner_income'], 2)); ?> €</h3>
                <p><?php _e('Celkové príjmy majiteľov', 'komunitna-kniznica'); ?></p>
            </div>
        </div>
    </div>

    <div class="kk-admin-quick-links">
        <h2><?php _e('Rýchle odkazy', 'komunitna-kniznica'); ?></h2>
        <ul>
            <li><a href="<?php echo admin_url('admin.php?page=kk-books'); ?>"><?php _e('Spravovať knihy', 'komunitna-kniznica'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=kk-lendings'); ?>"><?php _e('Spravovať požičania', 'komunitna-kniznica'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=kk-settings'); ?>"><?php _e('Nastavenia', 'komunitna-kniznica'); ?></a></li>
        </ul>
    </div>
</div>
