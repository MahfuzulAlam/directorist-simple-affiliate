<?php
/**
 * Affiliate Requests Tab Template
 * 
 * @package DirectoristSimpleAffiliate
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$affiliate_manager = \DirectoristSimpleAffiliate\Core\AffiliateManager::get_instance();
$affiliates = $affiliate_manager->get_affiliates('pending');
?>

<div class="dsa-tab-panel">
    <div class="dsa-overview-content">
        <?php if (empty($affiliates)): ?>
            <div class="dsa-notice dsa-notice-info">
                <p><?php esc_html_e('No pending affiliate requests.', 'directorist-simple-affiliate'); ?></p>
            </div>
        <?php else: ?>
            <div class="dsa-requests-list">
                <?php foreach ($affiliates as $affiliate): ?>
                    <?php \DirectoristSimpleAffiliate\Admin\AffiliateRequestsPage::render_affiliate_card($affiliate, $affiliate_manager); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

