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
 * Defines the editing form for the sassessment question type.
 *
 * @package    qtype
 * @subpackage sassessment
 * @copyright  2018 Kochi-Tech.ac.jp

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * sassessment question editing form definition.
 *
 * @copyright  THEYEAR YOURNAME (YOURCONTACTINFO)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_sassessment_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('sassessment');

        $config = get_config('qtype_sassessment');

        $mform->addElement('checkbox', 'show_transcript', get_string('show_transcript', 'qtype_sassessment'));
        $mform->addElement('checkbox', 'save_stud_audio', get_string('save_stud_audio', 'qtype_sassessment'));
        $mform->addElement('checkbox', 'show_analysis', get_string('show_analysis', 'qtype_sassessment'));


        $speechtotextlang = array( "en-US" => "US English (en-US)", "en-AU" => "Australian English (en-AU)", "en-GB" => "British English (en-GB)",
            "fr-CA" => "Canadian French (fr-CA)", "fr-FR" => "French (fr-FR)",
            "es-US" => "US Spanish (es-US)");
        $mform->addElement('select', 'speechtotextlang', get_string('speechtotextlang', 'qtype_sassessment'), $speechtotextlang);
        $mform->setDefault('speechtotextlang', $config->speechtotextlang);


        //
        $mform->addElement('select', 'fb_tyfb_typepe',
                 get_string('fb_type', 'qtype_sassessment'), $qtype->feedback_types());

        $mform->addElement('hidden', 'fb_type', 0);

        $this->add_per_answer_fields($mform, '{no}', question_bank::fraction_options_full(), 1, 3);

        $this->add_combined_feedback_fields(false);

        /*
         * Intermediate feedback
         */
        $mform->addElement('textarea', 'immediatefeedback', get_string('immediatefeedback', 'qtype_sassessment'),
            array('rows' => 3, 'cols' => 65), $this->editoroptions);  // or editor
        $mform->setType('immediatefeedback', PARAM_RAW);
        // Using setValue() as setDefault() does not work for the editor class.
        //$element->setValue(array('text' => ''));

        $immediatefeedbackpercent = array_replace(array(0 => "No"), array_combine(range(70, 100, 5), range(70, 100, 5)) );
        $mform->addElement('select', 'immediatefeedbackpercent', '', $immediatefeedbackpercent);

        // $mform->disabledIf('shownumcorrect', 'single', 'eq', 1);

        //$this->add_interactive_settings(true, true);
    }

    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] = $mform->createElement('textarea', 'answer', get_string('comment', 'qtype_sassessment') . ' ' . $label, array('rows' => 3, 'cols' => 65), $this->editoroptions);
        //$repeated[] = $mform->createElement('textarea', 'feedback', get_string('feedback'), array('rows' => 1, 'cols' => 65), $this->editoroptions);
        // $repeatedoptions['answer']['type'] = PARAM_RAW;
        $answersoption = 'answers';
        return $repeated;
    }

    protected function data_preprocessing($question) {

        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        $question->show_transcript = $question->options->show_transcript;
        $question->save_stud_audio = $question->options->save_stud_audio;
        $question->show_analysis = $question->options->show_analysis;
        $question->speechtotextlang = $question->options->speechtotextlang;
        $question->immediatefeedback = $question->options->immediatefeedback;
        $question->immediatefeedbackpercent = $question->options->immediatefeedbackpercent;


        $question->fb_type = $question->options->fb_type;

        $this->data_preprocessing_answers($question, true);

        $this->data_preprocessing_combined_feedback($question, true);

        return $question;
    }

    protected function data_preprocessing_answers($question, $withanswerfiles = false) {
        if (empty($question->options->answers)) {
            return $question;
        }

        $key = 0;
        foreach ($question->options->answers as $answer) {
          $question->answer[$key] = $answer->answer;

            // $draftitemid = file_get_submitted_draft_itemid('answer['.$key.']');
            // $question->answer[$key]['text'] = file_prepare_draft_area(
            //   $draftitemid,
            //   $this->context->id,
            //   'question',
            //   'answer',
            //   !empty($answer->id) ? (int) $answer->id : null,
            //   $this->fileoptions,
            //   $answer->answer
            // );
            // $question->answer[$key]['itemid'] = $draftitemid;
            // $question->answer[$key]['format'] = $answer->answerformat;

            unset($this->_form->_defaultValues["fraction[{$key}]"]);
            $key++;
        }

    }

    public function qtype() {
        return 'sassessment';
    }
}
