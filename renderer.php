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
 * sassessment question renderer class.
 *
 * @package    qtype
 * @subpackage sassessment
 * @copyright  2018 Kochi-Tech.ac.jp

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/lib.php');


/**
 * Generates the output for sassessment questions.
 *
 * @copyright  2018 Kochi-Tech.ac.jp

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_sassessment_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
                                             question_display_options $options) {
        global $USER, $CFG, $PAGE, $OUTPUT, $DB;

        $PAGE->requires->jquery_plugin('jquery');

        $question = $qa->get_question();

        $questiontext = $question->format_questiontext($qa);

        /*
         * Subtitles parser
         */
        if (strstr($questiontext, ".vtt")){
            if (preg_match('/src="(.*?).vtt/', $questiontext, $match) == 1) {

                $vttFile = parse_url($match[1]);
                $vttPatchArray = array_reverse(explode("/", $vttFile["path"]));
                $fileID = $vttPatchArray[1];
                $fileName = $vttPatchArray[0].".vtt";

                $fileData = $DB->get_record("files", array("filename"=>urldecode($fileName), "itemid"=>$fileID, "component"=>"question", "filearea"=>"questiontext"));

                $filePatch = substr($fileData->contenthash, 0, 2)."/".substr($fileData->contenthash, 2, 2);

                $vttContents = file ($CFG->dataroot."/filedir/".$filePatch."/".$fileData->contenthash);

                $strings = array();

                foreach($vttContents as $k => $v){
                    if (substr($v, 0, 3) == "00:"){
                        $strings[$v] = $vttContents[$k+1];
                    }
                }

                $parsedToLast = 0;

                $stringLinks = html_writer::start_tag('ul', array('style'=>'text-align: left;list-style-type: none;'));
                foreach ($strings as $k => $v){
                    $stringLinks .= html_writer::start_tag('li');
                    list($from, $to) = explode(" --> ", $k);
                    $parsed = date_parse($from);
                    $parsedTo = date_parse($to);

                    $secondsFull = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
                    $parsedTo = $parsedTo['hour'] * 3600 + $parsedTo['minute'] * 60 + $parsedTo['second'];

                    if ($parsedToLast == $secondsFull){
                        $secondsFull++;
                    }

                    $allSecs = range($secondsFull, $parsedTo);

                    $timeClasses = "";
                    foreach($allSecs as $sec){
                        $timeClasses .= " qaclass".$sec."id";
                    }

                    $seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'] + $parsed['fraction'];
                    $stringLinks .= html_writer::tag('a', "[".$seconds."] ".$v, array('href' => 'javascript:;', 'class' => 'goToVideo'.$timeClasses, 'data-value'=>$seconds));
                    $stringLinks .= html_writer::end_tag('li');

                    $parsedToLast = $parsedTo;
                }
                $stringLinks .= html_writer::end_tag('ul');

                $questiontext = str_replace("</video>", "</video>". $stringLinks, $questiontext);
            }
        }

        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        if (!$options->readonly) {
            $sampleResponses = html_writer::start_tag('ul');
            foreach ($question->questions as $q) {
                $sampleResponses .= html_writer::start_tag('li');
                $sampleResponses .= html_writer::tag('div', $question->format_text($q->answer, $q->answerformat,
                    $qa, 'question', 'answer', $q->id)); // , array('class' => 'qtext')
                $sampleResponses .= html_writer::end_tag('li');

                break;
            }
            $sampleResponses .= html_writer::end_tag('ul');
        }

        $answername = $qa->get_qt_field_name('answer');
        {
            $label = 'answer';
            $currentanswer = $qa->get_last_qt_var($label);
            $inputattributes = array(
                'type' => 'hidden',
                'name' => $answername,
                'value' => $currentanswer,
                'id' => $answername,
                'size' => 60,
                'class' => 'form-control d-inline',
                'readonly' => 'readonly',
                //'style' => 'border: 0px; background-color: transparent;',
            );

            $answerDiv = $qa->get_qt_field_name('answerDiv');

            /*
             * Check user teacher role
             */
            $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
            $isteacheranywhere = $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]);

            $input = html_writer::div($currentanswer, $answerDiv, array("id" => $answerDiv));
            $input .= html_writer::empty_tag('input', $inputattributes);


            if ($question->show_transcript == 1 || $isteacheranywhere === true) {
                $answerDisplayStatus = "none";
            } else {
                $answerDisplayStatus = "display:none";
            }

            if ($question->save_stud_audio == 1) {
                $audioDisplayStatus = "none";
            } else {
                $audioDisplayStatus = "display:none";
            }

            /* Answer Div old position */
        }

        $config = get_config('qtype_sassessment');

        $itemid = $qa->prepare_response_files_draft_itemid('attachments', $options->context->id);

        if (!$options->readonly) {
            if ($question->speechtotextlang == "en") {
                $question->speechtotextlang = "en-US";
            }

            $gradename = $qa->get_qt_field_name('grade');
            $btnname = $qa->get_qt_field_name('rec');
            $audioname = $qa->get_qt_field_name('audio');
            $medianame = $qa->get_qt_field_name('mediaembed');
            $mediaelement = $qa->get_qt_field_name('mediaelement');

            $btnattributes = array(
                'name' => $btnname,
                'id' => $btnname,
                'class' => 'srecordingBTN button-xl',
                'size' => 80,
                'qid' => $question->id,
                'answername' => $answername,
                'answerDiv' => $answerDiv,
                'gradename' => $gradename,
                'medianame' => $medianame,
                'mediaelement' => $mediaelement,
                'mediapercent' => $question->immediatefeedbackpercent,
                'speechtotextlang' => $question->speechtotextlang,
                'amazon_region' => $config->amazon_region,
                'amazon_accessid' => $config->amazon_accessid,
                'amazon_secretkey' => $config->amazon_secretkey,
                'onclick' => 'recBtn(event);',
                'type' => 'button',
                'options' => json_encode(array(
                    'repo_id' => $this->get_repo_id(),
                    'ctx_id' => $options->context->id,
                    'itemid' => $itemid,
                    'title' => 'audio.mp3',
                )),
                'audioname' => $audioname,
            );

            $btn  = html_writer::start_tag('button', $btnattributes);
            $btn .= html_writer::tag('i', "", array('class' => 'fa fa-microphone'));
            $btn .= " " . get_string("startrecording", 'qtype_sassessment');
            $btn .= html_writer::end_tag('button');

            $audio = html_writer::empty_tag('audio', array('src' => ''));

            $result .= html_writer::start_tag('div', array('class' => 'ablock'));
            $result .= html_writer::tag('label', "" . $btn,
                array('for' => $btnattributes['id']));
            $result .= html_writer::empty_tag('input', array('type' => 'hidden',
                'name' => $qa->get_qt_field_name('attachments'), 'value' => $itemid));
            $result .= html_writer::end_tag('div');


            if (!$options->readonly) {

                $result .= html_writer::start_tag('div', array('class' => 'ablock form-inline'));
                /*
                 * Disable target response
                 */
                /*                $result .= html_writer::tag('label', get_string('targetresponse', 'qtype_sassessment',
                                $sampleResponses . html_writer::tag('span', $input, array('class' => 'answer'))),
                                    array('for' => $inputattributes['id'], 'style' => $answerDisplayStatus)); */
                $result .= html_writer::tag('label', html_writer::tag('span', $input, array('class' => 'answer')),
                    array('for' => $inputattributes['id'], 'style' => $answerDisplayStatus));
                $result .= html_writer::end_tag('div');

            }


            $result .= html_writer::start_tag('div', array('class' => 'ablock', 'style' => $audioDisplayStatus));
            $result .= html_writer::empty_tag('audio', array('id' => $audioname, 'name' => $audioname, 'controls' => ''));
            $result .= html_writer::end_tag('div');

            $result .= html_writer::start_tag('div', array('class' => 'ablock', 'id' => $medianame, 'style' => 'display: none'));
            $result .= qtype_sassessment_embed_media($question->immediatefeedback, $mediaelement);
            $result .= html_writer::end_tag('div');


            if ($config->stt_core == "amazon") {
                //$PAGE->requires->js( new moodle_url($CFG->wwwroot . '/question/type/sassessment/js/jquery.min.js') );
                //$PAGE->requires->js( new moodle_url($CFG->wwwroot . '/question/type/sassessment/js/lame.js?1') );
                //$PAGE->requires->js( new moodle_url($CFG->wwwroot . '/question/type/sassessment/js/main.js?5') );
                $result .= html_writer::script(null, "https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js");
                $result .= html_writer::script(null, new moodle_url('/question/type/sassessment/js/lame.js?1'));
                $result .= html_writer::script(null, new moodle_url('/question/type/sassessment/js/main.js?6'));
            } else if ($config->stt_core == "google"){
                $result .= html_writer::script(null, new moodle_url('/question/type/sassessment/js/google/recorder.js'));
                $result .= html_writer::script(null, new moodle_url('/question/type/sassessment/js/google/main.js?5'));
                $result .= html_writer::script(null, new moodle_url('/question/type/sassessment/js/google/Mp3LameEncoder.min.js?1'));
            }

        }
        else {
            $files = $qa->get_last_qt_files('attachments', $options->context->id);

            if ($question->save_stud_audio == 1) {
                $audioDisplayStatus = "none";
            } else {
                $audioDisplayStatus = "display:none";
            }

            foreach ($files as $file) {
                $result .= html_writer::start_tag('div', array('class' => 'ablock', 'style' => $audioDisplayStatus));
                $result .= html_writer::tag('p', html_writer::empty_tag('audio', array('src' => $qa->get_response_file_url($file), 'controls' => '')));
                $result .= html_writer::end_tag('div');
            }
        }


        $gradename = $qa->get_qt_field_name('grade');
        {
            $label = 'grade';
            $currentanswer = $qa->get_last_qt_var($label);
            $inputattributes = array(
                'name' => $gradename,
                'value' => $currentanswer,
                'id' => $gradename,
                'size' => 10,
                'class' => 'form-control d-inline',
                'readonly' => 'readonly',
                'style' => 'border: 0px; background-color: transparent;',
            );

            if (count($question->questions) > 0) {  // Show grade if sample answers exist only
                $input = html_writer::empty_tag('input', $inputattributes);

                if (!$options->readonly && !empty($q->answer)) {
                    $result .= html_writer::start_tag('div', array('class' => 'ablock form-inline'));

                    $result .= html_writer::tag('label', get_string('score', 'qtype_sassessment',
                        html_writer::tag('span', $input, array('class' => 'answer'))),
                        array('for' => $inputattributes['id']));
                    $result .= html_writer::end_tag('div');
                }
            }
        }


        /*
         * IOS app integration code START
         */
        /*
         * This code works with voiceshadow module only!
         */
        if (function_exists("voiceshadow_is_ios") && $config->stt_core == "google")
            if (voiceshadow_is_ios() && !$options->readonly && file_exists($CFG->dirroot.'/mod/voiceshadow/ajax-apprecord.php')) {
                $time = time();
                $result .= html_writer::start_tag("a", array("href" => 'voiceshadow://?link='.$CFG->wwwroot.'&id='.$options->context->id.'&uid='.$USER->id.'&time='.$time.'&fid='.$question->id.'&var=0&audioBtn=1&sstBtn=1&type=voiceshadow&mod=voiceshadow', "id" => "id_recoring_link",  //
                    "onclick" => 'formsubmit(this.href)'));

                $result .= get_string('recordAudioIniosapp', 'qtype_sassessment');
                $result .= html_writer::end_tag('a');


                $PAGE->requires->js_amd_inline('
require(["jquery"], function(min) {
    $(function() {
        $.get( "'.$CFG->wwwroot.'/mod/voiceshadow/ajax-apprecord.php", { id: '.$question->id.', inst: '.$options->context->id.', uid: '.$USER->id.' }, function(json){
            var j = JSON.parse(json);
            var t = +new Date();
    
            if (j.status == "success") {
    
                  $(":text").each(function() {
                      if ($(this).attr("id") == "'.$answername.'") {
                            $(this).val(j.text);
                      }
                  });
                  
                  $("audio").each(function() {
                      if ($(this).attr("id") == "'.$audioname.'") {
                            $(this).attr("src", "'.$CFG->wwwroot.'/mod/voiceshadow/file.php?file="+j.fileid);
                      }
                  });
                  
                  $("input").each(function() {
                      if ($(this).attr("name") == "'.$qa->get_qt_field_name('attachments').'") {
                            $(this).val(j.itemid);
                      }
                  });
                  
                  $.post("'.$CFG->wwwroot.'/question/type/sassessment/ajax-score.php", { qid: '.$question->id.', ans: j.text },
                     function (data) {
                        $("input").each(function() {
                                         if ($(this).attr("name") == "'.$gradename.'") {
                                $(this).val(JSON.parse(data).gradePercent);
                             }
                        });
                     });
    
                  $.get( "'.$CFG->wwwroot.'/mod/voiceshadow/ajax-apprecord.php", { a: "delete", id: '.$question->id.', inst: '.$options->context->id.', uid: '.$USER->id.' });
            }
        });    
    });
});
');

                /*
                 * IOS app integration code END
                 */
            }


        $PAGE->requires->js_amd_inline('
        
require(["jquery"], function(min) {
    $(function() {
        
        speechLang = "'.$question->speechtotextlang.'";
        console.log(\'Spell checking:\' + speechLang);
    
        $(".goToVideo").click(function() {
            $(".goToVideo").attr("style","");
            var $div = $(this).closest(".qtext");
            video = $div.find("video").get(0);
            video.currentTime = $(this).attr("data-value");
            video.play();
            console.log(video.currentTime);
        });
        
        window.latestID = -1;

            $("video").on("timeupdate", function(event){
                time = Math.round(this.currentTime);
                
                if (window.latestID != time) {
 
                for (var i=(time-1); i>0; i--) {
                   $(".qaclass"+i+"id").attr("style","");
                }
                
                $(".qaclass"+time+"id").attr("style","background-color: antiquewhite; color: black;");
                
                window.latestID = time
                }
            });    
    });
});

');


        return $result;
    }

    public function get_repo_id($type = 'upload') {
        global $CFG;
        require_once($CFG->dirroot . '/lib/form/filemanager.php');
        foreach (repository::get_instances() as $rep) {
            $meta = $rep->get_meta();
            if ($meta->type == $type)
                return $meta->id;
        }
        return null;
    }

    public function specific_feedback(question_attempt $qa) {
        global $DB, $USER;

        include_once "finediff.php";

        $question = $qa->get_question();
        $ans = $qa->get_last_qt_var('answer');
        $grade = qtype_sassessment_compare_answer($ans, $qa->get_question()->id);
        $grade['gradePercent'] = $qa->get_last_qt_var('grade');  /*  Change grade??!! */

        $result = '';
        $result .= html_writer::start_tag('div', array('class' => 'ablock'));


        /*
         * Feed Back report
         *
         */

        $state = $qa->get_state();

        if (!$state->is_finished()) {
            $response = $qa->get_last_qt_data();
            if (!$qa->get_question()->is_gradable_response($response)) {
                return '';
            }
            list($notused, $state) = $qa->get_question()->grade_response($response);
        }

        $feedback = '';
        $field = $state->get_feedback_class() . 'feedback';
        $format = $state->get_feedback_class() . 'feedbackformat';
        if ($question->$field) {
            $feedback .= $question->format_text($question->$field, $question->$format,
                $qa, 'question', $field, $question->id);
        }

        if (!empty($feedback)) {
            if (!is_numeric($grade['gradePercent'])) {
                $feedback = get_string('teachergraded', 'qtype_sassessment');
            }

            $result .= html_writer::tag('p', /*get_string('feedback', 'qtype_sassessment') . ": " .*/ $feedback);
        }

/*
        if ($grade['gradePercent'] > 80) {
            $result .= html_writer::tag('p', get_string('feedback', 'qtype_sassessment') . ": " . $qa->get_question()->correctfeedback);
        } else if ($grade['gradePercent'] > 30) {
            $result .= html_writer::tag('p', get_string('feedback', 'qtype_sassessment') . ": " . $qa->get_question()->partiallycorrectfeedback);
        } else {
            $result .= html_writer::tag('p', get_string('feedback', 'qtype_sassessment') . ": " . $qa->get_question()->incorrectfeedback);
        }
*/

        /*
         * No need to show target response
         */
        //$result .= html_writer::tag('p', get_string('targetresponsee', 'qtype_sassessment') . ": " . $grade['answer']);

        /*
         * Check user teacher role
         */
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $isteacheranywhere = $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]);

        /*
         * Teacher always see transcription
         */
        if ($question->show_transcript == 1 || $isteacheranywhere === true) {
            $result .= html_writer::tag('p', get_string('myanswer', 'qtype_sassessment') . ": " . $ans);

            //$result .= html_writer::tag('style', "del{display:none}ins{color:red;background:#fdd;text-decoration:none}");
        }

        if (!empty($grade['answer']) && !empty($grade['answerSource'])) {
            $grade['answer'] = str_replace(".", " ", $grade['answer']);
            $ans = str_replace(".", " ", $ans);

            //$from_str = preg_replace('/[^A-Za-z0-9 ]/i', '', strtolower($grade['answer']));  /* Original code: Two different lines!!! " ]" and "] " */
            //$to_str = preg_replace('/[^A-Za-z0-9] /i', '', strtolower($ans));

            //$from_str = preg_replace('/[^a-zA-Z\s]+/', '', $grade['answer']);
            $from_str = str_replace(array("!","?",".",","), ' ', $grade['answerSource']);
            $from_str = preg_replace('!\s+!', ' ', $from_str);
            $from_str = trim(strtolower($from_str));


            //$to_str = preg_replace('/[^a-zA-Z\s]+/', '', $ans);
            $to_str = str_replace(array("!","?",".",","), ' ', $grade['answer']);
            $to_str = preg_replace('!\s+!', ' ', $to_str);
            $to_str = trim(strtolower($to_str));

            $diff = new FineDiff($from_str, $to_str, FineDiff::$wordGranularity);
            $rendered_diff = $diff->renderDiffToHTML();

            $result .= html_writer::tag('p', get_string('feedback', 'qtype_sassessment') . ": " . $rendered_diff);
            $result .= html_writer::tag('style', "del{color:red;background:#fdd;text-decoration:none}ins{display:none}");
        }

        if (!empty($grade['answer'])) {
            $result .= html_writer::tag('p', get_string('scoree', 'qtype_sassessment') . ": " . $grade['gradePercent']);
        }

        $result .= html_writer::end_tag('div');

        /*
         * TMP disabled Analises report
         */
        if ($question->show_analysis == 1) {
            $anl = qtype_sassessment_printanalizeform($ans);
            unset($anl['laters']);
            $table = new html_table();
            $table->head = array('Analysis', 'Result');
            $table->data = array();

            foreach ($anl as $k => $v)
                $table->data[] = array(get_string($k, 'qtype_sassessment'), $v);

            $result .= html_writer::table($table);
        }


        return $result;
    }

    public function correct_response(question_attempt $qa) {
        // TODO.
        return '';
    }
}
