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

use ceLTIc\LTI\Util;
use ceLTIc\LTI\Enum\LogLevel;
use ceLTIc\LTI\Enum\LtiVersion;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-lti-platform
 * @since      1.0.0
 * @package    LTI_Platform
 * @subpackage LTI_Platform/includes
 * @author     Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */
class LTI_Platform
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      LTI_Platform_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The name of the post type.
     *
     * @since    2.0.0
     * @access   static
     * @var      string    $version    The name of the post type.
     */
    public static $postType = null;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   static
     * @var      DataConnector_wp    $ltiPlatformDataConnector    The LTI data connector.
     */
    public static $ltiPlatformDataConnector;

    /**
     * Whether the object has been successfully instantiated.
     *
     * @since    2.0.0
     * @access   private
     * @var      bool    $ok    True when the object has been successfully instantiated.
     */
    private $ok = true;

    /**
     * Plugin option values.
     *
     * @since    2.2.0
     * @access   private static
     * @var      array    $options    Plugin options.
     */
    private static $options = null;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        global $wpdb;

        $this->version = LTI_PLATFORM_VERSION;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->check_dependencies();
        if ($this->ok && class_exists('DataConnector_wp')) {
            self::$ltiPlatformDataConnector = DataConnector_wp::createDataConnector($wpdb->dbh, $wpdb->base_prefix);
            if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) {
                self::$postType = LTI_Platform_Tool::POST_TYPE_NETWORK;
            } else {
                self::$postType = LTI_Platform_Tool::POST_TYPE;
            }
        }
    }

    /**
     * Get the status of the object.
     *
     * @since    1.0.0
     */
    public function isOK()
    {
        return $this->ok;
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        if (LTI_Platform::getOption('debug', 'false') === 'true') {
            $this->set_debug_loglevel();
        }
        $this->loader->run();
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    LTI_Platform_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Display a message when the plugin has to be deactivated.
     *
     * @since    1.0.0
     */
    public function error_deactivate()
    {
        $allowed = array('em' => array());
        echo('  <div class="notice notice-error">' . "\n");
        echo('    <p>' . wp_kses(__('The <em>LTI  Platform</em> plugin has been deactivated because a dependency is missing; either use <em>Composer</em> to install the dependent libraries or activate the <em>ceLTIc LTI Library</em> plugin.',
                self::get_plugin_name()), $allowed) . '</p>' . "\n");
        echo('  </div>' . "\n");
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     2.0.0
     * @return    string    The name of the plugin.
     */
    public static function get_plugin_name()
    {
        return LTI_PLATFORM_NAME;
    }

    /**
     * Retrieve the name of the settings entry for the plugin.
     *
     * @since     2.0.0
     * @return    string    The settings entry name for the plugin.
     */
    public static function get_settings_name()
    {
        return str_replace('-', '_', LTI_PLATFORM_NAME) . '_options';
    }

    /**
     * Get the plugin settings values.
     *
     * @since    2.2.0
     * @return   array     Array of settings.
     */
    public static function getOptions()
    {
        if (empty(self::$options)) {
            if (is_multisite()) {
                self::$options = get_site_option(LTI_Platform::get_settings_name(), array());
            } else {
                self::$options = get_option(LTI_Platform::get_settings_name(), array());
            }
            if (!is_array(self::$options)) {
                self::$options = array();
            }
        }

        return self::$options;
    }

    /**
     * Get the value of a setting.
     *
     * @since    2.2.0
     * @param    string    Name of setting
     * @param    string    Default value
     *
     * @return   string    Setting value.
     */
    public static function getOption($name, $default)
    {
        self::getOptions();
        if (array_key_exists($name, self::$options)) {
            $default = self::$options[$name];
        } elseif (strpos($name, 'role_') === 0) {
            $defaultroles = LTI_Platform::get_default_roles();
            if (isset($defaultroles[substr($name, 5)])) {
                $default = $defaultroles[substr($name, 5)];
            }
        }

        return $default;
    }

    /**
     * Get WordPress roles.
     *
     * @since    2.2.0
     * @return   array     Array of roles
     */
    public static function get_roles()
    {
        $roles = array_merge(wp_roles()->roles, get_editable_roles());

        return $roles;
    }

    /**
     * Get default role mapping for standard WordPress roles.
     *
     * @since    2.2.0
     * @return   array     Array of role mappings.
     */
    public static function get_default_roles()
    {
        $defaultroles = array(
            'administrator' => array('administrator', 'instructor'),
            'editor' => array('instructor'),
            'author' => array('learner'),
            'contributor' => array('learner'),
            'subscriber' => array('learner')
        );

        return $defaultroles;
    }

    /**
     * Check for version 5 of the ceLTIc LTI library.
     *
     * @since    2.2.0
     * @return   boolean     True if version 5 is being used
     */
    public static function use_lti_library_v5()
    {
        return function_exists('enum_exists') && enum_exists('ceLTIc\\LTI\\Enum\\LtiVersion');
    }

    /**
     * Get LTI version constant or enum.
     *
     * @param    boolean     True if LTI 1.3 version is to be returned
     *
     * @since    2.2.0
     * @return   LtiVersion|string     LTI version value
     */
    public static function get_lti_version($lti_1p3)
    {
        if (self::use_lti_library_v5()) {
            if ($lti_1p3) {
                $lti_version = LtiVersion::V1P3;
            } else {
                $lti_version = LtiVersion::V1;
            }
        } elseif ($lti_1p3) {
            $lti_version = Util::LTI_VERSION1P3;
        } else {
            $lti_version = Util::LTI_VERSION1;
        }

        return $lti_version;
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        /**
         * Autoload file for dependent libraries.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-lti-platform-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-lti-platform-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-lti-platform-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-lti-platform-public.php';

        if (class_exists('ceLTIc\LTI\Tool')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-lti-platform-tool.php';
        }
        if (class_exists('ceLTIc\LTI\Platform')) {
            if (self::use_lti_library_v5()) {
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-lti-platform-platform_v5.php';
            } else {
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-lti-platform-platform.php';
            }
        }

        if (!class_exists('WP_List_Table')) {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
        }
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-lti-platform-tool-list-table.php';

        if (class_exists('ceLTIc\LTI\DataConnector\DataConnector')) {
            if (self::use_lti_library_v5()) {
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-lti-platform-dataconnector_v5.php';
            } else {
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-lti-platform-dataconnector.php';
            }
        }

        $this->loader = new LTI_Platform_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the LTI_Platform_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new LTI_Platform_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new LTI_Platform_Admin(self::get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_init', $plugin_admin, 'settings_init');
        $this->loader->add_action('admin_menu', $plugin_admin, 'options_page');
        if (is_multisite()) {
            $this->loader->add_action('network_admin_menu', $plugin_admin, 'network_options_page');
        }

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        $this->loader->add_action('init', 'LTI_Platform_Tool', 'register');

        $this->loader->add_filter('posts_orderby', 'LTI_Platform_Tool_List_Table', 'tools_orderby', 10, 2);

        $prefix = '';
        if (is_multisite()) {
            $prefix = 'network_admin_';
        }
        $this->loader->add_filter("{$prefix}plugin_action_links_" . self::get_plugin_name() . '/' . self::get_plugin_name() . '.php',
            $plugin_admin, 'plugin_settings_link');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {
        $plugin_public = new LTI_Platform_Public(self::get_plugin_name(), $this->get_version());

        $this->loader->add_action('parse_request', $plugin_public, 'parse_request');

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Check that the plugin dependencies are available.
     *
     * @since     2.0.1
     * @return    bool    True if the library is found.
     */
    private function check_dependencies()
    {
        $this->ok = $this->check_lti_library();
        if (!$this->ok) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            add_action('all_admin_notices', array($this, 'error_deactivate'));
            $plugin_name = self::get_plugin_name();
            deactivate_plugins("{$plugin_name}/{$plugin_name}.php");
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }
    }

    /**
     * Check that the LTI class library is available.
     *
     * @since     2.0.1
     * @return    bool    True if the library is found.
     */
    private function check_lti_library()
    {
        return class_exists('ceLTIc\LTI\Platform');
    }

    /**
     * Set debug level logging.
     *
     * @since    2.2.0
     */
    private function set_debug_loglevel()
    {
        if (self::use_lti_library_v5()) {
            Util::$logLevel = LogLevel::Debug;
        } else {
            Util::$logLevel = Util::LOGLEVEL_DEBUG;
        }
    }

}
