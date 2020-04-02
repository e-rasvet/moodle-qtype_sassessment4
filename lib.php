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
 * Serve question type files
 *
 * @since      2.0
 * @package    qtype_sassessment
 * @copyright  2018 Kochi-Tech.ac.jp

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Checks file access for sassessment questions.
 * @package  qtype_sassessment
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool
 */
function qtype_sassessment_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    question_pluginfile($course, $context, 'qtype_sassessment', $filearea, $args, $forcedownload, $options);
}

/**
 * @param $ans
 * @param $qid
 * @param bool $get
 * @return array
 */
function qtype_sassessment_compare_answer($ans, $qid, $get = true) {
  global $DB, $CFG;

  $maxp = 0;
  $maxpF = 0;
  $maxi = 1;
  $maxtext = "";
  $allSampleResponses = "";

  if ($sampleresponses = $DB->get_records('question_answers', array('question' => $qid))) {
      $questionOptions = $DB->get_records('qtype_sassessment_options', array('questionid' => $qid));

      foreach ($sampleresponses as $k => $sampleresponse) {
          $allSampleResponses .= $sampleresponse->answer."\n";

          $percent = qtype_sassessment_similar_text($sampleresponse->answer, $ans, $questionOptions->speechtotextlang);
          $percentOrig = qtype_sassessment_similar_text_original($sampleresponse->answer, $ans, $questionOptions->speechtotextlang);
          $totalPercent = round (($percent + $percentOrig) / 2, 2);

          if ($maxp < $totalPercent) {
              $maxi = $k;
              $maxp = $totalPercent;

              $maxpF = max($percent, $percentOrig);

              if ($maxp > 100 || $maxpF > 100) {
                  $maxp = 100;
                  $maxpF = 100;
              }

              $maxtext = $sampleresponse->answer;
          }
      }
  }

  if ($maxpF > 90) {
      $maxpF = 100;
  }

  $result = array(
    "gradePercent" => round($maxpF),
    "grade" => round($maxpF/100, 2),
  );

  if ($get)
    $result["answer"] = $maxtext;

  if (empty($allSampleResponses)) {
      $result["gradePercent"] = 100;
      $result["grade"] = 1;
  }

  $result["answer"] = $ans;
  $result["sampleAnswers"] = $allSampleResponses;
  $result["i"] = $maxi;

  return $result;
}


/**
 * @param $text1
 * @param $text2
 * @param string $lang
 * @return float|mixed
 */
function qtype_sassessment_similar_text($text1, $text2, $lang = "en"){

    $text1 = strip_tags($text1);
    $text2 = strip_tags($text2);

    $text1 = preg_replace('/[^a-zA-Z\s]+/', '', $text1);
    $text1 = preg_replace('!\s+!', ' ', $text1);


    $text2 = preg_replace('/[^a-zA-Z\s]+/', '', $text2);
    $text2 = preg_replace('!\s+!', ' ', $text2);

    $text1 = strtolower($text1);
    $text2 = strtolower($text2);


    if (strstr($lang, "en")) {
        $res = qtype_sassessment_cmp_phon($text1, $text2);
        $percent = $res['percent'];
    } else {
        $sim = similar_text($text1, $text2, $percent);
        $percent = round($percent);
    }


    return $percent;
}


/**
 * @param $text1
 * @param $text2
 * @param string $lang
 * @return float|mixed
 */
function qtype_sassessment_similar_text_original($text1, $text2, $lang = "en"){

    $text1 = strip_tags($text1);
    $text2 = strip_tags($text2);

    $text1 = preg_replace('!\s+!', ' ', $text1);

    $text2 = preg_replace('!\s+!', ' ', $text2);

    $text1 = strtolower($text1);
    $text2 = strtolower($text2);


    if ($lang == "en") {
        $res = qtype_sassessment_cmp_phon($text1, $text2);
        $percent = $res['percent'];
    } else {
        $sim = similar_text($text1, $text2, $percent);
        $percent = round($percent);
    }


    return $percent;
}


/**
 * @param $spoken
 * @param $target
 * @return array
 */
function qtype_sassessment_cmp_phon($spoken, $target){
    global $CFG;

    if (!isset($CFG->pron_dict_loaded)) {
        $lines = explode("\n",file_get_contents($CFG->dirroot . '/question/type/sassessment/pron-dict.txt'));

        $pron_dict = array();

        foreach($lines as $line){
            $elements = explode(",",$line);
            $pron_dict[$elements[0]] = $elements[1];
        }

        $CFG->pron_dict_loaded = $pron_dict;
    } else
        $pron_dict = $CFG->pron_dict_loaded;

    if (isset($lines))
        foreach($lines as $line){
            $elements=explode(",",$line);
            $pron_dict[$elements[0]]=$elements[1];
        }

    // Set up two objects, spoken and target

    $spoken_obj=new stdClass;
    $target_obj=new stdClass;

    $spoken_obj->raw=$spoken;
    $spoken_obj->clean=strtolower(preg_replace("/[^a-zA-Z0-9' ]/","",$spoken_obj->raw));
    $spoken_obj->words=array_filter(explode(" ", $spoken_obj->clean));
    $spoken_obj->phonetic=array();

    // Convert each spoken word to phonetic script

    foreach($spoken_obj->words as $word){
        if(array_key_exists(strtoupper($word), $pron_dict)){
            $spoken_obj->phonetic[]=$pron_dict[strtoupper($word)];
        }
        else{
            $spoken_obj->phonetic[]=$word;
        }
    }

    $target_obj->raw=$target;
    $target_obj->clean=strtolower(preg_replace("/[^a-zA-Z0-9' ]/","",$target_obj->raw));
    $target_obj->words=array_filter(explode(" ", $target_obj->clean));
    $target_obj->phonetic=array();

    // Convert each target word to phonetic script

    foreach($target_obj->words as $word){
        if(array_key_exists(strtoupper($word), $pron_dict)){
            $target_obj->phonetic[]=$pron_dict[strtoupper($word)];
        }
        else{
            $target_obj->phonetic[]=$word;
        }
    }

    // Check for matches

    $matched=array();
    $unmatched=array();
    $score=0;

    foreach($target_obj->phonetic as $index=>$word){
        if(in_array($word, $spoken_obj->phonetic)){
            $score++;
            if(!in_array($target_obj->words[$index], $matched)){
                $matched[]=$target_obj->words[$index];
            }
        }
        else if(!in_array($word, $spoken_obj->phonetic)){
            if(!in_array($target_obj->words[$index], $unmatched)){
                $unmatched[]=$target_obj->words[$index];
            }
        }
    }

    /*
     * New unmached calculation system
     */
    foreach($spoken_obj->phonetic as $index=>$word){
        if(!in_array($word, $target_obj->phonetic)){
            if(!in_array($spoken_obj->words[$index], $unmatched)){
                $unmatched[]=$spoken_obj->words[$index];
            }
        }
    }
    //$percent=round($score/count($spoken_obj->words)*100);  //Old
    $percent=round(count($matched)/(count($matched)+count($unmatched))*100);  //New

    if (count($spoken_obj->phonetic) == 0) {
        $percent = 100;
    }

    return array("spoken"=>$spoken_obj,"target"=>$target_obj,"matched"=>$matched,"unmatched"=>$unmatched,"percent"=>$percent);
}


/**
 * @param $text
 * @return array
 */
function qtype_sassessment_printanalizeform($text) {
    $data = Array ();

    $text = strip_tags ($text);

    if (empty($text)) {
        return array(
            "wordcount" => 0,
            "worduniquecount" => 0,
            "numberofsentences" => 0,
            "averagepersentence" => 0,
            "hardwords" => 0,
            "hardwordspersent" => 0,
            "lexicaldensity" => 0,
            "fogindex" => 0,
            "laters" => 0
        );
    }

    $data['wordcount'] = qtype_sassessment_wordcount($text);
    $data['worduniquecount'] = qtype_sassessment_worduniquecount ($text);
    $data['numberofsentences'] = qtype_sassessment_numberofsentences ($text);
    if ($data['numberofsentences'] == 0 || empty($data['numberofsentences'])) {
        $data['numberofsentences'] = 1;
    }
    $data['averagepersentence'] = qtype_sassessment_averagepersentence ($text, $data['wordcount'], $data['numberofsentences']);
    list ($data['hardwords'], $data['hardwordspersent']) = qtype_sassessment_hardwords ($text, $data['wordcount']);
    $data['lexicaldensity'] = qtype_sassessment_lexicaldensity ($text, $data['wordcount'], $data['worduniquecount']);
    $data['fogindex'] = qtype_sassessment_fogindex ($text, $data['averagepersentence'], $data['hardwordspersent']);
    $data['laters'] = qtype_sassessment_laters ($text);

    return $data;
}


/**
 * @param $text
 * @return mixed
 */
function qtype_sassessment_wordcount ($text) {
    return str_word_count ($text);
}


/**
 * @param $text
 * @return int
 */
function qtype_sassessment_worduniquecount ($text) {
    $words  = str_word_count ($text, 1);
    $words_ = Array ();

    foreach ($words as $word) {
        if (!in_array($word, $words_)) {
            $words_[] = strtolower ($word);
        }
    }
    return count ($words_);
}


/**
 * @param $text
 * @return int
 */
function qtype_sassessment_numberofsentences ($text) {
    $text = strip_tags ($text);
    $noneed = array ("\r", "\n", ".0", ".1", ".2", ".3", ".4", ".5", ".6", ".7", ".8", ".9");
    foreach ($noneed as $noneed_) {
        $text = str_replace ($noneed_, " ", $text);
    }
    $text = str_replace ("!", ".", $text);
    $text = str_replace ("?", ".", $text);
    $textarray = explode (".", $text);
    $textarrayf = array();
    foreach ($textarray as $textarray_) {
        if (!empty($textarray_) && strlen ($textarray_) > 5) {
            $textarrayf[] = $textarray_;
        }
    }
    $count = count($textarrayf);
    return $count;
}


/**
 * @param $text
 * @param $words
 * @param $sentences
 * @return float|int
 */
function qtype_sassessment_averagepersentence ($text, $words, $sentences) {
    if ($sentences == 0 || empty($sentences)) {
        return 0;
    }
    $count = round($words / $sentences, 2);
    return $count;
}


/**
 * @param $text
 * @param $word
 * @param $wordunic
 * @return float|int
 */
function qtype_sassessment_lexicaldensity ($text, $word, $wordunic) {
    if ($word == 0 || empty($word)) {
        return 0;
    }
    $count = round(($wordunic / $word) * 100, 2);
    return $count;
}


/**
 * @param $text
 * @param $averagepersentence
 * @param $hardwordspersent
 * @return float
 */
function qtype_sassessment_fogindex ($text, $averagepersentence, $hardwordspersent) {
    $count = round(($averagepersentence + $hardwordspersent) * 0.4, 2);
    return $count;
}


/**
 * @param $text
 * @return array
 */
function qtype_sassessment_laters ($text) {
    $words  = str_word_count ($text, 1);
    $words_ = array();
    $result = array();

    $max = 1;

    foreach ($words as $word) {
        if (!in_array($word, $words_)) {
            $words_[] = strtolower ($word);
            if (strlen ($word) > $max) {
                $max = strlen ($word);
            }
        }
    }

    for ($i=1; $i<=$max; $i++) {
        foreach ($words as $word) {
            if (strlen($word) == $i) {
                if (!isset($result[$i])) {
                    $result[$i] = 0;
                }
                $result[$i] ++;
            }
        }
    }
    return $result;
}


/**
 * @param $text
 * @param $wordstotal
 * @return array
 */
function qtype_sassessment_hardwords($text, $wordstotal) {
    $syllables = 0;
    $words = explode(' ', $text);
    for ($i = 0; $i < count($words); $i++) {
        if (qtype_sassessment_count_syllables($words[$i]) > 2) {
            $syllables ++;
        }
    }

    if ($syllables == 0) {
        return Array(0, 0);
    }

    $score = round(($syllables / $wordstotal) * 100, 2);
    return Array($syllables, $score);
}


/**
 * @param $word
 * @return float|int
 */
function qtype_sassessment_count_syllables($word) {
    $nos = strtoupper($word);
    $syllables = 0;
    $before = strlen($nos);
    if ($before >= 2){
        $nos = str_replace(array('AA','AE','AI','AO','AU',
            'EA','EE','EI','EO','EU','IA','IE','II','IO',
            'IU','OA','OE','OI','OO','OU','UA','UE',
            'UI','UO','UU'), "", $nos);
        $after = strlen($nos);
        $diference = $before - $after;
        if($before != $after) $syllables += $diference / 2;
        if($nos[strlen($nos)-1] == "E") $syllables --;
        if($nos[strlen($nos)-1] == "Y") $syllables ++;
        $before = $after;
        $nos = str_replace(array('A','E','I','O','U'),"",$nos);
        $after = strlen($nos);
        $syllables += ($before - $after);
    } else {
        $syllables = 0;
    }
    return $syllables;
}


function qtype_sassessment_embed_media($url, $mediaId){

    $filename = basename(parse_url($url, PHP_URL_PATH));
    $mime = getMimeType($filename);

    if(strstr($mime, "video/")){
        // this code for video

        //https://docs.moodle.org/dev/skins/moodledocs/sitebar/pix/logo.png
        //http://techslides.com/demos/sample-videos/small.mp4
        //https://dl.espressif.com/dl/audio/gs-16b-2c-44100hz.mp3

        $embed = html_writer::start_tag('video', array("id" => $mediaId, "controls" => ""));
        $embed .= html_writer::empty_tag('source', array('src' => $url, 'type' => $mime));
        $embed .= html_writer::tag('p', "Your browser doesn't support HTML5 video.");
        $embed .= html_writer::end_tag('video');
    } else if(strstr($mime, "image/")){
        $embed = html_writer::empty_tag('img', array('src' => $url));
    } else if(strstr($mime, "audio/")){
        $embed = html_writer::start_tag('audio', array("id" => $mediaId, "controls" => ""));
        $embed .= html_writer::empty_tag('source', array('src' => $url, 'type' => $mime));
        $embed .= html_writer::tag('p', "Your browser doesn't support HTML5 audio.");
        $embed .= html_writer::end_tag('audio');

    } else {
        // this code for text
        $embed = $url;
    }

    return $embed;
}


function getMimeType($ext){
    $ext = strtolower($ext);
    $path_parts = pathinfo($ext);
    $ext = '.' . $path_parts['extension'] ;

    switch ($ext) {
        case '.aac': $mime ='audio/aac'; break; // AAC audio
        case '.mp3': $mime ='audio/mp3'; break; // mp3 audio
        case '.wav': $mime ='audio/wav'; break; // Waveform Audio Format
        case '.weba': $mime ='audio/webm'; break; // WEBM audio
        case '.mpeg': $mime ='video/mpeg'; break; // MPEG Video
        case '.webm': $mime ='video/webm'; break; // WEBM video
        case '.mp4': $mime ='video/mp4'; break; // mp4 video
        case '.avi': $mime ='video/avi'; break; // avi video
        case '.webp': $mime ='image/webp'; break; // WEBP image
        case '.jpeg': $mime ='image/jpeg'; break; // JPEG images
        case '.jpg': $mime ='image/jpeg'; break; // JPEG images
        case '.png': $mime ='image/png'; break; // Portable Network Graphics
        default: $mime = 'application/octet-stream' ; // general purpose MIME-type
    }
    return $mime ;

}

