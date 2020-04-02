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
 * Question type class for the sassessment question type.
 *
 * @package    qtype
 * @subpackage sassessment
 * @copyright  2018 Kochi-Tech.ac.jp

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/sassessment/question.php');


/**
 * The sassessment question type.
 *
 * @copyright  THEYEAR YOURNAME (YOURCONTACTINFO)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_sassessment extends question_type {

    /**
     * data used by export_to_xml (among other things possibly
     * @return array
     */
    public function extra_question_fields() {
        return array('qtype_sassessment_options', 'show_transcript', 'save_stud_audio', 'show_analysis', 'speechtotextlang',
            'immediatefeedback', 'immediatefeedbackpercent');
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    public function get_question_options($question) {
        global $DB;
        $question->options = $DB->get_record('qtype_sassessment_options',
                array('questionid' => $question->id), '*', MUST_EXIST);
        parent::get_question_options($question);
    }

    public function save_question_options($formdata) {
        global $DB;

        $context = $formdata->context;
        $result = new stdClass();

        $this->save_question_answers($formdata);

        {
          $options = $DB->get_record('qtype_sassessment_options', array('questionid' => $formdata->id));
          if (!$options) {
              $options = new stdClass();
              $options->questionid = $formdata->id;
              $options->correctfeedback = '';
              $options->partiallycorrectfeedback = '';
              $options->incorrectfeedback = '';
              $options->immediatefeedback = '';
              $options->immediatefeedbackpercent = '';
              $options->id = $DB->insert_record('qtype_sassessment_options', $options);
          }

          $options->show_transcript = (int)$formdata->show_transcript;
          $options->save_stud_audio = (int)$formdata->save_stud_audio;
          $options->show_analysis = (int)$formdata->show_analysis;
          $options->speechtotextlang = $formdata->speechtotextlang;
          $options->immediatefeedback = $formdata->immediatefeedback;
          $options->immediatefeedbackpercent = (int)$formdata->immediatefeedbackpercent;

          $options->fb_type = $formdata->fb_type;

          $options = $this->save_combined_feedback_helper($options, $formdata, $context, true);

          $DB->update_record('qtype_sassessment_options', $options);

        }
    }


    protected function is_answer_empty($questiondata, $key) {
       return html_is_blank($questiondata->answer[$key]['text']) || trim($questiondata->answer[$key]) == '';
    }

    protected function fill_answer_fields($answer, $questiondata, $key, $context) {
        // $answer->answer = $this->import_or_save_files($questiondata->answer[$key],
        //         $context, 'question', 'answer', $answer->id);
        // $answer->answerformat = $questiondata->answer[$key]['format'];
        $answer->answer = trim($questiondata->answer[$key]);
        return $answer;
    }

    public function delete_question($questionid, $contextid) {
        global $DB;

        $DB->delete_records('qtype_sassessment_options', array('questionid' => $questionid));
        parent::delete_question($questionid, $contextid);
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $this->initialise_combined_feedback($question, $questiondata, true);
        $question->questions = $questiondata->options->answers;
    }

    public function get_random_guess_score($questiondata) {
        // TODO.
        return 0;
    }

    public function get_possible_responses($questiondata) {
        // TODO.
        return array();
    }

    public function feedback_types() {
        return array(
            'percent' => get_string('percent_score', 'qtype_sassessment'),
            'points' => get_string('points_score', 'qtype_sassessment'),
        );
    }


    /**
     * Create a question from reading in a file in Moodle xml format
     *
     * @param array $data
     * @param stdClass $question (might be an array)
     * @param qformat_xml $format
     * @param stdClass $extra
     * @return boolean
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra = null) {
        if (!isset($data['@']['type']) || $data['@']['type'] != 'sassessment') {
            return false;
        }
        $question = parent::import_from_xml($data, $question, $format, null);
        $format->import_combined_feedback($question, $data, true);
        $format->import_hints($question, $data, true, false, $format->get_format($question->questiontextformat));
        $question->isimport = true;
        $question->itemsettings = [];
        if (isset($data['#']['sassessmentsetting'])) {
            foreach ($data['#']['sassessmentsetting'] as $key => $setxml) {
                $question->itemsettings[$key]['show_transcript'] = $format->getpath($setxml, array('#', 'show_transcript', 0, '#'), 0);
                $question->itemsettings[$key]['save_stud_audio'] = $format->getpath($setxml, array('#', 'save_stud_audio', 0, '#'), 0);
                $question->itemsettings[$key]['show_analysis'] = $format->getpath($setxml, array('#', 'show_analysis', 0, '#'), 0);
                $question->itemsettings[$key]['correctfeedback'] = $format->getpath($setxml, array('#', 'correctfeedback', 0, '#'), 0);
                $question->itemsettings[$key]['correctfeedbackformat'] = $format->getpath($setxml, array('#', 'correctfeedbackformat', 0, '#'), 0);
                $question->itemsettings[$key]['partiallycorrectfeedback'] = $format->getpath($setxml, array('#', 'partiallycorrectfeedback', 0, '#'), 0);
                $question->itemsettings[$key]['partiallycorrectfeedbackformat'] = $format->getpath($setxml, array('#', 'partiallycorrectfeedbackformat', 0, '#'), 0);
                $question->itemsettings[$key]['incorrectfeedback'] = $format->getpath($setxml, array('#', 'incorrectfeedback', 0, '#'), 0);
                $question->itemsettings[$key]['incorrectfeedbackformat'] = $format->getpath($setxml, array('#', 'incorrectfeedbackformat', 0, '#'), 0);
                $question->itemsettings[$key]['immediatefeedback'] = $format->getpath($setxml, array('#', 'immediatefeedback', 0, '#'), 0);
                $question->itemsettings[$key]['immediatefeedbackpercent'] = $format->getpath($setxml, array('#', 'immediatefeedbackpercent', 0, '#'), 0);
                $question->itemsettings[$key]['speechtotextlang'] = $format->getpath($setxml, array('#', 'speechtotextlang', 0, '#'), 0);
                $question->itemsettings[$key]['fb_type'] = $format->getpath($setxml, array('#', 'fb_type', 0, '#'), 0);
            }
        }
        return $question;

    }

    /**
     * Export question to the Moodle XML format
     *
     * @param object $question
     * @param qformat_xml $format
     * @param object $extra
     * @return string
     */
    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        global $CFG;
        $pluginmanager = core_plugin_manager::instance();
        $question->options->itemsettings = json_decode($question->options->itemsettings);

        $output = parent::export_to_xml($question, $format);
        foreach ($question->options->itemsettings as $set) {
            $output .= "      <sassessmentsetting>\n";
            $output .= '        <show_transcript>' . $set->show_transcript . "</show_transcript>\n";
            $output .= '        <save_stud_audio>' . $set->save_stud_audio . "</save_stud_audio>\n";
            $output .= '        <show_analysis>' . $set->show_analysis . "</show_analysis>\n";
            $output .= '        <correctfeedback>' . $set->correctfeedback . "</correctfeedback>\n";
            $output .= '        <correctfeedbackformat>' . $set->correctfeedbackformat . "</correctfeedbackformat>\n";
            $output .= '        <partiallycorrectfeedback>' . $set->partiallycorrectfeedback . "</partiallycorrectfeedback>\n";
            $output .= '        <partiallycorrectfeedbackformat>' . $set->partiallycorrectfeedbackformat . "</partiallycorrectfeedbackformat>\n";
            $output .= '        <incorrectfeedback>' . $set->incorrectfeedback . "</incorrectfeedback>\n";
            $output .= '        <incorrectfeedbackformat>' . $set->incorrectfeedbackformat . "</incorrectfeedbackformat>\n";
            $output .= '        <immediatefeedback>' . $set->immediatefeedback . "</immediatefeedback>\n";
            $output .= '        <immediatefeedbackpercent>' . $set->immediatefeedbackpercent . "</immediatefeedbackpercent>\n";
            $output .= '        <speechtotextlang>' . $set->speechtotextlang . "</speechtotextlang>\n";
            $output .= '        <fb_type>' . $set->fb_type . "</fb_type>\n";
            $output .= "     </sassessmentsetting>\n";
        }
        $output .= $format->write_combined_feedback($question->options, $question->id, $question->contextid);
        return $output;
    }

}
