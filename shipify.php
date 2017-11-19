<?php session_start();

	require 'vendor/autoload.php';

	use phpish\shopify;



	require 'conf.php';

	# shopify\access_token can throw an exception

		$oauth_token = shopify\access_token($_GET['shop'], SHOPIFY_APP_API_KEY, SHOPIFY_APP_SHARED_SECRET);



		$_SESSION['oauth_token'] = $oauth_token;

		$_SESSION['shop'] = $_GET['shop'];
?>
<a href=""></a>