<?php

/**
 * Openfire settings view.
 *
 * @category   apps
 * @package    openfire
 * @subpackage views
 * @author     Marc Laporte
 * @copyright  2016 Marc Laporte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       https://github.com/eglooca/app-openfire
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//  
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('openfire');

///////////////////////////////////////////////////////////////////////////////
// Form - Not Running
///////////////////////////////////////////////////////////////////////////////

echo "<div id='openfire_not_running' style='display:none;'>";

echo infobox_warning(
    lang('openfire_admin_console'),
    lang('openfire_admin_console_not_running_help'),
    $options
);

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/openfire/settings'),
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/openfire/settings/edit')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form - Running
///////////////////////////////////////////////////////////////////////////////

echo "<div id='openfire_running' style='display:none;'>";

$options['buttons']  = array(
    anchor_custom($admin_url, lang('openfire_go_to_admin_console'), 'high', array('target' => '_blank'))
);

echo infobox_highlight(
    lang('openfire_admin_console'),
    lang('openfire_admin_console_help'),
    $options
);

echo form_open('openfire/settings');
echo form_header(lang('base_settings'));

echo field_dropdown('admin', $admins, $admin, lang('base_administrator'), $read_only);
echo field_button_set($buttons);

echo form_footer();
echo form_close();

echo "</div>";
