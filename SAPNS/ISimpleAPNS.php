<?php

/**
* Simple APNS
*
* @link https://github.com/mdominas/SAPNS
* @copyright Copyright (c) 2013 Marcin Dominas
* @author Marcin Dominas, www.marcindominas.com
* @license The MIT License (MIT) http://opensource.org/licenses/MIT
*/

namespace SAPNS;

interface ISimpleAPNS
{

	public function run($delegate = null);
	public function push($device_id = "", $message = "");

}