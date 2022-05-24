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

    const POST_TYPE = 'lti-platform-tool';
    const POST_TYPE_NETWORK = 'lti-platform-ms-tool';

    public $blogId = null;
    public $code = null;
    public $useContentItem = false;
    public $contentItemUrl = null;
    public $deleted = false;
    private static $options = null;

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

    public static function getOption($name, $default)
    {
        self::getOptions();
        if (array_key_exists($name, self::$options)) {
            $default = self::$options[$name];
        }
        return $default;
    }

    public function __construct($dataConnector = null)
    {
        parent::__construct($dataConnector);
        $options = self::getOptions();
        $this->setSetting('sendUserName',
            (isset($options['sendusername']) && ($options['sendusername'] === 'true')) ? 'true' : 'false');
        $this->setSetting('sendUserId', (isset($options['senduserid']) && ($options['senduserid'] === 'true')) ? 'true' : 'false');
        $this->setSetting('sendUserEmail',
            (isset($options['senduseremail']) && ($options['senduseremail'] === 'true')) ? 'true' : 'false');
        $this->setSetting('sendUserRole',
            (isset($options['senduserrole']) && ($options['senduserrole'] === 'true')) ? 'true' : 'false');
        $this->setSetting('sendUserUsername',
            (isset($options['senduserusername']) && ($options['senduserusername'] === 'true')) ? 'true' : 'false');
        $this->setSetting('presentationTarget', (!empty($options['presentationtarget'])) ? $options['presentationtarget'] : '');
        $this->setSetting('presentationWidth', (!empty($options['presentationwidth'])) ? $options['presentationwidth'] : '');
        $this->setSetting('presentationHeight', (!empty($options['presentationheight'])) ? $options['presentationheight'] : '');
    }

    public function save($quiet = false)
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
                if (!$quiet) {
                    add_action('all_admin_notices', array($this, 'save_notice_duplicate'));
                }
                break;
            }
        }
        if ($ok) {
            if ($this->enabled && !$this->canBeEnabled()) {
                $this->enabled = false;
                if (!$quiet) {
                    add_action('all_admin_notices', array($this, 'save_notice_disabled'));
                }
            }
            $ok = $this->dataConnector->saveTool($this);
            if ($ok) {
                if (!$quiet) {
                    add_action('all_admin_notices', array($this, 'save_notice_success'));
                }
            } else if (!$quiet) {
                add_action('all_admin_notices', array($this, 'save_notice_error'));
            }
        }

        return $ok;
    }

    public function save_notice($message, $type = 'success')
    {
        echo('    <div class="notice notice-' . esc_html($type) . ' is-dismissible">' . "\n");
        echo('        <p>' . esc_html($message) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function save_notice_success()
    {
        $this->save_notice('Tool updated.');
    }

    public function save_notice_error()
    {
        $this->save_notice('An error occurred when saving tool.', 'error');
    }

    public function save_notice_duplicate()
    {
        $this->save_notice('A tool already exists with this code.', 'error');
    }

    public function save_notice_disabled()
    {
        $this->save_notice('This tool cannot be enabled because it is not fully configured for either LTI 1.0 or LTI 1.3, or no private key has been defined.',
            'warning');
    }

    public function trash()
    {
        return $this->dataConnector->trashTool($this);
    }

    public function restore()
    {
        return $this->dataConnector->restoreTool($this);
    }

    public function canUseLTI13()
    {
        self::getOptions();
        return !empty($this->initiateLoginUrl) && !empty($this->redirectionUris) &&
            !empty(self::$options['kid']) && !empty(self::$options['privatekey']);
    }

    public static function all($args = array())
    {
        return LTI_Platform::$ltiPlatformDataConnector->getToolsWithArgs($args);
    }

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

    public function canBeEnabled()
    {
        return !empty($this->messageUrl) &&
            ((!empty($this->getKey()) && !empty($this->secret)) || $this->canUseLTI13());
    }

}
