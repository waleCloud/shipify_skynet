<?php

	define('SHOPIFY_APP_API_KEY', '1311226823b2a723b171f87aa0471713');
	define('SHOPIFY_APP_SHARED_SECRET', '60247372a36655565a4426aa7a7c080f');

	define('MYSQL_HOST', 'localhost');
	define('MYSQL_USER', 'latsilco_db1');
	define('MYSQL_PASS', 'latsilco_db1');
	define('MYSQL_DB', 'latsilco_shipify');
	define('URL', 'http://latsil.com/shipify_skynet');


	$conn = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB); 
    if(!$conn) die("Error connecting to dbase: ".mysqli_error($conn));

