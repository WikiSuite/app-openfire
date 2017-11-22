<?php

/**
 * Openfire network check controller.
 *
 * @category   apps
 * @package    openfire
 * @subpackage controllers
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2017 Marc Laporte
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require clearos_app_base('network') . '/controllers/network_check.php';

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Openfire network check controller.
 *
 * @category   apps
 * @package    openfire
 * @subpackage controllers
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2017 Marc Laporte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       https://github.com/eglooca/app-openfire
 */

class Network extends Network_Check
{
    /**
     * Network check constructor.
     */

    function __construct()
    {
        $this->lang->load('openfire');

        $rules = [
            [ 'name' => lang('openfire_xmpp_client'), 'protocol' => 'TCP', 'port' => 5222 ],
            [ 'name' => lang('openfire_xmpp_ssl_client'), 'protocol' => 'TCP', 'port' => 5223 ],
            [ 'name' => lang('openfire_meetings'), 'protocol' => 'TCP', 'port' => 7443 ],
            [ 'name' => lang('openfire_admin_console'), 'protocol' => 'TCP', 'port' => 9090 ],
        ];
        
        parent::__construct('openfire', $rules);
    }
}
