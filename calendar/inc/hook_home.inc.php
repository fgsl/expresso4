<?php
  use Expresso\Core\GlobalService;

  /**************************************************************************\
  * eGroupWare - Calendar                                                    *
  * http://www.egroupware.org                                                *
  * Based on Webcalendar by Craig Knudsen <cknudsen@radix.net>               *
  *          http://www.radix.net/~cknudsen                                  *
  * Written by Mark Peters <skeeter@phpgroupware.org>                        *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/
	$phpgw_flags = Array(
		'currentapp'	=>	'calendar',
		'noheader'	=>	True,
		'nonavbar'	=>	True,
		'noappheader'	=>	True,
		'noappfooter'	=>	True,
		'nofooter'	=>	True
	);

	GlobalService::get('phpgw_info')['flags'] = $phpgw_flags;

	$d1 = strtolower(substr(PHPGW_APP_INC,0,3));
	if($d1 == 'htt' || $d1 == 'ftp' )
	{
		echo 'Failed attempt to break in via an old Security Hole!<br>'."\n";
		GlobalService::get('phpgw')->common->phpgw_exit();
	}
	unset($d1);

	$showevents = (int)GlobalService::get('phpgw_info')['user']['preferences']['calendar']['mainscreen_showevents'];
	if($showevents>0)
	{
		GlobalService::get('phpgw')->translation->add_app('calendar');
		if(!is_object(GlobalService::get('phpgw')->datetime))
		{
			GlobalService::get('phpgw')->datetime = CreateObject('phpgwapi.date_time');
		}

		GlobalService::set('date',date('Ymd',GlobalService::get('phpgw')->datetime->users_localtime));
		GlobalService::set('g_year',substr(GlobalService::get('date'),0,4));
		GlobalService::set('g_month',substr(GlobalService::get('date'),4,2));
		GlobalService::set('g_day',substr(GlobalService::get('date'),6,2));
		GlobalService::set('owner',GlobalService::get('phpgw_info')['user']['account_id']);
		GlobalService::set('css',"\n".'<style type="text/css">'."\n".'<!--'."\n"
			. ExecMethod('calendar.uicalendar.css').'-->'."\n".'</style>');

		if($showevents==2)
		{
			$_page = "small";
		}
		else
		{
			$page_ = explode('.',GlobalService::get('phpgw_info')['user']['preferences']['calendar']['defaultcalendar']);
			$_page = substr($page_[0],0,7);	// makes planner from planner_{user|category}
			if ($_page=='index' || ($_page != 'day' && $_page != 'week' && $_page != 'month' && $_page != 'year' && $_page != 'planner'))
			{
				$_page = 'month';
//			GlobalService::get('phpgw')->preferences->add('calendar','defaultcalendar','month');
//			GlobalService::get('phpgw')->preferences->save_repository();
			}
		}

		if(!@file_exists(PHPGW_INCLUDE_ROOT.'/calendar/inc/hook_home_'.$_page.'.inc.php'))
		{
			$_page = 'day';
		}
		include(PHPGW_INCLUDE_ROOT.'/calendar/inc/hook_home_'.$_page.'.inc.php');

		$title = lang('Calendar');

		$portalbox = CreateObject('phpgwapi.listbox',
			Array(
				'title'	=> $title,
				'primary'	=> GlobalService::get('phpgw_info')['theme']['navbar_bg'],
				'secondary'	=> GlobalService::get('phpgw_info')['theme']['navbar_bg'],
				'tertiary'	=> GlobalService::get('phpgw_info')['theme']['navbar_bg'],
				'width'	=> '100%',
				'outerborderwidth'	=> '0',
				'header_background_image'	=> GlobalService::get('phpgw')->common->image('phpgwapi/templates/default','bg_filler')
			)
		);

		$app_id = GlobalService::get('phpgw')->applications->name2id('calendar');
		GlobalService::get('portal_order')[] = $app_id;
		$var = Array(
			'up'	=> Array('url'	=> '/set_box.php', 'app'	=> $app_id),
			'down'	=> Array('url'	=> '/set_box.php', 'app'	=> $app_id),
			'close'	=> Array('url'	=> '/set_box.php', 'app'	=> $app_id),
			'question'	=> Array('url'	=> '/set_box.php', 'app'	=> $app_id),
			'edit'	=> Array('url'	=> '/set_box.php', 'app'	=> $app_id)
		);

		while(list($key,$value) = each($var))
		{
			$portalbox->set_controls($key,$value);
		}

		$portalbox->data = Array();

		echo "\n".'<!-- BEGIN Calendar info -->'."\n".$portalbox->draw(GlobalService::get('extra_data'))."\n".'<!-- END Calendar info -->'."\n";
		unset($cal);
	}
	flush();
	unset($showevents);
?>
