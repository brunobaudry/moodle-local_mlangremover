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
 * @module     local_mlangremover/deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default {
    actions: {
        // CheckBoxes: '[data-action="local_mlangremover/checkbox"]', //
        // selectAllBtn: '[data-action="local_mlangremover/select-all"]', // selectAllBtn
        letsdobutton: '[data-action="local_mlangremover/letsdobutton"]', //
        removeRadios: '[name="local_mlangremover/removehow"]', //
        removehow: '[name="local_mlangremover/removehow"]:checked', //
    },
    statuses: {
        // CheckedCheckBoxes: '[data-action="local_mlangremover/checkbox"]:checked', //
        allMLangCkboxes: '#local_mlangremover__mlangtags input[type="checkbox"]', //
        allMLangCkboxesNames: '[name="mlangsselected[]"]', //
        selectedMLangCkboxes: '#local_mlangremover__mlangtags input[type="checkbox"]:checked', //
        removetag: '#local_mlangremover__removetag', //
    },
    editors: {
        multiples: {
            textAreasResults: 'div[data-result-key="<KEY>"]', //
        },
    },
    sourcetexts: {
        // Keys: '[data-sourcetext-key="<KEY>"]', //
        parentrow: '[data-row-id="<KEY>"]', //
    }
};
