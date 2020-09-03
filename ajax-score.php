<?php

require_once '../../../config.php';
require_once 'lib.php';
require_once($CFG->libdir . '/questionlib.php');

$ans  = optional_param('ans', 0, PARAM_TEXT);
$targetAnswer  = optional_param('targetAnswer', "", PARAM_TEXT);
$usageid  = optional_param('usageid', 0, PARAM_INT);
$slot = optional_param('slot', 1, PARAM_INT);

$quba = question_engine::load_questions_usage_by_activity($usageid);
//$slot = $quba->get_first_question_number();
$qa = $quba->get_question_attempt($slot);

echo json_encode(qtype_sassessment_compare_answer($ans, $qa, $targetAnswer));


