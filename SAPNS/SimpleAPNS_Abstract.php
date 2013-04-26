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

namespace SAPNS;
use SAPNS\SimpleAPNS_Exception;

abstract class SimpleAPNS_Abstract implements ISimpleAPNS
{
	/**
	* Socket resource for APNS connection
	*
	* @var resource
	*/	
	protected $apns_socket;

	/**
	* Socket resource for a local server
	*
	* @var resource
	*/	
	protected $server_socket;

	/**
	* APNS host
	*
	* @var string
	*/		
	public $apns_host;

	/**
	* APNS connection port
	*
	* @var int
	*/	
	public $apns_port;

	/**
	* Filename of PEM certificate
	*
	* @var string
	*/		
	public $apns_cert;

	/**
	* Listening port for a local server
	*
	* @var resource
	*/	
	public $server_port;

	 /**
	* Setter for APNS host
	*
	* @param string $host
	* @return SimpleAPNS_Abstract
	* @throws SimpleAPNS_Exception
	*/	
	public function setApnsHost($host = null)
	{
		if (!$host)
		{
			throw new SimpleAPNS_Exception("Invalid APNS host", 10);
		}		
		$this->apns_host = $host;
		return $this;
	}

	 /**
	* Setter for APNS port
	*
	* @param int $port
	* @return SimpleAPNS_Abstract
	* @throws SimpleAPNS_Exception
	*/	
	public function setApnsPort($port = null)
	{
		if (!is_numeric($port))
		{
			throw new SimpleAPNS_Exception("Invalid APNS port number", 10);
		}
		$this->apns_port = $port;
		return $this;
	}

	 /**
	* Setter for APNS certificate file
	*
	* @param string $cert
	* @return SimpleAPNS_Abstract
	* @throws SimpleAPNS_Exception
	*/
	public function setApnsCert($cert = null)
	{
		if (!@file_get_contents($cert))
		{
			throw new SimpleAPNS_Exception("Certificate not found", 20);
		}
		$this->apns_cert = $cert;
		return $this;
	}

	 /**
	* Setter for a local server port
	*
	* @param int $port
	* @return SimpleAPNS_Abstract
	* @throws SimpleAPNS_Exception
	*/		
	public function setServerPort($port = null) 
	{
		if (!is_numeric($port)) 
		{
			throw new SimpleAPNS_Exception("Invalid port number", 30);
		}
		$this->server_port = $port;
		return $this;
	}

	 /**
	* This method does multiple things in the following order :
	* 1) Sets maximum execution time to infinite.
	* 2) Creates the main server's loop.
	* 3) Attempts to create a local server.
	* 4) Attempts to connect to APNS.
	* 5) Starts listening for incoming connections. 
	*	 Blocking socket's type nature ensures only one client's connection will be served at any given time
	* 6) Once connection to the local server is established an anonymous delegate function will be called passing 
	*	 SimpleAPNS_Abstract object to it. A delegate function should be used to encapsulate your business logic behind 
	*	 sending push notifications. Server will be restarted if delegate function returns FALSE.
	*	 Restarting server will be required when connection to the APNS is lost or broken.
	*
	* @param anonymous function $delegate
	* @return void
	*/	
	public function run($delegate = null) 
	{

		set_time_limit(0);

		while (true) 
		{
			if (null === $this->server_socket) 
			{
				self::log("Creating server");
				$this->server_socket = @stream_socket_server("tcp://0.0.0.0:".$this->server_port, $errno, $errstr);
			}

			if (!$this->server_socket) 
			{
				self::log("Unable to create server : $errstr ($errno)");
				$this->server_socket = null;
			  	sleep(mt_rand(5, 15));
			} else
			{
				self::log("Server's up... Attempting to connect to APNS now");
				if (!$this->connect())
				{
					 self::log("Error occured while trying to connect to APNS");
					 sleep(mt_rand(5, 15));
				} else 
				{
					self::log("Connected to APNS...");
					while ($client = stream_socket_accept($this->server_socket, -1))
					{
						$result = $delegate($this);

						if (!$result) 
						{
							fwrite($client, 'Broken APNS pipe... restarting server');
							self::disconnect($client);
							break;
						}

						fwrite($client, 'SAPNS alive...');
						self::disconnect($client);
					}

					self::log("Server has stopped listening");
					self::disconnect($this->apns_socket);
					self::disconnect($this->server_socket);
					$this->server_socket = null;
					sleep(mt_rand(5, 15));

				}
			} 
		}
	}

	 /**
	* Sends the payload to APNS via an active APNS socket. 
	* Push notification $message will be delivered to $device_id passed
	*
	* @param string $device_id
	* @param string $message
	* @return boolean true on success otherwise false
	*/		
	public function push($device_id = "", $message = "") 
	{
		if (!is_resource($this->apns_socket)) return false;
		$payload['aps'] = array('alert' => $message, 'sound' => 'default');
		$payload = json_encode($payload);
		$msg = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $device_id)) . chr(0) . chr(strlen($payload)) . $payload;
		return fwrite($this->apns_socket, $msg) === false ? false : true;
	}

	 /**
	* Simple event's log via stdout
	*
	* @param string $message
	* @return void
	*/
	public static function log($message = null) 
	{
		echo sprintf("%s - %s\n", date("Y-m-d H:i:s"), $message);
	}	

	 /**
	* Connects to APNS 
	*
	* @return boolean true on success otherwise false
	*/
	protected function connect()
	{
		$streamContext = stream_context_create();
		stream_context_set_option($streamContext, 'ssl', 'local_cert', $this->apns_cert);
		$this->apns_socket = @stream_socket_client('ssl://' . $this->apns_host . ':' . $this->apns_port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $streamContext);
		if ($errstr) 
		{
			self::log("Unable to connect : $errstr ($errno)");
		}
		return is_resource($this->apns_socket) ? true : false;
	}	

	 /**
	* Closes active socket connection
	*
	* @param resource $socket
	* @return void
	*/	
	protected static function disconnect($socket = null) 
	{
		if (is_resource($socket)) 
		{
			fclose($socket);
		}
	}

}