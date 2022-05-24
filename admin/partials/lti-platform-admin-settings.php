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
 * This file is used to markup the page for the plugin's settings.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-lti-platform
 * @since      1.0.0
 * @package    LTI_Platform
 * @subpackage LTI_Platform/admin/partials
 * @author     Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */
if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) {
    $page = 'settings.php';
    $url = 'edit.php?action=' . LTI_Platform::get_plugin_name() . '-settings';
} else {
    $page = 'options-general.php';
    $url = 'options.php';
}
echo('<div class="wrap">' . "\n");
echo('  <h1>' . "\n");
echo('    ' . esc_html__('LTI Platform Settings', LTI_Platform::get_plugin_name()) . "\n");
echo('    <a href="' . esc_url($page . '?page=' . LTI_Platform::get_plugin_name()) . '" class="page-title-action">LTI Tools List</a>' . "\n");
echo('  </h1>' . "\n");
echo('  <form action="' . esc_url($url) . '" method="post">' . "\n");
do_action('all_admin_notices');

submit_button(null, 'primary', 'submit', true, array('id' => 'submit_top'));
settings_fields(LTI_Platform::get_plugin_name());
do_settings_sections(LTI_Platform::get_plugin_name());
submit_button(null, 'primary', 'submit', true, array('id' => 'submit_bottom'));

echo('  </form>' . "\n");
echo('</div>' . "\n");
