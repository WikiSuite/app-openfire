<?php

/**
 * Openfire settings controller.
 *
 * @category   apps
 * @package    openfire
 * @subpackage controllers
 * @author     Marc Laporte
 * @copyright  2017-2018 Marc Laporte
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
 * Openfire settings controller.
 *
 * @category   apps
 * @package    openfire
 * @subpackage controllers
 * @author     Marc Laporte
 * @copyright  2017-2018 Marc Laporte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       https://github.com/eglooca/app-openfire
 */

class Settings extends ClearOS_Controller
{
    /**
     * Openfire settings controller
     *
     * @return view
     */

    function index()
    {
        $this->_common('view');
    }

    /**
     * Edit view.
     *
     * @return view
     */

    function edit()
    {
        $this->_common('edit');
    }

    /**
     * View view.
     *
     * @return view
     */

    function view()
    {
        $this->_common('view');
    }

    /**
     * Common view/edit handler.
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _common($form_type)
    {
        // Load dependencies
        //------------------

        $this->lang->load('openfire');
        $this->load->library('openfire/Openfire');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('admin', 'openfire/Openfire', 'validate_username');
        $this->form_validation->set_policy('domain', 'openfire/Openfire', 'validate_xmpp_domain', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && $form_ok) {
            try {
                // Note: set the certificate/domain first since it's needed for setting the admin
                $cert_and_hostname = preg_split('/\|/', $this->input->post('hostname'));

                $this->openfire->set_certificate($cert_and_hostname[0]);
                $this->openfire->set_xmpp_fqdn($cert_and_hostname[1]);
                $this->openfire->set_xmpp_domain($this->input->post('domain'));
                $this->openfire->set_admins($this->input->post('admins'));

                // A bit hacky, but add/update ofmeet user at this point too
                $this->openfire->update_ofmeet_properties();

                $this->openfire->reset(TRUE);

                $this->page->set_status_updated();
                redirect('/openfire/settings');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['admin_url'] = 'https://' . $this->openfire->get_xmpp_fqdn() . ':9091/';
            $data['possible_admins'] = $this->openfire->get_possible_admins();
            $data['current_admins'] = $this->openfire->get_current_admins();
            $data['domain'] = $this->openfire->get_xmpp_domain();
            $data['initialized'] = $this->openfire->is_initialized();
            $data['domain_edit'] = (empty($_REQUEST['domain_edit'])) ? FALSE : TRUE;

            // TODO: handling the group options is a bit of a manual job.
            // This should be merged into something better

            $hostname = $this->openfire->get_xmpp_fqdn();
            $cert = $this->openfire->get_digital_certificate();

            if ($form_type === 'edit') {
                $data['hostname'] = $cert . '|' . $hostname;

                $hostname_info = $this->openfire->get_digital_certificates();

                foreach ($hostname_info as $cert => $details) {
                    $list = [];
                    foreach ($details['hostnames'] as $hostname)
                        $list[$cert . '|' . $hostname] = $hostname;

                    $data['hostnames'][$details['name']] = $list;
                }
            } else {
                // TODO: the group options doesn't like view-mode.  Hack it in.
                $data['hostnames'][$hostname] = $hostname;
                $data['hostname'] = $hostname;
            }
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('openfire/settings', $data, lang('base_settings'));
    }
}
