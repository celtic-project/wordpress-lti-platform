<?php
/*
 *  wordpress-lti-platform - Enable WordPress to act as an LTI Platform.

 *  Copyright (C) 2024  Stephen P Vickers
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
 * This file is used to markup the table of LTI tools.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-lti-platform
 * @since      1.0.0
 * @package    LTI_Platform
 * @subpackage LTI_Platform/admin/partials
 * @author     Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */
$list_table = new LTI_Platform_Tool_List_Table();
$list_table->prepare_items();

if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) {
    $file = 'settings.php';
} else {
    $file = 'options-general.php';
}
echo('<div class="wrap">' . "\n");
echo('  <h1 class="wp-heading-inline">LTI Tools</h1>' . "\n");
if (!is_multisite() || defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) {
    echo('  <a href="' . esc_url($file . '?page=' . LTI_Platform::get_plugin_name() . '-settings') . '" class="page-title-action">Default settings</a>' . "\n");
}
echo('  <a href="' . esc_url($file . '?page=' . LTI_Platform::get_plugin_name() . '-edit') . '" class="page-title-action">Add New</a>' . "\n");
echo('  <hr class="wp-header-end">' . "\n");

do_action('all_admin_notices');

$list_table->views();

echo('  <form method="get" action="">' . "\n");
echo('    <input type="hidden" name="page" value="' . esc_attr($_REQUEST['page']) . '" />');

$list_table->display();

echo('  </form>' . "\n");
echo('</div>' . "\n");
