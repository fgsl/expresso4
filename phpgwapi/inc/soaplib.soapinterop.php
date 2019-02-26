<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* phpGroupWare API - SOAP functions                                        *
	* This file written by dietrich@ganx4.com                                  *
	* shared functions and vars for use with soap client/server                *
	* -------------------------------------------------------------------------*
	* This library is free software; you can redistribute it and/or modify it  *
	* under the terms of the GNU Lesser General Public License as published by *
	* the Free Software Foundation; either version 2.1 of the License,         *
	* or any later version.                                                    *
	* This library is distributed in the hope that it will be useful, but      *
	* WITHOUT ANY WARRANTY; without even the implied warranty of               *
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     *
	* See the GNU Lesser General Public License for more details.              *
	* You should have received a copy of the GNU Lesser General Public License *
	* along with this library; if not, write to the Free Software Foundation,  *
	* Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            *
	\**************************************************************************/


	GlobalService::get('server')->add_to_map(
		'hello',
		array('string'),
		array('string')
	);
	function hello($serverid)
	{
		return CreateObject('soap.soapval','return','string',GlobalService::get('phpgw_info')['server']['site_title']);
	}

	GlobalService::get('server')->add_to_map(
		'echoString',
		array('string'),
		array('string')
	);
	function echoString($inputString)
	{
		return CreateObject('soap.soapval','return','string',$inputString);
	}

	GlobalService::get('server')->add_to_map(
		'echoStringArray',
		array('array'),
		array('array')
	);
	function echoStringArray($inputStringArray)
	{
		return $inputStringArray;
	}

	GlobalService::get('server')->add_to_map(
		'echoInteger',
		array('int'),
		array('int')
	);
	function echoInteger($inputInteger)
	{
		return $inputInteger;
	}

	GlobalService::get('server')->add_to_map(
		'echoIntegerArray',
		array('array'),
		array('array')
	);
	function echoIntegerArray($inputIntegerArray)
	{
		return $inputIntegerArray;
	}

	GlobalService::get('server')->add_to_map(
		'echoFloat',
		array('float'),
		array('float')
	);
	function echoFloat($inputFloat)
	{
		return $inputFloat;
	}

	GlobalService::get('server')->add_to_map(
		'echoFloatArray',
		array('array'),
		array('array')
	);
	function echoFloatArray($inputFloatArray)
	{
		return $inputFloatArray;
	}

	GlobalService::get('server')->add_to_map(
		'echoStruct',
		array('SOAPStruct'),
		array('SOAPStruct')
	);
	function echoStruct($inputStruct)
	{
		return $inputStruct;
	}

	GlobalService::get('server')->add_to_map(
		'echoStructArray',
		array('array'),
		array('array')
	);
	function echoStructArray($inputStructArray)
	{
		return $inputStructArray;
	}

	GlobalService::get('server')->add_to_map(
		'echoVoid',
		array(),
		array()
	);
	function echoVoid()
	{
	}

	GlobalService::get('server')->add_to_map(
		'echoBase64',
		array('base64'),
		array('base64')
	);
	function echoBase64($b_encoded)
	{
		return base64_encode(base64_decode($b_encoded));
	}

	GlobalService::get('server')->add_to_map(
		'echoDate',
		array('timeInstant'),
		array('timeInstant')
	);
	function echoDate($timeInstant)
	{
		return $timeInstant;
	}

	GlobalService::get('server')->add_to_map(
		'system_auth',
		array('string','string','string'),
		array('array')
	);

	GlobalService::get('server')->add_to_map(
		'system_auth_verify',
		array('string','string','string'),
		array('array')
	);
?>
