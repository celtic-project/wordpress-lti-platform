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

/*
  Plugin Name: LTI Platform
  Plugin URI: http://www.spvsoftwareproducts.com/php/wordpress-lti-platform/
  Description: This plugin allows WordPress to act as a Platform using the IMS Learning Tools Interoperability (LTI) specification.
  Version: 2.0.3
  Author: Stephen P Vickers
  Author URI: http://www.celtic-project.org/
  License: GPL3
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin name.
 */
define('LTI_PLATFORM_NAME', 'lti-platform');

/**
 * Current plugin version.
 */
define('LTI_PLATFORM_VERSION', '2.0.3');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-lti-platform.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_lti_platform()
{
    $plugin = new LTI_Platform();
    if ($plugin->isOK()) {
        $plugin->run();
    }
}

run_lti_platform();
