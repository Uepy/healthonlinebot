<?php

require_once __DIR__ . '/vendor/autoload.php';
date_default_timezone_set('Asia/Tokyo');

$inputString = file_get_contents('php://input');
error_log($inputString);

 ?>