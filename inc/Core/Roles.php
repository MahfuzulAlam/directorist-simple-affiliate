<?php

namespace DirectoristSimpleAffiliate\Core;

/**
 * User roles management
 */
class Roles
{
    /**
     * Create custom user role for affiliates
     */
    public static function create_affiliate_role()
    {
        // Check if role already exists
        if (get_role('directorist_affiliate')) {
            return;
        }

        $capabilities = [
            'read' => true,
            'edit_posts' => false,
            'upload_files' => true,
            'access_affiliate_dashboard' => true,
        ];

        $result = add_role('directorist_affiliate', 'Directorist Affiliate', $capabilities);
        
        // If role creation failed, try to refresh roles
        if (!$result) {
            // Force refresh of roles
            global $wp_roles;
            if (!isset($wp_roles)) {
                $wp_roles = new \WP_Roles();
            }
            $wp_roles->add_role('directorist_affiliate', 'Directorist Affiliate', $capabilities);
        }
    }

    /**
     * Remove custom user role
     */
    public static function remove_affiliate_role()
    {
        remove_role('directorist_affiliate');
    }

    /**
     * Check if user has affiliate role
     *
     * @param int $user_id
     * @return bool
     */
    public static function is_affiliate($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        $user = get_userdata($user_id);
        return $user && in_array('directorist_affiliate', (array) $user->roles);
    }
}

