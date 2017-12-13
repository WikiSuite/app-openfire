<?php

/**
 * Openfire controller.
 *
 * @category   apps
 * @package    openfire
 * @subpackage controllers
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Openfire controller.
 *
 * @category   apps
 * @package    openfire
 * @subpackage controllers
 * @author     Marc Laporte
 * @copyright  2016 Marc Laporte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       https://github.com/eglooca/app-openfire
 */

class Openfire extends ClearOS_Controller
{
    /**
     * Openfire default controller.
     *
     * @return view
     */

    function index()
    {
        // Show account status widget if we're not in a happy state
        //---------------------------------------------------------

        $this->load->module('accounts/status');

        if ($this->status->unhappy()) {
            $this->status->widget('users');
            return;
        }

        // Show Certificate Manager widget if it is not initialized
        //---------------------------------------------------------

        $this->load->module('certificate_manager/certificate_status');

        if (!$this->certificate_status->is_initialized()) {
            $this->certificate_status->widget();
            return;
        }

        // Load dependencies
        //------------------

        $this->load->library('openfire/Openfire');
        $this->lang->load('openfire');

        // Load view data
        //---------------

        try {
            $initialized = $this->openfire->is_initialized();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        if ($initialized) {
            $views = array('openfire/server', 'openfire/network', 'openfire/settings', 'openfire/policy');
            $this->page->view_forms($views, lang('openfire_app_name'));
        } else {
            redirect('/openfire/settings/edit');
        }
    }
}
