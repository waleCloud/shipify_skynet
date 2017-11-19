<?php
header('Access-Control-Allow-Origin: *');
$postdata = file_get_contents("php://input");
$file = "log.txt";

$f = @fopen("log.txt", "r+");
if ($f !== false) {
    ftruncate($f, 0);
    fclose($f);
}

$current = file_get_contents($file);
$current .= $postdata;
/*require 'webhook_capture.php';
$whc = new webhook_capture($current);
$whc->process_json();*/
file_put_contents($file, $current);

//header("Location: webhook_capture.php");

/*
$webhookContent = "";

$webhook = fopen('php://input' , 'rb');
while (!feof($webhook)) {
    $webhookContent .= fread($webhook, 4096);
}
fclose($webhook);

error_log($webhookContent);
*/