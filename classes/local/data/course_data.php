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
namespace local_mlangremover\local\data;

require_once('textfield.php');
require_once('multilangfield.php');

/**
 * Course Data Processor.
 *
 * Processess course data for moodleform. This class is logic heavy.
 *
 * @package    local_mlangremover
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class course_data {
    /** @var string */
    protected $dbtable;
    /** @var \stdClass */
    protected $course;
    /** @var \course_modinfo|null */
    protected $modinfo;
    /** @var string */
    protected $lang;
    /** @var string */
    protected $contextid;
    /** @var \context_course */
    protected $context;
    /** @var string[]
     * List of db columns of type text that are know to be useless to tranlsate for a specific mod.
     */
    protected $modcolstoskip;
    /** @var string[]
     * List of common db columns of type text that are know to be useless to tranlsate .
     */
    protected $comoncolstoskip;
    /** @var string[]
     * List of db columns of type text that the user decides they to be useless to tranlsate.
     */
    protected $usercolstoskip;
    /** @var string[]
     * List of used mlang tags in the course.
     * */
    protected $mlangtags;

    /**
     * Class Construct.
     *
     * @param \stdClass $course
     * @param string $lang
     * @param int $contextid
     * @throws \moodle_exception
     */
    public function __construct(\stdClass $course, string $lang, int $contextid) {
        // Set db table.
        $this->dbtable = 'local_deepler';
        // Set course.
        $this->course = $course;
        // Get the context id.
        $this->contextid = $contextid;
        // Set modinfo.
        $modinfo = get_fast_modinfo($course);
        $this->modinfo = $modinfo;
        $plugins = \core_component::get_plugin_types();
        // Set language.
        $this->lang = $lang;
        // init the collection of used mlang tags in the course.
        $this->mlangtags = [];
        // Set the db fields to skipp.
        $this->comoncolstoskip = ['*_displayoptions'];
        $this->modcolstoskip =
                ['url_parameters', 'hotpot_outputformat', 'hvp_authors', 'hvp_changes', 'lesson_conditions',
                        'scorm_reference', 'studentquiz_allowedqtypes', 'studentquiz_excluderoles', 'studentquiz_reportingemail',
                        'survey_questions', 'data_csstemplate', 'data_config', 'wiki_firstpagetitle',
                        'bigbluebuttonbn_moderatorpass', 'bigbluebuttonbn_participants', 'bigbluebuttonbn_guestpassword',
                        'rattingallocate_setting', 'rattingallocate_strategy', 'hvp_json_content', 'hvp_filtered', 'hvp_slug',
                        'wooclap_linkedwooclapeventslug', 'wooclap_wooclapeventid', 'kalvidres_metadata',
                ];
        $this->usercolstoskip = [];
    }

    /**
     * Get Course Data via modinfo.
     *
     * @return array
     */
    public function getdata() {
        $coursedata = $this->getcoursedata();
        $sectiondata = $this->getsectiondata();
        $activitydata = $this->getactivitydata();
        // Sections added to the activity items.
        return $this->prepare_data($coursedata, $sectiondata, $activitydata);
    }

    /**
     * Prepare multidimentional array to re-arrange textfields to match course presentation.
     *
     * @param array $coursedata
     * @param array $sectiondata
     * @param array $activitydata
     * @return array[]
     */
    private function prepare_data(array $coursedata, array $sectiondata, array $activitydata) {
        $tab = ['0' => ['sections' => $coursedata, 'activities' => []]];
        /** @var multilangfield $v */
        foreach ($sectiondata as $v) {
            $tab[$v->get_textfield()->get_id()]['sections'][] = $v;
        }
        /** @var multilangfield $av */
        foreach ($activitydata as $av) {
            // If the section is not found place it under the course data as general intro.
            $sectionid = isset($tab[$av->get_textfield()->get_sectionid()]) ? $av->get_textfield()->get_sectionid() : "0";
            $tab[$sectionid]['activities'][] = $av;
        }
        return $tab;
    }

    /**
     * Get Course Data.
     *
     * @return array
     */
    private function getcoursedata() {
        $coursedata = [];
        $course = $this->modinfo->get_course();
        $activity = new \stdClass();
        $activity->modname = 'course';
        $activity->id = null;
        $activity->section = null;
        if ($course->fullname) {
            $data = $this->build_data(
                    $course->id,
                    $course->fullname,
                    0,
                    'fullname',
                    $activity
            );
            array_push($coursedata, $data);
        }
        if ($course->shortname) {
            $data = $this->build_data(
                    $course->id,
                    $course->shortname,
                    0,
                    'shortname',
                    $activity
            );
            array_push($coursedata, $data);
        }
        if ($course->summary) {
            $data = $this->build_data(
                    $course->id,
                    $course->summary,
                    $course->summaryformat,
                    'summary',
                    $activity
            );
            array_push($coursedata, $data);
        }

        return $coursedata;
    }

    /**
     * Get Section Data.
     *
     * @return array
     */
    private function getsectiondata() {
        global $DB;
        $sections = $this->modinfo->get_section_info_all();
        $sectiondata = [];
        $activity = new \stdClass();
        $activity->modname = 'course_sections';
        $activity->id = null;
        $activity->section = null;
        foreach ($sections as $sk => $section) {
            $record = $DB->get_record('course_sections', ['course' => $this->course->id, 'section' => $sk]);
            if ($record->name) {
                $data = $this->build_data(
                        $record->id,
                        $record->name,
                        0,
                        'name',
                        $activity
                );
                array_push($sectiondata, $data);
            }
            if ($record->summary) {
                $data = $this->build_data(
                        $record->id,
                        $record->summary,
                        $record->summaryformat,
                        'summary',
                        $activity
                );
                array_push($sectiondata, $data);
            }
        }
        return $sectiondata;
    }

    /**
     * Get Activity Data.
     *
     * @return array
     * TODO MDL-000 Parse recursive wiki pages. Though only for no collaborative wikis as built by students.
     */
    private function getactivitydata() {
        global $CFG;
        global $DB;
        $activitydata = [];

        foreach ($this->modinfo->instances as $instances) {
            foreach ($instances as $activity) {
                // Build first level activities.
                $activitydbrecord = $this->injectactivitydata($activitydata, $activity, $activity->modname);
                // Build outstanding subcontent.
                switch ($activity->modname) {
                    case 'book':
                        include_once($CFG->dirroot . '/mod/book/locallib.php');
                        $chapters = book_preload_chapters($activitydbrecord);
                        foreach ($chapters as $c) {
                            $this->injectbookchapter($activitydata, $c, $activity->section);
                        }
                        break;
                    case 'wiki':
                        include_once($CFG->dirroot . '/mod/wiki/locallib.php');
                        $wikis = wiki_get_subwikis($activitydbrecord->id);
                        foreach ($wikis as $wid => $wiki) {
                            $firstpage = wiki_get_first_page($wid);
                            $this->injectwikipage($activitydata, $firstpage, $activity->section);
                        }
                        break;
                }
            }
        }
        return $activitydata;
    }

    /**
     * Special function for book's subchapters.
     *
     * @param array $activities
     * @param mixed $chapter
     * @param int $section
     * @return void
     * @throws \dml_exception
     */
    private function injectbookchapter(array &$activities, mixed $chapter, int $section) {
        global $DB;
        $activity = new \stdClass();
        $activity->modname = 'book_chapters';
        $activity->section = $section;
        // Need to make sure the activity content is blank so that it is not replaced in the hacky get_file_url.
        $activity->content = '';
        // Book chapters have title and content.
        $titledata = $this->build_data(
                $chapter->id,
                $chapter->title,
                0,
                'title',
                $activity
        );
        $contentdata = $this->build_data(
                $chapter->id,
                $chapter->content,
                1,
                'content',
                $activity
        );
        array_push($activities, $titledata);
        array_push($activities, $contentdata);
    }

    /**
     * Special functions for wiki pages.
     *
     * @param array $activities
     * @param mixed $chapter
     * @param int $section
     * @return void
     * @throws \dml_exception
     */
    private function injectwikipage(array &$activities, mixed $chapter, int $section) {
        global $DB;
        $activity = new \stdClass();
        $activity->modname = 'wiki_pages';
        $activity->section = $section;
        // Need to make sure the activity content is blank so that it is not replaced in the hacky get_file_url.
        $activity->content = '';
        // Wiki pages have title and cachedcontent.
        $titledata = $this->build_data(
                $chapter->id,
                $chapter->title,
                0,
                'title',
                $activity
        );
        $contentdata = $this->build_data(
                $chapter->id,
                $chapter->cachedcontent,
                1,
                'cachedcontent',
                $activity
        );
        array_push($activities, $titledata);
        array_push($activities, $contentdata);
    }

    /**
     * Sub function that map the main activity generic types to our activitydata format.
     *
     * @param array $activities
     * @param mixed $activity
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    private function injectactivitydata(array &$activities, mixed $activity) {
        global $DB;
        $activitymodname = $activity->modname;
        $activitydbrecord = $DB->get_record($activitymodname, ['id' => $activity->instance]);
        // We build an array of all Text fields for this record.
        $columns = $DB->get_columns($activitymodname);

        // Just get db collumns we need (texts content).
        $textcols = array_filter($columns, function($field) use ($activitymodname) {
            return (($field->meta_type === "C" && $field->max_length > 254)
                            || $field->meta_type === "X")
                    && !in_array('*_' . $field->name, $this->comoncolstoskip)
                    && !in_array($activitymodname . '_' . $field->name, $this->usercolstoskip)
                    && !in_array($activitymodname . '_' . $field->name, $this->modcolstoskip);
        });
        $textcollumnskeys = array_keys($textcols);

        // Feed the data array with found text.
        foreach ($textcollumnskeys as $field) {
            if ($activitydbrecord->{$field} !== null && trim($activitydbrecord->{$field}) !== '') {
                $data = $this->build_data(
                        $activitydbrecord->id,
                        $activitydbrecord->{$field},
                        isset($activitydbrecord->{$field . 'format'}) ?? 0,
                        $field,
                        $activity
                );
                array_push($activities, $data);
            }
        }
        return $activitydbrecord;
    }

    /**
     * Build Data Item.
     *
     * @param int $id
     * @param string $text
     * @param int $format
     * @param string $field
     * @param mixed $activity
     * @return multilangfield
     * @throws \dml_exception
     */
    private function build_data(int $id, string $text, int $format, string $field, mixed $activity) {
        global $DB;
        $item = new textfield();
        // Activity stuff.
        $table = $activity->modname;
        $cmid = $activity->id;
        $sectionid = $activity->section;
        //$status = $this->store_status_db($id, $table, $field);
        // Build item id, tid, displaytext, format, table, field, tneeded, section.
        //$item = new \stdClass();
        // Object stuff.
        $item->set_id($id);
        $item->set_cmid($activity->id ?? 0); // Module ID, 0 if course.
        $item->set_text($text);

        $item->set_format($format);
        $item->set_table($table);
        $item->set_field($field);

        $item->set_sectionid($sectionid ?? 0); // Section ID, 0 if course.
        $multilangfield = new multilangfield($item);
        $this->add_langs($multilangfield->get_languages());
        // Get the activity icon, if it is a real activity/resource.
        /*if ($cmid !== null) {
            $item->purpose = call_user_func($table . '_supports', FEATURE_MOD_PURPOSE);
            $item->iconurl = $this->modinfo->get_cm($cmid)->get_icon_url()->out(false);
        }
        if ($table !== null) {
            // Try to find the activity names as well as the field translated in the current lang.
            $item->translatedtablename = get_string('pluginname', $table);
            if ($field !== null) {
                if ($table === 'course') {
                    $item->translatedfieldname = get_string($field . $table);
                } else if ($table === 'course_sections') {
                    if ($field === 'name') {
                        $item->translatedfieldname = get_string('sectionname');
                    } else if ($field === 'summary') {
                        $item->translatedfieldname = get_string('description');
                    }
                } else {
                    $item->translatedfieldname = get_string($table . $field, 'mod_' . $table);
                    $isnoptfound = strpos($item->translatedfieldname, '[[');
                    if ($isnoptfound === 0) {
                        $item->translatedfieldname = get_string($field, 'mod_' . $table);
                        $isnoptfound = strpos($item->translatedfieldname, '[[');
                    }
                    if ($isnoptfound === 0) {
                        $item->translatedfieldname = get_string($field);
                        $isnoptfound = strpos($item->translatedfieldname, '[[');
                    }
                    if ($isnoptfound === 0 && $field === 'intro') {
                        $item->translatedfieldname = get_string('description');
                        $isnoptfound = strpos($item->translatedfieldname, '[[');
                    }
                    if ($isnoptfound === 0 && $field === 'name') {
                        $item->translatedfieldname = get_string('name');
                    }
                }
            }
        }*/
        return $multilangfield;
    }

    /**
     * Collects the course mlang tags.
     *
     * @param array $tags
     * @return void
     */
    private function add_langs(array $tags) {
        foreach ($tags as $tag) {
            // Ensure the tag is a string and not already in the array before adding
            if (is_string($tag) && !in_array($tag, $this->mlangtags)) {
                $this->mlangtags[] = $tag;
            }
        }
    }

    /**
     * Getter for mlangtags.
     *
     * @return array|string[]
     */
    public function get_mlangtags(): array {
        return $this->mlangtags;
    }
}
