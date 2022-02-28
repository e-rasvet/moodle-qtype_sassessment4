<?php

require_once '../../../config.php';
require_once 'lib.php';

$text  = optional_param('text', 0, PARAM_TEXT);

qtype_sassessment_log($text);