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
use \clearos\apps\base\File as File;
use \clearos\apps\groups\Group_Engine as Group_Engine;
use \clearos\apps\groups\Group_Manager_Factory as Group_Manager_Factory;
use \clearos\apps\ldap\LDAP_Factory as LDAP_Factory;
use \clearos\apps\network\Domain as Domain;
use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\system_database\System_Database as System_Database;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('groups/Group_Engine');
clearos_load_library('groups/Group_Manager_Factory');
clearos_load_library('ldap/LDAP_Factory');
clearos_load_library('network/Domain');
clearos_load_library('network/Hostname');
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
    const FILE_CONFIG = '/usr/share/openfire/conf/openfire.xml';
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

        $ldap = LDAP_Factory::create();
        $base_domain = $ldap->get_base_internet_domain(); // TODO: what is this base_domain AD mode?

        $list = [];

        if (array_key_exists('openfire_plugin', $groups)) {
            foreach ($groups['openfire_plugin']['core']['members'] as $username) {
                $openfire_username = $username . '@' . $base_domain;
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

    /**
     * Updates properties when underlying configuration changes.
     *
     * If an adminstrator makes changes to the ClearOS system, some
     * properties need to be updated.  For example, if the underlying LDAP
     * base DN changes, the ldap.adminDN property needs to be updated.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function update_properties()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Update domain and hostname information
        //---------------------------------------

        $domain = new Domain();
        $xmpp_domain = $domain->get_default();

        $hostname = new Hostname();
        $xmpp_fqdn = $hostname->get_internet_hostname();

        $this->_set_property('xmpp.domain', $xmpp_domain);
        $this->_set_property('xmpp.fqdn', $xmpp_fqdn);

        // Update LDAP base DN and search filter
        //--------------------------------------
        // TODO: add Active Directory support for search_filter

        $ldap = LDAP_Factory::create();
        $base_dn = $ldap->get_base_dn();
        $search_filter = '(memberof=cn=openfire_plugin,ou=Groups,ou=Accounts,' . $base_dn . ')';

        $this->_set_property('ldap.baseDN', $base_dn);
        $this->_set_property('ldap.searchFilter', $search_filter);

        // Update domain used in username
        //-------------------------------
        // TODO: Active Directory probably does something different here

        $base_domain = $ldap->get_base_internet_domain();
        $admin = $this->get_admin();
        $new_admin = preg_replace('/@.*/', '@' . $base_domain, $admin);

        $this->set_admin($new_admin);

        // Update adminDN and adminPassword via openfire.xml
        //--------------------------------------------------
        // TODO: more Active Directory changes required

        $this->_set_xml('adminDN', 'cn=manager,ou=Internal,' . $base_dn);
        $this->_set_xml('adminPassword', 'jFZ3/Hce3LA7lE77');

        // Restart Openfire
        //-----------------

        $this->reset(FALSE);
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

    /**
     * Sets special LDAP values in openfire.xml.
     *
     * @param string $property property
     * @param string $value value
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_xml($property, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: pull in webconfig-php-xml parser in a future release.
        // For now, just hack it in so we can avoid a webconfig restart.

        $file = new File(self::FILE_CONFIG);
        $contents = $file->get_contents_as_array();
        $ldap_found = FALSE;
        $in_ldap = FALSE;
        $new = [];

        foreach ($contents as $line) {
            if ($in_ldap && (preg_match('/<' . $property . '>/', $line)))
                continue;

            $new[] = $line;

            if (preg_match('/<ldap>/', $line)) {
                $new[] = '    <' . $property . '>' . $value . '</' . $property . '>';
                $ldap_found = TRUE;
                $in_ldap = TRUE;
            }
        }

        if (!$ldap_found) {
            $new = [];

            foreach ($contents as $line) {
                if (preg_match('/<\/jive>/', $line)) {
                    $new[] = '  <ldap>';
                    $new[] = '    <' . $property . '>' . $value . '</' . $property . '>';
                    $new[] = '  </ldap>';
                }

                $new[] = $line;
            }
        }

        if ($file->exists())
            $file->delete();

        $file->create('openfire', 'openfire', '0644');
        $file->add_lines(implode("\n", $new));
    }
}
