<?php
/*
 *  wordpress-lti-platform - Enable WordPress to act as an LTI Platform.

 *  Copyright (C) 2022  Stephen P Vickers
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 *  Contact: Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-lti-platform
 * @since      1.0.0
 * @package    LTI_Platform
 * @author     Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */
// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once('lti-platform.php');
require_once('includes/class-lti-platform.php');

global $wpdb;

function lti_platform_delete_tools($post_type)
{
    $tools = get_posts(array(
        'post_type' => $post_type,
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
    ));
    if (!empty($tools)) {
        foreach ($tools as $tool) {
            wp_delete_post($tool, true);
        }
    }
}

// Check if data should be deleted on uninstall
if (is_multisite()) {
    $options = get_site_option(LTI_Platform::get_settings_name(), array());
} else {
    $options = get_option(LTI_Platform::get_settings_name(), array());
}
if (!is_array($options)) {
    $options = array();
}
if (!empty($options['uninstall']) && ($options['uninstall'] === 'true')) {
    // Delete plugin options.
    $plugin_name = LTI_Platform::get_settings_name();
    delete_option($plugin_name);

    // Delete plugin user meta.
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wp\_{$plugin_name}-%'");
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '{$plugin_name}-%'");
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'managesettings_page_{$plugin_name}columnshidden'");

    // Delete tool configurations
    $sites = get_sites();
    foreach ($sites as $site) {
        switch_to_blog($site->blog_id);
        lti_platform_delete_tools(LTI_Platform_Tool::POST_TYPE);
        restore_current_blog();
    }
    lti_platform_delete_tools(LTI_Platform_Tool::POST_TYPE_NETWORK);
}
