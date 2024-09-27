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
 * The public-facing functionality of the plugin.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-lti-platform
 * @since      1.0.0
 * @package    LTI_Platform
 * @subpackage LTI_Platform/public
 * @author     Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */
use ceLTIc\LTI;

class LTI_Platform_Public
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
     * @param    string    $plugin_name       The name of the plugin.
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
    public function enqueue_scripts()
    {
        if (LTI_Platform::getOption('storage', 'false') === 'true') {
            wp_enqueue_script("{$this->plugin_name}-storagejs",
                get_option('siteurl') . '/?' . LTI_Platform::get_plugin_name() . '&storagejs', array(), $this->version, false);
        }
    }

    /**
     * Process a URL for this plugin.
     *
     * @since    1.0.0
     */
    public function parse_request()
    {
        if (isset($_GET[LTI_Platform::get_plugin_name()])) {
            if (isset($_GET['tools'])) {
                header('Content-type: text/html; charset=UTF-8');
                $allowed = array('div' => array('class' => true), 'h2' => array(), 'p' => array(), 'br' => array(), 'input' => array('type' => true, 'name' => true, 'class' => true, 'value' => true, 'toolname' => true), 'button' => array('class' => true, 'id' => true, 'disabled' => true));
                echo(wp_kses($this->get_tools_list(), $allowed));
            } else if (isset($_GET['usecontentitem'])) {
                if (!empty($_GET['tool'])) {
                header('Content-type: application/json; charset=UTF-8');
                    echo(wp_json_encode($this->get_tool(sanitize_text_field(wp_unslash($_GET['tool'])))));
                }
            } else if (isset($_GET['keys'])) {
                $jwt = LTI\Jwt\Jwt::getJwtClient();
                $keys = $jwt::getJWKS(LTI_Platform::getOption('privatekey', ''), 'RS256', LTI_Platform::getOption('kid', ''));
                header('Content-type: application/json; charset=UTF-8');
                echo(wp_json_encode($keys));
            } else if (isset($_GET['auth'])) {
                $this->handleRequest();
            } else if (isset($_GET['storagejs'])) {
                header('Content-Type: text/javascript; charset=UTF-8');
                echo LTI\Platform::getStorageJS();
            } else if (isset($_GET['embed'])) {
                $this->renderTool();
            } else if (isset($_GET['content'])) {
                $this->content(sanitize_text_field(wp_unslash($_GET['tool'])));
            } else if (isset($_GET['deeplink'])) {
                $this->message(true);
            } else if (isset($_GET['post'])) {
                $this->message();
            }
            exit;
        }
    }

    /**
     * Handle an incoming LTI message.
     *
     * @since    1.0.0
     */
    private function handleRequest()
    {
        $ok = !empty($_REQUEST['client_id']);
        if ($ok) {
            $tool = LTI_Platform_Tool::fromCode(sanitize_text_field(wp_unslash($_REQUEST['client_id'])),
                    LTI_Platform::$ltiPlatformDataConnector);
            $ok = !empty($tool->created);
        }
        if ($ok) {
            LTI\Tool::$defaultTool = $tool;
            $platform = $this->get_platform();
            $platform->handleRequest();
        } else {
            $this->error_page(__('Tool not found.', LTI_Platform::get_plugin_name()));
        }
    }

    /**
     * Display the LTI tool in an iframe.
     *
     * @since    1.0.0
     */
    private function renderTool()
    {
        $allowed = array('em' => array());
        $reason = null;
        get_header();
        echo('		<div id="primary" class="content-area">' . "\n");
        echo('          <div id="content" class="site-content" role="main">' . "\n");
        $ok = !empty($_GET['post']);
        if ($ok) {
            $post = $this->get_post(intval(sanitize_text_field(wp_unslash($_GET['post']))));
        $ok = !empty($post);
        }
        if (!$ok) {
            $reason = 'Missing or invalid post attribute in link';
        } else {
            $ok = !empty($_GET['id']);
            if (!$ok) {
                $reason = 'Missing id attribute in link';
            }
        }
        if ($ok) {
            $link_atts = $this->get_link_atts($post, sanitize_text_field(wp_unslash($_GET['id'])));
            $ok = !empty($link_atts['tool']);
            if (!$ok) {
                $reason = 'No tool specified';
            }
        }
        if ($ok) {
            $tool = LTI_Platform_Tool::fromCode($link_atts['tool'], LTI_Platform::$ltiPlatformDataConnector);
            $ok = !empty($tool);
            if (!$ok) {
                $reason = 'Tool not found';
            }
        }
        if ($ok) {
            $url = esc_url(add_query_arg(array(LTI_Platform::get_plugin_name() => '', 'post' => $post->ID, 'id' => $link_atts['id']),
                    get_site_url()));
            $width = $tool->getSetting('presentationWidth');
            if (!empty($atts['width'])) {
                $width = intval($atts['width']);
            }
            $height = $tool->getSetting('presentationHeight');
            if (!empty($atts['height'])) {
                $height = intval($atts['height']);
            }
            if (empty($width)) {
                $width = '100%';
            }
            if (empty($height)) {
                $height = '400px';
            }
            $size = '';
            if (!empty($width)) {
                $size = " width: {$width};";
            }
            if (!empty($height)) {
                $size .= " height: {$height};";
            }
            echo('            <iframe style="border: none; overflow: scroll;' . esc_attr($size) . '" src="' . esc_attr($url) . '" allowfullscreen></iframe>');
        } else {
            $message = __('Sorry, the LTI tool could not be launched.', LTI_Platform::get_plugin_name());
            if (!empty($reason)) {
                $debug = $tool->debugMode || (LTI_Platform::getOption('debug', 'false') === 'true');
                if ($debug) {
                    $message .= ' <em>[' . esc_html($reason) . ']</em>';
                }
            }
            echo('            <p><strong>' . wp_kses($message, $allowed) . '</strong></p>' . "\n");
        }
        echo('          </div>' . "\n");
        echo('        </div>' . "\n");
        get_sidebar();
        get_footer();
    }

    /**
     * Send an LTI message to a tool.
     *
     * @since    1.0.0
     * @param    bool      $deeplink  Whether a content-item (deep linking) message is to be sent
     */
    public function message($deeplink = false)
    {
        $debug = false;
        $reason = null;
        $ok = !empty($_GET['post']);
        if ($ok) {
            $post = $this->get_post(intval(sanitize_text_field(wp_unslash($_GET['post']))));
        $ok = !empty($post);
        }
        if (!$ok) {
            $reason = __('Missing or invalid post attribute in link', LTI_Platform::get_plugin_name());
        } else if (!$deeplink) {
            $ok = !empty($_GET['id']);
            if (!$ok) {
                $reason = __('Missing id attribute in link', LTI_Platform::get_plugin_name());
            }
        }
        if ($ok) {
            if (!$deeplink) {
                $link_atts = $this->get_link_atts($post, sanitize_text_field(wp_unslash($_GET['id'])));
                $ok = !empty($link_atts['tool']);
                if (!$ok) {
                    $reason = __('No tool specified', LTI_Platform::get_plugin_name());
                }
            } elseif (empty($_GET['tool'])) {
                $ok = false;
                $reason = __('Missing tool attribute in link', LTI_Platform::get_plugin_name());
            } else {
                $link_atts['tool'] = sanitize_text_field(wp_unslash($_GET['tool']));
            }
        }
        if ($ok) {
            $tool = LTI_Platform_Tool::fromCode($link_atts['tool'], LTI_Platform::$ltiPlatformDataConnector);
            $ok = !empty($tool);
            if (!$ok) {
                $reason = __('Tool not found', LTI_Platform::get_plugin_name());
            }
        }
        if ($ok && !$deeplink) {
            $debug = $tool->debugMode;
            $ok = !empty($link_atts['id']);
            if (!$ok) {
                $reason = __('Duplicate id attribute in link', LTI_Platform::get_plugin_name());
            }
        }
        if ($ok) {
            $target = (!empty($link_atts['target'])) ? $link_atts['target'] : $tool->getSetting('presentationTarget', 'window');
            $ok = in_array($target, array('window', 'popup', 'iframe', 'embed'));
            if (!$ok) {
                $reason = __('Invalid target specified', LTI_Platform::get_plugin_name());
            }
        }
        if ($ok) {
            if (empty($link_atts['url'])) {
                $url = $tool->messageUrl;
            } elseif (strpos($link_atts['url'], '://') === false) {
                $url = "{$tool->messageUrl}{$link_atts['url']}";
            } elseif (strpos($link_atts['url'], $tool->messageUrl) === 0) {
                $url = $link_atts['url'];
            } else {
                $ok = false;
                $reason = __('Invalid url attribute', LTI_Platform::get_plugin_name());
            }
        }
        if ($ok) {
            $user = wp_get_current_user();
            LTI\Tool::$defaultTool = $tool;
            $platform = $this->get_platform();
            if (!empty($link_atts['title'])) {
                $title = $link_atts['title'];
            } else {
                $title = (!empty($link_text)) ? $link_text : $link_atts['tool'];
            }
            $params = array(
                'context_id' => strval($post->ID),
                'context_title' => $post->post_title,
                'context_type' => 'CourseSection',
                'launch_presentation_document_target' => ($target !== 'popup') ? $target : 'window',
                'tool_consumer_info_product_family_code' => 'WordPress',
                'tool_consumer_info_version' => get_bloginfo('version'),
                'tool_consumer_instance_name' => get_bloginfo('name'),
                'tool_consumer_instance_description' => get_bloginfo('description'),
                'tool_consumer_instance_url' => get_site_url(),
                'tool_consumer_instance_contact_email' => get_bloginfo('admin_email'),
            );
            if (!empty(LTI_Platform::getOption('platformguid', ''))) {
                $params['tool_consumer_instance_guid'] = LTI_Platform::getOption('platformguid', '');
            }
            if (!$deeplink) {
                $msg = 'basic-lti-launch-request';
                $params['resource_link_id'] = "{$post->ID}-{$link_atts['id']}";
                $params['resource_link_title'] = $title;
            } else {
                $msg = 'ContentItemSelectionRequest';
                $params['accept_media_types'] = 'application/vnd.ims.lti.v1.ltilink,*/*';
                $params['accept_multiple'] = 'false';
                $params['accept_presentation_document_targets'] = 'embed,frame,iframe,window,popup';
                $params['content_item_return_url'] = get_option('siteurl') . '/?' . LTI_Platform::get_plugin_name() . '&content&tool=' . urlencode($link_atts['tool']);
            }
            if (($target === 'popup') || ($target === 'iframe') || ($target === 'embed')) {
                $width = $tool->getSetting('presentationWidth');
                if (!empty($link_atts['width'])) {
                    $width = intval($link_atts['width']);
                }
                if (!empty($width)) {
                    $params['launch_presentation_width'] = $width;
                }
                $height = $tool->getSetting('presentationHeight');
                if (!empty($link_atts['height'])) {
                    $height = intval($link_atts['height']);
                }
                if (!empty($height)) {
                    $params['launch_presentation_height'] = $height;
                }
            }
            if ($tool->getSetting('sendUserId', 'false') === 'true') {
                $params['user_id'] = strval($user->ID);
            }
            if ($tool->getSetting('sendUserName', 'false') === 'true') {
                if (!empty($user->display_name)) {
                    $params['lis_person_name_full'] = $user->display_name;
                }
                if (!empty($user->first_name)) {
                    $params['lis_person_name_given'] = $user->first_name;
                }
                if (!empty($user->last_name)) {
                    $params['lis_person_name_family'] = $user->last_name;
                }
            }
            if ($tool->getSetting('sendUserEmail', 'false') === 'true') {
                $params['lis_person_contact_email_primary'] = $user->user_email;
            }
            if ($tool->getSetting('sendUserRole', 'false') === 'true') {
                $roles = array();
                foreach ($user->roles as $role) {
                    if (!empty(LTI_Platform::getOption("role_{$role}", ''))) {
                        $roles = array_merge($roles, explode(',', $tool->getSetting("role_{$role}", '')));
                    }
                }
                $roles = array_unique($roles);
                $ltiroles = array();
                foreach ($roles as $role) {
                    if ($platform->ltiVersion === LTI_Platform::get_lti_version(false)) {
                        switch ($role) {
                            case 'administrator':
                                $ltiroles[] = 'urn:lti:sysrole:ims/lis/Administrator';
                                $ltiroles[] = 'urn:lti:instrole:ims/lis/Administrator';
                                break;
                            case 'contentdeveloper':
                                $ltiroles[] = 'urn:lti:role:ims/lis/ContentDeveloper';
                                break;
                            case 'instructor':
                                $ltiroles[] = 'urn:lti:role:ims/lis/Instructor';
                                break;
                            case 'learner':
                                $ltiroles[] = 'urn:lti:role:ims/lis/Learner';
                                break;
                            case 'mentor':
                                $ltiroles[] = 'urn:lti:role:ims/lis/Mentor';
                                break;
                            case 'teachingassistant':
                                $ltiroles[] = 'urn:lti:role:ims/lis/TeachingAssistant';
                                break;
                        }
                    } else {
                        switch ($role) {
                            case 'administrator':
                                $ltiroles[] = 'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator';
                                $ltiroles[] = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator';
                                break;
                            case 'contentdeveloper':
                                $ltiroles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#ContentDeveloper';
                                break;
                            case 'instructor':
                                $ltiroles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';
                                break;
                            case 'learner':
                                $ltiroles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner';
                                break;
                            case 'mentor':
                                $ltiroles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor';
                                break;
                            case 'teachingassistant':
                                $ltiroles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';
                                $ltiroles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant';
                                break;
                        }
                    }
                }
                $params['roles'] = implode(',', $ltiroles);
            }
            if ($tool->getSetting('sendUserUsername', 'false') === 'true') {
                $params['ext_username'] = $user->user_login;
            }
            $custom = array();
            if (!empty($link_atts['custom'])) {
                $custom = $link_atts['custom'];
                $ampersand = $this->getSubstituteString($custom);
                $custom = str_replace('&', $ampersand, $custom);
                $semicolon = $this->getSubstituteString($custom);
                $custom = str_replace('\\;', $semicolon, $custom);
                parse_str(str_replace(';', '&', $custom), $custom);
                foreach ($custom as $name => $value) {
                    $lcname = preg_replace('/[^a-z0-9]/', '_', strtolower(trim($name)));
                    if (!empty($lcname)) {
                        $value = str_replace($ampersand, '&', $value);
                        $value = str_replace($semicolon, ';', $value);
                        $params["custom_{$lcname}"] = $value;
                        if (($platform->ltiVersion === LTI_Platform::get_lti_version(true)) && ($name !== $lcname)) {
                            $params["custom_{$name}"] = $value;
                        }
                    }
                }
            }
            if (!empty($tool->getSetting('custom'))) {
                parse_str(str_replace('&#13;&#10;', '&', $tool->getSetting('custom')), $custom);
                foreach ($custom as $name => $value) {
                    $lcname = preg_replace('/[^a-z0-9]/', '_', strtolower(trim($name)));
                    if (!empty($lcname)) {
                        $params["custom_{$lcname}"] = $value;
                        if (($platform->ltiVersion === LTI_Platform::get_lti_version(true)) && ($name !== $lcname)) {
                            $params["custom_{$name}"] = $value;
                        }
                    }
                }
            }
            echo($platform->sendMessage($url, $msg, $params));
            $day = date('Y-m-d');
            if ($day !== date('Y-m-d', $tool->lastAccess)) {
                $tool->lastAccess = strtotime($day);  // Update last access
                $tool->save();
            }
        } else {
            $this->error_page($reason, $debug);
        }
    }

    /**
     * Get the WordPress post for an LTI tool.
     *
     * @since    1.0.0
     * @param    int       $post_id   ID of WordPress post record
     *
     * @return   WP_Post   WordPress post record.
     */
    private function get_post($post_id)
    {
        $post = null;
        if (current_user_can('read_post', $post_id)) {
            $post = get_post($post_id);
        }

        return $post;
    }

    /**
     * Get the attributes from a shortcode.
     *
     * @since    1.0.0
     * @param    WP_Post   $post      WordPress post record
     * @param    string    $id        ID of shortcode instance
     */
    private function get_link_atts($post, $id)
    {
        $link_atts = array();
        $pattern = get_shortcode_regex(array(LTI_Platform::get_plugin_name()));
        if (preg_match_all("/{$pattern}/", $post->post_content, $shortcodes, PREG_SET_ORDER) !== false) {
            $link_text = '';
            foreach ($shortcodes as $shortcode) {
                $atts = $shortcode[3];
                $semicolon = $this->getSubstituteString($atts);
                $atts = str_replace('\\;', $semicolon, $atts);
                $doublequote = $this->getSubstituteString($atts);
                $atts = str_replace('\\"', $doublequote, $atts);
                $atts = shortcode_parse_atts($atts);
                foreach ($atts as $key => $value) {
                    $value = str_replace($semicolon, '\\;', $value);
                    $value = str_replace($doublequote, '\\"', $value);
                    $atts[$key] = $value;
                }
                if (!empty($atts['id']) && ($atts['id'] === $id)) {
                    if (empty($link_atts)) {
                        $link_atts = $atts;
                        $link_text = $shortcode[5];
                    } else {  // Duplicate link
                        unset($link_atts['id']);
                        break;
                    }
                }
            }
        }
        foreach ($link_atts as $key => $value) {
            $link_atts[$key] = str_replace('&amp;', '&', $value);
        }

        return $link_atts;
    }

    /**
     * Get the LTI platform object for connections with an LTI tool.
     *
     * @since    1.0.0
     * @return   Platform  Platform object
     */
    private function get_platform()
    {
        $platform = new LTI_Platform_Platform(LTI_platform::$ltiPlatformDataConnector);
        $platform->setKey(LTI\Tool::$defaultTool->getKey());
        $platform->secret = LTI\Tool::$defaultTool->secret;
        $platform->platformId = get_option('siteurl');
        $platform->clientId = LTI\Tool::$defaultTool->code;
        $platform->deploymentId = strval(get_current_blog_id());
        $platform->kid = LTI_Platform::getOption('kid', '');
        $platform->rsaKey = LTI_Platform::getOption('privatekey', '');
        if (!LTI\Tool::$defaultTool->canUseLTI13()) {
            $platform->ltiVersion = LTI_Platform::get_lti_version(false);
            $platform->signatureMethod = 'HMAC-SHA1';
        } else {
            $platform->ltiVersion = LTI_Platform::get_lti_version(true);
            $platform->signatureMethod = 'RS256';
            if (LTI_Platform::getOption('storage', '') === 'true') {
                $platform::$browserStorageFrame = '_parent';
            }
        }

        return $platform;
    }

    /**
     * Get HTML for selecting an LTI tool.
     *
     * @since    1.0.0
     * @return   string    HTML
     */
    private function get_tools_list()
    {
        $hereValue = function($text) {
            return esc_html($text);
        };
        $hereAttr = function($text) {
            return esc_attr($text);
        };

        $args = array(
            'post_status' => 'publish'
        );
        $tools = LTI_Platform_Tool::all($args);
        if (is_multisite()) {
            switch_to_blog(1);
            $tools = array_merge($tools,
                LTI_Platform_Tool::all(array_merge($args, array('post_type' => LTI_Platform_Tool::POST_TYPE_NETWORK))));
            restore_current_blog();
        }
        ksort($tools, SORT_STRING);

        $list = <<< EOD
<div class="lti-platform-modal">
  <div class="lti-platform-modal-content">
    <h2>LTI Tool</h2>
    <p>

EOD;
        if (!empty($tools)) {
            $list .= <<< EOD
      Select the LTI tool you want to add a link for:
EOD;
            foreach ($tools as $tool) {
                $list .= <<< EOD
<br>
      &nbsp;&nbsp;<input type="radio" name="tool" class="lti-platform-tool" value="{$hereAttr($tool->code)}" toolname="{$hereAttr($tool->name)}">&nbsp;{$hereValue($tool->name)}
EOD;
            }
        } else {
            $list .= <<< EOD
      There are no enabled LTI tools defined.
EOD;
        }
        $list .= <<< EOD

    </p>
    <p>
      <button class="button button-primary" id="lti-platform-select" disabled>Select</button>
      <button class="button" id="lti-platform-cancel">Cancel</button>
    </p>
  </div>
</div>

EOD;

        return $list;
    }

    /**
     * Get whether tool supports the content-item (deep linking) message.
     *
     * @since    1.0.0
     * @param    string    $code      Code for LTI tool.
     *
     * @return   object    Object with useContentItem property
     */
    private function get_tool($code)
    {
        $obj = new \stdClass();
        $tool = LTI_Platform_Tool::fromCode($code, LTI_Platform::$ltiPlatformDataConnector);
        $obj->useContentItem = $tool->useContentItem;

        return $obj;
    }

    /**
     * Display the HTML for handling an incoming content-item (deep linking) message.
     *
     * @since    1.0.0
     * @param    string    $code      Code for LTI tool.
     */
    private function content($code)
    {
        $tool = LTI_Platform_Tool::fromCode($code, LTI_Platform::$ltiPlatformDataConnector);
        $platform = new LTI_Platform_Platform(LTI_platform::$ltiPlatformDataConnector);
        LTI\Tool::$defaultTool = $tool;
        $platform->handleRequest();

        $html = <<< EOD
<html>
  <head>
    <title>Content</title>
    <script>
      var wdw = window.opener;

EOD;
        if ($platform->ok) {
            $linktext = $tool->name;
            $item = $platform->contentItem;
            $attr = "tool={$code}";
            $attr .= ' id=' . strtolower(LTI\Util::getRandomString());
            if (!empty($item->title)) {
                $attr .= static::setAttribute('title', $item->title);
                $linktext = $item->title;
            }
            $linktext = str_replace('\'', '\\\'', $linktext);
            if (!empty($item->url)) {
                $attr .= static::setAttribute('url', $item->url);
            }
            if (isset($item->placementAdvice)) {
                if (!empty($item->placementAdvice->presentationDocumentTarget)) {
                    $targets = explode(',', $item->placementAdvice->presentationDocumentTarget);
                    $attr .= static::setAttribute('target', $targets[0]);
                    if (!empty($item->placementAdvice->displayWidth)) {
                        $attr .= static::setAttribute('width', $item->placementAdvice->displayWidth);
                    }
                    if (!empty($item->placementAdvice->displayHeight)) {
                        $attr .= static::setAttribute('height', $item->placementAdvice->displayHeight);
                    }
                }
            }
            if (!empty($item->custom)) {
                $attr .= static::setAttribute('custom', $item->custom);
            }
            $attr = str_replace('\\', '\\\\', $attr);
            $attr = str_replace('\'', '\\\'', $attr);
            $plugin_name = LTI_Platform::get_plugin_name();
            $html .= <<< EOD
      if (!wdw.LtiPlatformText) {
        wdw.LtiPlatformText = '{$linktext}';
      }
      var shortcode = wdw.wp.richText.create({
        html: '[{$plugin_name} {$attr}]' + wdw.LtiPlatformText + '[/{$plugin_name}]'
      });
      wdw.LtiPlatformProps.onChange(wdw.wp.richText.insert(wdw.LtiPlatformProps.value, shortcode));
      wdw.LtiPlatformProps.onFocus();
      window.close();

EOD;
        } else {
            $html .= <<< EOD
      window.close();
      wdw.alert('Sorry, unable to verify the selected content');

EOD;
        }
        $html .= <<< EOD
    </script>
  </head>
  <body>
  </body>
</html>

EOD;
        $allowed = array('html' => array(), 'head' => array(), 'title' => array(), 'script' => array(), 'body' => array());
        echo wp_kses($html, $allowed);
    }

    private function getSubstituteString($str)
    {
        do {
            $sub = '{' . LTI\Util::getRandomString() . '}';
        } while (strpos($str, $sub) !== false);

        return $sub;
    }

    /**
     * Get the entry for a shortcode attribute.
     *
     * @since    1.0.0
     * @param    string    $name      Name of attribute.
     * @param    string    $value     Value of attribute.
     */
    private static function setAttribute($name, $value)
    {
        $attr = '';
        if (!empty($value)) {
            if (!is_array($value)) {
                $attr = $value;
            } else {
                foreach ($value as $key => $val) {
                    $val = str_replace(';', '\\;', $val);
                    $attr .= "{$key}={$val};";
                }
                $attr = substr($attr, 0, -1);
            }
            if (strpos($attr, ' ') !== false) {
                $attr = str_replace('"', '\\"', $attr);
                $attr = '"' . $attr . '"';
            }
            $attr = " {$name}={$attr}";
        }

        return $attr;
    }

    /**
     * Display an error page.
     *
     * @since    1.0.0
     * @param    string    $reason    Reason for error.
     * @param    bool      $debug     Status of debug mode.
     */
    private function error_page($reason, $debug = false)
    {
        $allowed = array('em' => array());
        $message = __('Sorry, the LTI tool could not be launched.', LTI_Platform::get_plugin_name());
        if (!empty($reason)) {
            $debug = $debug || (LTI_Platform::getOption('debug', 'false') === 'true');
            if ($debug) {
                $message .= ' <em>[' . $reason . ']</em>';
            }
        }
        echo('<html>' . "\n");
        echo('  <head>' . "\n");
        echo('    <title>' . esc_html__('LTI Tool launch error', LTI_Platform::get_plugin_name()) . '</title>' . "\n");
        echo('  </head>' . "\n");
        echo('  <body>' . "\n");
        echo('    <p><strong>' . wp_kses($message, $allowed) . '</strong></p>' . "\n");
        echo('  </body>' . "\n");
        echo('</html>' . "\n");
    }

}
