<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* phpGroupWare                                                             *
	* http://www.phpgroupware.org                                              *
	* Written by Joseph Engo <jengo@phpgroupware.org>                          *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/


	$phpgw_info = array();
	GlobalService::get('phpgw_info')['flags'] = array(
		'disable_Template_class' => True,
		'currentapp'             => 'logout',
		'noheader'               => True,
		'nofooter'               => True,
		'nonavbar'               => True
	);
	include(dirname( __FILE__ ).'/header.inc.php');

	GlobalService::set('sessionid', get_var('sessionid',array('GET','COOKIE')));
	GlobalService::set('kp3', get_var('kp3',array('GET','COOKIE')));
	$account_id = GlobalService::get('phpgw_info')['user']['account_id'];
	$verified = GlobalService::get('phpgw')->session->verify();
	if ($verified)
	{
		if (file_exists(GlobalService::get('phpgw_info')['server']['temp_dir'] . SEP . GlobalService::get('sessionid')))
		{
			$dh = opendir(GlobalService::get('phpgw_info')['server']['temp_dir'] . SEP . GlobalService::get('sessionid'));
			while ($file = readdir($dh))
			{
				if ($file != '.' && $file != '..')
				{
					unlink(GlobalService::get('phpgw_info')['server']['temp_dir'] . SEP . GlobalService::get('sessionid') . SEP . $file);
				}
			}
			rmdir(GlobalService::get('phpgw_info')['server']['temp_dir'] . SEP . GlobalService::get('sessionid'));
		}
		GlobalService::get('phpgw')->hooks->process('logout');
		GlobalService::get('phpgw')->session->destroy(GlobalService::get('sessionid'),GlobalService::get('kp3'));
	}
	else
	{	
		if(is_object(GlobalService::get('phpgw')->log) && $account_id != '')
		{
			GlobalService::get('phpgw')->log->write(array(
				'text' => 'W-VerifySession, could not verify session during logout',
				'line' => __LINE__,
				'file' => __FILE__
			));
		}
	}
	GlobalService::get('phpgw')->session->phpgw_setcookie('sessionid');
	GlobalService::get('phpgw')->session->phpgw_setcookie('kp3');
	GlobalService::get('phpgw')->session->phpgw_setcookie('domain');
	if(GlobalService::get('phpgw_info')['server']['sessions_type'] == 'php4')
	{
		GlobalService::get('phpgw')->session->phpgw_setcookie(PHPGW_PHPSESSID);
	}

	GlobalService::get('phpgw')->redirect(GlobalService::get('phpgw_info')['server']['webserver_url'].'/login.php?cd=1');
?>
