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
 * sassessment question definition class.
 *
 * @package    qtype
 * @subpackage sassessment
 * @copyright  2018 Kochi-Tech.ac.jp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');

/**
 * Represents a sassessment question.
 *
 * @copyright  2018 Kochi-Tech.ac.jp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_sassessment_question extends question_graded_automatically {
    public $questions = array();
    public $attachments;
    public $filetypeslist = 'html_audio';

    public $correctfeedback;
    public $correctfeedbackformat;
    public $partiallycorrectfeedback;
    public $partiallycorrectfeedbackformat;
    public $incorrectfeedback;
    public $incorrectfeedbackformat;
    public $immediatefeedback;
    public $stt_core;
    public $auto_score;
    public $spokenpoints1_status;
    public $spokenpoints2_status;
    public $spokenpoints3_status;
    public $spokenpoints4_status;
    public $spokenpoints5_status;
    public $spokenpoints1_words;
    public $spokenpoints2_words;
    public $spokenpoints3_words;
    public $spokenpoints4_words;
    public $spokenpoints5_words;
    public $spokenpoints1_points;
    public $spokenpoints2_points;
    public $spokenpoints3_points;
    public $spokenpoints4_points;
    public $spokenpoints5_points;
    public $immediatefeedbackpercent;

    public function get_expected_data() {
        return array(
                'answer' => PARAM_RAW_TRIMMED,
                'grade' => PARAM_RAW_TRIMMED,
                'targetAnswer' => PARAM_RAW_TRIMMED,
                'attachments' => question_attempt::PARAM_FILES
        );
    }

    public function summarise_response(array $response) {
        return null;
    }

    public function is_complete_response(array $response) {
        return true;
    }

    public function get_validation_error(array $response) {
        return '';
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

    public function get_correct_response() {
        return array();
    }

    /*
     * No need to grade questions without answers. Questions without answers be manual graded items.
     */
    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        $question = $qa->get_question();
        if (empty(count($question->questions))) {
            return question_engine::make_behaviour('manualgraded', $qa, $preferredbehaviour);
        } else {
            return question_engine::make_archetypal_behaviour($preferredbehaviour, $qa);
        }
    }

    public function check_file_access($qa, $options, $component, $filearea,
            $args, $forcedownload) {
        if ($component == 'question' && in_array($filearea,
                        array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback',
                                'immediatefeedback', 'immediatefeedbackpercent', 'stt_core', 'auto_score',
                                'spokenpoints1_status', 'spokenpoints1_words', 'spokenpoints1_points',
                                'spokenpoints2_status', 'spokenpoints2_words', 'spokenpoints2_points',
                                'spokenpoints3_status', 'spokenpoints3_words', 'spokenpoints3_points',
                                'spokenpoints4_status', 'spokenpoints4_words', 'spokenpoints4_points',
                                'spokenpoints5_status', 'spokenpoints5_words', 'spokenpoints5_points'
                        ))) {
            return $this->check_combined_feedback_file_access($qa, $options, $filearea, $args);
        } else if ($component == 'question' && $filearea == 'response_attachments') {
            return true;
        } else if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);
        } else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }

    public function grade_response(array $response) {
        $fraction = 0.01 * (float) $response['grade'];
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    public function compute_final_grade($responses, $totaltries) {
        return 0;
    }
}
