<?php
/**
 * Template: Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();

// Získanie kníh používateľa
$my_books = BBK_Book::get_instance()->get_user_books($user_id);

// Získanie požičaní
$borrowed_by_others = BBK_Lending::get_instance()->get_lender_lendings($user_id, 'active');
$borrowed_by_me = BBK_Lending::get_instance()->get_borrower_lendings($user_id, 'active');

// Štatistiky
$total_my_books = count($my_books);
$total_borrowed_by_others = count($borrowed_by_others);
$total_borrowed_by_me = count($borrowed_by_me);

// Výpočet príjmov
$total_earnings = 0;
$all_my_lendings = BBK_Lending::get_instance()->get_lender_lendings($user_id);
foreach ($all_my_lendings as $lending) {
    $total_earnings += $lending->owner_commission;
}
?>

<div class="bbk-dashboard-wrapper">
    <div class="bbk-dashboard-header">
        <h2>📅 <?php _e('Môj Dashboard', 'buddyboss-kniznica'); ?></h2>
        <p><?php _e('Sleduj svoje knihy a požičania na jednom mieste.', 'buddyboss-kniznica'); ?></p>
    </div>

    <!-- Štatistiky -->
    <div class="bbk-stats-grid">
        <div class="bbk-stat-card">
            <div class="bbk-stat-icon">📚</div>
            <div class="bbk-stat-value"><?php echo $total_my_books; ?></div>
            <div class="bbk-stat-label"><?php _e('Moje knihy', 'buddyboss-kniznica'); ?></div>
        </div>

        <div class="bbk-stat-card">
            <div class="bbk-stat-icon">🤝</div>
            <div class="bbk-stat-value"><?php echo $total_borrowed_by_others; ?></div>
            <div class="bbk-stat-label"><?php _e('Požičané iným', 'buddyboss-kniznica'); ?></div>
        </div>

        <div class="bbk-stat-card">
            <div class="bbk-stat-icon">📖</div>
            <div class="bbk-stat-value"><?php echo $total_borrowed_by_me; ?></div>
            <div class="bbk-stat-label"><?php _e('Požičané odo mňa', 'buddyboss-kniznica'); ?></div>
        </div>

        <div class="bbk-stat-card bbk-stat-earnings">
            <div class="bbk-stat-icon">💰</div>
            <div class="bbk-stat-value"><?php echo number_format($total_earnings, 2); ?> €</div>
            <div class="bbk-stat-label"><?php _e('Celkový príjem (70%)', 'buddyboss-kniznica'); ?></div>
        </div>
    </div>

    <!-- Moje knihy -->
    <div class="bbk-dashboard-section">
        <h3>📚 <?php _e('Moje knihy', 'buddyboss-kniznica'); ?></h3>
        <?php if (empty($my_books)): ?>
            <p><?php _e('Nemáte žiadne knihy.', 'buddyboss-kniznica'); ?> <a href="<?php echo esc_url(add_query_arg('action', 'add-book')); ?>"><?php _e('Pridať knihu', 'buddyboss-kniznica'); ?></a></p>
        <?php else: ?>
            <div class="bbk-books-list">
                <?php foreach ($my_books as $book): ?>
                    <div class="bbk-book-item">
                        <div class="bbk-book-item-image">
                            <?php if ($book->image_url): ?>
                                <img src="<?php echo esc_url($book->image_url); ?>" alt="<?php echo esc_attr($book->title); ?>">
                            <?php else: ?>
                                <div class="bbk-book-placeholder">📚</div>
                            <?php endif; ?>
                        </div>
                        <div class="bbk-book-item-info">
                            <h4><?php echo esc_html($book->title); ?></h4>
                            <p><?php echo esc_html($book->author); ?></p>
                            <span class="bbk-status bbk-status-<?php echo esc_attr($book->status); ?>">
                                <?php echo esc_html(ucfirst($book->status)); ?>
                            </span>
                            <span class="bbk-price">
                                <?php if ($book->lending_price > 0): ?>
                                    <?php echo number_format($book->lending_price, 2); ?> €
                                <?php else: ?>
                                    <?php _e('Zadarmo', 'buddyboss-kniznica'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Požičané iným -->
    <div class="bbk-dashboard-section">
        <h3>🤝 <?php _e('Požičané iným (doručovacie údaje)', 'buddyboss-kniznica'); ?></h3>
        <?php if (empty($borrowed_by_others)): ?>
            <p><?php _e('Nikto si momentálne nepožičal vaše knihy.', 'buddyboss-kniznica'); ?></p>
        <?php else: ?>
            <div class="bbk-lendings-list">
                <?php foreach ($borrowed_by_others as $lending): ?>
                    <?php
                    $book = BBK_Book::get_instance()->get_book($lending->book_id);
                    $borrower = get_user_by('id', $lending->borrower_id);
                    $days_left = ceil((strtotime($lending->due_date) - time()) / 86400);
                    ?>
                    <div class="bbk-lending-item">
                        <div class="bbk-lending-info">
                            <h4><?php echo esc_html($book->title); ?></h4>
                            <p>👤 <?php echo esc_html($borrower->display_name); ?></p>
                            <p>📅 <?php _e('Vrátiť do:', 'buddyboss-kniznica'); ?> <?php echo date('d.m.Y', strtotime($lending->due_date)); ?>
                                (<?php echo $days_left; ?> <?php _e('dní', 'buddyboss-kniznica'); ?>)</p>

                            <?php if ($lending->lending_price > 0): ?>
                                <p class="bbk-commission">💰 <?php _e('Vaša provízia:', 'buddyboss-kniznica'); ?> <strong><?php echo number_format($lending->owner_commission, 2); ?> € (70%)</strong></p>
                            <?php endif; ?>
                        </div>

                        <?php if ($lending->shipping_name): ?>
                            <div class="bbk-shipping-info">
                                <h5>📦 <?php _e('Doručovacie údaje:', 'buddyboss-kniznica'); ?></h5>
                                <p><strong><?php echo esc_html($lending->shipping_name); ?></strong></p>
                                <p><?php echo esc_html($lending->shipping_address); ?></p>
                                <p><?php echo esc_html($lending->shipping_city); ?>, <?php echo esc_html($lending->shipping_postcode); ?></p>
                                <p><?php echo esc_html($lending->shipping_country); ?></p>
                                <p>📞 <?php echo esc_html($lending->shipping_phone); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Požičané odo mňa -->
    <div class="bbk-dashboard-section">
        <h3>📖 <?php _e('Požičané odo mňa', 'buddyboss-kniznica'); ?></h3>
        <?php if (empty($borrowed_by_me)): ?>
            <p><?php _e('Nemáte žiadne požičané knihy.', 'buddyboss-kniznica'); ?></p>
        <?php else: ?>
            <div class="bbk-lendings-list">
                <?php foreach ($borrowed_by_me as $lending): ?>
                    <?php
                    $book = BBK_Book::get_instance()->get_book($lending->book_id);
                    $lender = get_user_by('id', $lending->lender_id);
                    $days_left = ceil((strtotime($lending->due_date) - time()) / 86400);
                    ?>
                    <div class="bbk-lending-item">
                        <div class="bbk-lending-info">
                            <h4><?php echo esc_html($book->title); ?></h4>
                            <p>👤 <?php _e('Od:', 'buddyboss-kniznica'); ?> <?php echo esc_html($lender->display_name); ?></p>
                            <p>📅 <?php _e('Vrátiť do:', 'buddyboss-kniznica'); ?> <?php echo date('d.m.Y', strtotime($lending->due_date)); ?>
                                <span class="bbk-days-left <?php echo $days_left < 3 ? 'bbk-urgent' : ''; ?>">
                                    (<?php echo $days_left; ?> <?php _e('dní', 'buddyboss-kniznica'); ?>)
                                </span>
                            </p>
                            <button class="bbk-btn bbk-btn-primary bbk-return-book" data-lending-id="<?php echo $lending->id; ?>">
                                <?php _e('Vrátiť knihu', 'buddyboss-kniznica'); ?>
                            </button>
                            <a href="<?php echo esc_url(add_query_arg(array('action' => 'rate', 'lending_id' => $lending->id))); ?>" class="bbk-btn bbk-btn-secondary">
                                ⭐ <?php _e('Ohodnotiť', 'buddyboss-kniznica'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
