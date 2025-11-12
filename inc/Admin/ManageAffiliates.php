<?php

namespace DirectoristSimpleAffiliate\Admin;

use DirectoristSimpleAffiliate\Core\AffiliateManager;

/**
 * Admin page for managing affiliate requests
 */
class ManageAffiliates
{
    /**
     * Initialize the admin page
     */
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_submenu_page']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }

    /**
     * Add submenu page under Directorist post type
     */
    public static function add_submenu_page()
    {
        add_submenu_page(
            'edit.php?post_type=at_biz_dir',
            __('Simple Affiliate', 'directorist-simple-affiliate'),
            __('Simple Affiliate', 'directorist-simple-affiliate'),
            'manage_options',
            'dsa-simple-affiliate',
            [__CLASS__, 'render_page']
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public static function enqueue_scripts($hook)
    {
        // Check if we're on the simple affiliate page
        if (strpos($hook, 'dsa-simple-affiliate') === false) {
            return;
        }

        wp_enqueue_style(
            'dsa-admin-styles',
            DSA_PLUGIN_URL . 'assets/css/admin.css',
            [],
            DSA_VERSION
        );

        wp_enqueue_script(
            'dsa-admin-scripts',
            DSA_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            DSA_VERSION,
            true
        );

        wp_localize_script('dsa-admin-scripts', 'dsaAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dsa_admin_nonce'),
        ]);
    }

    /**
     * Handle form submissions
     */
    private static function handle_actions()
    {
        if (!isset($_POST['dsa_action']) || !check_admin_referer('dsa_affiliate_action', 'dsa_nonce')) {
            return;
        }

        $affiliate_manager = AffiliateManager::get_instance();
        $action = sanitize_text_field($_POST['dsa_action']);
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $comment = isset($_POST['dsa_comment']) ? sanitize_textarea_field($_POST['dsa_comment']) : '';

        if (!$user_id) {
            return;
        }

        $message = '';
        $message_type = 'error';

        switch ($action) {
            case 'approve':
                $result = $affiliate_manager->approve_affiliate($user_id);
                if (!is_wp_error($result)) {
                    $message = __('Affiliate has been approved successfully.', 'directorist-simple-affiliate');
                    $message_type = 'success';
                } else {
                    $message = $result->get_error_message();
                }
                break;

            case 'reject':
                $result = $affiliate_manager->reject_affiliate($user_id, $comment);
                if (!is_wp_error($result)) {
                    $message = __('Affiliate has been rejected.', 'directorist-simple-affiliate');
                    $message_type = 'success';
                } else {
                    $message = $result->get_error_message();
                }
                break;

            case 'suspend':
                $result = $affiliate_manager->suspend_affiliate($user_id, $comment);
                if (!is_wp_error($result)) {
                    $message = __('Affiliate has been suspended.', 'directorist-simple-affiliate');
                    $message_type = 'success';
                } else {
                    $message = $result->get_error_message();
                }
                break;
        }

        if ($message) {
            echo '<div class="notice notice-' . esc_attr($message_type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }

    /**
     * Get current active tab
     *
     * @return string
     */
    private static function get_current_tab()
    {
        return isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
    }

    /**
     * Render the admin page with tabs
     */
    public static function render_page()
    {
        // Handle actions
        self::handle_actions();

        $current_tab = self::get_current_tab();
        $tabs = [
            'overview' => __('Overview', 'directorist-simple-affiliate'),
            'requests' => __('Requests', 'directorist-simple-affiliate'),
            'tracking' => __('Tracking', 'directorist-simple-affiliate'),
        ];

        ?>
        <div class="wrap dsa-simple-affiliate">
            <h1 class="wp-heading-inline"><?php esc_html_e('Simple Affiliate', 'directorist-simple-affiliate'); ?></h1>
            <hr class="wp-header-end">

            <nav class="dsa-tab-wrapper">
                <?php foreach ($tabs as $tab_key => $tab_label): ?>
                    <a href="<?php echo esc_url(add_query_arg('tab', $tab_key, admin_url('edit.php?post_type=at_biz_dir&page=dsa-simple-affiliate'))); ?>" 
                       class="dsa-tab <?php echo $current_tab === $tab_key ? 'dsa-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="dsa-tab-content">
                <?php
                switch ($current_tab) {
                    case 'requests':
                        self::render_requests_tab();
                        break;
                    case 'overview':
                        self::render_overview_tab();
                        break;
                    case 'tracking':
                        self::render_tracking_tab();
                        break;
                    default:
                        self::render_overview_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render Overview tab
     */
    private static function render_overview_tab()
    {
        $template_path = DSA_PLUGIN_DIR . 'templates/admin/overview-tab.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            ?>
            <div class="dsa-tab-panel">
                <p><?php esc_html_e('Overview content coming soon...', 'directorist-simple-affiliate'); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Render Affiliate Requests tab
     */
    private static function render_requests_tab()
    {
        $affiliate_manager = AffiliateManager::get_instance();
        $affiliates = $affiliate_manager->get_affiliates('pending');

        $template_path = DSA_PLUGIN_DIR . 'templates/admin/affiliate-requests-tab.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Render Tracking tab
     */
    private static function render_tracking_tab()
    {
        $template_path = DSA_PLUGIN_DIR . 'templates/admin/tracking-tab.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Render affiliate card with details
     *
     * @param array $affiliate Affiliate data
     * @param AffiliateManager $affiliate_manager
     */
    public static function render_affiliate_card($affiliate, $affiliate_manager)
    {
        $user = get_userdata($affiliate['user_id']);
        
        // Get affiliate code from separate table
        $affiliate_code = $affiliate_manager->get_affiliate_code($affiliate['user_id']);
        ?>
        <div class="dsa-affiliate-card" data-user-id="<?php echo esc_attr($affiliate['user_id']); ?>">
            <div class="dsa-card-header">
                <div class="dsa-user-info">
                    <h3><?php echo esc_html($affiliate['display_name']); ?></h3>
                    <p class="dsa-user-email"><?php echo esc_html($affiliate['user_email']); ?></p>
                    <p class="dsa-registered-date">
                        <strong><?php esc_html_e('Applied:', 'directorist-simple-affiliate'); ?></strong>
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($affiliate['registered_at']))); ?>
                    </p>
                </div>
                <?php if ($affiliate_code): ?>
                <div class="dsa-affiliate-code">
                    <strong><?php esc_html_e('Affiliate Code:', 'directorist-simple-affiliate'); ?></strong>
                    <code><?php echo esc_html($affiliate_code); ?></code>
                </div>
                <?php endif; ?>
            </div>

            <div class="dsa-card-body">
                <div class="dsa-info-grid">
                    <?php if (!empty($affiliate['payment_method'])): ?>
                        <div class="dsa-info-item">
                            <label><?php esc_html_e('Payment Method:', 'directorist-simple-affiliate'); ?></label>
                            <span><?php echo esc_html($affiliate['payment_method']); ?></span>
                        </div>

                        <?php if ($affiliate['payment_method'] === 'PayPal' && !empty($affiliate['paypal_email'])): ?>
                            <div class="dsa-info-item">
                                <label><?php esc_html_e('PayPal Email:', 'directorist-simple-affiliate'); ?></label>
                                <span><?php echo esc_html($affiliate['paypal_email']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($affiliate['payment_method'] === 'Bank Transfer' && !empty($affiliate['bank_details'])): ?>
                            <div class="dsa-info-item dsa-full-width">
                                <label><?php esc_html_e('Bank Details:', 'directorist-simple-affiliate'); ?></label>
                                <div class="dsa-bank-details"><?php echo nl2br(esc_html($affiliate['bank_details'])); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($affiliate['website'])): ?>
                        <div class="dsa-info-item">
                            <label><?php esc_html_e('Website/Social Media:', 'directorist-simple-affiliate'); ?></label>
                            <span><a href="<?php echo esc_url($affiliate['website']); ?>" target="_blank"><?php echo esc_html($affiliate['website']); ?></a></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($affiliate['phone'])): ?>
                        <div class="dsa-info-item">
                            <label><?php esc_html_e('Phone:', 'directorist-simple-affiliate'); ?></label>
                            <span><?php echo esc_html($affiliate['phone']); ?></span>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <div class="dsa-card-footer">
                <form method="post" class="dsa-action-form">
                    <?php wp_nonce_field('dsa_affiliate_action', 'dsa_nonce'); ?>
                    <input type="hidden" name="dsa_action" value="" id="dsa_action_<?php echo esc_attr($affiliate['user_id']); ?>">
                    <input type="hidden" name="user_id" value="<?php echo esc_attr($affiliate['user_id']); ?>">
                    
                    <div class="dsa-comment-field" style="display: none;">
                        <label for="dsa_comment_<?php echo esc_attr($affiliate['user_id']); ?>">
                            <?php esc_html_e('Comment/Reason:', 'directorist-simple-affiliate'); ?>
                        </label>
                        <textarea 
                            name="dsa_comment" 
                            id="dsa_comment_<?php echo esc_attr($affiliate['user_id']); ?>" 
                            rows="3" 
                            class="dsa-comment-textarea"
                            placeholder="<?php esc_attr_e('Optional: Add a comment or reason...', 'directorist-simple-affiliate'); ?>"
                        ></textarea>
                    </div>

                    <div class="dsa-action-buttons">
                        <button 
                            type="button" 
                            class="button button-primary dsa-approve-btn"
                            data-user-id="<?php echo esc_attr($affiliate['user_id']); ?>"
                        >
                            <?php esc_html_e('Approve', 'directorist-simple-affiliate'); ?>
                        </button>
                        <button 
                            type="button" 
                            class="button button-secondary dsa-reject-btn"
                            data-user-id="<?php echo esc_attr($affiliate['user_id']); ?>"
                        >
                            <?php esc_html_e('Reject', 'directorist-simple-affiliate'); ?>
                        </button>
                        <button 
                            type="submit" 
                            class="button dsa-submit-action"
                            style="display: none;"
                        >
                            <?php esc_html_e('Confirm', 'directorist-simple-affiliate'); ?>
                        </button>
                        <button 
                            type="button" 
                            class="button dsa-cancel-action"
                            style="display: none;"
                        >
                            <?php esc_html_e('Cancel', 'directorist-simple-affiliate'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}

