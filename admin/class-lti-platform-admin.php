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

    /**
     * Set CSS and scripts to be loaded.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook)
    {
        if (($hook === 'post-new.php') || ($hook === 'post.php')) {
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/lti-platform-post.css', array(), $this->version);
            wp_enqueue_script("{$this->plugin_name}-post", plugin_dir_url(__FILE__) . 'js/lti-platform-post.js',
                array('wp-element', 'wp-editor', 'wp-rich-text'), $this->version, false);
        } elseif (($hook === "settings_page_{$this->plugin_name}-settings") || ($hook === "settings_page_{$this->plugin_name}-edit")) {
            wp_enqueue_script("{$this->plugin_name}-settings", plugin_dir_url(__FILE__) . 'js/lti-platform-settings.js', array(),
                $this->version, false);
        }
    }

    /**
     * Add a settings link to plugin page.
     *
     * @since    2.2.0
     * @return   array    Array of plugin page links.
     */
    public function plugin_settings_link($links)
    {
        if (is_multisite()) {
            $page = 'settings.php';
        } else {
            $page = 'options-general.php';
        }
        $url = add_query_arg(array('page' => "{$this->plugin_name}-settings"), $page);
        array_unshift($links,
            sprintf('<a href="%1$s" title="%2$s">%3$s</a>', esc_url($url), esc_html__('Change plugin settings', $this->plugin_name),
                esc_html__('Settings', $this->plugin_name)));

        return $links;
    }

    /**
     * Define the plugin menu options for a site.
     *
     * @since    1.0.0
     */
    public function options_page()
    {
        $menu = add_options_page('LTI Tools', 'LTI Tools', 'manage_options', $this->plugin_name, array($this, 'view_page_html'));
        add_action("load-{$menu}", array($this, 'load_tools_table'));

        $submenu = add_submenu_page(null, 'Add LTI Tool', 'Add New', 'manage_options', "{$this->plugin_name}-edit",
            array($this, 'edit_page_html'));
        add_action("load-{$submenu}", array($this, 'load_submenu'));

        $submenu = add_submenu_page(null, 'LTI Platform Settings', 'Settings', 'manage_options', "{$this->plugin_name}-settings",
            array($this, 'options_page_html')
        );
        add_action("load-{$submenu}", array($this, 'load_submenu'));
    }

    /**
     * Define the plugin menu options at the network level.
     *
     * @since    2.0.0
     */
    public function network_options_page()
    {
        $menu = add_submenu_page('settings.php', 'Network LTI Tools', 'Network LTI Tools', 'manage_options', $this->plugin_name,
            array($this, 'view_page_html'));
        add_action("load-{$menu}", array($this, 'load_tools_table'));

        $submenu = add_submenu_page(null, 'Add Network LTI Tool', 'Add New', 'manage_options', "{$this->plugin_name}-edit",
            array($this, 'edit_page_html'));
        add_action("load-{$submenu}", array($this, 'load_submenu'));

        $submenu = add_submenu_page(null, 'Network LTI Platform Settings', 'Network Settings', 'manage_options',
            "{$this->plugin_name}-settings", array($this, 'options_page_html')
        );
        add_action("load-{$submenu}", array($this, 'load_submenu'));
        add_action("network_admin_edit_{$this->plugin_name}-settings", array($this, 'save_network_options'));
    }

    /**
     * Save the plugin options.
     *
     * @since    2.0.0
     */
    public function save_network_options()
    {
        $nonce = null;
        if (isset($_REQUEST['_wpnonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce']));
        }
        $ok = (empty($nonce) || !wp_verify_nonce($nonce, "{$this->plugin_name}-nonce"));
        if ($ok && isset($_POST[LTI_Platform::get_settings_name()]) &&
            is_array($_POST[LTI_Platform::get_settings_name()])) {
            $rawoptions = $_POST[LTI_Platform::get_settings_name()];
            $options = array();
            foreach ($rawoptions as $option => $value) {
                switch ($option) {
                    case 'privatekey':
                        $value = sanitize_textarea_field(wp_unslash($value));
                        break;
                    default:
                        if (!is_array($value)) {
                            $value = sanitize_text_field(wp_unslash($value));
                        } else {
                            $arr = $value;
                            $value = array();
                            foreach ($arr as $item) {
                                $value[] = sanitize_text_field(wp_unslash($item));
                            }
                        }
                        break;
                }
                $options[$option] = $value;
            }
            update_site_option(LTI_Platform::get_settings_name(), $options);
            add_settings_error('general', 'settings_updated', __('Settings saved.', $this->plugin_name), 'success');
            set_transient('settings_errors', get_settings_errors(), 30);
        } else {
            add_settings_error('general', 'settings_update_error', __('Error saving settings.', $this->plugin_name), 'error');
            set_transient('settings_errors', get_settings_errors(), 30);
        }
        wp_redirect(add_query_arg(array('page' => "{$this->plugin_name}-settings", 'settings-updated' => 'true'),
                network_admin_url('settings.php')));
        exit;
    }

    /**
     * Initialise the table.
     *
     * @since    1.0.0
     */
    public function load_tools_table()
    {
        $screen = get_current_screen();
        add_filter("manage_{$screen->id}_columns", array('LTI_Platform_Tool_List_Table', 'define_columns'), 10, 0);
        add_screen_option('per_page',
            array('label' => __('Tools', $this->plugin_name), 'default' => 5, 'option' => "{$this->plugin_name}-tool_per_page"));
        $screen->add_help_tab(array(
            'id' => "{$this->plugin_name}-display",
            'title' => __('Screen Display', $this->plugin_name),
            'content' => '<p>' . __('You can select which columns to display and the number of LTI Tools to list per screen using the Screen Options tab.',
                $this->plugin_name) . '</p>'
        ));
    }

    /**
     * Add filter for when plugin menu option is displayed.
     *
     * @since    1.0.0
     */
    public function load_submenu()
    {
        add_filter('submenu_file', array($this, 'submenu_file'), 10, 2);
    }

    /**
     * Get name of plugin to highlight in settings menu.
     *
     * @since    1.0.0
     * @param    string    $submenu_file    The submenu file.
     * @param    string    $parent_file     The submenu item's parent file.
     *
     * @return   string    Name of submenu file to highlight.
     */
    function submenu_file($submenu_file, $parent_file)
    {
// Ensure plugin remains highlighted as current in menu
        if ((($parent_file === 'options-general.php') || ($parent_file === 'settings.php')) && empty($submenu_file)) {
            $submenu_file = $this->plugin_name;
        }

        return $submenu_file;
    }

    /**
     * Initialise settings fields.
     *
     * @since    1.0.0
     */
    public function settings_init()
    {
        register_setting($this->plugin_name, LTI_Platform::get_settings_name());

        add_settings_section(
            'section_general', __('General Settings', $this->plugin_name), array($this, 'section_general'), $this->plugin_name
        );
        add_settings_field('field_debug', __('Debug mode?', $this->plugin_name), array($this, 'field_checkbox'), $this->plugin_name,
            'section_general', array('label_for' => 'id_debug', 'name' => 'debug'));
        add_settings_field('field_uninstall', __('Delete data on uninstall?', $this->plugin_name), array($this, 'field_checkbox'),
            $this->plugin_name, 'section_general', array('label_for' => 'id_uninstall', 'name' => 'uninstall'));
        add_settings_field('field_platformguid', __('Platform GUID', $this->plugin_name), array($this, 'field_text'),
            $this->plugin_name, 'section_general',
            array('label_for' => 'id_platformguid', 'name' => 'platformguid', 'class' => 'regular-text'));
        add_settings_field('field_checkmeta', __('Check custom fields for shortcodes?', $this->plugin_name),
            array($this, 'field_checkbox'), $this->plugin_name, 'section_general',
            array('label_for' => 'id_checkmeta', 'name' => 'checkmeta'));

        add_settings_section(
            'section_privacy', __('Privacy Settings', $this->plugin_name), array($this, 'section_privacy'), $this->plugin_name
        );
        add_settings_field('field_name', __('Send user\'s name?', $this->plugin_name), array($this, 'field_checkbox'),
            $this->plugin_name, 'section_privacy', array('label_for' => 'id_sendusername', 'name' => 'sendusername'));
        add_settings_field('field_id', __('Send user\'s ID?', $this->plugin_name), array($this, 'field_checkbox'),
            $this->plugin_name, 'section_privacy', array('label_for' => 'id_senduserid', 'name' => 'senduserid'));
        add_settings_field('field_email', __('Send user\'s email?', $this->plugin_name), array($this, 'field_checkbox'),
            $this->plugin_name, 'section_privacy', array('label_for' => 'id_senduseremail', 'name' => 'senduseremail'));
        add_settings_field('field_role', __('Send user\'s role?', $this->plugin_name), array($this, 'field_checkbox'),
            $this->plugin_name, 'section_privacy', array('label_for' => 'id_senduserrole', 'name' => 'senduserrole'));
        add_settings_field('field_username', __('Send user\'s username?', $this->plugin_name), array($this, 'field_checkbox'),
            $this->plugin_name, 'section_privacy', array('label_for' => 'id_senduserusername', 'name' => 'senduserusername'));

        add_settings_section(
            'section_roles', __('Role Mappings', $this->plugin_name), array($this, 'section_roles'), $this->plugin_name
        );
        $roles = LTI_Platform::get_roles();
        foreach ($roles as $key => $role) {
            add_settings_field("field_role_{$key}", __($role['name'], $this->plugin_name), array($this, 'field_role'),
                $this->plugin_name, 'section_roles', array('label_for' => "id_role_{$key}", 'name' => "role_{$key}"));
        }

        add_settings_section(
            'section_presentation', __('Presentation Settings', $this->plugin_name), array($this, 'section_presentation'),
            $this->plugin_name
        );
        add_settings_field('field_target', __('Presentation target', $this->plugin_name), array($this, 'field_target'),
            $this->plugin_name, 'section_presentation',
            array('label_for' => 'id_presentationtarget', 'name' => 'presentationtarget'));
        add_settings_field('field_width', __('Width of pop-up window or iframe', $this->plugin_name), array($this, 'field_text'),
            $this->plugin_name, 'section_presentation',
            array('label_for' => 'id_presentationwidth', 'name' => 'presentationwidth', 'class' => 'small-text'));
        add_settings_field('field_height', __('Height of pop-up window or iframe', $this->plugin_name), array($this, 'field_text'),
            $this->plugin_name, 'section_presentation',
            array('label_for' => 'id_presentationheight', 'name' => 'presentationheight', 'class' => 'small-text'));
        add_settings_field('field_class', __('Classes for launch link element', $this->plugin_name), array($this, 'field_text'),
            $this->plugin_name, 'section_presentation',
            array('label_for' => 'id_presentationclass', 'name' => 'presentationclass', 'class' => 'large-text'));
        add_settings_field('field_style', __('Styles for launch link element', $this->plugin_name), array($this, 'field_text'),
            $this->plugin_name, 'section_presentation',
            array('label_for' => 'id_presentationstyle', 'name' => 'presentationstyle', 'class' => 'large-text'));

        add_settings_section(
            'section_security', __('Security Settings', $this->plugin_name), array($this, 'section_security'), $this->plugin_name
        );
        add_settings_field('field_kid', __('Key ID', $this->plugin_name), array($this, 'field_text'), $this->plugin_name,
            'section_security', array('label_for' => 'id_kid', 'name' => 'kid', 'class' => 'regular-text'));
        add_settings_field('field_privatekey', __('Private key', $this->plugin_name), array($this, 'field_textarea'),
            $this->plugin_name, 'section_security',
            array('label_for' => 'id_privatekey', 'name' => 'privatekey', 'rows' => '10', 'cols' => '65'));
        add_settings_field('field_storage', __('Offer platform storage to tools?', $this->plugin_name),
            array($this, 'field_checkbox'), $this->plugin_name, 'section_security',
            array('label_for' => 'id_storage', 'name' => 'storage'));
    }

    /**
     * Display HTML for general section of settings page.
     *
     * @since    1.0.0
     */
    public function section_general()
    {

    }

    /**
     * Display HTML for privacy section of settings page.
     *
     * @since    1.0.0
     */
    public function section_privacy()
    {

    }

    /**
     * Display HTML for roles section of settings page.
     *
     * @since    1.0.0
     */
    public function section_roles()
    {
        echo '<p>' . esc_html__('Select the default LTI role(s) to be passed to a tool for each WordPress role.', $this->plugin_name) . "</p>\n";
    }

    /**
     * Display HTML for presentation section of settings page.
     *
     * @since    1.0.0
     */
    public function section_presentation()
    {

    }

    /**
     * Display HTML for security section of settings page.
     *
     * @since    1.0.0
     */
    public function section_security()
    {

    }

    /**
     * Display HTML for a checkbox on settings page.
     *
     * @since    1.0.0
     */
    public function field_checkbox($args)
    {
        $checked = LTI_Platform::getOption($args['name'], 'false');
        echo('<input id="' . esc_attr($args['label_for']) . '" type="checkbox" aria-required="false" value="true" name="' . esc_attr(LTI_Platform::get_settings_name()) . '[' . esc_attr($args['name']) . ']"' .
        checked($checked === 'true', true, false) . '>' . "\n");
    }

    /**
     * Display HTML for a text field on settings page.
     *
     * @since    1.0.0
     */
    public function field_text($args)
    {
        $text = LTI_Platform::getOption($args['name'], '');
        echo('<input id="' . esc_attr($args['label_for']) . '" type="text" class="' . esc_attr($args['class']) . '" aria-required="false" value="');
        echo(esc_attr($text));
        echo('" name="' . esc_attr(LTI_Platform::get_settings_name()) . '[' . esc_attr($args['name']) . ']">' . "\n");
    }

    /**
     * Display HTML for a textarea on settings page.
     *
     * @since    1.0.0
     */
    public function field_textarea($args)
    {
        $textarea = LTI_Platform::getOption($args['name'], '');
        echo('<textarea id=" ' . esc_attr($args['label_for']) . '" class="code large-text" name="' . esc_attr(LTI_Platform::get_settings_name()) . '[' . esc_attr($args['name']) . ']" class="code" rows="' . esc_attr($args['rows']) . '" cols="' . esc_attr($args['cols']) . '">');
        echo(esc_textarea($textarea));
        echo('</textarea>' . "\n");
    }

    /**
     * Display HTML for a role selection on settings page.
     *
     * @since    1.0.0
     */
    public function field_role($args)
    {
        $roles = LTI_Platform::getOption($args['name'], array());
        echo('<select id="' . esc_attr($args['label_for']) . '" name="' . esc_attr(LTI_Platform::get_settings_name()) . '[' . esc_attr($args['name']) . '][]" size="6" multiple>' . "\n");
        echo('  <option value="administrator"' . selected(in_array('administrator', $roles), true, false) . '>Administrator</option>' . "\n");
        echo('  <option value="contentdeveloper"' . selected(in_array('contentdeveloper', $roles), true, false) . '>Content developer</option>' . "\n");
        echo('  <option value="instructor"' . selected(in_array('instructor', $roles), true, false) . '>Instructor</option>' . "\n");
        echo('  <option value="learner"' . selected(in_array('learner', $roles), true, false) . '>Learner</option>' . "\n");
        echo('  <option value="mentor"' . selected(in_array('mentor', $roles), true, false) . '>Mentor</option>' . "\n");
        echo('  <option value="teachingassistant"' . selected(in_array('teachingassistant', $roles), true, false) . '>Teaching assistant</option>' . "\n");
        echo('</select>' . "\n");
    }

    /**
     * Display HTML for a target selection on settings page.
     *
     * @since    1.0.0
     */
    public function field_target($args)
    {
        $target = LTI_Platform::getOption($args['name'], '');
        echo('<select id="' . esc_attr($args['label_for']) . '" name="' . esc_attr(LTI_Platform::get_settings_name()) . '[' . esc_attr($args['name']) . ']">' . "\n");
        echo('  <option value="window"' . selected($target === 'window', true, false) . '>New window</option>' . "\n");
        echo('  <option value="popup"' . selected($target === 'popup', true, false) . '>Pop-up window</option>' . "\n");
        echo('  <option value="iframe"' . selected($target === 'iframe', true, false) . '>iFrame</option>' . "\n");
        echo('  <option value="embed"' . selected($target === 'embed', true, false) . '>Embed</option>' . "\n");
        echo('</select>' . "\n");
    }

    /**
     * Display HTML for settings page.
     *
     * @since    1.0.0
     */
    public function options_page_html()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (is_multisite()) {
            settings_errors();
        }

        require_once(plugin_dir_path(dirname(__FILE__)) . 'admin/partials/lti-platform-admin-settings.php');
    }

    /**
     * Display HTML for LTI tools list page.
     *
     * @since    1.0.0
     */
    public function view_page_html()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/lti-platform-admin-view.php';
    }

    /**
     * Display HTML for LTI tool edit page.
     *
     * @since    1.0.0
     */
    public function edit_page_html()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (empty($_REQUEST['tool'])) {
            $tool = new LTI_Platform_Tool(LTI_platform::$ltiPlatformDataConnector);
        } else {
            $tool = LTI_Platform_Tool::fromRecordId(intval(sanitize_text_field(wp_unslash($_REQUEST['tool']))),
                LTI_Platform::$ltiPlatformDataConnector);
        }
        if (isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'POST')) {
            $this->update_tool($tool);
        }
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/lti-platform-admin-edit.php';
    }

    /**
     * Display HTML when saving changes was not successful.
     *
     * @since    1.0.0
     */
    public function error_update()
    {
        $allowed = array('em' => array());
        echo('  <div class="notice notice-error">' . "\n");
        echo('    <p>' . esc_html__('Unable to save the changes.', $this->plugin_name) . '</p>' . "\n");
        echo('  </div>' . "\n");
    }

    /**
     * Update LTI tool definition.
     *
     * @since    1.0.0
     */
    private function update_tool($tool)
    {
        $nonce = null;
        if (isset($_REQUEST['_wpnonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce']));
        }
        if (empty($nonce) || !wp_verify_nonce($nonce, "{$this->plugin_name}-nonce")) {
            add_action('all_admin_notices', array($this, 'error_update'));
        } else {
            $tool->name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
            $tool->code = isset($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : '';
            $tool->enabled = !empty($_POST['enabled']) && (sanitize_text_field(wp_unslash($_POST['enabled'])) === 'true');
            $tool->debugMode = !empty($_POST['debugmode']) && (sanitize_text_field(wp_unslash($_POST['debugmode'])) === 'true');
            $tool->setSetting('sendUserName',
                (!empty($_POST['sendusername']) && (sanitize_text_field(wp_unslash($_POST['sendusername'])) === 'true')) ? 'true' : null);
            $tool->setSetting('sendUserId',
                (!empty($_POST['senduserid']) && (sanitize_text_field(wp_unslash($_POST['senduserid'])) === 'true')) ? 'true' : null);
            $tool->setSetting('sendUserEmail',
                (!empty($_POST['senduseremail']) && (sanitize_text_field(wp_unslash($_POST['senduseremail'])) === 'true')) ? 'true' : null);
            $tool->setSetting('sendUserRole',
                (!empty($_POST['senduserrole']) && (sanitize_text_field(wp_unslash($_POST['senduserrole'])) === 'true')) ? 'true' : null);
            $tool->setSetting('sendUserUsername',
                (!empty($_POST['senduserusername']) && (sanitize_text_field(wp_unslash($_POST['senduserusername'])) === 'true')) ? 'true' : null);
            $roles = LTI_Platform::get_roles();
            foreach (array_keys($roles) as $role) {
                if (!empty($_POST["role_{$role}"]) && is_array($_POST["role_{$role}"])) {
                    $param = array_map('wp_unslash', $_POST["role_{$role}"]);
                    $param = array_map('sanitize_text_field', $param);
                    $param = implode(',', $param);
                } else {
                    $param = null;
                }
                $tool->setSetting("role_{$role}", $param);
            }
            $tool->setSetting('presentationTarget',
                (!empty($_POST['presentationtarget'])) ? sanitize_text_field(wp_unslash($_POST['presentationtarget'])) : null);
            $tool->setSetting('presentationWidth',
                (!empty($_POST['presentationwidth'])) ? sanitize_text_field(wp_unslash($_POST['presentationwidth'])) : null);
            $tool->setSetting('presentationHeight',
                (!empty($_POST['presentationheight'])) ? sanitize_text_field(wp_unslash($_POST['presentationheight'])) : null);
            $tool->setSetting('presentationClass',
                (!empty($_POST['presentationclass'])) ? sanitize_text_field(wp_unslash($_POST['presentationclass'])) : null);
            $tool->setSetting('presentationStyle',
                (!empty($_POST['presentationstyle'])) ? sanitize_text_field(wp_unslash($_POST['presentationstyle'])) : null);
            $tool->messageUrl = (!empty($_POST['messageurl'])) ? esc_url_raw(wp_unslash($_POST['messageurl'])) : null;
            $tool->useContentItem = !empty($_POST['usecontentitem']) && (sanitize_text_field(wp_unslash($_POST['usecontentitem'])) === 'true');
            $tool->contentItemUrl = (!empty($_POST['contentitemurl'])) ? esc_url_raw(wp_unslash($_POST['contentitemurl'])) : null;
            $tool->setSetting('custom',
                (!empty($_POST['custom'])) ? str_replace("\r\n", '&#13;&#10;', sanitize_textarea_field(wp_unslash($_POST['custom']))) : null);
            $tool->setKey(!empty($_POST['consumerkey']) ? sanitize_text_field(wp_unslash($_POST['consumerkey'])) : null);
            $tool->secret = (!empty($_POST['sharedsecret'])) ? sanitize_text_field(wp_unslash($_POST['sharedsecret'])) : null;
            $tool->initiateLoginUrl = !empty($_POST['initiateloginurl']) ? sanitize_text_field(wp_unslash($_POST['initiateloginurl'])) : null;
            $redirectionUris = !empty($_POST['redirectionuris']) ? trim(sanitize_textarea_field(wp_unslash($_POST['redirectionuris']))) : null;
            if (!empty($redirectionUris)) {
                $tool->redirectionUris = explode("\r\n", $redirectionUris);
            } else {
                $tool->redirectionUris = array();
            }
            $tool->jku = (!empty($_POST['jwksurl'])) ? sanitize_text_field(wp_unslash($_POST['jwksurl'])) : null;
            $tool->rsaKey = (!empty($_POST['publickey'])) ? sanitize_textarea_field(wp_unslash($_POST['publickey'])) : null;
            if (empty($tool->initiateLoginUrl) || empty($tool->redirectionUris)) {
                $tool->ltiVersion = LTI_Platform::get_lti_version(false);
                $tool->signatureMethod = 'HMAC-SHA1';
            } else {
                $tool->ltiVersion = LTI_Platform::get_lti_version(true);
                $tool->signatureMethod = 'RS256';
            }
            $tool->save();
        }
    }

}
