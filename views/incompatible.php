<?php

/**
 * Openfire incompatible view.
 *
 * @category   apps
 * @package    openfire
 * @subpackage views
 * @author     Marc Laporte
 * @copyright  2018 Marc Laporte
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

$this->lang->load('openfire');

echo infobox_warning(
    lang('base_warning'),
    lang('openfire_openfire_incompatible_message')
);
