<?php

/**
* Simple APNS
*
* @link https://github.com/mdominas/SAPNS
* @copyright Copyright (c) 2013 Marcin Dominas
* @author Marcin Dominas, www.marcindominas.com
* @license The MIT License (MIT) http://opensource.org/licenses/MIT
*/

/* for live environment use gateway.push.apple.com */
define("APNS_HOST", "gateway.sandbox.push.apple.com");
/* make sure this port is open on your server (TCP OUT) */
define("APNS_PORT", "2195");
/* make sure certificate can be read by the script */
define("APNS_CERT", "apns-cert.pem");
/* make sure this port is open on your server (TCP IN/OUT) or even forwarded to allow external connections */
define("SERVER_PORT", 6000);

date_default_timezone_set('Europe/London');

function __autoload($name) 
{
	$path_parts = explode('\\', $name);
    require_once "SAPNS/".end($path_parts) . '.php';
}

try
{
	$apns = new SAPNS\SimpleAPNS();

	/*
	* 	This function is called as soon as incoming connection to the local server is established.
	* 	Server will automatically block any other connections until this function finishes its job.
	* 	Here you should implement your business logic behind sending push notifications.
	* 	IMPORTANT. This funtion must return a boolean value. Service will restart itself if FALSE is returned.
	* 	If this function returns TRUE, service will continue accepting new connections repeating the cycle.
	* 	Have a look below at the pseudo-code below to get an idea of how to use it.
	*/

	$delegate = function($apns_context)
	{
		/* 
		*	Call the database to retrieve unprocessed notifications.
		*	It's wise to set a fixed limit on how many notifications you want to push in one go.
		*	Send notification for each device in a loop.
		*/
		$device_id = "eed2020f4f792bf3bb67e8aeea773b23384764db3cab1b12d60d764d2213e400";
		$message   = "Hello World";

		/* 
		*	Always check what push() returns. 
		*	If APNS pipe is broken notification won't be sent and push() will return FALSE.
		*/
		if (!$apns_context->push($device_id, $message))
		{
			/* Log an "incident" if you need to. */			
			$apns_context::log(sprintf("Failed attempt to send a message : %s to device : %s", $mesage, $device_id));

			/* Return FALSE in order to restart the service possibly due to broken APNS pipe. */
			return false;
		}

		/* 	Update database to flag just processed notifications and return TRUE 
		* 	so that server can continue accepting new connections in order to process another bunch of notifications.
		*/

		return true;
	};
	/* Don't forget a ";" character in the line above. */

	$apns->setAPNSHost(APNS_HOST)
		 ->setApnsPort(APNS_PORT)
		 ->setApnsCert(APNS_CERT)
		 ->setServerPort(SERVER_PORT)
		 ->run($delegate);

} catch (SAPNS\SimpleAPNS_Exception $e)
{
	SAPNS\SimpleAPNS::log($e->getMessage());
}
