<?php

/**
 * Openfire ajax helpers.
 *
 * @category   apps
 * @package    openfire
 * @subpackage javascript
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('openfire');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

///////////////////////////////////////////////////////////////////////////
// M A I N
///////////////////////////////////////////////////////////////////////////

$(document).ready(function() {
    $('#openfire_not_running').hide();
    $('#openfire_running').hide();

    if ($(location).attr('href').match('.*openfire\/settings\/edit$') != null) {
        $('#openfire_status').hide();
        $('#openfire_running').show();
    } else {
        $('#openfire_status').show();
        getOpenfireStatus(0, 0);
    }
});


// Functions
//----------

function getOpenfireStatus(upCount, downCount) {
    $.ajax({
        url: '/app/openfire/server/status',
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            if (payload.status == 'running') {
                downCount = 0;
                upCount++;
                if (upCount <= 3) {
                    $('#openfire_status').show();
                    $('#openfire_not_running').hide();
                    $('#openfire_running').hide();
                } else {
                    $('#openfire_status').hide();
                    $('#openfire_not_running').hide();
                    $('#openfire_running').show();
                }
            } else {
                upCount = 0;
                downCount++;
                if (downCount <= 2) {
                    $('#openfire_status').show();
                    $('#openfire_not_running').hide();
                    $('#openfire_running').hide();
                } else {
                    $('#openfire_status').hide();
                    $('#openfire_not_running').show();
                    $('#openfire_running').hide();
                }
            }
            window.setTimeout(getOpenfireStatus, 2000, upCount, downCount);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            window.setTimeout(getOpenfireStatus, 2000, upCount, downCount);
        }
    });
}

// vim: syntax=javascript
