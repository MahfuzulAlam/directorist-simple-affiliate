<?php
/**
 * Affiliate Settings Tab Template
 * 
 * @package DirectoristSimpleAffiliate
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use DirectoristSimpleAffiliate\Core\AffiliateManager;

$affiliate_manager = AffiliateManager::get_instance();
$user_id = get_current_user_id();
$affiliate_data = $affiliate_manager->get_affiliate_data($user_id);

if (!$affiliate_data) {
    return;
}

$payment_method = $affiliate_data['payment_method'] ?? '';
$website = $affiliate_data['website'] ?? '';
$phone = $affiliate_data['phone'] ?? '';
$paypal_email = $affiliate_data['paypal_email'] ?? '';
$bank_details = $affiliate_data['bank_details'] ?? '';
?>

<div class="dsa-dashboard-tab">
    <div class="dsa-dashboard-header">
        <h2><?php esc_html_e('Affiliate Settings', 'directorist-simple-affiliate'); ?></h2>
        <p class="dsa-description">
            <?php esc_html_e('Update your affiliate information and payment details.', 'directorist-simple-affiliate'); ?>
        </p>
    </div>

    <div class="dsa-settings-form-wrapper">
        <form id="dsa-affiliate-settings-form" class="dsa-settings-form">
            <?php wp_nonce_field('dsa_affiliate_dashboard', 'dsa_affiliate_dashboard_nonce'); ?>

            <div class="dsa-form-section">
                <h3 class="dsa-section-title"><?php esc_html_e('Payment Information', 'directorist-simple-affiliate'); ?></h3>

                <div class="dsa-form-row">
                    <label for="dsa_settings_payment_method" class="dsa-form-label">
                        <?php esc_html_e('Payment Method', 'directorist-simple-affiliate'); ?>
                        <span class="dsa-required">*</span>
                    </label>
                    <select
                        id="dsa_settings_payment_method"
                        name="payment_method"
                        class="dsa-form-select"
                        required
                    >
                        <option value=""><?php esc_html_e('Select Payment Method', 'directorist-simple-affiliate'); ?></option>
                        <option value="PayPal" <?php selected($payment_method, 'PayPal'); ?>>
                            <?php esc_html_e('PayPal', 'directorist-simple-affiliate'); ?>
                        </option>
                        <option value="Bank Transfer" <?php selected($payment_method, 'Bank Transfer'); ?>>
                            <?php esc_html_e('Bank Transfer', 'directorist-simple-affiliate'); ?>
                        </option>
                    </select>
                </div>

                <!-- PayPal Email Field -->
                <div class="dsa-form-row dsa-payment-field dsa-paypal-field" style="display: <?php echo $payment_method === 'PayPal' ? 'block' : 'none'; ?>;">
                    <label for="dsa_settings_paypal_email" class="dsa-form-label">
                        <?php esc_html_e('PayPal Email', 'directorist-simple-affiliate'); ?>
                        <span class="dsa-required">*</span>
                    </label>
                    <input
                        type="email"
                        id="dsa_settings_paypal_email"
                        name="paypal_email"
                        class="dsa-form-input"
                        value="<?php echo esc_attr($paypal_email); ?>"
                        placeholder="<?php esc_attr_e('your-email@example.com', 'directorist-simple-affiliate'); ?>"
                    />
                    <small class="dsa-form-help">
                        <?php esc_html_e('This email will be used for PayPal payments.', 'directorist-simple-affiliate'); ?>
                    </small>
                </div>

                <!-- Bank Transfer Details Field -->
                <div class="dsa-form-row dsa-payment-field dsa-bank-transfer-field" style="display: <?php echo $payment_method === 'Bank Transfer' ? 'block' : 'none'; ?>;">
                    <label for="dsa_settings_bank_details" class="dsa-form-label">
                        <?php esc_html_e('Bank Transfer Details', 'directorist-simple-affiliate'); ?>
                        <span class="dsa-required">*</span>
                    </label>
                    <textarea
                        id="dsa_settings_bank_details"
                        name="bank_details"
                        class="dsa-form-textarea"
                        rows="5"
                        placeholder="<?php esc_attr_e('Please provide your bank account details (Account Name, Account Number, Bank Name, IBAN/SWIFT, etc.)', 'directorist-simple-affiliate'); ?>"
                    ><?php echo esc_textarea($bank_details); ?></textarea>
                    <small class="dsa-form-help">
                        <?php esc_html_e('Please provide all necessary bank account information for wire transfers.', 'directorist-simple-affiliate'); ?>
                    </small>
                </div>
            </div>

            <div class="dsa-form-section">
                <h3 class="dsa-section-title"><?php esc_html_e('Contact Information', 'directorist-simple-affiliate'); ?></h3>

                <div class="dsa-form-row">
                    <label for="dsa_settings_website" class="dsa-form-label">
                        <?php esc_html_e('Website/Social Media URL', 'directorist-simple-affiliate'); ?>
                        <span class="dsa-required">*</span>
                    </label>
                    <input
                        type="url"
                        id="dsa_settings_website"
                        name="website"
                        class="dsa-form-input"
                        value="<?php echo esc_attr($website); ?>"
                        placeholder="<?php esc_attr_e('https://example.com or social media profile', 'directorist-simple-affiliate'); ?>"
                        required
                    />
                </div>

                <div class="dsa-form-row">
                    <label for="dsa_settings_phone" class="dsa-form-label">
                        <?php esc_html_e('Phone Number', 'directorist-simple-affiliate'); ?>
                    </label>
                    <input
                        type="text"
                        id="dsa_settings_phone"
                        name="phone"
                        class="dsa-form-input"
                        value="<?php echo esc_attr($phone); ?>"
                        placeholder="<?php esc_attr_e('Optional', 'directorist-simple-affiliate'); ?>"
                    />
                </div>
            </div>

            <div class="dsa-form-actions">
                <button type="submit" class="dsa-btn dsa-btn-primary">
                    <?php esc_html_e('Save Settings', 'directorist-simple-affiliate'); ?>
                </button>
                <span class="dsa-form-message" style="display: none;"></span>
            </div>
        </form>
    </div>
</div>

