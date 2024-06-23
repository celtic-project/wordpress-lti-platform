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
 * Define the WordPress LTI Tool class.
 *
 * Extends the Tool class to include methods specific to a WordPress instance of an LTI tool.
 *
 * @since      1.0.0
 * @package    LTI_Platform
 * @subpackage LTI_Platform/includes
 * @author     Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */
use ceLTIc\LTI\Tool;

class LTI_Platform_Tool extends Tool
{

    /**
     * Type of WordPress post for a site-level LTI tool.
     */
    const POST_TYPE = 'lti-platform-tool';

    /**
     * Type of WordPress post for a network-level LTI tool.
     */
    const POST_TYPE_NETWORK = 'lti-platform-ms-tool';

    /**
     * The ID of the site associated with the tool.
     *
     * @since    1.0.0
     * @access   public
     * @var      int       $blogId    The ID of the site associated with the tool.
     */
    public $blogId = null;

    /**
     * The code of the LTI tool.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $code      Code for LTI tool.
     */
    public $code = null;

    /**
     * Whether the LTI tool supports the content-item (deep linking) message.
     *
     * @since    1.0.0
     * @access   public
     * @var      bool      $useContentItem  True if content-item (deep linking) message is supported.
     */
    public $useContentItem = false;

    /**
     * URL for content-item (deep linking) message.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $contentItemUrl  URL for content-item (deep linking) message.
     */
    public $contentItemUrl = null;

    /**
     * Whether the tool has been moved to the trash bin.
     *
     * @since    1.0.0
     * @access   public
     * @var      bool      $deleted   True when the tool is in the trash bin.
     */
    public $deleted = false;

    /**
     * Whether messages should be displayed.
     *
     * @since    2.1.0
     * @access   public
     * @var      bool      $showMessages   True when messages are to be displayed.
     */
    public $showMessages = true;

    /**
     * Get the LTI tool from its code.
     *
     * @since    1.0.0
     * @access   public static
     * @param    string    $code      Code for LTI tool.
     * @param    DataConnector_wp  $dataConnector  Data connector object
     *
     * @return   LTI_Platform_Tool Tool instance
     */
    public static function fromCode($code, $dataConnector)
    {
        $tool = null;
        $post = null;
        if (is_multisite()) {
            switch_to_blog(0);
            $post = get_page_by_path($code, OBJECT, LTI_Platform_Tool::POST_TYPE_NETWORK);
            restore_current_blog();
            if ($post instanceof WP_POST) {
                $tool = new self($dataConnector);
                LTI_Platform::$ltiPlatformDataConnector->fromPost($tool, $post, 0);
                if (!$tool->enabled) {
                    $tool = null;
                }
            }
        }
        if (empty($tool)) {
            $post = get_page_by_path($code, OBJECT, LTI_Platform_Tool::POST_TYPE);
            if ($post instanceof WP_POST) {
                $tool = new self($dataConnector);
                LTI_Platform::$ltiPlatformDataConnector->fromPost($tool, $post, get_current_blog_id());
            }
        }

        return $tool;
    }

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     * @param    DataConnector_wp  $dataConnector  Data connector object
     */
    public function __construct($dataConnector = null)
    {
        parent::__construct($dataConnector);
        $this->setSetting('sendUserName', LTI_Platform::getOption('sendusername', 'false'));
        $this->setSetting('sendUserId', LTI_Platform::getOption('senduserid', 'false'));
        $this->setSetting('sendUserEmail', LTI_Platform::getOption('senduseremail', 'false'));
        $this->setSetting('sendUserRole', LTI_Platform::getOption('senduserrole', 'false'));
        $this->setSetting('sendUserUsername', LTI_Platform::getOption('senduserusername', 'false'));
        $this->setSetting('presentationTarget', LTI_Platform::getOption('presentationtarget', ''));
        $this->setSetting('presentationWidth', LTI_Platform::getOption('presentationwidth', ''));
        $this->setSetting('presentationHeight', LTI_Platform::getOption('presentationheight', ''));
        $options = LTI_Platform::getOptions();
        foreach ($options as $name => $value) {
            if (strpos($name, 'role_') === 0) {
                $this->setSetting($name, implode(',', $options[$name]));
            }
        }
    }

    /**
     * Save the tool.
     *
     * @since    1.0.0
     *
     * @return   bool      True if the tool was successfully saved.
     */
    public function save(): bool
    {
        $platform = new LTI_Platform_Platform(LTI_platform::$ltiPlatformDataConnector);
        $tools = $platform->getTools();
        $ok = true;
        $this->code = strtolower($this->code);
        foreach ($tools as $tool) {
            if ($tool->getRecordId() === $this->getRecordId()) {
                continue;
            } elseif ($tool->code === $this->code) {
                $ok = false;
                if ($this->showMessages) {
                    add_action('all_admin_notices', array($this, 'save_notice_duplicate'));
                }
                break;
            }
        }
        if ($ok) {
            if ($this->enabled && !$this->canBeEnabled()) {
                $this->enabled = false;
                if ($this->showMessages) {
                    add_action('all_admin_notices', array($this, 'save_notice_disabled'));
                }
            }
            $ok = $this->dataConnector->saveTool($this);
            if ($ok) {
                if ($this->showMessages) {
                    add_action('all_admin_notices', array($this, 'save_notice_success'));
                }
            } else if ($this->showMessages) {
                add_action('all_admin_notices', array($this, 'save_notice_error'));
            }
        }

        return $ok;
    }

    /**
     * Display a message.
     *
     * @since    1.0.0
     * @param    string    Message to display
     * @param    string    Type of message ('success' or 'error') to display
     */
    public function save_notice($message, $type = 'success')
    {
        echo('    <div class="notice notice-' . esc_html($type) . ' is-dismissible">' . "\n");
        echo('        <p>' . esc_html($message) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Display a message when a tool has been successfully saved.
     *
     * @since    1.0.0
     */
    public function save_notice_success()
    {
        $this->save_notice('Tool updated.');
    }

    /**
     * Display a message when saving a tool has not been successful.
     *
     * @since    1.0.0
     */
    public function save_notice_error()
    {
        $this->save_notice('An error occurred when saving tool.', 'error');
    }

    /**
     * Display a message when saving a tool has not been successful because its code is already in use.
     *
     * @since    1.0.0
     */
    public function save_notice_duplicate()
    {
        $this->save_notice('A tool already exists with this code.', 'error');
    }

    /**
     * Display a message when enabling a tool has not been successful.
     *
     * @since    1.0.0
     */
    public function save_notice_disabled()
    {
        $this->save_notice('This tool cannot be enabled because it is not fully configured for either LTI 1.0 or LTI 1.3, or no private key has been defined.',
            'warning');
    }

    /**
     * Move the tool to the trash bin.
     *
     * @since    1.0.0
     * @return   bool      True if successful.
     */
    public function trash()
    {
        return $this->dataConnector->trashTool($this);
    }

    /**
     * Restore the tool from the trash bin.
     *
     * @since    1.0.0
     * @return   bool      True if successful.
     */
    public function restore()
    {
        return $this->dataConnector->restoreTool($this);
    }

    /**
     * Check whether the tool can be accessed using LTI 1.3.
     *
     * @since    1.0.0
     * @return   bool      True if LTI 1.3 is available.
     */
    public function canUseLTI13()
    {
        return !empty($this->initiateLoginUrl) && !empty($this->redirectionUris) &&
            !empty(LTI_Platform::getOption('kid', '')) && !empty(LTI_Platform::getOption('privatekey', ''));
    }

    /**
     * Get defined LTI tools.
     *
     * @since    1.0.0
     * @return   array     Array of LTI tools.
     */
    public static function all($args = array())
    {
        return LTI_Platform::$ltiPlatformDataConnector->getToolsWithArgs($args);
    }

    /**
     * Register the LTI tool types.
     *
     * @since    1.0.0
     */
    public static function register()
    {
        register_post_type(self::POST_TYPE,
            array(
                'labels' => array(
                    'name' => __('LTI Tools', LTI_Platform::get_plugin_name()),
                    'singular_name' => __('LTI Tool', LTI_Platform::get_plugin_name()),
                ),
                'rewrite' => false,
                'query_var' => false,
                'public' => false,
                'capability_type' => 'page',
        ));
        register_post_type(self::POST_TYPE_NETWORK,
            array(
                'labels' => array(
                    'name' => __('Network LTI Tools', LTI_Platform::get_plugin_name()),
                    'singular_name' => __('Network LTI Tool', LTI_Platform::get_plugin_name()),
                ),
                'rewrite' => false,
                'query_var' => false,
                'public' => false,
                'capability_type' => 'page',
        ));
        add_shortcode(LTI_Platform::get_plugin_name(), array('LTI_Platform_Tool', 'shortcode'));
    }

    /**
     * Get the HTML to replace the shortcode for an LTI tool.
     *
     * @since    1.0.0
     * @return   string    HTML to display.
     */
    public static function shortcode($atts, $content, $tag)
    {
        global $post;

        $html = '<em>LTI link appears here</em>';

        $atts = shortcode_atts(array(
            'tool' => '',
            'id' => '',
            'custom' => '',
            'target' => '',
            'width' => '',
            'height' => ''), $atts);

        $error = '';
        $missing = array();
        if (empty($atts['tool'])) {
            $missing[] = 'tool';
        }
        if (empty($atts['id'])) {
            $missing[] = 'id';
        }
        if (!empty($missing)) {
            $error = 'Missing attribute(s): ' . implode(', ', $missing);
        }
        if (empty($error)) {
            $tool = LTI_Platform_Tool::fromCode($atts['tool'], LTI_Platform::$ltiPlatformDataConnector);
            if (empty($tool)) {
                $error = 'Tool parameter not recognised: ' . $atts['tool'];
            }
        }
        if (empty($error) && !$tool->enabled) {
            $error = 'LTI Tool is not available';
        }
        if (empty($error)) {
            $target = (!empty($atts['target'])) ? $atts['target'] : $tool->getSetting('presentationTarget');
            if (!in_array($target, array('window', 'popup', 'iframe', 'embed'))) {
                $error = 'Invalid presentation target: ' . $target;
            }
        }
        if (empty($error)) {
            if (!empty($content)) {
                $link_text = $content;
            } else {
                $link_text = $atts['tool'];
            }
            if (($target === 'popup') || ($target === 'embed')) {
                $width = $tool->getSetting('presentationWidth');
                if (!empty($atts['width'])) {
                    $width = intval($atts['width']);
                }
                $height = $tool->getSetting('presentationHeight');
                if (!empty($atts['height'])) {
                    $height = intval($atts['height']);
                }
                if ($target === 'popup') {
                    $sep = ',';
                    $sep2 = '=';
                    if (empty($width)) {
                        $width = '800';
                    }
                    if (empty($height)) {
                        $height = '500';
                    }
                } else {
                    $sep = ';';
                    $sep2 = ': ';
                    if (empty($width)) {
                        $width = '100%';
                    }
                    if (empty($height)) {
                        $height = '400px';
                    }
                }
                $size = '';
                if (!empty($width)) {
                    $size = "width{$sep2}{$width}{$sep}";
                }
                if (!empty($height)) {
                    $size .= "height{$sep2}{$height}{$sep}";
                }
                if (!empty($size) && ($target === 'popup')) {
                    $size = substr($size, 0, -1);
                }
            }
            $url = add_query_arg(array(LTI_Platform::get_plugin_name() => '', 'post' => $post->ID, 'id' => $atts['id']),
                get_site_url());
            switch ($target) {
                case 'window':
                    $html = "<a href=\"{$url}\" title=\"Launch {$atts['tool']} tool\" target=\"_blank\">{$link_text}</a>";
                    break;
                case 'popup':
                    $html = "<a href=\"#\" title=\"Launch {$atts['tool']} tool\" onclick=\"window.open('{$url}', '', '{$size}'); return false;\">{$link_text}</a>";
                    break;
                case 'iframe':
                    $url = add_query_arg(array('embed' => ''), $url);
                    $html = "<a href=\"{$url}\" title=\"Embed {$atts['tool']} tool\">{$link_text}</a>";
                    break;
                case 'embed':
                    $html = "{$content}</p><div><iframe style=\"border: none;{$size}\" class=\"\" src=\"{$url}\" allowfullscreen></iframe></div><p>";
                    break;
            }
        } else {
            $html = "<strong>{$error}</strong>";
        }

        return $html;
    }

    /**
     * Check whether an LTI tool can be enabled.
     *
     * @since    1.0.0
     * @return   bool      True if the tool can be enabled.
     */
    public function canBeEnabled()
    {
        return !empty($this->messageUrl) &&
            ((!empty($this->getKey()) && !empty($this->secret)) || $this->canUseLTI13());
    }

}
