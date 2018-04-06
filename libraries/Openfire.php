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
clearos_load_language('network');
clearos_load_language('certificate_manager');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\certificate_manager\Certificate_Manager as Certificate_Manager;
use \clearos\apps\groups\Group_Factory as Group_Factory;
use \clearos\apps\ldap\LDAP_Factory as LDAP_Factory;
use \clearos\apps\network\Domain as Domain;
use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\system_database\System_Database as System_Database;
use \clearos\apps\users\User_Factory as User_Factory;
use \clearos\apps\users\User_Utilities as User_Utilities;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('certificate_manager/Certificate_Manager');
clearos_load_library('groups/Group_Factory');
clearos_load_library('ldap/LDAP_Factory');
clearos_load_library('network/Domain');
clearos_load_library('network/Hostname');
clearos_load_library('network/Network_Utils');
clearos_load_library('system_database/System_Database');
clearos_load_library('users/User_Factory');
clearos_load_library('users/User_Utilities');

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
    const APP_BASENAME = 'openfire';
    const FILE_CONFIG = '/usr/share/openfire/conf/openfire.xml';
    const FILE_SECURITY_CONFIG = '/usr/share/openfire/conf/security.xml';
    const FILE_PKCS12 = '/var/clearos/openfire/keystore.p12';
    const FILE_KEYSTORE = '/usr/share/openfire/resources/security/keystore';
    const CONSTANT_KEYSTORE_PW = 'changeit';
    const PATH_HOTDEPLOY = '/usr/share/openfire/resources/security/hotdeploy';
    const PROPERTY_ADMINS = 'admin.authorizedJIDs';
    const PROPERTY_XMPP_DOMAIN = 'xmpp.domain';
    const PROPERTY_XMPP_FQDN = 'xmpp.fqdn';
    const DEFAULT_OFMEET_USER = 'focus';
    const OPENFIRE_USER = 'openfire';
    const OPENFIRE_GROUP = 'openfire';
    const COMMAND_KEYTOOL = '/usr/bin/keytool';
    const COMMAND_OPENSSL = '/usr/bin/openssl';

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
     * Returns the Openfire administrator accounts.
     *
     * @return array Openfire administrator usernames (without @example.org)
     * @throws Engine_Exception
     */

    public function get_current_admins()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = $this->_get_property(self::PROPERTY_ADMINS);
        $matches = [];
        preg_match_all('/(?:(?P<username>[a-zA-Z0-9_.+-]+)@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+)/', $list, $matches);
        $usernames = $matches['username'];
        return $usernames;
    }

    /**
     * Returns configured digital certificate.
     *
     * @return string basename of digital certificate
     * @throws Engine_Exception
     */

    public function get_digital_certificate()
    {
        clearos_profile(__METHOD__, __LINE__);

        $certificate_manager = new Certificate_Manager();
        $registered = $certificate_manager->get_registered_certificate(self::APP_BASENAME, '-');

        return $registered;
    }

    /**
     * Returns list of available certificates.
     *
     * @return array list of available certificates
     * @throws Engine_Exception
     */

    public function get_digital_certificates()
    {
        clearos_profile(__METHOD__, __LINE__);

        $certificate_manager = new Certificate_Manager();
        $hostnames = $certificate_manager->get_secure_hostnames();

        return $hostnames;
    }

    /**
     * Returns XMPP domain.
     *
     * @param boolean $return_default return a default value if XMPP domain is not set
     *
     * @return string XMPP domain
     * @throws Engine_Exception
     */

    public function get_xmpp_domain($return_default = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $domain = $this->_get_property(self::PROPERTY_XMPP_DOMAIN);

        if ($return_default && empty($domain)) {
            $domainObject = new Domain();
            $domain = $domainObject->get_default();
        }

        return $domain;
    }

    /**
     * Returns XMPP fully-qualified domain name.
     *
     * @param boolean $return_default return a default value if XMPP FQDN is not set
     * @return string XMPP domain
     * @throws Engine_Exception
     */

    public function get_xmpp_fqdn($return_default = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $domain = $this->_get_property(self::PROPERTY_XMPP_FQDN);

        if ($return_default && empty($domain)) {
            $hostname = new Hostname();
            $domain = $hostname->get_internet_hostname();
        }

        return $domain;
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

        $group = Group_Factory::create('openfire_plugin');
        $raw_list = $group->get_members();

        $list = [];

        foreach ($raw_list as $member) {
            if ($member != self::DEFAULT_OFMEET_USER)
                $list[] = $member;
        }

        return $list;
    }

    /**
     * Returns initialization state.
     *
     * @return boolean TRUE if Openfire has been initialized
     * @throws Engine_Exception
     */

    public function is_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $xmpp_domain = $this->get_xmpp_domain(FALSE);

        if (empty($xmpp_domain))
            return FALSE;
        else
            return TRUE;
    }

    /**
     * Sets the Openfire administrator account.
     *
     * @param array usernames Array of username without the domain part
     * @return void
     * @throws Engine_Exception
     */

    public function set_admins($usernames)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation_Exception::is_valid($this->validate_username($username));
        $domain = $this->get_xmpp_domain();

        $add_domain = function($username) use ($domain) {
            return $username . '@' . $domain;
        };
        $full_usernames = array_map($add_domain, $usernames);
        $openfire_value = implode(', ', $full_usernames);
        $this->_set_property(self::PROPERTY_ADMINS, $openfire_value);
    }

    /**
     * Sets the digital certificate.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_certificate($certificate)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_certificate($certificate));

        // Register certificate with Certificate Manager
        //----------------------------------------------

        $cert_object['-'] = $certificate;
        $certificate_manager = new Certificate_Manager();
        $certificate_manager->register($cert_object, 'openfire', lang('openfire_app_name'));

        // Import via keytool
        //-------------------

        $details = $certificate_manager->get_certificate($certificate);
        if (array_key_exists('fullchain-filename', $details))
            $cert_filename = $details['fullchain-filename'];
        else
            $cert_filename = $details['certificate-filename'];

        $shell = new Shell();
        $shell->execute(
            self::COMMAND_OPENSSL,
            'pkcs12 -export ' .
            '-name ' . $certificate . ' ' .
            '-out ' . self::FILE_PKCS12 . ' -inkey ' . $details['key-filename']  . ' -in ' . $cert_filename . ' ' .
            '-password "pass:' . self::CONSTANT_KEYSTORE_PW . '"',
            TRUE
        );

        try {
            $options['validate_exit_code'] = FALSE;

            $shell->execute(
                self::COMMAND_KEYTOOL,
                '-keystore ' . self::FILE_KEYSTORE . ' ' .
                '-delete -alias ' . $certificate . ' ' .
                '-storepass ' . self::CONSTANT_KEYSTORE_PW,
                TRUE,
                $options
            );
        } catch (\Exception $e) {
            // Not fatal
        }

        $shell->execute(
            self::COMMAND_KEYTOOL,
            '-importkeystore ' .
            '-alias ' . $certificate . ' ' .
            '-destkeystore ' . self::FILE_KEYSTORE . ' ' .
            '-deststorepass changeit ' .
            '-destkeypass changeit ' .
            '-srcstoretype PKCS12 ' .
            '-srcstorepass changeit ' .
            '-srckeystore ' . self::FILE_PKCS12,
            TRUE
        );

        // TODO: Hotdeploy
        /*
        $details = $certificate_manager->get_certificate($certificate);

        $cert_files = ['certificate-filename', 'key-filename'];

        foreach ($cert_files as $cert_file) {
            $source = $details[$cert_file];
            $basename = basename($source);
            $destination = self::PATH_HOTDEPLOY . '/' . $cert_file . '.pem';

            $destination_file = new File($destination);

            if ($destination_file->exists())
                $destination_file->delete();

            $file = new File($source);
            $file->copy_to($destination, FALSE);

            $destination_file->chown(self::OPENFIRE_USER, self::OPENFIRE_GROUP);
            $destination_file->chmod('0660');
        }
        */
    }

    /**
     * Sets install defaults.
     *
     * On install, a few defaults are guessed based on existing ClearOS
     * configuration.  For instance, we can use the default internet hostname
     * to sett the XMPP domain.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_install_defaults()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Update domain and hostname information
        //---------------------------------------

        $hostname = new Hostname();
        $xmpp_fqdn = $hostname->get_internet_hostname();
        $this->_set_property(self::PROPERTY_XMPP_FQDN, $xmpp_fqdn);
    }

    /**
     * Sets XMPP domain.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_xmpp_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_xmpp_domain($domain));

        $this->_set_property(self::PROPERTY_XMPP_DOMAIN, $domain);

        // The admin IDs needs the domain portion replaced
        $admins = $this->get_current_admins();
        if (!empty($admins)) {
            //The following will replace the domain to the one set above as a side-effect
            $this->set_admins($admins);
        }

        // And so does the ofmeet focus user.
        $this->_set_property('org.jitsi.videobridge.ofmeet.focus.user.jid', self::DEFAULT_OFMEET_USER .  '@' . $domain);
    }

    /**
     * Sets XMPP fully-qualified hostname.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_xmpp_fqdn($fqdn)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_xmpp_fqdn($fqdn));

        $this->_set_property(self::PROPERTY_XMPP_FQDN, $fqdn);
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

    public function update_ldap_properties()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Update LDAP base DN and search filter
        //--------------------------------------
        // TODO: add Active Directory support for search_filter

        $ldap = LDAP_Factory::create();
        $base_dn = $ldap->get_base_dn();
        $bind_pw = $ldap->get_bind_password();
        $admin_dn = 'cn=manager,ou=Internal,' . $base_dn;
        $search_filter = '(|(memberof=cn=openfire_plugin,ou=Groups,ou=Accounts,' . $base_dn . ')(uid=openfire-focus))';

        $this->_set_property('ldap.baseDN', $base_dn);
        $this->_set_property('ldap.searchFilter', $search_filter);
        $this->_set_property('ldap.adminDN', $admin_dn);
        $this->_set_property('ldap.adminPassword', $bind_pw);
        $this->_set_property('ldap.groupSearchFilter', '(&(objectClass=groupOfNames)(gidNumber<=100000)(!(cn=*_plugin)))');
        $this->_set_property('provider.auth.className', 'org.jivesoftware.openfire.ldap.LdapAuthProvider');

        // Update adminDN and adminPassword via openfire.xml
        //--------------------------------------------------
        // TODO: more Active Directory changes required

        $this->_reset_security_config();
        // $this->_set_xml('adminDN', $admin_dn);
        // $this->_set_xml('adminPassword', $bind_pw);

        // Restart Openfire
        //-----------------

        $this->reset(FALSE);
    }

    /**
     * Updates ofmeet focus user.
     *
     * The ofmeet plugin requires a special user.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function update_ofmeet_properties()
    {
        clearos_profile(__METHOD__, __LINE__);

        $password = User_Utilities::generate_password();
        $xmpp_domain = $this->get_xmpp_domain();

        $this->_set_property('org.jitsi.videobridge.ofmeet.focus.user.jid', self::DEFAULT_OFMEET_USER .  '@' . $xmpp_domain);
        $this->_set_property('org.jitsi.videobridge.ofmeet.focus.user.password', $password);

        $user = User_Factory::create(self::DEFAULT_OFMEET_USER);

        if ($user->exists()) {
            $user->reset_password($password, $password, 'openfire', FALSE);
        } else {
            clearos_log('openfire', 'creating focus user');
            $user_info['core']['first_name'] = 'Openfire';
            $user_info['core']['last_name'] = 'Focus User';
            $user_info['core']['type'] = 'built-in';
            $user_info['core']['home_directory'] = '/var/clearos/openfire/focus-user';

            $user_info['plugins']['openfire']['state'] = TRUE;
            $user->add($user_info, $password);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for digital certificates.
     *
     * @param string $certificate basename of digital certificate
     *
     * @return string error message if certificate is invalid
     */

    public function validate_certificate($certificate)
    {
        clearos_profile(__METHOD__, __LINE__);

        $certificate_manager = new Certificate_Manager();
        $certificates = $certificate_manager->get_certificates();

        if (!array_key_exists($certificate, $certificates))
            return lang('certificate_manager_certificate_invalid');
    }

    /**
     * Validation routine for XMPP domain.
     *
     * @param string $ip IP address
     *
     * @return string error message if domain is invalid
     */

    public function validate_xmpp_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_domain($domain))
            return lang('network_domain_invalid');
    }

    /**
     * Validation routine for XMPP fully-qualified domain.
     *
     * @param string $ip IP address
     *
     * @return string error message if FQDN is invalid
     */

    public function validate_xmpp_fqdn($fqdn)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_hostname($fqdn))
            return lang('network_hostname_invalid');
    }

    /**
     * Validation routine for username.
     *
     * @param string $ip IP address
     *
     * @return string error message if username is invalid
     */

    public function validate_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        $all_users = $this->get_possible_admins();

        if (!in_array($username, $all_users))
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

        $query = "INSERT INTO ofProperty (name,propValue) " .
            "VALUES ('$property','$value') " .
            "ON DUPLICATE KEY UPDATE name = VALUES(name), propValue = VALUES(propValue);";

        $system_database->run_update(self::DATABASE, $query);
    }

    /**
     * Resets security configuration back to default.
     *
     * The security.xml parameters disappear.  Copy fresh default when needed.
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _reset_security_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: just inject the LDAP parameters instead of stomping
        // on security.xml.

        $file = new File(clearos_app_base('openfire') . '/deploy/security.xml');
        $file->copy_to(self::FILE_SECURITY_CONFIG);

        $file = new File(self::FILE_SECURITY_CONFIG);
        $file->chown('openfire', 'openfire');
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
