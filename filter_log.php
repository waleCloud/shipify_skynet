<?php
require 'conf.php';

$file = "log.txt";
// Get the shop object content
$order_content = file_get_contents( $file );

// Decode the JSON
$order_json = json_decode( $order_content, true );

// print the outputs
//echo '<pre>' . print_r($order_json, true) . '</pre>';
var_dump($order_json);
 echo $order_json['email'].'<br />';
 echo $order_json['line_items'][0]['title'];
 echo $order_json['line_items'][0]['quantity'];
 echo $order_json['line_items'][0]['grams'];
 echo $order_json['line_items'][0]['vendor'];
 echo $order_json['line_items'][0]['total_weight'];
 

// save into database
