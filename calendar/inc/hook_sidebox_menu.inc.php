<?php
  use Expresso\Core\GlobalService;

  /**************************************************************************\
  * eGroupWare - Calendar's Sidebox-Menu for idots-template                  *
  * http://www.egroupware.org                                                *
  * Written by Pim Snel <pim@lingewoud.nl>                                   *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

{

 /*
	This hookfile is for generating an app-specific side menu used in the idots
	template set.

	$menu_title speaks for itself
	$file is the array with link to app functions

	display_sidebox can be called as much as you like
 */

	$menu_title = GlobalService::get('phpgw_info')['apps'][$appname]['title'] . ' '. lang('Menu');
	$file = Array(
		'New Entry'   => GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uicalendar.add'),
		'_NewLine_', // give a newline
		'Today'=>GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uicalendar.day'),
		'This week'=>GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uicalendar.week'),
		'This month'=>GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uicalendar.month'),
		'This year'=>GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uicalendar.year'),
		'Group Planner'=>GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uicalendar.planner'),
		//'Daily Matrix View'=>GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uicalendar.matrixselect'),
		'_NewLine_', // give a newline
		'Import'=>GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uiicalendar.import'),
		'Report of hours'=>GlobalService::get('phpgw')->link('/calendar/inc/hourReport.php',''),
		'Public Calendars'=> "javascript:openwindow('calendar/templates/".GlobalService::get('phpgw_info')['user']['preferences']['common']['template_set']."/publicView.php')"
	);
	display_sidebox($appname,$menu_title,$file);

	if (GlobalService::get('phpgw_info')['user']['apps']['preferences'])
	{
		$menu_title = lang('Preferences');
		$file = Array(
			'Calendar preferences'=>GlobalService::get('phpgw')->link('/preferences/preferences.php','appname=calendar'),
			'Grant Access'=>GlobalService::get('phpgw')->link('/index.php','menuaction=preferences.uiaclprefs.index&acl_app=calendar'),
			'Edit Categories' =>GlobalService::get('phpgw')->link('/index.php','menuaction=preferences.uicategories.index&cats_app=calendar&cats_level=True&global_cats=True'),
		);
		display_sidebox($appname,$menu_title,$file);
	}

	if (GlobalService::get('phpgw_info')['user']['apps']['admin'])
	{
		$menu_title = lang('Administration');
		$file = Array(
			'Configuration'=>GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiconfig.index&appname=calendar'),
			'Custom Fields'=>GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uicustom_fields.index'),
			'Holiday Management'=>GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uiholiday.admin'),
			'Import CSV-File' => GlobalService::get('phpgw')->link('/calendar/csv_import.php'),
			'Global Categories' =>GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uicategories.index&appname=calendar'),
		);
		display_sidebox($appname,$menu_title,$file);
	}
}
?>
