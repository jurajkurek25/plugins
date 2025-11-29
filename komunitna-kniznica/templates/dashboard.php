<?php
/**
 * Template: Používateľský Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$dashboard_data = KK_Dashboard::get_instance()->get_dashboard_data($user_id);
?>

<div class="kk-dashboard-wrapper">
    <h2><?php _e('Môj Dashboard', 'komunitna-kniznica'); ?></h2>

    <!-- Štatistiky -->
    <div class="kk-dashboard-stats">
        <div class="kk-stat-box">
            <div class="kk-stat-icon">📚</div>
            <div class="kk-stat-content">
                <h3><?php echo esc_html($dashboard_data['stats']['my_books_count']); ?></h3>
                <p><?php _e('Moje knihy', 'komunitna-kniznica'); ?></p>
            </div>
        </div>

        <div class="kk-stat-box">
            <div class="kk-stat-icon">📤</div>
            <div class="kk-stat-content">
                <h3><?php echo esc_html($dashboard_data['stats']['lent_count']); ?></h3>
                <p><?php _e('Požičané iným', 'komunitna-kniznica'); ?></p>
            </div>
        </div>

        <div class="kk-stat-box">
            <div class="kk-stat-icon">📥</div>
            <div class="kk-stat-content">
                <h3><?php echo esc_html($dashboard_data['stats']['borrowed_count']); ?></h3>
                <p><?php _e('Požičané odo mňa', 'komunitna-kniznica'); ?></p>
            </div>
        </div>

        <div class="kk-stat-box">
            <div class="kk-stat-icon">💰</div>
            <div class="kk-stat-content">
                <h3><?php echo esc_html(number_format($dashboard_data['stats']['total_earnings'], 2)); ?> €</h3>
                <p><?php _e('Celkové príjmy', 'komunitna-kniznica'); ?></p>
            </div>
        </div>
    </div>

    <!-- Notifikácie -->
    <?php if (!empty($dashboard_data['notifications'])): ?>
        <div class="kk-notifications-section">
            <h3><?php _e('Najnovšie notifikácie', 'komunitna-kniznica'); ?></h3>
            <div class="kk-notifications-list">
                <?php foreach ($dashboard_data['notifications'] as $notif): ?>
                    <div class="kk-notification <?php echo $notif->is_read ? '' : 'kk-unread'; ?>">
                        <h4><?php echo esc_html($notif->title); ?></h4>
                        <p><?php echo esc_html($notif->message); ?></p>
                        <span class="kk-notif-time"><?php echo esc_html(human_time_diff(strtotime($notif->created_at), current_time('timestamp'))); ?> <?php _e('dozadu', 'komunitna-kniznica'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="kk-dashboard-tabs">
        <div class="kk-tabs-nav">
            <button class="kk-tab-btn active" data-tab="my-books"><?php _e('Moje knihy', 'komunitna-kniznica'); ?></button>
            <button class="kk-tab-btn" data-tab="lent-out"><?php _e('Požičané iným', 'komunitna-kniznica'); ?></button>
            <button class="kk-tab-btn" data-tab="borrowed"><?php _e('Požičané odo mňa', 'komunitna-kniznica'); ?></button>
        </div>

        <!-- Tab 1: Moje knihy -->
        <div class="kk-tab-content active" id="tab-my-books">
            <h3><?php _e('Moje knihy', 'komunitna-kniznica'); ?></h3>
            <?php if (empty($dashboard_data['my_books'])): ?>
                <p><?php _e('Zatiaľ ste nepridali žiadne knihy.', 'komunitna-kniznica'); ?></p>
            <?php else: ?>
                <div class="kk-books-table">
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e('Názov', 'komunitna-kniznica'); ?></th>
                                <th><?php _e('Autor', 'komunitna-kniznica'); ?></th>
                                <th><?php _e('Žáner', 'komunitna-kniznica'); ?></th>
                                <th><?php _e('Cena', 'komunitna-kniznica'); ?></th>
                                <th><?php _e('Status', 'komunitna-kniznica'); ?></th>
                                <th><?php _e('Akcie', 'komunitna-kniznica'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboard_data['my_books'] as $book): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($book->title); ?></strong></td>
                                    <td><?php echo esc_html($book->author); ?></td>
                                    <td><?php echo esc_html($book->genre); ?></td>
                                    <td><?php echo $book->lending_price > 0 ? esc_html(number_format($book->lending_price, 2)) . ' €' : __('Zadarmo', 'komunitna-kniznica'); ?></td>
                                    <td><span class="kk-status kk-status-<?php echo esc_attr($book->status); ?>"><?php echo esc_html(ucfirst($book->status)); ?></span></td>
                                    <td>
                                        <button class="kk-btn kk-btn-small kk-btn-edit" data-book-id="<?php echo esc_attr($book->id); ?>"><?php _e('Upraviť', 'komunitna-kniznica'); ?></button>
                                        <button class="kk-btn kk-btn-small kk-btn-delete" data-book-id="<?php echo esc_attr($book->id); ?>"><?php _e('Zmazať', 'komunitna-kniznica'); ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 2: Požičané iným -->
        <div class="kk-tab-content" id="tab-lent-out">
            <h3><?php _e('Knihy požičané iným', 'komunitna-kniznica'); ?></h3>
            <?php if (empty($dashboard_data['books_lent_to_others'])): ?>
                <p><?php _e('Momentálne nemáte žiadne knihy požičané iným.', 'komunitna-kniznica'); ?></p>
            <?php else: ?>
                <div class="kk-lendings-list">
                    <?php foreach ($dashboard_data['books_lent_to_others'] as $lending): ?>
                        <div class="kk-lending-card">
                            <div class="kk-lending-header">
                                <h4><?php echo esc_html($lending->book_title); ?></h4>
                                <span class="kk-lending-author"><?php echo esc_html($lending->book_author); ?></span>
                            </div>
                            <div class="kk-lending-details">
                                <p><strong><?php _e('Požičiavajúci:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html($lending->borrower_name); ?></p>
                                <p><strong><?php _e('Dátum požičania:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html(date('d.m.Y', strtotime($lending->start_date))); ?></p>
                                <p><strong><?php _e('Dátum vrátenia:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html(date('d.m.Y', strtotime($lending->due_date))); ?></p>
                                <?php if ($lending->lending_price > 0): ?>
                                    <p><strong><?php _e('Cena požičania:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html(number_format($lending->lending_price, 2)); ?> €</p>
                                    <p><strong><?php _e('Vaša provízia:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html(number_format($lending->owner_commission, 2)); ?> €</p>
                                <?php endif; ?>

                                <!-- DORUČOVACIE ÚDAJE - Zlatý box -->
                                <?php if ($lending->shipping_name): ?>
                                    <div class="kk-shipping-info">
                                        <h5><?php _e('DORUČOVACIE ÚDAJE:', 'komunitna-kniznica'); ?></h5>
                                        <p><strong><?php _e('Meno:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html($lending->shipping_name); ?></p>
                                        <p><strong><?php _e('Adresa:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html($lending->shipping_address); ?></p>
                                        <p><strong><?php _e('Mesto:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html($lending->shipping_city); ?></p>
                                        <p><strong><?php _e('PSČ:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html($lending->shipping_postcode); ?></p>
                                        <p><strong><?php _e('Krajina:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html($lending->shipping_country); ?></p>
                                        <p><strong><?php _e('Telefón:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html($lending->shipping_phone); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 3: Požičané odo mňa -->
        <div class="kk-tab-content" id="tab-borrowed">
            <h3><?php _e('Knihy požičané odo mňa', 'komunitna-kniznica'); ?></h3>
            <?php if (empty($dashboard_data['books_borrowed'])): ?>
                <p><?php _e('Momentálne nemáte požičané žiadne knihy.', 'komunitna-kniznica'); ?></p>
            <?php else: ?>
                <div class="kk-lendings-list">
                    <?php foreach ($dashboard_data['books_borrowed'] as $lending): ?>
                        <div class="kk-lending-card">
                            <div class="kk-lending-header">
                                <h4><?php echo esc_html($lending->book_title); ?></h4>
                                <span class="kk-lending-author"><?php echo esc_html($lending->book_author); ?></span>
                            </div>
                            <div class="kk-lending-details">
                                <p><strong><?php _e('Majiteľ:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html($lending->lender_name); ?></p>
                                <p><strong><?php _e('Dátum požičania:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html(date('d.m.Y', strtotime($lending->start_date))); ?></p>
                                <p><strong><?php _e('Dátum vrátenia:', 'komunitna-kniznica'); ?></strong> <?php echo esc_html(date('d.m.Y', strtotime($lending->due_date))); ?></p>
                                <p><strong><?php _e('Status:', 'komunitna-kniznica'); ?></strong> <span class="kk-status kk-status-<?php echo esc_attr($lending->status); ?>"><?php echo esc_html(ucfirst($lending->status)); ?></span></p>

                                <?php if ($lending->status === 'active'): ?>
                                    <button class="kk-btn kk-btn-primary kk-return-book" data-lending-id="<?php echo esc_attr($lending->id); ?>">
                                        <?php _e('Vrátiť knihu', 'komunitna-kniznica'); ?>
                                    </button>
                                <?php endif; ?>

                                <?php if ($lending->status === 'returned'): ?>
                                    <?php
                                    $can_rate = KK_Rating::get_instance()->can_rate_book($lending->book_id, $user_id);
                                    if ($can_rate): ?>
                                        <button class="kk-btn kk-btn-secondary kk-rate-book" data-lending-id="<?php echo esc_attr($lending->id); ?>">
                                            <?php _e('Ohodnotiť knihu', 'komunitna-kniznica'); ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
