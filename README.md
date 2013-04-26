SAPNS - Simple APNS server for PHP
=====

Version 1.0

Apple Push Notification Service (APNS) has been with us since 2009. Yet I'm a bit disappointed with PHP tools available to support this awesome service. 
Some primitive tools "recommend" a cron-job approach to periodically poke a script that connects to APNS in order to send notifications. These tools are in breach of Apple's policy that clearly states "The Apple Push Notification Service provides a high-speed, high-capacity interface, so you should establish and maintain an open connection to handle all your notifications. Connections that are repeatedly opened and closed will affect the performance and stability of your connection to the Apple Push Notification Service and may be considered denial-of-service attacks.".

On the other end, more advanced PHP tools are available that typically run as background processes (daemons) re-using APNS connection socket once connected. Tools I've come across so far were either overcomplicated or didn't provide enough flexibility to separate out a business logic behind sending push notifications.

SAPNS server is my solution to address those two issues. It favours simplicity over complexity allowing you to implement your own business logic behind sending push notifications in an elegant and simple manner.

## Requirements

PHP 5.3.0+, access to the firewall/port forwarding, own dedicated server is recommended.

## How does it work

SAPNS server application is started with an anonymous push delegate function which encapsulates your business logic behind sending push notifications.
The life-cycle of the SAPNS server application can be explained in the following steps :

* (S state) SAPNS server starts up (or restarts itself)
* Connection to the APNS is established
* (L state) SAPNS server listens for incoming connections
* Client connects to SAPNS server
* SAPNS server blocks the socket so no other clients can connect to it at the same time (this is intentional by design)
* SAPNS server executes a user defined push delegate function in order to send push notifications
* Push delegate function should return a boolean value. TRUE on success (ie notifications were sent OK, database was updated etc), otherwise FALSE (something went wrong)
* What SAPNS server will do next depends on the previous step. If a push delegate function returns TRUE, SAPNS server will get back to the (L state). If a push delegate function returns FALSE (ie due to broken APNS pipe), SAPNS server will attempt to restart itself establishing a fresh connection pipe with APNS - (S state).

## Working example

For a full working example refer to daemon.php file. This section explains three simple steps required to get SAPNS server up and running quickly.

### Initial configuration

Before we can start SAPNS server we need to set it up first. The following configuration variables are used in example code :
* APNS_HOST - Apple Push Notification Service host - SAPNS server will attempt to connect to it via the port defined below
* APNS_PORT - Apple Push Notification Service port
* APNS_CERT - A filename pointing to the .pem certificate bundle
* SERVER_PORT - SAPNS server will listen on this port in order to accept incoming connections

<pre><code>/* for live environment use gateway.push.apple.com */
define("APNS_HOST", "gateway.sandbox.push.apple.com");

/* make sure this port is open on your server (TCP OUT) */
define("APNS_PORT", "2195");

/* make sure certificate can be read by the script */
define("APNS_CERT", "apns-cert.pem");

/* make sure this port is open on your server (TCP IN/OUT) or forwarded to allow external connections */
define("SERVER_PORT", 6000);
</code></pre>

### Push delegate function

This is where you implement and encapsulate your business logic behind sending push notifications. Push delegate function will be called every time an incoming connection to SAPNS server is established. SAPNS server will automatically block any other connections until push delegate function finishes its job. 

IMPORTANT. Push delegate function must return a boolean value. SAPNS server will restart itself if anything other than boolean TRUE is returned.

<pre><code>
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

</code></pre>

### Starting up

So, it's time to start SAPNS server up. This couldn't be easier.

<pre><code>
$apns = new SAPNS\SimpleAPNS();

$apns->setAPNSHost(APNS_HOST)
     ->setApnsPort(APNS_PORT)
     ->setApnsCert(APNS_CERT)
     ->setServerPort(SERVER_PORT)
     ->run($push_delegate);

</code></pre>


## Running in a background

## Connecting to SAPNS server

## Licence

The MIT License (MIT) http://opensource.org/licenses/MIT
Copyright (c) 2013 Marcin Dominas, www.marcindominas.com
