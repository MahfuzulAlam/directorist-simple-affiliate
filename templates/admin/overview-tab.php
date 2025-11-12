<?php
/**
 * Overview Tab Template - Visitor Tracking
 * 
 * @package DirectoristSimpleAffiliate
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use DirectoristSimpleAffiliate\Core\Tracking;
use DirectoristSimpleAffiliate\Core\AffiliateManager;
use DirectoristSimpleAffiliate\Database\Managers\AffiliatesManager;

// Get filter parameters
$affiliate_filter = isset($_GET['affiliate_id']) ? absint($_GET['affiliate_id']) : 0;
$code_filter = isset($_GET['code_id']) ? absint($_GET['code_id']) : 0;
$converted_filter = isset($_GET['converted']) ? sanitize_text_field($_GET['converted']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

// Get visits
$visits_args = [
    'affiliate_id' => $affiliate_filter,
    'code_id' => $code_filter,
    'converted' => $converted_filter,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
    'limit' => $per_page,
    'offset' => $offset,
];

$visits = Tracking::get_visits($visits_args);
$total_visits = Tracking::count_visits($visits_args);
$total_pages = ceil($total_visits / $per_page);

// Get all affiliates for filter dropdown
$all_affiliates = AffiliatesManager::get_all();
?>

<div class="dsa-tab-panel">
    <div class="dsa-tracking-overview">
        <div class="dsa-tracking-header">
            <h2><?php esc_html_e('Visitor Tracking', 'directorist-simple-affiliate'); ?></h2>
            <p class="dsa-description">
                <?php esc_html_e('Monitor affiliate referral link clicks and visitor activity.', 'directorist-simple-affiliate'); ?>
            </p>
        </div>

        <!-- Filters -->
        <div class="dsa-tracking-filters">
            <form method="get" class="dsa-filters-form">
                <input type="hidden" name="post_type" value="at_biz_dir">
                <input type="hidden" name="page" value="dsa-simple-affiliate">
                <input type="hidden" name="tab" value="overview">

                <div class="dsa-filters-grid">
                    <div class="dsa-filter-item">
                        <label for="dsa_affiliate_filter"><?php esc_html_e('Affiliate:', 'directorist-simple-affiliate'); ?></label>
                        <select id="dsa_affiliate_filter" name="affiliate_id" class="dsa-filter-select">
                            <option value="0"><?php esc_html_e('All Affiliates', 'directorist-simple-affiliate'); ?></option>
                            <?php foreach ($all_affiliates as $affiliate): 
                                $user = get_userdata($affiliate->user_id);
                                if ($user):
                            ?>
                                <option value="<?php echo esc_attr($affiliate->id); ?>" <?php selected($affiliate_filter, $affiliate->id); ?>>
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <div class="dsa-filter-item">
                        <label for="dsa_converted_filter"><?php esc_html_e('Status:', 'directorist-simple-affiliate'); ?></label>
                        <select id="dsa_converted_filter" name="converted" class="dsa-filter-select">
                            <option value=""><?php esc_html_e('All', 'directorist-simple-affiliate'); ?></option>
                            <option value="0" <?php selected($converted_filter, '0'); ?>><?php esc_html_e('Not Converted', 'directorist-simple-affiliate'); ?></option>
                            <option value="1" <?php selected($converted_filter, '1'); ?>><?php esc_html_e('Converted', 'directorist-simple-affiliate'); ?></option>
                        </select>
                    </div>

                    <div class="dsa-filter-item">
                        <label for="dsa_date_from"><?php esc_html_e('Date From:', 'directorist-simple-affiliate'); ?></label>
                        <input type="date" id="dsa_date_from" name="date_from" class="dsa-filter-input" value="<?php echo esc_attr($date_from); ?>">
                    </div>

                    <div class="dsa-filter-item">
                        <label for="dsa_date_to"><?php esc_html_e('Date To:', 'directorist-simple-affiliate'); ?></label>
                        <input type="date" id="dsa_date_to" name="date_to" class="dsa-filter-input" value="<?php echo esc_attr($date_to); ?>">
                    </div>

                    <div class="dsa-filter-item">
                        <label for="dsa_search"><?php esc_html_e('Search:', 'directorist-simple-affiliate'); ?></label>
                        <input type="text" id="dsa_search" name="search" class="dsa-filter-input" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search by name, email, code, or IP...', 'directorist-simple-affiliate'); ?>">
                    </div>

                    <div class="dsa-filter-item dsa-filter-actions">
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Filter', 'directorist-simple-affiliate'); ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('edit.php?post_type=at_biz_dir&page=dsa-simple-affiliate&tab=overview')); ?>" class="button">
                            <?php esc_html_e('Reset', 'directorist-simple-affiliate'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stats Summary -->
        <div class="dsa-tracking-stats">
            <div class="dsa-stat-card">
                <div class="dsa-stat-value"><?php echo number_format($total_visits); ?></div>
                <div class="dsa-stat-label"><?php esc_html_e('Total Visits', 'directorist-simple-affiliate'); ?></div>
            </div>
            <?php
            $converted_count = Tracking::count_visits(array_merge($visits_args, ['converted' => 1]));
            $conversion_rate = $total_visits > 0 ? ($converted_count / $total_visits * 100) : 0;
            ?>
            <div class="dsa-stat-card">
                <div class="dsa-stat-value"><?php echo number_format($converted_count); ?></div>
                <div class="dsa-stat-label"><?php esc_html_e('Conversions', 'directorist-simple-affiliate'); ?></div>
            </div>
            <div class="dsa-stat-card">
                <div class="dsa-stat-value"><?php echo number_format($conversion_rate, 2); ?>%</div>
                <div class="dsa-stat-label"><?php esc_html_e('Conversion Rate', 'directorist-simple-affiliate'); ?></div>
            </div>
        </div>

        <!-- Visits Table -->
        <div class="dsa-tracking-table-wrapper">
            <?php if (empty($visits)): ?>
                <div class="dsa-empty-state">
                    <p><?php esc_html_e('No visits found matching your filters.', 'directorist-simple-affiliate'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped dsa-visits-table">
                    <thead>
                        <tr>
                            <th class="column-date"><?php esc_html_e('Date', 'directorist-simple-affiliate'); ?></th>
                            <th class="column-affiliate"><?php esc_html_e('Affiliate', 'directorist-simple-affiliate'); ?></th>
                            <th class="column-code"><?php esc_html_e('Code', 'directorist-simple-affiliate'); ?></th>
                            <th class="column-ip"><?php esc_html_e('IP Address', 'directorist-simple-affiliate'); ?></th>
                            <th class="column-referrer"><?php esc_html_e('Referrer', 'directorist-simple-affiliate'); ?></th>
                            <th class="column-landing"><?php esc_html_e('Landing Page', 'directorist-simple-affiliate'); ?></th>
                            <th class="column-status"><?php esc_html_e('Status', 'directorist-simple-affiliate'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visits as $visit): ?>
                            <tr>
                                <td class="column-date">
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($visit->created_at))); ?>
                                </td>
                                <td class="column-affiliate">
                                    <?php 
                                    if (!empty($visit->display_name)) {
                                        echo esc_html($visit->display_name);
                                        echo '<br><small>' . esc_html($visit->user_email) . '</small>';
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td class="column-code">
                                    <code><?php echo esc_html($visit->affiliate_code ?: 'N/A'); ?></code>
                                </td>
                                <td class="column-ip">
                                    <?php echo esc_html($visit->ip_address ?: '—'); ?>
                                </td>
                                <td class="column-referrer">
                                    <?php if (!empty($visit->referrer_url)): ?>
                                        <a href="<?php echo esc_url($visit->referrer_url); ?>" target="_blank" title="<?php echo esc_attr($visit->referrer_url); ?>">
                                            <?php echo esc_html(wp_parse_url($visit->referrer_url, PHP_URL_HOST) ?: $visit->referrer_url); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="dsa-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-landing">
                                    <?php if (!empty($visit->landing_url)): ?>
                                        <a href="<?php echo esc_url($visit->landing_url); ?>" target="_blank" title="<?php echo esc_attr($visit->landing_url); ?>">
                                            <?php echo esc_html(basename(wp_parse_url($visit->landing_url, PHP_URL_PATH) ?: $visit->landing_url)); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="dsa-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-status">
                                    <?php if ($visit->converted): ?>
                                        <span class="dsa-badge dsa-badge-success"><?php esc_html_e('Converted', 'directorist-simple-affiliate'); ?></span>
                                    <?php else: ?>
                                        <span class="dsa-badge dsa-badge-default"><?php esc_html_e('Not Converted', 'directorist-simple-affiliate'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="dsa-pagination">
                        <?php
                        $base_url = add_query_arg([
                            'post_type' => 'at_biz_dir',
                            'page' => 'dsa-simple-affiliate',
                            'tab' => 'overview',
                            'affiliate_id' => $affiliate_filter,
                            'code_id' => $code_filter,
                            'converted' => $converted_filter,
                            'date_from' => $date_from,
                            'date_to' => $date_to,
                            'search' => $search,
                        ], admin_url('edit.php'));
                        
                        $pagination_args = [
                            'base' => $base_url . '%_%',
                            'format' => '&paged=%#%',
                            'current' => $paged,
                            'total' => $total_pages,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        ];
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

