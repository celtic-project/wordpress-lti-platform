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
 * The WordPress LTI data connector class.
 *
 * This is used to define the methods for loading and saving LTI tools from/to the database.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-lti-platform
 * @since      1.0.0
 * @package    LTI_Platform
 * @subpackage LTI_Platform/includes
 * @author     Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */
use ceLTIc\LTI\Tool;
use ceLTIc\LTI\DataConnector;
use ceLTIc\LTI\Util;

class DataConnector_wp extends DataConnector\DataConnector
{
###
###  Tool methods
###

    /**
     * Load tool object.
     *
     * @param Tool $tool  Tool object
     *
     * @return bool    True if the tool object was successfully loaded
     */
    public function loadTool($tool)
    {
        $blogId = null;
        if (!empty($tool->getRecordId())) {
            $blogId = $tool->blogId;
            if (is_multisite()) {
                switch_to_blog($blogId);
            }
            $post = get_post($tool->getRecordId(), OBJECT, LTI_Platform::$postType);
            if (is_multisite()) {
                restore_current_blog();
            }
        } elseif (!empty($tool->code)) {
            $blogId = get_current_blog_id();
            $post = get_page_by_path($code, OBJECT, LTI_Platform::$postType);
        } else {
            $post = null;
        }
        $ok = !empty($post);
        if ($ok) {
            $this->fromPost($tool, $post, $blogId);
        }

        return $ok;
    }

    /**
     * Save tool object.
     *
     * @param Tool $tool  Tool object
     *
     * @return bool    True if the tool object was successfully saved
     */
    public function saveTool($tool)
    {
        $time = time();
        $this->fixToolSettings($tool, true);
        $settingsValue = json_encode($tool->getSettings());
        $this->fixToolSettings($tool, false);
        if ($tool->deleted) {
            $status = 'trash';
        } elseif ($tool->enabled) {
            $status = 'publish';
        } else {
            $status = 'draft';
        }
        $post = array(
            'post_type' => LTI_Platform::$postType,
            'post_name' => $tool->code,
            'post_title' => $tool->name,
            'post_content' => $settingsValue,
            'post_status' => $status
        );
        if (!empty($tool->getRecordId())) {
            if (is_multisite()) {
                switch_to_blog($tool->blogId);
            }
            $post['ID'] = $tool->getRecordId();
            $post['post_date_gmt'] = gmdate('Y-m-d H:i:s', $tool->created);
        } else {
            $post['post_date_gmt'] = gmdate('Y-m-d H:i:s', $time);
        }
        $result = wp_insert_post($post);
        if (!empty($tool->getRecordId()) && is_multisite()) {
            restore_current_blog();
        }
        $ok = $result && (!$result instanceof WP_Error);
        if ($ok) {
            if (empty($tool->getRecordId())) {
                $tool->setRecordId($result);
                $tool->blogId = get_current_blog_id();
                $tool->created = $time;
            }
            $tool->updated = $time;
        }

        return $ok;
    }

    /**
     * Trash tool object.
     *
     * @param Tool $tool  Tool object
     *
     * @return bool    True if the tool object was successfully trashed
     */
    public function trashTool($tool)
    {
        $tool->deleted = true;
        $ok = $this->saveTool($tool);

        return $ok;
    }

    /**
     * Restore tool object.
     *
     * @param Tool $tool  Tool object
     *
     * @return bool    True if the tool object was successfully restored
     */
    public function restoreTool($tool)
    {
        $tool->deleted = false;
        $ok = $this->saveTool($tool);

        return $ok;
    }

    /**
     * Delete tool object.
     *
     * @param Tool $tool  Tool object
     *
     * @return bool    True if the tool object was successfully deleted
     */
    public function deleteTool($tool)
    {
        $ok = !empty(wp_delete_post($tool->getRecordId()));
        if ($ok) {
            $tool->initialize();
        }

        return $ok;
    }

    /**
     * Load tool objects.
     *
     * @return Tool[] Array of all defined Tool objects
     */
    public function getTools()
    {
        return array_values($this->getToolsWithArgs());
    }

    /**
     * Load tool objects.
     *
     * @return Tool[] Array of all defined Tool objects
     */
    public function getToolsWithArgs($args = array())
    {
        $tools = array();
        $default_args = array(
            'post_type' => LTI_Platform::$postType,
            'post_status' => 'any',
            'numberposts' => -1,
            'offset' => 0
        );
        $args = array_merge($default_args, $args);
        $posts = get_posts($args);
        foreach ($posts as $post) {
            $tool = new LTI_Platform_Tool(LTI_Platform::$ltiPlatformDataConnector);
            $this->fromPost($tool, $post, get_current_blog_id());
            $tools[$tool->code] = $tool;
        }

        return $tools;
    }

    public function fromPost($tool, $post, $blogId)
    {
        $tool->setRecordId(intval($post->ID));
        $tool->blogId = $blogId;
        $tool->name = $post->post_title;
        $tool->code = $post->post_name;
        $tool->deleted = $post->post_status === 'trash';
        $tool->enabled = $post->post_status === 'publish';
        $tool->created = strtotime($post->post_date_gmt);
        $tool->updated = strtotime($post->post_modified_gmt);
        $settings = json_decode($post->post_content, true);
        if (!is_array($settings)) {
            $settings = array();
        }
        $tool->setSettings($settings);
        $this->fixToolSettings($tool, false);
    }

    /**
     * Adjust the settings for any tool properties being stored as a setting value.
     *
     * @param Tool      $tool       Tool object
     * @param bool      $isSave     True if the settings are being saved
     */
    protected function fixToolSettings($tool, $isSave)
    {
        parent::fixToolSettings($tool, $isSave);
        if (!$isSave) {
            $tool->setKey($tool->getSetting('__key'));
            $tool->setSetting('__key');
            $tool->secret = $tool->getSetting('__secret');
            $tool->setSetting('__secret');
            $tool->messageUrl = $tool->getSetting('__messageUrl');
            $tool->setSetting('__messageUrl');
            $tool->useContentItem = $tool->getSetting('__useContentItem') === 'true';
            $tool->setSetting('__useContentItem');
            $tool->contentItemUrl = $tool->getSetting('__contentItemUrl');
            $tool->setSetting('__contentItemUrl');
            $tool->initiateLoginUrl = $tool->getSetting('__initiateLoginUrl');
            $tool->setSetting('__initiateLoginUrl');
            $tool->redirectionUris = json_decode(str_replace('&quot;', '"', $tool->getSetting('__redirectionUris')), true);
            if (!is_array($tool->redirectionUris)) {
                $tool->redirectionUris = array();
            }
            $tool->setSetting('__redirectionUris');
            $tool->jku = $tool->getSetting('__jku');
            $tool->setSetting('__jku');
            $tool->rsaKey = str_replace('&#13;&#10;', "\r\n", $tool->getSetting('__rsaKey'));
            $tool->setSetting('__rsaKey');
            $tool->lastAccess = null;
            if (!empty($tool->getSetting('__lastAccess'))) {
                $tool->lastAccess = strtotime($tool->getSetting('__lastAccess'));
            }
            $tool->setSetting('__lastAccess');
        } else {
            $tool->setSetting('__key', $tool->getKey());
            $tool->setSetting('__secret', $tool->secret);
            $tool->setSetting('__messageUrl', $tool->messageUrl);
            $tool->setSetting('__useContentItem', ($tool->useContentItem) ? 'true' : null);
            $tool->setSetting('__contentItemUrl', $tool->contentItemUrl);
            $tool->setSetting('__initiateLoginUrl', $tool->initiateLoginUrl);
            $tool->setSetting('__redirectionUris', str_replace('"', '&quot;', json_encode($tool->redirectionUris)));
            $tool->setSetting('__jku', $tool->jku);
            $tool->setSetting('__rsaKey', str_replace("\r\n", '&#13;&#10;', $tool->rsaKey));
            $last = null;
            if (!empty($tool->lastAccess)) {
                $last = gmdate($this->dateFormat, $tool->lastAccess);
            }
            $tool->setSetting('__lastAccess', $last);
        }
    }

###
###  Other methods
###

    /**
     * Create data connector object.
     *
     * @param object|resource  $db                 A database connection object or string
     * @param string           $dbTableNamePrefix  Prefix for database table names
     *
     * @return DataConnector_wp Data connector object
     */
    public static function createDataConnector($db, $dbTableNamePrefix)
    {
        return new DataConnector_wp($db, $dbTableNamePrefix);
    }

}
