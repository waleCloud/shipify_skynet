<?php

	session_start();

	require 'vendor/autoload.php';
	use phpish\shopify;

	require 'conf.php';

	# Guard: http://docs.shopify.com/api/authentication/oauth#verification
	//shopify\is_valid_request($_GET, SHOPIFY_APP_SHARED_SECRET) or die('Invalid Request! Request or redirect did not come from Shopify');


	# Step 2: http://docs.shopify.com/api/authentication/oauth#asking-for-permission
	if (!isset($_GET['code']))
	{
		$permission_url = shopify\authorization_url($_GET['shop'], SHOPIFY_APP_API_KEY, array('read_content', 'write_content', 'read_products', 'write_products', 'read_orders', 'write_orders', 'read_fulfillments', 'write_fulfillments', 'read_shipping', 'write_shipping'));
		die("<script> top.location.href='$permission_url'</script>");
	}


	# Step 3: http://docs.shopify.com/api/authentication/oauth#confirming-installation
	try
	{
		# shopify\access_token can throw an exception
		$oauth_token = shopify\access_token($_GET['shop'], SHOPIFY_APP_API_KEY, SHOPIFY_APP_SHARED_SECRET, $_GET['code']);

		# Saving session variables for easy access after installation
		$_SESSION['oauth_token'] = $oauth_token;
		$_SESSION['shop'] = $_GET['shop'];	

		# Inserting the shop installed data into the database with parameter( shop, date accestoken)
		$shop = $_GET['shop'];
		$date_add = date("d-m-Y");
		$q = "INSERT INTO installs (shop, oauth_token, date_add, stat) VALUES ('$shop', '$oauth_token', '$date_add', 1);";
		if( !mysqli_query($conn, $q) ) die("Error inserting into the database ".mysqli_error($conn));

		# Create an API request connection
		$shopify = shopify\client($_SESSION['shop'], SHOPIFY_APP_API_KEY, $_SESSION['oauth_token']);

		# parameters for webhook registration for orders/paid
		$params = array("webhook" => array( "topic" => "orders/paid",
                                            "address" => URL."/order_paid_webhook.php",
                                            "format" => "json")
                        );
		#register the webhook 
		$reg_webhook = $shopify('POST /admin/webhooks.json', $params);
		if(!$reg_webhook) die("Error registering the webhook". $reg_webhook);


		//echo 'App Successfully Installed! <br /><p> <a href="'.URL.'/shipify.php">Continue >></a></p>';

		#redirect to successs page
		$install_success = "http://latsil.com/shipify_skynet/install_success.php?shop=$shop&oauth_token=$oauth_token";
		echo "<script> top.location.href='$install_success'</script>";
	}
	catch (shopify\ApiException $e)
	{
		# HTTP status code was >= 400 or response contained the key 'errors'
		echo $e;
		print_R($e->getRequest());
		print_R($e->getResponse());
	}
	catch (shopify\CurlException $e)
	{
		# cURL error
		echo $e;
		print_R($e->getRequest());
		print_R($e->getResponse());
	}


?>