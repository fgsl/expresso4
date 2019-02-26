<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* eGroupWare                                                               *
	* http://www.egroupware.org                                                *
	* Written by Joseph Engo <jengo@phpgroupware.org>                          *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

{
// Only Modify the $file and $title variables.....
	$title = $appname;
	$file = Array(
		'Site Configuration' => GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiconfig.index&appname=' . $appname),
		'Custom fields and sorting' => GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uicustom_fields.index'),
		'Calendar Holiday Management' => GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uiholiday.admin'),
		'Import CSV-File' => GlobalService::get('phpgw')->link('/calendar/csv_import.php'),
		'Global Categories' => GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uicategories.index&appname=calendar'),
		'Grant Access by Group' => GlobalService::get('phpgw')->link('/index.php','menuaction=calendar.uigroup_access.index')
	);
//Do not modify below this line
	display_section($appname,$title,$file);
}
?>
