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
 * Define the LTI Platform class.
 *
 * Processes incoming LTI messages to the platform.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-lti-platform
 * @since      1.0.0
 * @package    LTI_Platform
 * @subpackage LTI_Platform/includes
 * @author     Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */
use ceLTIc\LTI\Platform;
use ceLTIc\LTI\Tool;
use ceLTIc\LTI\Content;
use ceLTIc\LTI\Util;

class LTI_Platform_Platform extends Platform
{

    /**
     * Content item received from tool.
     *
     * @since    1.0.0
     * @access   public
     * @var      object    $contentItem  The content item.
     */
    public $contentItem = null;

    /**
     * Save the hint and message parameters when sending an initiate login request.
     *
     * Override this method to save the data elsewhere.
     *
     * @param string $url                  The message URL
     * @param string $loginHint            The ID of the user
     * @param string|null $ltiMessageHint  The message hint being sent to the tool
     * @param array $params                An associative array of message parameters
     */
    protected function onInitiateLogin(string &$url, string &$loginHint, ?string &$ltiMessageHint, array $params): void
    {
        $ltiMessageHint = null;
        $user = wp_get_current_user();
        $data = array(
            'messageUrl' => $url,
            'login_hint' => $loginHint,
            'params' => $params
        );
        update_user_option($user->ID, LTI_Platform::get_plugin_name() . '-login', $data);
    }

    /**
     * Check the hint and recover the message parameters.
     */
    protected function onAuthenticate(): void
    {
        $user = wp_get_current_user();
        $login = get_user_option(LTI_Platform::get_plugin_name() . '-login');
        update_user_option($user->ID, LTI_Platform::get_plugin_name() . '-login', null);
        $parameters = Util::getRequestParameters();
        if ($parameters['login_hint'] !== $login['login_hint'] ||
            (isset($login['lti_message_hint']) && (!isset($parameters['lti_message_hint']) || ($parameters['lti_message_hint'] !== $login['lti_message_hint'])))) {
            $this->ok = false;
            $this->messageParameters['error'] = 'access_denied';
        } else {
            Tool::$defaultTool->messageUrl = $login['messageUrl'];
            $this->messageParameters = $login['params'];
        }
    }

    /**
     * Process a valid content-item message
     */
    protected function onContentItem(): void
    {
        $this->ok = false;
        $items = Content\Item::fromJson(json_decode($this->messageParameters['content_items']));
        if (empty($items)) {
            $this->reason = 'No items returned';
        } elseif (count($items) > 1) {
            $this->reason = 'More than one item has been returned';
        } elseif (!$items[0] instanceof Content\LtiLinkItem) {
            $this->reason = 'Item must be an LTI link or assignment';
        } else {
            $this->ok = true;
            $this->contentItem = $items[0]->toJsonldObject();
        }
    }

}
