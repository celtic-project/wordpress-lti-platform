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
 * This file is used to markup the page for editing an LTI tool configuration.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-lti-platform
 * @since      1.0.0
 * @package    LTI_Platform
 * @subpackage LTI_Platform/admin/partials
 * @author     Stephen P Vickers <stephen@spvsoftwareproducts.com>
 */
wp_enqueue_script('wp-ajax-response');

if (empty($tool->getRecordId())) {
    $verb = 'Add New';
} else {
    $verb = 'Edit';
}
if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) {
    $page = 'settings.php';
} else {
    $page = 'options-general.php';
}
$url = add_query_arg(array('page' => LTI_Platform::get_plugin_name() . '-edit', 'tool' => absint($tool->getRecordId())), $page);

echo('<div class="wrap">' . "\n");
echo('  <h1>' . "\n");
echo('    ' . esc_html($verb) . ' LTI Tool' . "\n");
echo('    <a href="' . esc_attr($page) . '?page=' . LTI_Platform::get_plugin_name() . '" class="page-title-action">LTI Tools List</a>' . "\n");
echo('  </h1>' . "\n");
echo('  <form action="' . esc_url($url) . '" name="a" id="a" method="post" class="validate" novalidate="novalidate">' . "\n");
wp_nonce_field(LTI_Platform::get_plugin_name() . '-nonce');
do_action('all_admin_notices');
submit_button(null, 'primary', 'submit', true, array('id' => 'submit_top'));

echo('    <h2>' . esc_html__('General Details', LTI_Platform::get_plugin_name()) . '</h2>' . "\n");
echo("\n");
echo('    <table class="form-table">' . "\n");
echo('      <tbody>' . "\n");
echo('        <tr class="form-field form-required">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_name">' . "\n");
echo('              ' . esc_html__('Name', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('              <span class="description">' . esc_html__('(required)', LTI_Platform::get_plugin_name(),
    LTI_Platform::get_plugin_name()) . '</span>' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_name" type="text" aria-required="true" value="' . esc_attr($tool->name) . '" name="name" class="regular-text">' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field form-required">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_code">' . "\n");
echo('              ' . esc_html__('Code', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('              <span class="description">' . esc_html__('(required)', LTI_Platform::get_plugin_name()) . '</span>' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_code" type="text" aria-required="true" value="' . esc_attr($tool->code) . '" name="code" class="regular-text">' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_enabled">' . "\n");
echo('              ' . esc_html__('Enabled?', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_enabled" type="checkbox" aria-required="false" value="true" name="enabled"' . checked($tool->enabled,
    true, false) . '>' . "\n");
echo('            <p class="description">' . esc_html__('The tool will not be available for use unless this box is checked.',
    LTI_Platform::get_plugin_name()) . '</p>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_debugmode">' . "\n");
echo('              ' . esc_html__('Debug mode?', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_debugmode" type="checkbox" aria-required="false" value="true" name="debugmode"' . checked($tool->debugMode,
    true, false) . '>' . "\n");
echo('            <p class="description">' . esc_html__('Check this box to include details of the LTI messages sent and received in the WordPress log file.',
    LTI_Platform::get_plugin_name()) . '</p>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field form-required">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_messageurl">' . "\n");
echo('              ' . esc_html__('Launch message URL', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('              <span class="description">' . esc_html__('(required)', LTI_Platform::get_plugin_name()) . '</span>' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_messageurl" type="text" aria-required="true" value="' . esc_attr($tool->messageUrl) . '" name="messageurl" class="regular-text">' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_usecontentitem">' . "\n");
echo('              ' . esc_html__('Use Content-Item/Deep Linking?', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_usecontentitem" type="checkbox" aria-required="false" value="true" name="usecontentitem"' . checked($tool->useContentItem,
    true, false) . '>' . "\n");
echo('            <p class="description">' . esc_html__('Check this box to use the Content-Item/Deep Linking message when creating a link to this tool.',
    LTI_Platform::get_plugin_name()) . '</p>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_contentitemurl">' . "\n");
echo('              ' . esc_html__('Content-Item/Deep Linking URL', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_contentitemurl" type="text" value="' . esc_attr($tool->contentItemUrl) . '" name="contentitemurl" class="regular-text">' . "\n");
echo('            <p class="description">' . esc_html__('Enter the URL to which content-item/deep linking messages should be sent if different from the launch message URL.',
    LTI_Platform::get_plugin_name()) . '</p>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_custom">' . "\n");
echo('              ' . esc_html__('Custom parameters', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <textarea id="id_custom" name="custom" class="regular-text">' . esc_textarea($tool->getSetting('custom')) . '</textarea>' . "\n");
echo('            <p class="description">' . esc_html__('Use a format of "name=value", one per line.',
    LTI_Platform::get_plugin_name()) . '</p>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('      </tbody>' . "\n");
echo('    </table>' . "\n");
echo('' . "\n");
echo('    <h2>' . esc_html__('Presentation Settings', LTI_Platform::get_plugin_name()) . '</h2>' . "\n");
echo('' . "\n");
echo('    <table class="form-table">' . "\n");
echo('      <tbody>' . "\n");
echo('        <tr class="form-field form-required">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_presentationtarget">' . "\n");
echo('              ' . esc_html__('Presentation target', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <select id="id_presentationtarget" aria-required="true" name="presentationtarget">' . "\n");
echo('              <option value="window"' . selected($tool->getSetting('presentationTarget') === 'window', true, false) . '>' . esc_html__('New window',
    LTI_Platform::get_plugin_name()) . '</option>' . "\n");
echo('              <option value="popup"' . selected($tool->getSetting('presentationTarget'), 'popup', true, false) . '>' . esc_html__('Popup window',
    LTI_Platform::get_plugin_name()) . '</option>' . "\n");
echo('              <option value="iframe"' . selected($tool->getSetting('presentationTarget'), 'iframe', true, false) . '>' . esc_html__('iFrame',
    LTI_Platform::get_plugin_name()) . '</option>' . "\n");
echo('              <option value="embed"' . selected($tool->getSetting('presentationTarget'), 'embed', true, false) . '>' . esc_html__('Embed',
    LTI_Platform::get_plugin_name()) . '</option>' . "\n");
echo('            </select>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_presentationwidth">' . "\n");
echo('              ' . esc_html__('Width of popup window', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_presentationwidth" type="text" aria-required="false" value="' . esc_attr($tool->getSetting('presentationWidth')) . '" name="presentationwidth" class="regular-text">' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_presentationheight">' . "\n");
echo('              ' . esc_html__('Height of popup window', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_presentationheight" type="text" aria-required="false" value="' . esc_attr($tool->getSetting('presentationHeight')) . '" name="presentationheight" class="regular-text">' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('      </tbody>' . "\n");
echo('    </table>' . "\n");
echo('' . "\n");
echo('    <h2>' . esc_html__('Privacy Settings', LTI_Platform::get_plugin_name()) . '</h2>' . "\n");
echo('' . "\n");
echo('    <table class="form-table">' . "\n");
echo('      <tbody>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_sendusername">' . "\n");
echo('              ' . esc_html__('Send user\'s name?', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_sendusername" type="checkbox" aria-required="false" value="true" name="sendusername"' . checked($tool->getSetting('sendUserName') === 'true',
    true, false) . '>' . "\n");
echo('            <p class="description">Send <em>lis_person_name_given</em>, <em>lis_person_name_family</em> and <em>lis_person_name_full</em> parameters (or equivalent claims)</p>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_senduserid">' . "\n");
echo('              ' . esc_html__('Send user\'s ID?', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_senduserid" type="checkbox" aria-required="false" value="true" name="senduserid"' . checked($tool->getSetting('sendUserId') === 'true',
    true, false) . '>' . "\n");
echo('            <p class="description">Send <em>user_id</em> parameter (or equivalent claim)</p>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_senduseremail">' . "\n");
echo('              ' . esc_html__('Send user\'s email?', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_senduseremail" type="checkbox" aria-required="false" value="true" name="senduseremail"' . checked($tool->getSetting('sendUserEmail') === 'true',
    true, false) . '>' . "\n");
echo('            <p class="description">Send <em>lis_person_contact_email_primary</em> parameter (or equivalent claim)</p>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_senduserrole">' . "\n");
echo('              ' . esc_html__('Send user\'s role?', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_senduserrole" type="checkbox" aria-required="false" value="true" name="senduserrole"' . checked($tool->getSetting('sendUserRole') === 'true',
    true, false) . '>' . "\n");
echo('            <p class="description">Send <em>roles</em> parameter (or equivalent claim)</p>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_senduserusername">' . "\n");
echo('              ' . esc_html__('Send user\'s username?', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_senduserusername" type="checkbox" aria-required="false" value="true" name="senduserusername"' . checked($tool->getSetting('sendUserUsername') === 'true',
    true, false) . '>' . "\n");
echo('            <p class="description">Send <em>ext_username</em> parameter (or equivalent claim)</p>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('      </tbody>' . "\n");
echo('    </table>' . "\n");
echo('' . "\n");
echo('    <h2>' . esc_html__('LTI 1.0/1.1/1.2 Configuration', LTI_Platform::get_plugin_name()) . '</h2>' . "\n");
echo('' . "\n");
echo('    <table class="form-table">' . "\n");
echo('      <tbody>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_consumerkey">' . "\n");
echo('              ' . esc_html__('Consumer Key', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_consumerkey" type="text" value="' . esc_attr($tool->getKey()) . '" name="consumerkey" class="regular-text">' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_sharedsecret">' . "\n");
echo('              ' . esc_html__('Shared secret', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_sharedsecret" type="text" value="' . esc_attr($tool->secret) . '" name="sharedsecret" class="regular-text">' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('      </tbody>' . "\n");
echo('    </table>' . "\n");
echo('' . "\n");
echo('    <h2>' . esc_html__('LTI 1.3 Configuration', LTI_Platform::get_plugin_name()) . '</h2>' . "\n");
echo('' . "\n");
echo('    <table class="form-table">' . "\n");
echo('      <tbody>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_initiateloginurl">' . "\n");
echo('              ' . esc_html__('Initiate Login URL', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_initiateloginurl" type="text" value="' . esc_attr($tool->initiateLoginUrl) . '" name="initiateloginurl" class="regular-text">' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_redirectionuris">' . "\n");
echo('              ' . esc_html__('Redirection URI(s)', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <textarea id="id_redirectionuris" name="redirectionuris" class="regular-text">');
if (is_array($tool->redirectionUris)) {
    echo(esc_html(implode("\r\n", $tool->redirectionUris)));
}
echo('</textarea>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_jwksurl">' . "\n");
echo('              ' . esc_html__('Public Keyset URL', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <input id="id_jwksurl" type="text" aria-required="true" value="' . esc_url($tool->jku) . '" name="jwksurl" class="regular-text">' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('        <tr class="form-field">' . "\n");
echo('          <th scope="row">' . "\n");
echo('            <label for="id_publickey">' . "\n");
echo('              ' . esc_html__('Public key', LTI_Platform::get_plugin_name()) . '' . "\n");
echo('              <span class="description">' . esc_html__('(PEM format)', LTI_Platform::get_plugin_name()) . '</span>' . "\n");
echo('            </label>' . "\n");
echo('          </th>' . "\n");
echo('          <td>' . "\n");
echo('            <textarea id="id_publickey" name="publickey" class="regular-text">' . esc_textarea($tool->rsaKey) . '</textarea>' . "\n");
echo('          </td>' . "\n");
echo('        </tr>' . "\n");
echo('      </tbody>' . "\n");
echo('    </table>' . "\n");
echo('' . "\n");
if ($tool->canUseLTI13()) {
    echo('    <div class="card">' . "\n");
    echo('      <h3 class="title">' . esc_html__('Platform configuration to share with this tool') . '</h3>' . "\n");
    echo('      <div class="inside">' . "\n");
    echo('        <table>' . "\n");
    echo('          <tbody>' . "\n");
    echo('            <tr><th class="alignleft">' . esc_html__('Platform ID', LTI_Platform::get_plugin_name()) . '</th><td>' . esc_url(get_option('siteurl')) . '</td></tr>' . "\n");
    echo('            <tr><th class="alignleft">' . esc_html__('Client ID', LTI_Platform::get_plugin_name()) . '</th><td>' . esc_html($tool->code) . '</td></tr>' . "\n");
    echo('            <tr><th class="alignleft">' . esc_html__('Deployment ID', LTI_Platform::get_plugin_name()) . '</th><td>' . esc_html(get_current_blog_id()) . '</td></tr>' . "\n");
    echo('            <tr><th class="alignleft">' . esc_html__('Public Keyset URL', LTI_Platform::get_plugin_name()) . '</th><td>' . esc_url(get_option('siteurl') . '/?' . LTI_Platform::get_plugin_name() . '&amp;keys') . '</td></tr>' . "\n");
    echo('            <tr><th class="alignleft">' . esc_html__('Authentication request URL', LTI_Platform::get_plugin_name()) . '</th><td>' . esc_url(get_option('siteurl') . '/?' . LTI_Platform::get_plugin_name() . '&amp;auth') . '</td></tr>' . "\n");
    echo('          </tbody>' . "\n");
    echo('        </table>' . "\n");
    echo('      </div>' . "\n");
    echo('    </div>' . "\n");
    echo("\n");
}
submit_button(null, 'primary', 'submit', true, array('id' => 'submit_bottom'));

echo('  </form>' . "\n");
echo('</div>' . "\n");
