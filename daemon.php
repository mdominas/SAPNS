<?php

/**
* Simple APNS
*
* @link https://github.com/mdominas/SAPNS
* @copyright Copyright (c) 2013 Marcin Dominas
* @author Marcin Dominas, www.marcindominas.com
* @version 1.0
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

	/*
	* 	This anonymous push delegate function will be called every time an incoming connection to SAPNS server is established.
	* 	SAPNS server will automatically block any other connections until push delegate function finishes its job.
	* 	Here you should implement your business logic behind sending push notifications.
	* 	IMPORTANT. Push delegate function must return a boolean value. SAPNS server will restart itself if FALSE is returned.
	* 	If push delegate function returns TRUE, SAPNS server will continue accepting new connections repeating the cycle.
	* 	Example of a push delegate function is defined below.
	*/

	$push_delegate = function($apns_context)
	{
		/* 
		*	At this stage you could call the database to retrieve unprocessed notifications.
		*	It's wise to set a fixed limit on how many notifications you want to push in one go.
		*	You can send mulitple notifications to multiple devices in a loop.
		*/
		$device_id = "eed2020f4f792bf3bb67e8aeea773b23384764db3cab1b12d60d764d2213e400";
		$message   = "Hello World";

		/* 
		*	Always check what push() method returns. 
		*	If APNS pipe is broken notification won't be sent and push() will return FALSE.
		*/
		if (!$apns_context->push($device_id, $message))
		{
			/* Log an "incident" if you need to. */			
			$apns_context::log(sprintf("Failed attempt to send a message : %s to device : %s", $mesage, $device_id));

			/* Return FALSE in order to restart SAPNS server possibly due to broken APNS pipe. */
			return false;
		}

		/* 	Update database flag for just processed notifications and return TRUE 
		* 	so that SAPNS server can continue accepting new connections
		*/

		return true;
	};
	/* Don't forget a trailing ";" character in the line above. */

	$apns = new SAPNS\SimpleAPNS();

	$apns->setApnsHost(APNS_HOST)
		 ->setApnsPort(APNS_PORT)
		 ->setApnsCert(APNS_CERT)
		 ->setServerPort(SERVER_PORT)
		 ->run($push_delegate);

} catch (SAPNS\SimpleAPNS_Exception $e)
{
	SAPNS\SimpleAPNS::log($e->getMessage().", error code : ".$e->getCode());
}
