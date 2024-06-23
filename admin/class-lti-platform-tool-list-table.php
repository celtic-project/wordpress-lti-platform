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
 * The table of current LTI tools.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-lti-platform
 * @since      1.0.0
 * @package    LTI_Platform
 * @subpackage LTI_Platform/admin
 * @author     Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */
class LTI_Platform_Tool_List_Table extends WP_List_Table
{

    /**
     * The codes of any tools defined at the network level.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $mu_items    Array of tool codes.
     */
    private $mu_items = array();

    /**
     * Whether a list of deleted tools has been requested.
     *
     * @since    1.0.0
     * @access   private
     * @var      bool    $is_trash    True if the list is of deleted tools.
     */
    private $is_trash;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'tool',
            'plural' => 'tools',
            'ajax' => false
        ));
        add_filter('list_table_primary_column', array($this, 'set_primary_column'), 10, 2);
        if (!defined('WP_NETWORK_ADMIN') || !WP_NETWORK_ADMIN) {
            $args = array(
                'post_type' => LTI_Platform_Tool::POST_TYPE_NETWORK,
                'post_status' => 'publish'
            );
            $this->mu_items = array_keys(LTI_Platform_Tool::all($args));
        }
    }

    /**
     * Get the name of the primary column.
     *
     * @since    1.0.0
     * @return   string    Name of primary column.
     */
    public static function set_primary_column()
    {
        return 'name';
    }

    /**
     * Get the details for the table columns.
     *
     * @since    1.0.0
     * @return   array    Array of column details.
     */
    public static function define_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox">',
            'name' => __('Name', LTI_Platform::get_plugin_name()),
            'code' => __('Code', LTI_Platform::get_plugin_name()),
            'enabled' => __('Enabled?', LTI_Platform::get_plugin_name()),
            'debugMode' => __('Debug mode?', LTI_Platform::get_plugin_name()),
            'lastAccess' => __('Last launch', LTI_Platform::get_plugin_name()),
            'created' => __('Created', LTI_Platform::get_plugin_name()),
            'modified' => __('Modified', LTI_Platform::get_plugin_name()),
        );

        return $columns;
    }

    /**
     * Get the SQL for ordering values in a column.
     *
     * @since    1.0.0
     * @return   string    SQL for ORDER BY clause.
     */
    public static function tools_orderby($args, $wp_query)
    {
        global $wpdb;

        if (isset($wp_query->query['post_type']) && ($wp_query->query['post_type'] === LTI_Platform::$postType)) {
            if ($wp_query->query['orderby'] === 'enabled') {
                $args = "{$wpdb->posts}.post_status {$wp_query->query['order']}, {$wpdb->posts}.post_name ASC";
            } elseif ($wp_query->query['orderby'] === 'debugMode') {
                $args = "LOCATE('\"_debug\":\"true\"', {$wpdb->posts}.post_content) > 0 {$wp_query->query['order']}, {$wpdb->posts}.post_name ASC";
            } elseif ($wp_query->query['orderby'] === 'url') {
                $args = "SUBSTR({$wpdb->posts}.post_content, LOCATE('\"__messageUrl\":\"', {$wpdb->posts}.post_content)+16, 100) {$wp_query->query['order']}, {$wpdb->posts}.post_name ASC";
            } elseif ($wp_query->query['orderby'] === 'lastAccess') {
                $args = "LOCATE('\"__lastAccess\":\"', {$wpdb->posts}.post_content), 27) {$wp_query->query['order']}, {$wpdb->posts}.post_name ASC";
            }
        }

        return $args;
    }

    /**
     * Display a message when a tool has been successfully moved to the trash bin.
     *
     * @since    1.0.0
     */
    public function trash_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tool(s) moved to the Bin.', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Display a message when moving a tool to the trash bin has not been successful.
     *
     * @since    1.0.0
     */
    public function trash_notice_error()
    {
        echo('    <div class="notice notice-error is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('An error occurred when moving tool(s) to the Bin.', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Display a message when a tool has been successfully deleted.
     *
     * @since    1.0.0
     */
    public function delete_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tool(s) deleted.', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Display a message when a tool deletion has not been successful.
     *
     * @since    1.0.0
     */
    public function delete_notice_error()
    {
        echo('    <div class="notice notice-error is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('An error occurred when deleting tool(s).', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Display a message when a tool has been successfully restored from the trash bin.
     *
     * @since    1.0.0
     */
    public function restore_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tool(s) restored.', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Display a message when restoring a tool from the trash bin has not been successful.
     *
     * @since    1.0.0
     */
    public function restore_notice_error()
    {
        echo('    <div class="notice notice-error is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('An error occurred when restoring tool(s).', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Display a message when a tool has been successfully enabled.
     *
     * @since    1.0.0
     */
    public function enable_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tool(s) enabled.', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Display a message when a tool cannot be enabled.
     *
     * @since    1.0.0
     */
    public function enable_notice_denied()
    {
        echo('    <div class="notice notice-warning is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tools cannot be enabled if they are not fully configured for either LTI 1.0/1.1/1.2 or LTI 1.3, or no private key has been defined.',
            LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Display a message when enabling a tool is not successful.
     *
     * @since    1.0.0
     */
    public function enable_notice_error()
    {
        echo('    <div class="notice notice-error is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('An error occurred when enabling tool(s).', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Display a message when a tool has been successfully disabled.
     *
     * @since    1.0.0
     */
    public function disable_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tool(s) disabled.', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Display a message when disabling a tool is not successful.
     *
     * @since    1.0.0
     */
    public function disable_notice_error()
    {
        echo('    <div class="notice notice-error is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('An error occurred when disablng tool(s).', LTI_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    /**
     * Process a form action request.
     *
     * @since    1.0.0
     */
    public function process_action()
    {
        if (!empty($_REQUEST['tool'])) {
            $ids = sanitize_text_field($_REQUEST['tool']);
            if (!is_array($ids)) {
                $ids = array($ids);
            }
            $ok = true;
            if ($this->current_action() === 'trash') {
                foreach ($ids as $id) {
                    $tool = LTI_Platform_Tool::fromRecordId(intval($id), LTI_Platform::$ltiPlatformDataConnector);
                    $ok = $ok && $tool->trash();
                }
                if ($ok) {
                    add_action('all_admin_notices', array($this, 'trash_notice_success'));
                } else {
                    add_action('all_admin_notices', array($this, 'trash_notice_error'));
                }
            } elseif ($this->current_action() === 'untrash') {
                foreach ($ids as $id) {
                    $tool = LTI_Platform_Tool::fromRecordId(intval($id), LTI_Platform::$ltiPlatformDataConnector);
                    $ok = $ok && $tool->restore();
                }
                if ($ok) {
                    add_action('all_admin_notices', array($this, 'restore_notice_success'));
                } else {
                    add_action('all_admin_notices', array($this, 'restore_notice_error'));
                }
            } elseif ($this->current_action() === 'delete') {
                foreach ($ids as $id) {
                    $tool = new LTI_Platform_Tool(LTI_platform::$ltiPlatformDataConnector);
                    $tool->setRecordId(intval($id));
                    $ok = $ok && $tool->delete();
                }
                if ($ok) {
                    add_action('all_admin_notices', array($this, 'delete_notice_success'));
                } else {
                    add_action('all_admin_notices', array($this, 'delete_notice_error'));
                }
            } else if ($this->current_action() === 'enable') {
                $denied = false;
                foreach ($ids as $id) {
                    $tool = LTI_Platform_Tool::fromRecordId(intval($id), LTI_Platform::$ltiPlatformDataConnector);
                    if ($tool->canBeEnabled()) {
                        $tool->enabled = true;
                        $tool->showMessages = false;
                        $ok = $ok && $tool->save();
                    } elseif (!$denied) {
                        $ok = false;
                        $denied = true;
                        add_action('all_admin_notices', array($this, 'enable_notice_denied'));
                    }
                }
                if ($ok) {
                    add_action('all_admin_notices', array($this, 'enable_notice_success'));
                } else {
                    add_action('all_admin_notices', array($this, 'enable_notice_error'));
                }
            } else if ($this->current_action() === 'disable') {
                foreach ($ids as $id) {
                    $tool = LTI_Platform_Tool::fromRecordId(intval($id), LTI_Platform::$ltiPlatformDataConnector);
                    $tool->enabled = false;
                    $tool->showMessages = false;
                    $ok = $ok && $tool->save();
                }
                if ($ok) {
                    add_action('all_admin_notices', array($this, 'disable_notice_success'));
                } else {
                    add_action('all_admin_notices', array($this, 'disable_notice_error'));
                }
            }
        }
    }

    /**
     * Prepare the table for display.
     *
     * @since    1.0.0
     */
    public function prepare_items()
    {
        if (!isset($_REQUEST['_wpnonce']) || wp_verify_nonce($_REQUEST['_wpnonce'], LTI_Platform::get_plugin_name() . '-nonce')) {
            $this->process_action();

            $per_page = $this->get_items_per_page(LTI_Platform::get_plugin_name() . '-tool_per_page');

            $args = array(
                'posts_per_page' => $per_page,
                'orderby' => 'name',
                'order' => 'ASC',
                'offset' => ($this->get_pagenum() - 1) * $per_page,
                'suppress_filters' => false
            );

            if (isset($_REQUEST['post_status'])) {
                $args['post_status'] = sanitize_text_field($_REQUEST['post_status']);
            }
            if (!empty($_REQUEST['s'])) {
                $args['s'] = sanitize_text_field($_REQUEST['s']);
            }

            if (!empty($_REQUEST['orderby'])) {
                if ('name' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'name';
                } elseif ('enabled' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'enabled';
                } elseif ('debugMode' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'debugMode';
                } elseif ('lastAccess' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'lastAccess';
                } elseif ('created' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'created';
                } elseif ('modified' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'modified';
                }
            }

            if (!empty($_REQUEST['order'])) {
                if ('asc' == strtolower(sanitize_text_field($_REQUEST['order']))) {
                    $args['order'] = 'ASC';
                } elseif ('desc' == strtolower(sanitize_text_field($_REQUEST['order']))) {
                    $args['order'] = 'DESC';
                }
            }

            $this->items = array_values(LTI_Platform_Tool::all($args));
            $tool_counts = (array) wp_count_posts(LTI_Platform::$postType, 'readable');
            if (isset($_REQUEST['post_status'])) {
                $total_items = $tool_counts[sanitize_text_field($_REQUEST['post_status'])];
            } else {
                $total_items = array_sum($tool_counts);
            }
            $total_pages = ceil($total_items / $per_page);

            $this->set_pagination_args(array(
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'per_page' => $per_page,
            ));

            $this->is_trash = isset($_REQUEST['post_status']) && (sanitize_text_field($_REQUEST['post_status']) === 'trash');
        }
    }

    /**
     * Get the views available for the table.
     *
     * @since    1.0.0
     * @return   array     Array of view details.
     */
    protected function get_views()
    {
        $views = array();
        $num_tools = wp_count_posts(LTI_Platform::$postType, 'readable');
        $total_tools = array_sum((array) $num_tools) - $num_tools->trash;

        $class = (count($_GET) <= 1) ? $class = 'current' : '';
        $views['all'] = $this->get_edit_link(array(), "All <span class=\"count\">({$total_tools})</span>", $class);
        if ($num_tools->publish > 0) {
            $class = (isset($_GET['post_status']) && (sanitize_text_field($_GET['post_status']) === 'publish')) ? $class = 'current' : '';
            $views['publish'] = $this->get_edit_link(array('post_status' => 'publish'),
                "Enabled <span class=\"count\">({$num_tools->publish})</span>", $class);
        }
        if ($num_tools->draft) {
            $class = (isset($_GET['post_status']) && (sanitize_text_field($_GET['post_status']) === 'draft')) ? $class = 'current' : '';
            $views['draft'] = $this->get_edit_link(array('post_status' => 'draft'),
                "Disabled <span class=\"count\">({$num_tools->draft})</span>", $class);
        }
        if ($num_tools->trash) {
            $class = (isset($_GET['post_status']) && (sanitize_text_field($_GET['post_status']) === 'trash')) ? $class = 'current' : '';
            $views['trash'] = $this->get_edit_link(array('post_status' => 'trash'),
                "Bin <span class=\"count\">({$num_tools->trash})</span>", $class);
        }

        return $views;
    }

    /**
     * Get the columns available for the table.
     *
     * @since    1.0.0
     * @return   array     Array of column details.
     */
    public function get_columns()
    {
        return get_column_headers(get_current_screen());
    }

    /**
     * Get the sortable status for the table =columns.
     *
     * @since    1.0.0
     * @return   array     Array of sortable column details.
     */
    protected function get_sortable_columns()
    {
        $columns = array(
            'name' => array('title', true),
            'code' => array('code', true),
            'enabled' => array('enabled', false),
            'debugMode' => array('debugMode', false),
            'lastAccess' => array('lastAccess', false),
            'created' => array('created', false),
            'modified' => array('modified', false)
        );

        return $columns;
    }

    /**
     * Get the default column display.
     *
     * @since    1.0.0
     * @return   string    Default column value.
     */
    protected function column_default($item, $column_name)
    {
        return '';
    }

    /**
     * Get the bulk actions available for a table row.
     *
     * @since    1.0.0
     * @return   array     Array of view actions.
     */
    protected function get_bulk_actions()
    {
        if (!$this->is_trash) {
            $actions = array(
                'enable' => __('Enable', LTI_Platform::get_plugin_name()),
                'disable' => __('Disable', LTI_Platform::get_plugin_name()),
                'trash' => __('Move to Bin', LTI_Platform::get_plugin_name())
            );
        } else {
            $actions = array(
                'untrash' => __('Restore', LTI_Platform::get_plugin_name()),
                'delete' => __('Delete permanently', LTI_Platform::get_plugin_name())
            );
        }

        return $actions;
    }

    /**
     * Get the HTML for the bulk actions.
     *
     * @since    1.0.0
     * @return   string    HTML for row actions.
     */
    protected function handle_row_actions($item, $column_name, $primary)
    {
        if ($column_name !== $primary) {
            return '';
        }

        if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) {
            $page = 'settings.php';
            $url = network_admin_url(add_query_arg('page', LTI_Platform::get_plugin_name(), 'settings.php'));
        } else {
            $page = 'options-general.php';
            $url = menu_page_url(LTI_Platform::get_plugin_name(), false);
        }
        $actions = array();
        if (!$item->deleted) {
            $edit_link = add_query_arg(array('page' => LTI_Platform::get_plugin_name() . '-edit', 'tool' => absint($item->getRecordId())),
                $page);
            $actions['edit'] = sprintf(
                '<a href = "%1$s" aria-label = "%2$s">%3$s</a>', esc_url($edit_link),
                esc_attr(sprintf(__('Edit &#8220;%s&#8221;', LTI_Platform::get_plugin_name()), $item->name)),
                esc_html(__('Edit', LTI_Platform::get_plugin_name()))
            );
            if (!$item->enabled) {
                $enable_link = add_query_arg(array('action' => 'enable', 'tool' => absint($item->getRecordId())), $url);
                $actions['enable'] = sprintf(
                    '<a href="%1$s" aria-label="%2$s">%3$s</a>', esc_url($enable_link),
                    esc_attr(sprintf(__('Enable &#8220;%s&#8221;', LTI_Platform::get_plugin_name()), $item->name)),
                    esc_html__('Enable', LTI_Platform::get_plugin_name())
                );
            } else {
                $disable_link = add_query_arg(array('action' => 'disable', 'tool' => absint($item->getRecordId())), $url);
                $actions['disable'] = sprintf(
                    '<a href="%1$s" aria-label="%2$s">%3$s</a>', esc_url($disable_link),
                    esc_attr(sprintf(__('Disable &#8220;%s&#8221;', LTI_Platform::get_plugin_name()), $item->name)),
                    esc_html__('Disable', LTI_Platform::get_plugin_name())
                );
            }
            $trash_link = add_query_arg(array('action' => 'trash', 'tool' => absint($item->getRecordId())), $url);
            $actions['trash'] = sprintf(
                '<a href="%1$s" aria-label="%2$s">%3$s</a>', esc_url($trash_link),
                esc_attr(sprintf(__('Bin &#8220;%s&#8221;', LTI_Platform::get_plugin_name()), $item->name)),
                esc_html__('Bin', LTI_Platform::get_plugin_name())
            );
        } else {
            $untrash_link = add_query_arg(array('action' => 'untrash', 'tool' => absint($item->getRecordId())), $url);
            $actions['untrash'] = sprintf(
                '<a href="%1$s" aria-label="%2$s">%3$s</a>', esc_url($untrash_link),
                esc_attr(sprintf(__('Disable &#8220;%s&#8221;', LTI_Platform::get_plugin_name()), $item->name)),
                esc_html__('Restore', LTI_Platform::get_plugin_name())
            );
            $delete_link = add_query_arg(array('action' => 'delete', 'tool' => absint($item->getRecordId())), $url);
            $actions['delete'] = sprintf(
                '<a href="%1$s" aria-label="%2$s">%3$s</a>', esc_url($delete_link),
                esc_attr(sprintf(__('Permanently delete &#8220;%s&#8221;', LTI_Platform::get_plugin_name()), $item->name)),
                esc_html__('Delete permanently', LTI_Platform::get_plugin_name())
            );
        }

        return $this->row_actions($actions);
    }

    /**
     * Get the HTML for a checkbox column.
     *
     * @since    1.0.0
     * @return   string    HTML for checkbox column.
     */
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->getRecordId()
        );
    }

    /**
     * Get the HTML for the tool name column.
     *
     * @since    1.0.0
     * @return   string    HTML for name column.
     */
    public function column_name($item)
    {
        if (in_array($item->code, $this->mu_items)) {
            return sprintf('<span style="text-decoration: line-through;" title="A network LTI tool exists with same code">%1$s</span>',
                esc_html($item->name));
        } else {
            return sprintf('<strong>%1$s</strong>', esc_html($item->name));
        }
    }

    /**
     * Get the HTML for the tool enabled column.
     *
     * @since    1.0.0
     * @return   string    HTML for enabled column.
     */
    public function column_enabled($item)
    {
        $post = get_post($item->getRecordId());

        if (!$post) {
            return;
        }

        return esc_html__($item->enabled ? 'Yes' : 'No', LTI_Platform::get_plugin_name());
    }

    /**
     * Get the HTML for the tool debug mode column.
     *
     * @since    1.0.0
     * @return   string    HTML for debug mode column.
     */
    public function column_debugMode($item)
    {
        $post = get_post($item->getRecordId());

        if (!$post) {
            return;
        }

        return esc_html__($item->debugMode ? 'Yes' : 'No', LTI_Platform::get_plugin_name());
    }

    /**
     * Get the HTML for the tool code column.
     *
     * @since    1.0.0
     * @return   string    HTML for code column.
     */
    public function column_code($item)
    {
        if ($item->deleted) {
            return esc_html(str_replace('__trashed', '', $item->code));
        } elseif (in_array($item->code, $this->mu_items)) {
            return sprintf('<span style="text-decoration: line-through;" title="A network LTI tool exists with same code">%1$s</span>',
                esc_html($item->code));
        } else {
            return esc_html($item->code);
        }
    }

    /**
     * Get the HTML for the tool last accessed column.
     *
     * @since    1.0.0
     * @return   string    HTML for last access column.
     */
    public function column_lastAccess($item)
    {
        if ($item->lastAccess) {
            $last_access = date('Y/m/d', $item->lastAccess);
        } else {
            $last_access = esc_html__('None', LTI_Platform::get_plugin_name());
        }

        return esc_html($last_access);
    }

    /**
     * Get the HTML for the tool created column.
     *
     * @since    1.0.0
     * @return   string    HTML for created column.
     */
    public function column_created($item)
    {
        if (empty($item->created)) {
            return '';
        } else {
            return date('Y/m/d H:i', $item->created);
        }
    }

    /**
     * Get the HTML for the tool last modified column.
     *
     * @since    1.0.0
     * @return   string    HTML for last modified column.
     */
    public function column_modified($item)
    {
        if (empty($item->updated)) {
            return '';
        } else {
            return date('Y/m/d H:i', $item->updated);
        }
    }

    /**
     * Get the HTML to display when the table is empty.
     *
     * @since    1.0.0
     * @return   string    HTML for empty table.
     */
    public function no_items()
    {
        if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) {
            esc_html_e('No Network LTI tools found.', LTI_Platform::get_plugin_name());
        } else {
            esc_html_e('No LTI tools found.', LTI_Platform::get_plugin_name());
        }
    }

    /**
     * Get the HTML for a tool action link.
     *
     * @since    1.0.0
     * @return   string    HTML for action link.
     */
    private function get_edit_link($args, $label, $class = '')
    {
        if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) {
            $args['page'] = LTI_Platform::get_plugin_name();
            $url = network_admin_url(add_query_arg($args, 'settings.php'));
        } else {
            $url = add_query_arg($args, menu_page_url(LTI_Platform::get_plugin_name(), false));
        }
        $class_html = '';
        $aria_current = '';
        if (!empty($class)) {
            $class_html = ' class="' . esc_attr($class) . '"';
            if ($class === 'current') {
                $aria_current = ' aria-current="page"';
            }
        }

        return '<a href="' . esc_url($url) . "\"{$class_html}{$aria_current}>{$label}</a>";
    }

}
