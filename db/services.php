<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local Mlangremover
 *
 * @package    local_mlangremover
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Web_services_API
 */

defined('MOODLE_INTERNAL') || die();

// Define edittranslation capability.
define('LOCAL_DEEPLER_CAP', 'local/deepler:edittranslations');

// Add functions for webservices.
$functions = [
        'local_mlangremover_update_translation' => [
                'classname' => 'local_mlangremover\external\update_translation',
                'description' => 'Update textfiield with new mlang tags',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => LOCAL_DEEPLER_CAP,
        ],
        'local_mlangremover_get_field' => [
                'classname' => 'local_mlangremover\external\get_field',
                'description' => 'Get field data',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => LOCAL_DEEPLER_CAP,
        ],
];
