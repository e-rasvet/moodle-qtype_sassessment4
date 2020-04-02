<?php

require_once '../../../config.php';
require_once 'lib.php';

$ans = optional_param('ans', 0, PARAM_TEXT);
$qid = optional_param('qid', 0, PARAM_INT);

echo json_encode(qtype_sassessment_compare_answer($ans, $qid, false));
