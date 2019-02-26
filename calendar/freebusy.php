<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* eGroupWare - freebusy times as iCals                                     *
	* http://www.egroupware.org                                                *
	* Written by RalfBecker@outdoor-training.de                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/


	GlobalService::get('phpgw_info') = array(
		'flags' => array(
			'currentapp' => 'calendar',
			'noheader'   => True,
			'nofooter'   => True,
		),
	);
	// check if we are loged in, by checking sessionid and kp3, as the sessionid get set automaticaly by php for php4-sessions
	$sessionid = isset($_COOKIE['sessionid']) ? $_COOKIE['sessionid'] : @$_GET['sessionid'];
	$kp3 = isset($_COOKIE['kp3']) ? $_COOKIE['kp3'] : @$_GET['kp3'];

	if (!($loged_in = $sessionid && $kp3))
	{
		GlobalService::get('phpgw_info')['flags']['currentapp'] = 'login';
		GlobalService::get('phpgw_info')['flags']['noapi'] = True;
	}
	include ('../header.inc.php');

	function fail_exit($msg)
	{
		echo "<html>\n<head>\n<title>$msg</title>\n<meta http-equiv=\"content-type\" content=\"text/html; charset=".
			GlobalService::get('phpgw')->translation->charset()."\" />\n</head>\n<body><h1>$msg</h1>\n</body>\n</html>\n";

		GlobalService::get('phpgw')->common->phpgw_exit();
	}

	if (!$loged_in)
	{
		include ('../phpgwapi/inc/functions.inc.php');
		GlobalService::get('phpgw_info')['flags']['currentapp'] = 'calendar';
	}
	$user  = is_numeric($_GET['user']) ? (int) $_GET['user'] : GlobalService::get('phpgw')->accounts->name2id($_GET['user']);

	if (!($username = GlobalService::get('phpgw')->accounts->id2name($user)))
	{
		fail_exit(lang("freebusy: Unknow user '%1', wrong password or not availible to not loged in users !!!",$_GET['user']));
	}
	if (!$loged_in)
	{
		GlobalService::get('phpgw')->preferences->account_id = $user;
		GlobalService::get('phpgw_info')['user']['preferences'] = GlobalService::get('phpgw')->preferences->read_repository();
		GlobalService::get('phpgw_info')['user']['account_id'] = $user;
		GlobalService::get('phpgw_info')['user']['account_lid'] = $username;

		$cal_prefs = &GlobalService::get('phpgw_info')['user']['preferences']['calendar'];
		if (!$cal_prefs['freebusy'] || !empty($cal_prefs['freebusy_pw']) && $cal_prefs['freebusy_pw'] != $_GET['password'])
		{
			fail_exit(lang("freebusy: Unknow user '%1', wrong password or not availible to not loged in users !!!",$_GET['user']));
		}
	}
	ExecMethod('calendar.boicalendar.freebusy');
