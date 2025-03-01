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

use cm_info;
use mod_quiz\quiz_settings;
use question_definition;
use stdClass;

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
    private string $targetlangdeepl;
    private string $targetlangmoodle;

    /**
     * Class Construct.
     *
     * @param \stdClass $course
     * @param string $lang
     * @param int $contextid
     * @throws \moodle_exception
     */
    public function __construct(stdClass $course, string $lang, int $contextid) {
        $this->mlangtags = [];
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
        $this->targetlangdeepl = $lang;
        $this->targetlangmoodle = strtolower(substr($lang, 0, 2));
        // Set the db fields to skipp.
        $this->comoncolstoskip = ['*_displayoptions', '*_stamp'];
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
        $activity = new stdClass();
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
        $activity = new stdClass();
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
        $cms = $this->modinfo->get_cms();
        foreach ($cms as $cmid => $activity) {
            // Build first level activities.
            $activitydbrecord = $this->injectactivitydata($activitydata, $activity, $cmid);
            // Build outstanding subcontent.
            switch ($activity->modname) {
                case 'book':
                    include_once($CFG->dirroot . '/mod/book/locallib.php');
                    $chapters = book_preload_chapters($activitydbrecord);
                    foreach ($chapters as $c) {
                        $this->injectbookchapter($activitydata, $c, $activity, $cmid);
                    }
                    break;
                case 'quiz':
                    // Get quiz questions.
                    $quizsettings = quiz_settings::create($activity->instance);
                    $structure = \mod_quiz\structure::create_for_quiz($quizsettings);
                    $slots = $structure->get_slots();
                    foreach ($slots as $slot) {
                        try {
                            $question = \question_bank::load_question($slot->questionid);
                            $this->injectquizcontent($activitydata, $question, $activity, $cmid);
                        } catch (\dml_read_exception $e) {
                            $this->build_data(-1, $e->getMessage(), 0, 'quiz_questions', $activity, 3);
                        } catch (\moodle_exception $me) {
                            $this->build_data(-1, $me->getMessage(), 0, 'quiz_questions', $activity, 3);
                        }
                    }
                    break;
                case 'wiki':
                    include_once($CFG->dirroot . '/mod/wiki/locallib.php');
                    $wikis = wiki_get_subwikis($activitydbrecord->id);

                    foreach ($wikis as $wid => $wiki) {
                        $pages = wiki_get_page_list($wid);
                        $pagesorphaned = array_map(function($op) {
                            return $op->id;
                        }, wiki_get_orphaned_pages($wid));
                        foreach ($pages as $p) {
                            if (!in_array($p->id, $pagesorphaned)) {
                                $this->injectwikipage($activitydata, $p, $activity, $cmid);
                            }
                        }

                    }
                    break;
                case 'moodleoverflow':
                    include_once($CFG->dirroot . '/mod/moodleoverflow/locallib.php');
                    $discussion = moodleoverflow_get_discussions($activity);
                    break;
                case 'hotquestion':
                    include_once($CFG->dirroot . '/mod/hotquestion/locallib.php');
                    $hq = new \mod_hotquestion($activity->id);
                    $questions = $hq->get_questions();
                    break;

            }
        }
        return $activitydata;
    }

    /**
     * Special function for book's subchapters.
     *
     * @param array $activities
     * @param mixed $chapter
     * @param \cm_info $act
     * @return void
     * @throws \dml_exception
     */
    private function injectbookchapter(array &$activities, mixed $chapter, cm_info $act, $cmid) {
        global $DB;
        $activity = new stdClass();
        $activity->id = $act->id;
        $activity->modname = 'book_chapters';
        $activity->section = $act->get_section_info()->id;
        // Need to make sure the activity content is blank so that it is not replaced in the hacky get_file_url.
        $activity->content = '';
        // Book chapters have title and content.
        $titledata = $this->build_data(
                $chapter->id,
                $chapter->title,
                0,
                'title',
                $activity,
                2,
                $cmid
        );
        $contentdata = $this->build_data(
                $chapter->id,
                $chapter->content,
                1,
                'content',
                $activity,
                2,
                $cmid
        );
        array_push($activities, $titledata);
        array_push($activities, $contentdata);
    }

    /**
     * Special function for wiki's subpages.
     *
     * @param array $activities
     * @param mixed $chapter
     * @param \cm_info $act
     * @return void
     * @throws \dml_exception
     * @todo MDL-0 check differences between collaborative and individual
     */
    private function injectwikipage(array &$activities, mixed $chapter, cm_info $act, $cmid) {
        global $DB;
        $activity = new stdClass();
        $activity->id = $act->id;
        $activity->modname = 'wiki_pages';
        $activity->section = $act->sectionid;
        // Need to make sure the activity content is blank so that it is not replaced in the hacky get_file_url.
        $activity->content = '';
        // Wiki pages have title and cachedcontent.
        $titledata = $this->build_data(
                $chapter->id,
                $chapter->title,
                0,
                'title',
                $activity,
                2,
                $cmid
        );
        $contentdata = $this->build_data(
                $chapter->id,
                $chapter->cachedcontent,
                1,
                'cachedcontent',
                $activity,
                2,
                $cmid
        );
        array_push($activities, $titledata);
        array_push($activities, $contentdata);
    }

    /**
     * Helper to list only interesting table fields.
     *
     * @param string $tablename
     * @return int[]|string[]
     */
    private function filterdbtextfields($tablename) {
        global $DB;
        // We build an array of all Text fields for this record.
        $columns = $DB->get_columns($tablename);

        // Just get db collumns we need (texts content).
        $textcols = array_filter($columns, function($field) use ($tablename) {
            // Only scan the main text types that are above minîmum text field size.
            return (($field->meta_type === "C" && $field->max_length > get_config('scannedfieldsize','local_deepler'))
                            || $field->meta_type === "X")
                    && !in_array('*_' . $field->name, $this->comoncolstoskip)
                    && !in_array($tablename . '_' . $field->name, $this->usercolstoskip)
                    && !in_array($tablename . '_' . $field->name, $this->modcolstoskip);
        });
        return array_keys($textcols);
    }

    /**
     * Sub function that map the main activity generic types to our activitydata format.
     *
     * @param array $activities
     * @param mixed $activity
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    private function injectactivitydata(array &$activities, mixed $activity, $cmid) {
        global $DB;
        $activitymodname = $activity->modname;
        $activitydbrecord = $DB->get_record($activitymodname, ['id' => $activity->instance]);
        $textcollumnskeys = $this->filterdbtextfields($activitymodname);

        // Feed the data array with found text.
        foreach ($textcollumnskeys as $field) {
            if ($activitydbrecord->{$field} !== null && trim($activitydbrecord->{$field}) !== '') {
                $data = $this->build_data(
                        $activitydbrecord->id,
                        $activitydbrecord->{$field},
                        isset($activitydbrecord->{$field . 'format'}) ?? 0,
                        $field,
                        $activity,
                        1,
                        $cmid
                );
                array_push($activities, $data);
            }
        }
        return $activitydbrecord;
    }

    /**
     * Build Data Item to be sent to renderer.
     *
     * @param int $id
     * @param string $text
     * @param int $format
     * @param string $field
     * @param mixed $activity
     * @param int $level
     * @param int $cmid
     * @return \stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function build_data(
            int $id,
            string $text,
            int $format,
            string $field,
            mixed $activity,
            int $level = 0,
            int $cmid = 0) {
        $item = new textfield();
        // Activity stuff.
        $table = $activity->modname;
        $sectionid = $activity->section;
        // Store the status of the translation.
        $item->set_id($id);
        $item->set_cmid($cmid); // Module ID, 0 if course.
        $item->set_text($text);

        $item->set_format($format);
        $item->set_table($table);
        $item->set_field($field);

        $item->set_sectionid($sectionid ?? 0); // Section ID, 0 if course.
        $multilangfield = new multilangfield($item);
        $this->add_langs($multilangfield->get_languages());
        // Get the activity icon, if it is a real activity/resource.

        return $multilangfield;
    }



    /**
     * Get the correct context.
     *
     * @param int $id
     * @param string $table
     * @param int $cmid
     * @return array
     */
    private function get_item_contextid($id, $table, $cmid = 0) {
        $i = 0;
        $iscomp = false;

        switch ($table) {
            case 'course':
                $i = \context_course::instance($id)->id;
                break;
            case 'course_sections':
                $i = \context_module::instance($id)->id;
                break;
            default :
                $i = \context_module::instance($cmid)->id;
                $iscomp = true;
                break;
        }
        return ['contextid' => $i, 'component' => $iscomp ? 'mod_' . $table : $table, 'itemid' => $iscomp ? $cmid : ''];
    }

    /**
     * Retrieve the urls of files.
     *
     * @param string $text
     * @param int $itemid
     * @param string $table
     * @param string $field
     * @param int $cmid
     * @return array|string|string[]
     * @throws \dml_exception
     */
    private function get_file_url(string $text, int $itemid, string $table, string $field, int $cmid) {
        global $DB;
        $tmp = $this->get_item_contextid($itemid, $table, $cmid);
        switch ($table) {
            case 'course_sections' :
                $select =
                        'component = "course" AND itemid =' . $itemid . ' AND filename != "." AND filearea = "section"';
                $params = [];
                break;
            default :
                $select =
                        'contextid = :contextid AND component = :component AND filename != "." AND ' .
                        $DB->sql_like('filearea', ':field');
                $params = ['contextid' => $tmp['contextid'], 'component' => $tmp['component'],
                        'field' => '%' . $DB->sql_like_escape($field) . '%'];
                break;
        }

        $result = $DB->get_recordset_select('files', $select, $params);
        if ($result->valid()) {
            $itemid = ($field == 'intro' || $field == 'summary') && $table != 'course_sections' ? '' : $result->current()->itemid;
            return file_rewrite_pluginfile_urls($text, 'pluginfile.php', $result->current()->contextid,
                    $result->current()->component, $result->current()->filearea, $itemid);
        } else {
            return file_rewrite_pluginfile_urls($text, 'pluginfile.php', $tmp['contextid'], $tmp['component'], $field,
                    $tmp['itemid']);
        }
    }

    /**
     * Special wrapper for questions to build data.
     *
     * @param array $activities
     * @param mixed $question
     * @param mixed $activity
     * @return false|mixed|\stdClass
     */
    private function injectquestiondata(array &$activities, mixed $question, mixed $activity, $cmid) {
        global $DB;
        return $this->injectdatafromtable($activities, 'question', 'id', $question->id, $activity, $cmid);
    }

    /**
     * Special wrapper for questions subs to build data.
     *
     * @param array $activities
     * @param string $table
     * @param string $idcolname
     * @param int $id
     * @param mixed $activity
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    private function injectdatafromtable(array &$activities, string $table, string $idcolname, int $id, mixed $activity,
            int $cmid) {
        global $DB;

        $activitydbrecord = $DB->get_record($table, [$idcolname => $id]);
        $textcollumnskeys = $this->filterdbtextfields($table);

        // Feed the data array with found text.
        foreach ($textcollumnskeys as $field) {
            if ($activitydbrecord->{$field} !== null && trim($activitydbrecord->{$field}) !== '') {
                $data = $this->build_data(
                        $activitydbrecord->id,
                        $activitydbrecord->{$field},
                        isset($activitydbrecord->{$field . 'format'}) ?? 0,
                        $field,
                        $activity,
                        3,
                        $cmid
                );
                array_push($activities, $data);
            }
        }
        return $activitydbrecord;
    }

    /**
     * Question and Answers factory.
     *
     * @param array $activitydata
     * @param \question_definition $question
     * @param mixed $act
     * @return void
     * @throws \dml_exception
     */
    private function injectquizcontent(array &$activitydata, question_definition $question, mixed $act, int $cmid) {
        global $DB;
        $dbman = $DB->get_manager(); // Get the database manager.
        $activity = new stdClass();
        $activity->id = $act->id;
        $activity->modname = 'question';
        $activity->section = $act->sectionid;
        $pluginname = $question->qtype->plugin_name();
        $activity->qtype = $pluginname;
        $this->injectquestiondata($activitydata, $question, $activity, $cmid);
        $qactivity = new stdClass();
        $qactivity->id = $act->id;
        $qactivity->modname = 'question_answers';
        $qactivity->section = $act->sectionid;
        $qactivity->parent = $question->id;
        $qidfiledname = $question->qtype->questionid_column_name();

        $optionstablename = '';

        $hasoptions = false;
        if ($dbman->table_exists($pluginname . '_options')) {
            $optionstablename = $pluginname . '_options';
            $hasoptions = true;
        }
        if ($dbman->table_exists($pluginname . '_choice')) {
            $optionstablename = $pluginname . '_choice';
            $hasoptions = true;
        }
        switch ($pluginname) {
            case 'qtype_match':
                if ($dbman->table_exists($pluginname . '_subquestions')) {
                    $substablename = $pluginname . '_subquestions';
                    $qactivity->modname = $substablename;
                    if ($DB->record_exists($substablename, [$qidfiledname => $question->id])) {
                        $submatches = $DB->get_records($substablename, [$qidfiledname => $question->id]);
                        foreach ($submatches as $submatch) {
                            $this->injectdatafromtable(
                                    $activitydata,
                                    $substablename,
                                    'id',
                                    $submatch->id,
                                    $qactivity,
                                    $cmid);
                        }

                    }
                }
                break;
            case 'qtype_multichoice':
                foreach ($question->answers as $answer) {
                    $activitydata[] = $this->build_data(
                            $answer->id,
                            $answer->answer,
                            $answer->answerformat,
                            'answer',
                            $qactivity,
                            3,
                            $cmid
                    );
                    if (!empty($answer->feedback)) {
                        $activitydata[] = $this->build_data(
                                $answer->id,
                                $answer->feedback,
                                $answer->feedbackformat,
                                'feedback',
                                $qactivity,
                                3,
                                $cmid
                        );
                    }
                }
                break;
            case 'qtype_description':
            case 'qtype_randomsamatch':
            case 'qtype_essay':
            case 'qtype_multianswer':
                // Break as all the data is in the question table.
                break;
            case 'qtype_calculated':
            case 'qtype_calculatedsimple':
                foreach ($question->answers as $answer) {

                    if (!empty($answer->feedback)) {
                        $activitydata[] = $this->build_data(
                                $answer->id,
                                $answer->feedback,
                                $answer->feedbackformat,
                                'feedback',
                                $qactivity,
                                3,
                                $cmid
                        );
                    }
                }
                break;
            case 'qtype_calculatedmulti':
                foreach ($question->answers as $answer) {
                    $activitydata[] = $this->build_data(
                            $answer->id,
                            $answer->answer,
                            $answer->answerformat,
                            'answer',
                            $qactivity,
                            3,
                            $cmid
                    );
                    if (!empty($answer->feedback)) {
                        $activitydata[] = $this->build_data(
                                $answer->id,
                                $answer->feedback,
                                $answer->feedbackformat,
                                'feedback',
                                $qactivity,
                                3,
                                $cmid
                        );
                    }
                }
                break;
            case 'qtype_truefalse':
                if (!empty($question->truefeedback)) {
                    $activitydata[] = $this->build_data(
                            $question->trueanswerid,
                            $question->truefeedback,
                            $question->truefeedbackformat,
                            'feedback',
                            $qactivity,
                            3,
                            $cmid
                    );
                }
                if (!empty($question->falsefeedback)) {
                    $activitydata[] = $this->build_data(
                            $question->falseanswerid,
                            $question->falsefeedback,
                            $question->falsefeedbackformat,
                            'feedback',
                            $qactivity,
                            3,
                            $cmid
                    );
                }
                break;
            case 'qtype_shortanswer':
                foreach ($question->answers as $answer) {
                    $activitydata[] = $this->build_data(
                            $answer->id,
                            $answer->answer,
                            $answer->answerformat,
                            'answer',
                            $qactivity,
                            3,
                            $cmid
                    );
                    if (!empty($answer->feedback)) {
                        $activitydata[] = $this->build_data(
                                $answer->id,
                                $answer->feedback,
                                $answer->feedbackformat,
                                'feedback',
                                $qactivity,
                                3,
                                $cmid
                        );
                    }
                }
            case 'qtype_numerical':
                foreach ($question->answers as $answer) {
                    if (!empty($answer->feedback)) {
                        $activitydata[] = $this->build_data(
                                $answer->id,
                                $answer->feedback,
                                $answer->feedbackformat,
                                'feedback',
                                $qactivity,
                                3,
                                $cmid
                        );
                    }
                }
                break;
            case 'qtype_gapselect':
            case 'qtype_ddwtos' :
                $choices = $DB->get_records('question_answers', ['question' => $question->id]);
                foreach ($choices as $answer) {
                    $activitydata[] = $this->build_data(
                            $answer->id,
                            $answer->answer,
                            0,
                            'answer',
                            $qactivity,
                            3,
                            $cmid
                    );
                }
                break;
            case 'qtype_ddimageortext' :
            case 'qtype_ddmarker' :
                $qactivity->modname = "{$pluginname}_drags";
                $choices = $DB->get_records($qactivity->modname, ['questionid' => $question->id]);
                foreach ($choices as $answer) {
                    if (trim($answer->label) === '') {
                        continue;
                    }
                    $activitydata[] = $this->build_data(
                            $answer->id,
                            $answer->label,
                            0,
                            'label',
                            $qactivity,
                            3,
                            $cmid
                    );
                }
                break;
            case 'qtype_ddimageortext' :
                break;
            case 'qtype_ordering' :
                foreach ($question->answers as $answer) {
                    $activitydata[] = $this->build_data(
                            $answer->id,
                            $answer->answer,
                            $answer->answerformat,
                            'answer',
                            $qactivity,
                            3,
                            $cmid
                    );
                }
                break;
            default:
                // Log or handle unknown question types.
                debugging('Unhandled question type: ' . $question->qtype->name(), DEBUG_DEVELOPER);
                break;
        }
        if ($hasoptions && $DB->record_exists($optionstablename, [$qidfiledname => $question->id])) {
            $qactivity->modname = $optionstablename;
            $this->injectdatafromtable($activitydata, $optionstablename, $qidfiledname, $question->id, $qactivity, $cmid);
        }
        if (count($question->hints)) {
            foreach ($question->hints as $hint) {
                $qactivity->modname = 'question_hints';
                $this->injectdatafromtable($activitydata, 'question_hints', 'id', $hint->id, $qactivity,
                        $cmid);
            }
        }
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
