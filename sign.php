<?php

require_once '../../../config.php';
require_once 'lib.php';

$date = optional_param('date', 0, PARAM_TEXT);
$region = optional_param('region', 0, PARAM_TEXT);
$service = optional_param('service', 0, PARAM_TEXT);
$stringToSign = optional_param('stringToSign', 0, PARAM_TEXT);

$config = get_config('qtype_sassessment');

$h1 = hash_hmac('sha256', $date, 'AWS4' . $config->amazon_secretkey, true);
$h2 = hash_hmac('sha256', $region, $h1, true);
$h3 = hash_hmac('sha256', $service, $h2, true);
$h4 = hash_hmac('sha256', 'aws4_request', $h3, true);
$h5 = hash_hmac('sha256', $stringToSign, $h4);

echo json_encode(array("key" => $h5));