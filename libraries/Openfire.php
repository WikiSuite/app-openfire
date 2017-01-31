<?php

/**
 * Openfire server class.
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
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\openfire;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('openfire');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\groups\Group_Engine as Group_Engine;
use \clearos\apps\groups\Group_Manager_Factory as Group_Manager_Factory;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\system_database\System_Database as System_Database;

clearos_load_library('base/Daemon');
clearos_load_library('groups/Group_Engine');
clearos_load_library('groups/Group_Manager_Factory');
clearos_load_library('network/Network_Utils');
clearos_load_library('system_database/System_Database');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Openfire server class.
 *
 * @category   apps
 * @package    openfire
 * @subpackage controllers
 * @author     Marc Laporte
 * @copyright  2016 Marc Laporte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       https://github.com/eglooca/app-openfire
 */

class Openfire extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const DATABASE = 'openfire';
    const PROPERTY_ADMINS = 'admin.authorizedJIDs';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Openfire constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('openfire');
    }

    /**
     * Returns the Openfire administrator account.
     *
     * @return string Openfire administrator account
     * @throws Engine_Exception
     */

    public function get_admin()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = $this->_get_property(self::PROPERTY_ADMINS);

        $first_match = preg_replace('/,.*/', '', $list);

        return $first_match;
    }

    /**
     * Returns list of possible Openfire administrators.
     *
     * @return array list of possible Openfire administrators
     * @throws Engine_Exception
     */

    public function get_possible_admins()
    {
        clearos_profile(__METHOD__, __LINE__);

        $manager = Group_Manager_Factory::create();
        $groups = $manager->get_details(Group_Engine::FILTER_ALL);

        $list = [];

        if (array_key_exists('openfire_plugin', $groups)) {
            foreach ($groups['openfire_plugin']['core']['members'] as $username) {
                $openfire_username = $username . '@' . 'clear7.lan'; // FIXME: what is this in AD mode?
                $list[$openfire_username] = $username;
            }
        }

        return $list;
    }

    /**
     * Sets the Openfire administrator account.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_admin($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_username($username));

        $this->_set_property(self::PROPERTY_ADMINS, $username);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N  M E T H O D S 
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for DNS server.
     *
     * @param string $ip IP address
     *
     * @return string error message if DNS server IP address is invalid
     */

    public function validate_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        $all_users = $this->get_possible_admins();

        if (!array_key_exists($username, $all_users))
            return lang('openfire_administrator_account_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns value of given property.
     *
     * @param string $property property
     *
     * @return string value of given property
     * @throws Engine_Exception
     */

    protected function _get_property($property)
    {
        clearos_profile(__METHOD__, __LINE__);

        $system_database = new System_Database();

        $results = $system_database->run_query(self::DATABASE, 'SELECT name,propValue FROM ofProperty');

        $value = '';

        foreach ($results as $result) {
            if ($property == $result['name'])
                $value = $result['propValue'];
        }

        return $value;
    }

    /**
     * Sets value for a given property.
     *
     * @param string $property property
     * @param string $value value
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_property($property, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $system_database = new System_Database();

        $query = "UPDATE ofProperty SET propValue='$value' WHERE name='$property';";
        $system_database->run_update(self::DATABASE, $query);
    }
}
