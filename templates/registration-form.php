<?php
/**
 * Affiliate Registration Form Template
 *
 * @var array|null $result Form submission result
 * @var array|null $user_data Current user data if logged in
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="dsa-registration-form-wrapper">
    <div class="dsa-registration-form-container">
        <h2 class="dsa-form-title"><?php esc_html_e('Become an Affiliate', 'directorist-simple-affiliate'); ?></h2>
        <p class="dsa-form-description">
            <?php esc_html_e('Join our affiliate program and earn commissions by promoting Directorist!', 'directorist-simple-affiliate'); ?>
        </p>

        <?php if ($result): ?>
            <div class="dsa-form-message dsa-form-message--<?php echo esc_attr($result['success'] ? 'success' : 'error'); ?>">
                <?php echo wp_kses_post($result['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (!$result || !$result['success']): ?>
            <form method="post" class="dsa-registration-form" id="dsa-registration-form">
                <?php wp_nonce_field('dsa_registration_form', 'dsa_registration_nonce'); ?>

                <div class="dsa-form-row">
                    <div class="dsa-form-group">
                        <label for="dsa_full_name" class="dsa-form-label">
                            <?php esc_html_e('Full Name', 'directorist-simple-affiliate'); ?>
                            <span class="dsa-required">*</span>
                        </label>
                        <input
                            type="text"
                            id="dsa_full_name"
                            name="dsa_full_name"
                            class="dsa-form-input"
                            value="<?php echo isset($user_data['full_name']) ? esc_attr($user_data['full_name']) : ''; ?>"
                            required
                        />
                    </div>
                </div>

                <div class="dsa-form-row">
                    <div class="dsa-form-group">
                        <label for="dsa_email" class="dsa-form-label">
                            <?php esc_html_e('Email Address', 'directorist-simple-affiliate'); ?>
                            <span class="dsa-required">*</span>
                        </label>
                        <input
                            type="email"
                            id="dsa_email"
                            name="dsa_email"
                            class="dsa-form-input"
                            value="<?php echo isset($user_data['email']) ? esc_attr($user_data['email']) : ''; ?>"
                            <?php echo is_user_logged_in() ? 'readonly' : 'required'; ?>
                        />
                        <?php if (is_user_logged_in()): ?>
                            <small class="dsa-form-help">
                                <?php esc_html_e('This is your account email. It cannot be changed.', 'directorist-simple-affiliate'); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dsa-form-row">
                    <div class="dsa-form-group">
                        <label for="dsa_payment_method" class="dsa-form-label">
                            <?php esc_html_e('Payment Method', 'directorist-simple-affiliate'); ?>
                            <span class="dsa-required">*</span>
                        </label>
                        <select
                            id="dsa_payment_method"
                            name="dsa_payment_method"
                            class="dsa-form-select"
                            required
                        >
                            <option value=""><?php esc_html_e('Select Payment Method', 'directorist-simple-affiliate'); ?></option>
                            <option value="PayPal"><?php esc_html_e('PayPal', 'directorist-simple-affiliate'); ?></option>
                            <option value="Bank Transfer"><?php esc_html_e('Bank Transfer', 'directorist-simple-affiliate'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- PayPal Email Field (shown when PayPal is selected) -->
                <div class="dsa-form-row dsa-payment-field dsa-paypal-field" style="display: none;">
                    <div class="dsa-form-group">
                        <label for="dsa_paypal_email" class="dsa-form-label">
                            <?php esc_html_e('PayPal Email', 'directorist-simple-affiliate'); ?>
                            <span class="dsa-required">*</span>
                        </label>
                        <input
                            type="email"
                            id="dsa_paypal_email"
                            name="dsa_paypal_email"
                            class="dsa-form-input"
                            placeholder="<?php esc_attr_e('your-email@example.com', 'directorist-simple-affiliate'); ?>"
                            value=""
                        />
                        <small class="dsa-form-help">
                            <?php esc_html_e('This email will be used for PayPal payments.', 'directorist-simple-affiliate'); ?>
                        </small>
                    </div>
                </div>

                <!-- Bank Transfer Details Field (shown when Bank Transfer is selected) -->
                <div class="dsa-form-row dsa-payment-field dsa-bank-transfer-field" style="display: none;">
                    <div class="dsa-form-group">
                        <label for="dsa_bank_details" class="dsa-form-label">
                            <?php esc_html_e('Bank Transfer Details', 'directorist-simple-affiliate'); ?>
                            <span class="dsa-required">*</span>
                        </label>
                        <textarea
                            id="dsa_bank_details"
                            name="dsa_bank_details"
                            class="dsa-form-textarea"
                            rows="5"
                            placeholder="<?php esc_attr_e('Please provide your bank account details (Account Name, Account Number, Bank Name, IBAN/SWIFT, etc.)', 'directorist-simple-affiliate'); ?>"
                        ></textarea>
                        <small class="dsa-form-help">
                            <?php esc_html_e('Please provide all necessary bank account information for wire transfers.', 'directorist-simple-affiliate'); ?>
                        </small>
                    </div>
                </div>

                <div class="dsa-form-row">
                    <div class="dsa-form-group">
                        <label for="dsa_website" class="dsa-form-label">
                            <?php esc_html_e('Website/Social Media URL', 'directorist-simple-affiliate'); ?>
                            <span class="dsa-required">*</span>
                        </label>
                        <input
                            type="url"
                            id="dsa_website"
                            name="dsa_website"
                            class="dsa-form-input"
                            placeholder="<?php esc_attr_e('https://example.com or social media profile', 'directorist-simple-affiliate'); ?>"
                            value=""
                            required
                        />
                    </div>
                </div>

                <div class="dsa-form-row">
                    <div class="dsa-form-group">
                        <label for="dsa_phone" class="dsa-form-label">
                            <?php esc_html_e('Phone Number', 'directorist-simple-affiliate'); ?>
                        </label>
                        <input
                            type="text"
                            id="dsa_phone"
                            name="dsa_phone"
                            class="dsa-form-input"
                            placeholder="<?php esc_attr_e('Optional', 'directorist-simple-affiliate'); ?>"
                            value=""
                        />
                    </div>
                </div>

                <div class="dsa-form-row">
                    <div class="dsa-form-group">
                        <label class="dsa-form-checkbox-label">
                            <input
                                type="checkbox"
                                id="dsa_terms"
                                name="dsa_terms"
                                value="1"
                                class="dsa-form-checkbox"
                                required
                            />
                            <span>
                                <?php esc_html_e('I agree to the affiliate terms and conditions', 'directorist-simple-affiliate'); ?>
                                <span class="dsa-required">*</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="dsa-form-row">
                    <div class="dsa-form-group">
                        <button
                            type="submit"
                            name="dsa_submit_registration"
                            class="dsa-form-submit"
                        >
                            <?php esc_html_e('Submit Application', 'directorist-simple-affiliate'); ?>
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

