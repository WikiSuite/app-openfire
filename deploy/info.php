<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'openfire';
$app['version'] = '1.2.4';
$app['release'] = '1';
$app['vendor'] = 'WikiSuite';
$app['packager'] = 'eGloo';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('openfire_app_description');
$app['powered_by'] = array(
    'vendor' => array(
        'name' => 'Ignite Realtime',
        'url' => 'https://www.igniterealtime.org/',
    ),
    'packages' => array(
        'openfire' => array(
            'name' => 'Openfire',
            'version' => '---',
            'url' => 'https://www.igniterealtime.org/projects/openfire/',
        ),
    ),
);

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('openfire_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_communication_and_collaboration');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['openfire']['title'] = $app['name'];
$app['controllers']['settings']['title'] = lang('base_settings');
$app['controllers']['policy']['title'] = lang('base_app_policy');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-certificate-manager'
);

$app['core_requires'] = array(
    'app-base >= 1:2.4.15',
    'app-certificate-manager-core >= 1:2.4.16',
    'app-users-core >= 1:2.4.0',
    'app-groups-core',
    'app-ldap-core',
    'app-network-core >= 1:2.4.3',
    'app-openfire-plugin-core',
    'app-system-database-core >= 1:2.3.3',
    'openfire >= 4.2.0',
    'openssl'
);

$app['core_directory_manifest'] = array(
    '/var/clearos/openfire' => array(),
    '/var/clearos/openfire/backup' => array(),
    '/var/clearos/openfire/focus-user' => array(),
);

$app['core_file_manifest'] = array(
    'openfire.php'=> array('target' => '/var/clearos/base/daemon/openfire.php'),
    'openldap-configuration-event'=> array(
        'target' => '/var/clearos/events/openldap_configuration/openfire',
        'mode' => '0755'
    ),
    'openldap-online-event'=> array(
        'target' => '/var/clearos/events/openldap_online/openfire',
        'mode' => '0755'
    ),
    'lets-encrypt-event'=> array(
        'target' => '/var/clearos/events/lets_encrypt/openfire',
        'mode' => '0755'
    ),
);

$app['delete_dependency'] = array(
    'app-openfire-core',
    'openfire',
);
