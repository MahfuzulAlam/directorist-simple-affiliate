<?php
/**
 * Affiliate Dashboard Tab Template
 * 
 * @package DirectoristSimpleAffiliate
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use DirectoristSimpleAffiliate\Frontend\DashboardTab;
use DirectoristSimpleAffiliate\Core\AffiliateManager;

$affiliate_manager = AffiliateManager::get_instance();
$user_id = get_current_user_id();
$affiliate_codes = DashboardTab::get_user_affiliate_codes();
$affiliate_code = $affiliate_manager->get_affiliate_code($user_id);
?>

<div class="dsa-dashboard-tab">
    <div class="dsa-dashboard-header">
        <h2><?php esc_html_e('Affiliate Program', 'directorist-simple-affiliate'); ?></h2>
        <p class="dsa-description">
            <?php esc_html_e('Generate and manage your affiliate codes and URLs to start earning commissions.', 'directorist-simple-affiliate'); ?>
        </p>
    </div>

    <?php if ($affiliate_code): ?>
        <div class="dsa-default-code-section">
            <h3><?php esc_html_e('Your Default Affiliate Code', 'directorist-simple-affiliate'); ?></h3>
            <div class="dsa-code-display">
                <code class="dsa-code-value"><?php echo esc_html($affiliate_code); ?></code>
                <button type="button" class="dsa-copy-btn" data-code="<?php echo esc_attr($affiliate_code); ?>">
                    <?php esc_html_e('Copy', 'directorist-simple-affiliate'); ?>
                </button>
            </div>
            <div class="dsa-url-display">
                <label><?php esc_html_e('Affiliate URL:', 'directorist-simple-affiliate'); ?></label>
                <div class="dsa-url-wrapper">
                    <input type="text" 
                           class="dsa-affiliate-url" 
                           value="<?php echo esc_attr(DashboardTab::generate_affiliate_url($affiliate_code)); ?>" 
                           readonly>
                    <button type="button" class="dsa-copy-url-btn" data-url="<?php echo esc_attr(DashboardTab::generate_affiliate_url($affiliate_code)); ?>">
                        <?php esc_html_e('Copy URL', 'directorist-simple-affiliate'); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="dsa-codes-section">
        <div class="dsa-section-header">
            <h3><?php esc_html_e('Your Affiliate Codes', 'directorist-simple-affiliate'); ?></h3>
            <button type="button" class="dsa-btn dsa-btn-primary dsa-generate-code-btn">
                <?php esc_html_e('Generate New Code', 'directorist-simple-affiliate'); ?>
            </button>
        </div>

        <!-- Generate Code Form (Hidden by default) -->
        <div class="dsa-generate-form" style="display: none;">
            <form id="dsa-generate-code-form">
                <?php wp_nonce_field('dsa_affiliate_dashboard', 'dsa_affiliate_dashboard_nonce'); ?>
                
                <div class="dsa-form-row">
                    <label for="dsa_code"><?php esc_html_e('Code (leave empty to auto-generate)', 'directorist-simple-affiliate'); ?></label>
                    <input type="text" id="dsa_code" name="code" class="dsa-form-input" placeholder="<?php esc_attr_e('DSA12345678', 'directorist-simple-affiliate'); ?>">
                </div>

                <div class="dsa-form-row">
                    <label for="dsa_type"><?php esc_html_e('Type', 'directorist-simple-affiliate'); ?></label>
                    <select id="dsa_type" name="type" class="dsa-form-select">
                        <option value="custom"><?php esc_html_e('Custom', 'directorist-simple-affiliate'); ?></option>
                        <option value="campaign"><?php esc_html_e('Campaign', 'directorist-simple-affiliate'); ?></option>
                    </select>
                </div>

                <div class="dsa-form-row dsa-campaign-field" style="display: none;">
                    <label for="dsa_campaign_name"><?php esc_html_e('Campaign Name', 'directorist-simple-affiliate'); ?></label>
                    <input type="text" id="dsa_campaign_name" name="campaign_name" class="dsa-form-input">
                </div>

                <div class="dsa-form-row">
                    <label for="dsa_description"><?php esc_html_e('Description (Optional)', 'directorist-simple-affiliate'); ?></label>
                    <textarea id="dsa_description" name="description" class="dsa-form-textarea" rows="3"></textarea>
                </div>

                <div class="dsa-form-row">
                    <label for="dsa_expires_at"><?php esc_html_e('Expires At (Optional)', 'directorist-simple-affiliate'); ?></label>
                    <input type="datetime-local" id="dsa_expires_at" name="expires_at" class="dsa-form-input">
                </div>

                <div class="dsa-form-actions">
                    <button type="submit" class="dsa-btn dsa-btn-primary">
                        <?php esc_html_e('Generate Code', 'directorist-simple-affiliate'); ?>
                    </button>
                    <button type="button" class="dsa-btn dsa-btn-secondary dsa-cancel-form">
                        <?php esc_html_e('Cancel', 'directorist-simple-affiliate'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Codes List -->
        <div class="dsa-codes-list">
            <?php if (empty($affiliate_codes)): ?>
                <div class="dsa-empty-state">
                    <p><?php esc_html_e('No affiliate codes yet. Generate your first code to get started!', 'directorist-simple-affiliate'); ?></p>
                </div>
            <?php else: ?>
                <table class="dsa-codes-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Code', 'directorist-simple-affiliate'); ?></th>
                            <th><?php esc_html_e('Type', 'directorist-simple-affiliate'); ?></th>
                            <th><?php esc_html_e('Campaign', 'directorist-simple-affiliate'); ?></th>
                            <th><?php esc_html_e('Clicks', 'directorist-simple-affiliate'); ?></th>
                            <th><?php esc_html_e('Conversions', 'directorist-simple-affiliate'); ?></th>
                            <th><?php esc_html_e('Status', 'directorist-simple-affiliate'); ?></th>
                            <th><?php esc_html_e('Actions', 'directorist-simple-affiliate'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($affiliate_codes as $code): ?>
                            <tr data-code-id="<?php echo esc_attr($code->id); ?>">
                                <td>
                                    <code class="dsa-code-value"><?php echo esc_html($code->code); ?></code>
                                </td>
                                <td><?php echo esc_html(ucfirst($code->type)); ?></td>
                                <td><?php echo esc_html($code->campaign_name ?: '-'); ?></td>
                                <td><?php echo esc_html($code->clicks); ?></td>
                                <td><?php echo esc_html($code->conversions); ?></td>
                                <td>
                                    <span class="dsa-status-badge dsa-status-<?php echo esc_attr($code->status); ?>">
                                        <?php echo esc_html(ucfirst($code->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="dsa-btn-small dsa-copy-code-btn" data-code="<?php echo esc_attr($code->code); ?>">
                                        <?php esc_html_e('Copy', 'directorist-simple-affiliate'); ?>
                                    </button>
                                    <button type="button" class="dsa-btn-small dsa-copy-url-btn" data-url="<?php echo esc_attr(DashboardTab::generate_affiliate_url($code->code)); ?>">
                                        <?php esc_html_e('Copy URL', 'directorist-simple-affiliate'); ?>
                                    </button>
                                    <?php if ($code->type !== 'default'): ?>
                                        <button type="button" class="dsa-btn-small dsa-delete-code-btn" data-code-id="<?php echo esc_attr($code->id); ?>">
                                            <?php esc_html_e('Delete', 'directorist-simple-affiliate'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

