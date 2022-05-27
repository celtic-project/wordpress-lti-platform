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
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-lti-platform
 * @since      1.0.0
 * @package    LTI_Platform
 * @subpackage LTI_Platform/admin
 * @author     Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */
use ceLTIc\LTI\Util;

class LTI_Platform_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_scripts($hook)
    {
        if (($hook === 'post-new.php') || ($hook === 'post.php')) {
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/lti-platform-post.css', array(), $this->version);
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/lti-platform-post.js',
                array('wp-element', 'wp-editor', 'wp-rich-text'), $this->version, false);
        } elseif (($hook === "settings_page_{$this->plugin_name}-settings") || ($hook === "settings_page_{$this->plugin_name}-edit")) {
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/lti-platform-settings.js');
        }
    }

    public function options_page()
    {
        $menu = add_options_page('LTI Tools', 'LTI Tools', 'manage_options', LTI_Platform::get_plugin_name(),
            array($this, 'view_page_html'));
        add_action("load-{$menu}", array($this, 'load_tools_table'));

        $submenu = add_submenu_page(null, 'Add LTI Tool', 'Add New', 'manage_options', LTI_Platform::get_plugin_name() . '-edit',
            array($this, 'edit_page_html'));
        add_action("load-{$submenu}", array($this, 'load_submenu'));

        $submenu = add_submenu_page(null, 'LTI Platform Settings', 'Settings', 'manage_options',
            LTI_Platform::get_plugin_name() . '-settings', array($this, 'options_page_html')
        );
        add_action("load-{$submenu}", array($this, 'load_submenu'));
    }

    public function network_options_page()
    {
        $menu = add_submenu_page('settings.php', 'Network LTI Tools', 'Network LTI Tools', 'manage_options',
            LTI_Platform::get_plugin_name(), array($this, 'view_page_html'));
        add_action("load-{$menu}", array($this, 'load_tools_table'));

        $submenu = add_submenu_page(null, 'Add Network LTI Tool', 'Add New', 'manage_options',
            LTI_Platform::get_plugin_name() . '-edit', array($this, 'edit_page_html'));
        add_action("load-{$submenu}", array($this, 'load_submenu'));

        $submenu = add_submenu_page(null, 'Network LTI Platform Settings', 'Network Settings', 'manage_options',
            LTI_Platform::get_plugin_name() . '-settings', array($this, 'options_page_html')
        );
        add_action("load-{$submenu}", array($this, 'load_submenu'));
        add_action('network_admin_edit_' . LTI_Platform::get_plugin_name() . '-settings', array($this, 'save_network_options'));
    }

    public function save_network_options()
    {
        $rawoptions = $_POST[LTI_Platform::get_settings_name()];
        $options = array();
        foreach ($rawoptions as $option => $value) {
            $option = sanitize_text_field($option);
            switch ($option) {
                case 'privatekey':
                    $value = sanitize_textarea_field($value);
                    break;
                default:
                    $value = sanitize_text_field($value);
                    break;
            }
            $options[$option] = $value;
        }
        if (is_multisite()) {
            update_site_option(LTI_Platform::get_settings_name(), $options);
        } else {
            update_option(LTI_Platform::get_settings_name(), $options);
        }
        add_action('all_admin_notices', 'save_network_notice_success');
        wp_redirect('settings.php?page=' . LTI_Platform::get_plugin_name() . '-settings');
        exit;
    }

    public function save_network_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Settings updated.', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function load_tools_table()
    {
        $screen = get_current_screen();
        add_filter("manage_{$screen->id}_columns", array('LTI_Platform_Tool_List_Table', 'define_columns'), 10, 0);
        add_screen_option('per_page',
            array('label' => __('Tools', LTI_Platform::get_plugin_name()), 'default' => 5, 'option' => LTI_Platform::get_plugin_name() . '-tool_per_page'));
        $screen->add_help_tab(array(
            'id' => LTI_Platform::get_plugin_name() . '-display',
            'title' => __('Screen Display', LTI_Platform::get_plugin_name()),
            'content' => '<p>' . __('You can select which columns to display and the number of LTI Tools to list per screen using the Screen Options tab.',
                LTI_Platform::get_plugin_name()) . '</p>'
        ));
    }

    public function load_submenu()
    {
        add_filter('submenu_file', array($this, 'submenu_file'), 10, 2);
    }

    function submenu_file($submenu_file, $parent_file)
    {
// Ensure plugin remains highlighted as current in menu
        if ((($parent_file === 'options-general.php') || ($parent_file === 'settings.php')) && empty($submenu_file)) {
            $submenu_file = LTI_Platform::get_plugin_name();
        }

        return $submenu_file;
    }

    public function settings_init()
    {
        register_setting(LTI_Platform::get_plugin_name(), LTI_Platform::get_settings_name());
        $options = LTI_Platform_Tool::getOptions();

        add_settings_section(
            'section_general', __('General Settings', LTI_Platform::get_plugin_name()), array($this, 'section_general'),
            LTI_Platform::get_plugin_name()
        );
        add_settings_field('field_debug', __('Debug mode?', LTI_Platform::get_plugin_name()), array($this, 'field_checkbox'),
            LTI_Platform::get_plugin_name(), 'section_general',
            array('class' => 'row', 'label_for' => 'id_debug', 'name' => 'debug', 'options' => $options));
        add_settings_field('field_uninstall', __('Delete data on uninstall?', LTI_Platform::get_plugin_name()),
            array($this, 'field_checkbox'), LTI_Platform::get_plugin_name(), 'section_general',
            array('class' => 'row', 'label_for' => 'id_uninstall', 'name' => 'uninstall', 'options' => $options));
        add_settings_field('field_platformguid', __('Platform GUID', LTI_Platform::get_plugin_name()), array($this, 'field_text'),
            LTI_Platform::get_plugin_name(), 'section_general',
            array('class' => 'row', 'label_for' => 'id_platformguid', 'name' => 'platformguid', 'options' => $options));

        add_settings_section(
            'section_privacy', __('Privacy Settings', LTI_Platform::get_plugin_name()), array($this, 'section_privacy'),
            LTI_Platform::get_plugin_name()
        );
        add_settings_field('field_name', __('Send user\'s name?', LTI_Platform::get_plugin_name()), array($this, 'field_checkbox'),
            LTI_Platform::get_plugin_name(), 'section_privacy',
            array('class' => 'row', 'label_for' => 'id_sendusername', 'name' => 'sendusername', 'options' => $options));
        add_settings_field('field_id', __('Send user\'s ID?', LTI_Platform::get_plugin_name()), array($this, 'field_checkbox'),
            LTI_Platform::get_plugin_name(), 'section_privacy',
            array('class' => 'row', 'label_for' => 'id_senduserid', 'name' => 'senduserid', 'options' => $options));
        add_settings_field('field_email', __('Send user\'s email?', LTI_Platform::get_plugin_name()),
            array($this, 'field_checkbox'), LTI_Platform::get_plugin_name(), 'section_privacy',
            array('class' => 'row', 'label_for' => 'id_senduseremail', 'name' => 'senduseremail', 'options' => $options));
        add_settings_field('field_role', __('Send user\'s role?', LTI_Platform::get_plugin_name()), array($this, 'field_checkbox'),
            LTI_Platform::get_plugin_name(), 'section_privacy',
            array('class' => 'row', 'label_for' => 'id_senduserrole', 'name' => 'senduserrole', 'options' => $options));
        add_settings_field('field_username', __('Send user\'s username?', LTI_Platform::get_plugin_name()),
            array($this, 'field_checkbox'), LTI_Platform::get_plugin_name(), 'section_privacy',
            array('class' => 'row', 'label_for' => 'id_senduserusername', 'name' => 'senduserusername', 'options' => $options));

        add_settings_section(
            'section_presentation', __('Presentation Settings', LTI_Platform::get_plugin_name()),
            array($this, 'section_presentation'), LTI_Platform::get_plugin_name()
        );
        add_settings_field('field_target', __('Presentation target', LTI_Platform::get_plugin_name()), array($this, 'field_target'),
            LTI_Platform::get_plugin_name(), 'section_presentation',
            array('class' => 'row', 'label_for' => 'id_presentationtarget', 'name' => 'presentationtarget', 'options' => $options));
        add_settings_field('field_width', __('Width of pop-up window or iframe', LTI_Platform::get_plugin_name()),
            array($this, 'field_text'), LTI_Platform::get_plugin_name(), 'section_presentation',
            array('class' => 'row', 'label_for' => 'id_presentationwidth', 'name' => 'presentationwidth', 'options' => $options));
        add_settings_field('field_height', __('Height of pop-up window or iframe', LTI_Platform::get_plugin_name()),
            array($this, 'field_text'), LTI_Platform::get_plugin_name(), 'section_presentation',
            array('class' => 'row', 'label_for' => 'id_presentationheight', 'name' => 'presentationheight', 'options' => $options));

        add_settings_section(
            'section_security', __('Security Settings', LTI_Platform::get_plugin_name()), array($this, 'section_security'),
            LTI_Platform::get_plugin_name()
        );
        add_settings_field('field_kid', __('Key ID', LTI_Platform::get_plugin_name()), array($this, 'field_text'),
            LTI_Platform::get_plugin_name(), 'section_security',
            array('class' => 'row', 'label_for' => 'id_kid', 'name' => 'kid', 'options' => $options));
        add_settings_field('field_privatekey', __('Private key', LTI_Platform::get_plugin_name()), array($this, 'field_textarea'),
            LTI_Platform::get_plugin_name(), 'section_security',
            array('class' => 'row', 'label_for' => 'id_privatekey', 'name' => 'privatekey', 'options' => $options));
    }

    public function section_general()
    {

    }

    public function section_privacy()
    {

    }

    public function section_presentation()
    {

    }

    public function section_security()
    {

    }

    public function field_checkbox($args)
    {
        echo('<input id="' . esc_attr($args['label_for']) . '" type="checkbox" aria-required="false" value="true" name="' . LTI_Platform::get_settings_name() . '[' . esc_attr($args['name']) . ']"' .
        checked(isset($args['options'][$args['name']]) && ($args['options'][$args['name']] === 'true'), true, false) . '>' . "\n");
    }

    public function field_text($args)
    {
        echo('<input id="' . esc_attr($args['label_for']) . '" type="text" aria-required="false" value="');
        if (isset($args['options'][$args['name']])) {
            echo(esc_attr($args['options'][$args['name']]));
        }
        echo('" name="' . LTI_Platform::get_settings_name() . '[' . esc_attr($args['name']) . ']">' . "\n");
    }

    public function field_textarea($args)
    {
        echo('<textarea id=" ' . esc_attr($args['label_for']) . '" name="' . LTI_Platform::get_settings_name() . '[' . esc_attr($args['name']) . ']" class="regular-text">');
        if (isset($args['options'][$args['name']])) {
            echo($args['options'][$args['name']]);
        }
        echo('</textarea>' . "\n");
    }

    public function field_target($args)
    {
        echo('<select id="' . esc_attr($args['label_for']) . '" name="' . LTI_Platform::get_settings_name() . '[' . esc_attr($args['name']) . ']">' . "\n");
        echo('  <option value="window"' . selected(isset($args['options'][$args['name']]) && ($args['options'][$args['name']] === 'window'),
            true, false) . '>New window</option>' . "\n");
        echo('  <option value="popup"' . selected(isset($args['options'][$args['name']]) && ($args['options'][$args['name']] === 'popup'),
            true, false) . '>Pop-up window</option>' . "\n");
        echo('  <option value="iframe"' . selected(isset($args['options'][$args['name']]) && ($args['options'][$args['name']] === 'iframe'),
            true, false) . '>iFrame</option>' . "\n");
        echo('  <option value="embed"' . selected(isset($args['options'][$args['name']]) && ($args['options'][$args['name']] === 'embed'),
            true, false) . '>Embed</option>' . "\n");
        echo('</select>' . "\n");
    }

    public function options_page_html()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        settings_errors(LTI_Platform::get_plugin_name() . '_messages');

        require_once(plugin_dir_path(dirname(__FILE__)) . 'admin/partials/lti-platform-admin-settings.php');
    }

    public function view_page_html()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/lti-platform-admin-view.php';
    }

    public function edit_page_html()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (empty($_REQUEST['tool'])) {
            $tool = new LTI_Platform_Tool(LTI_platform::$ltiPlatformDataConnector);
        } else {
            $tool = LTI_Platform_Tool::fromRecordId(intval(sanitize_text_field($_REQUEST['tool'])),
                    LTI_Platform::$ltiPlatformDataConnector);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], LTI_Platform::get_plugin_name() . '-nonce')) {
                add_action('all_admin_notices', array($this, 'error_update'));
            } else {
                $this->update_tool($tool);
            }
        }
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/lti-platform-admin-edit.php';
    }

    function error_update()
    {
        $allowed = array('em' => array());
        echo('  <div class="notice notice-error">' . "\n");
        echo('    <p>' . esc_html__('Unable to save the changes.', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('  </div>' . "\n");
    }

    private function update_tool($tool)
    {
        $tool->name = sanitize_text_field($_POST['name']);
        $tool->code = sanitize_text_field($_POST['code']);
        $tool->enabled = !empty($_POST['enabled']) && (sanitize_text_field($_POST['enabled']) === 'true');
        $tool->debugMode = !empty($_POST['debugmode']) && (sanitize_text_field($_POST['debugmode']) === 'true');
        $tool->setSetting('sendUserName',
            (!empty($_POST['sendusername']) && (sanitize_text_field($_POST['sendusername']) === 'true')) ? 'true' : null);
        $tool->setSetting('sendUserId',
            (!empty($_POST['senduserid']) && (sanitize_text_field($_POST['senduserid'] === 'true'))) ? 'true' : null);
        $tool->setSetting('sendUserEmail',
            (!empty($_POST['senduseremail']) && (sanitize_text_field($_POST['senduseremail'] === 'true'))) ? 'true' : null);
        $tool->setSetting('sendUserRole',
            (!empty($_POST['senduserrole']) && (sanitize_text_field($_POST['senduserrole'] === 'true'))) ? 'true' : null);
        $tool->setSetting('sendUserUsername',
            (!empty($_POST['senduserusername']) && (sanitize_text_field($_POST['senduserusername'] === 'true'))) ? 'true' : null);
        $tool->setSetting('presentationTarget', sanitize_text_field($_POST['presentationtarget']));
        $tool->setSetting('presentationWidth', sanitize_text_field($_POST['presentationwidth']));
        $tool->setSetting('presentationHeight', sanitize_text_field($_POST['presentationheight']));
        $tool->messageUrl = esc_url_raw($_POST['messageurl']);
        $tool->useContentItem = !empty($_POST['usecontentitem']) && (sanitize_text_field($_POST['usecontentitem']) === 'true');
        $tool->contentItemUrl = esc_url_raw($_POST['contentitemurl']);
        $tool->setSetting('custom', str_replace("\r\n", '&#13;&#10;', sanitize_textarea_field($_POST['custom'])));
        $tool->setKey(sanitize_text_field($_POST['consumerkey']));
        $tool->secret = sanitize_text_field($_POST['sharedsecret']);
        $tool->initiateLoginUrl = sanitize_text_field($_POST['initiateloginurl']);
        $redirectionUris = trim(sanitize_textarea_field($_POST['redirectionuris']));
        if (!empty($redirectionUris)) {
            $tool->redirectionUris = explode("\r\n", sanitize_textarea_field($_POST['redirectionuris']));
        } else {
            $tool->redirectionUris = array();
        }
        $tool->jku = sanitize_text_field($_POST['jwksurl']);
        $tool->rsaKey = sanitize_textarea_field($_POST['publickey']);
        if (empty($tool->initiateLoginUrl) || empty($tool->redirectionUris)) {
            $tool->ltiVersion = Util::LTI_VERSION1;
            $tool->signatureMethod = 'HMAC-SHA1';
        } else {
            $tool->ltiVersion = Util::LTI_VERSION1P3;
            $tool->signatureMethod = 'RS256';
        }
        $tool->save();
    }

}
