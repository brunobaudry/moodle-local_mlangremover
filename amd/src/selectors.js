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
        checkBoxes: '[data-action="local_mlangremover/checkbox"]',
        selectAllBtn: '[data-action="local_mlangremover/select-all"]',
        letsdobutton: '[data-action="local_mlangremover/letsdobutton"]',
        toggleMultilang: '#toggleMultilang',
        removeRadios: '[name="local_mlangremover/removehow"]',
        removehow: '[name="local_mlangremover/removehow"]:checked',
    },
    statuses: {
        checkedCheckBoxes: '[data-action="local_mlangremover/checkbox"]:checked',
        successMessages: '[data-status="local_mlangremover/success-message"][data-key="<KEY>"]',
        multilang: '[data-row-id="<KEY>"] span#toggleMultilang',
        wait: 'local_mlangremover/wait',
        totranslate: 'local_mlangremover/totranslate',
        tosave: 'local_mlangremover/tosave',
        failed: 'local_mlangremover/failed',
        success: 'local_mlangremover/success',
        saved: 'local_mlangremover/saved',
        MlangsContainer: '#local_mlangremover__mlangtags',
        allMLangCkboxes: '#local_mlangremover__mlangtags input[type="checkbox"]',
        allMLangCkboxesNames: '[name="mlangsselected[]"]',
        selectedMLangCkboxes: '#local_mlangremover__mlangtags input[type="checkbox"]:checked',
        removetag: '#local_mlangremover__removetag',
    },
    editors: {
        textarea: '[data-action="local_mlangremover/textarea"',
        iframes: '[data-action="local_mlangremover/editor"] iframe',
        contentEditable: '[data-action="local_mlangremover/editor"] [contenteditable="true"]',
        multiples: {
            checkBoxesWithKey: 'input[type="checkbox"][data-key="<KEY>"]',
            editorChilds: '[data-action="local_mlangremover/editor"][data-key="<KEY>"] > *',
            textAreasSource: 'div[data-sourcetext-key="<KEY>"]',
            textAreasResults: 'div[data-result-key="<KEY>"]',
            editorsWithKey: '[data-action="local_mlangremover/editor"][data-key="<KEY>"]',
            contentEditableKeys: '[data-key="<KEY>"] [contenteditable="true"]'
        },
    },
    sourcetexts: {
        keys: '[data-sourcetext-key="<KEY>"]',
        multilangs: '#<KEY>',
        parentrow: '[data-row-id="<KEY>"]',
    }
};
