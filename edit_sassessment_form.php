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

    public function qtype() {
        return 'sassessment';
    }

    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('sassessment');

        $config = get_config('qtype_sassessment');

        $mform->addElement('checkbox', 'show_transcript', get_string('show_transcript', 'qtype_sassessment'));
        $mform->addElement('checkbox', 'save_stud_audio', get_string('save_stud_audio', 'qtype_sassessment'));
        $mform->addElement('checkbox', 'show_analysis', get_string('show_analysis', 'qtype_sassessment'));

        $speechtotextlang = array("en-US" => "US English (en-US)", "en-AU" => "Australian English (en-AU)",
                "en-GB" => "British English (en-GB)",
                "fr-CA" => "Canadian French (fr-CA)", "fr-FR" => "French (fr-FR)",
                "es-US" => "US Spanish (es-US)");
        $mform->addElement('select', 'speechtotextlang', get_string('speechtotextlang', 'qtype_sassessment'), $speechtotextlang);
        $mform->setDefault('speechtotextlang', $config->speechtotextlang);

        $stt_core = array("amazon" => get_string('stt_core_amazon', 'qtype_sassessment'),
                "google" => get_string('stt_core_google', 'qtype_sassessment'));

        $mform->addElement('select', 'stt_core',
                get_string('stt_core', 'qtype_sassessment'), $stt_core);

        //$mform->setDefault('stt_core', $config->stt_core);

        //
        $auto_score_response = array(
                "target_teacher" => get_string('target_responses_teacher', 'qtype_sassessment'),
                "target_student" => get_string('target_responses_student', 'qtype_sassessment'),
                "open_response" => get_string('open_response', 'qtype_sassessment')
        );

        $mform->addElement('select', 'auto_score',
                get_string('auto_score_response', 'qtype_sassessment'), $auto_score_response);


        /*
         * (Teacher can add 1 or more ‘if’ conditions.)
         * If X (words) are spoken, then assign X points.
         * If X (sentences) are spoken, then assign X points.
         * If X (words per sentence) are spoken, then assign X points.
         * If X (long words with 6 or more letters) are spoken then assign X points.
         * If X (unique words) are spoken then assign X points.
         */



        $pointsHeader = array();
        $pointsHeader[] = $mform->createElement('html', get_string("pointsSectionHeader", "qtype_sassessment"));
        $mform->addGroup($pointsHeader, '', '');

        $typesOfIf = [];

        for ($i = 1; $i <=5; $i++){
            $typesOfIf[$i] = get_string('typeofif_' . $i, 'qtype_sassessment');
        }

        foreach ($typesOfIf as $key => $type_name){
            $spokenpoints = array();
            $spokenpoints[] = $mform->createElement('checkbox', 'spokenpoints' . $key . '_status', get_string('enable') . '. ' . get_string('if', 'qtype_sassessment'));
            $spokenpoints[] = $mform->createElement('text', 'spokenpoints' . $key . '_words', '', array('size'=>'4'));
            $spokenpoints[] = $mform->createElement('html', '('.$type_name.') ' . get_string('are_spoken', 'qtype_sassessment'));
            $spokenpoints[] = $mform->createElement('text', 'spokenpoints' . $key . '_points', '', array('size'=>'2'));
            $spokenpoints[] = $mform->createElement('html', get_string('points', 'qtype_sassessment'));
            $mform->addGroup($spokenpoints, '', ''); // without group name
            $mform->setType('spokenpoints' . $key . '_words', PARAM_INT);
            $mform->setType('spokenpoints' . $key . '_points', PARAM_INT);
            $mform->disabledIf('spokenpoints' . $key . '_words', 'spokenpoints' . $key . '_status');
            $mform->disabledIf('spokenpoints' . $key . '_points', 'spokenpoints' . $key . '_status');

            $mform->disabledIf('spokenpoints' . $key . '_status', 'auto_score', 'eq', 'target_teacher');
            $mform->disabledIf('spokenpoints' . $key . '_status', 'auto_score', 'eq', 'target_student');
        }


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

        //$mform->addElement('filepicker', 'attachments', get_string('file'), null,
        //    array('accepted_types' => '*'));

        $immediatefeedbackpercent = array_replace(array(0 => "No"), array_combine(range(70, 100, 5), range(70, 100, 5)));
        $mform->addElement('select', 'immediatefeedbackpercent', '', $immediatefeedbackpercent);


        for($i=0;$i<10;$i++){
            $mform->disabledIf('answer['.$i.']', 'auto_score', 'eq', 'open_response');
            $mform->disabledIf('answer['.$i.']', 'auto_score', 'eq', 'target_student');
        }

        $mform->disabledIf('addanswers', 'auto_score', 'eq', 'open_response');
        $mform->disabledIf('addanswers', 'auto_score', 'eq', 'target_student');

        //$this->add_interactive_settings(true, true);
    }

    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] = $mform->createElement('textarea', 'answer', get_string('comment', 'qtype_sassessment') . ' ' . $label,
                array('rows' => 3, 'cols' => 65), $this->editoroptions);
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
        $question->stt_core = $question->options->stt_core;
        $question->auto_score = $question->options->auto_score;
        $question->immediatefeedback = $question->options->immediatefeedback;
        $question->immediatefeedbackpercent = $question->options->immediatefeedbackpercent;

        $question->fb_type = $question->options->fb_type;

        for($i=1; $i <= 5; $i++) {
            $question->{"spokenpoints".$i."_status"} = empty($question->options->{"spokenpoints" . $i . "_status"}) ? 0 : 1;
            $question->{"spokenpoints".$i."_words"} = empty($question->options->{"spokenpoints" . $i . "_words"}) ? 0 : $question->options->{"spokenpoints" . $i . "_words"};
            $question->{"spokenpoints".$i."_points"} = empty($question->options->{"spokenpoints" . $i . "_points"}) ? 0 : $question->options->{"spokenpoints" . $i . "_points"};
        }

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
}
