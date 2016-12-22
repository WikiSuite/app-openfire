<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'openfire';
$app['version'] = '1.0.1';
$app['release'] = '1';
$app['vendor'] = 'Marc Laporte';
$app['packager'] = 'eGloo';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('openfire_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('openfire_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_communication_and_collaboration');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'openfire',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/openfire' => array(),
    '/var/clearos/openfire/backup' => array(),
);

$app['core_file_manifest'] = array(
    'openfire.php'=> array('target' => '/var/clearos/base/daemon/openfire.php')
);

$app['delete_dependency'] = array(
    'app-openfire-core',
    'openfire',
);
