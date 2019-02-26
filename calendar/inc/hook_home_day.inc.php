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


	$d1 = strtolower(substr(PHPGW_APP_INC,0,3));
	if($d1 == 'htt' || $d1 == 'ftp' )
	{
		echo 'Failed attempt to break in via an old Security Hole!<br>'."\n";
		GlobalService::get('phpgw')->common->phpgw_exit();
	}
	unset($d1);

	GlobalService::set('extra_data',GlobalService::get('css')."\n".'<td>'."\n".'<table border="0" cols="3"><tr><td align="center" width="35%" valign="top">')
		. ExecMethod('calendar.uicalendar.mini_calendar',
			Array(
				'day'		=> GlobalService::get('g_day'),
				'month'	=> GlobalService::get('g_month'),
				'year'	=> GlobalService::get('g_year'),
				'link'	=> 'day'
			)
		).'</td><td align="center"><table border="0" width="100%" cellspacing="0" cellpadding="0">'
		. '<tr><td align="center">'.ExecMethod('calendar.bocalendar.long_date',time())
		.'</td></tr><tr><td bgcolor="'.GlobalService::get('phpgw_info')['theme']['bg_text']
		.'" valign="top">'.ExecMethod('calendar.uicalendar.print_day',
			Array(
				'year'	=> GlobalService::get('g_year'),
				'month'	=> GlobalService::get('g_month'),
				'day'		=> GlobalService::get('g_day')
			)
		).'</td></tr></table>'."\n".'</td>'."\n".'</tr>'."\n".'</table>'."\n".'</td>'."\n";
?>
