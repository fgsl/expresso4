<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* eGroupWare                                                               *
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
		$file = array();

		if (! GlobalService::get('phpgw')->acl->check('site_config_access',1,'admin'))
		{
			$file['Site Configuration']         = GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiconfig.index&appname=admin');
		}

		if (! GlobalService::get('phpgw')->acl->check('applications_access',1,'admin'))
		{
			$file['Applications']               = GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiapplications.get_list');
		}

		if (! GlobalService::get('phpgw')->acl->check('global_categories_access',1,'admin'))
		{
			$file['Global Categories']          = GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uicategories.index');
		}

		if (!GlobalService::get('phpgw')->acl->check('mainscreen_message_access',1,'admin') || !GlobalService::get('phpgw')->acl->check('mainscreen_message_access',2,'admin'))
		{
			$file['Change Main Screen Message'] = GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uimainscreen.index');
		}

		if (! GlobalService::get('phpgw')->acl->check('current_sessions_access',1,'admin'))
		{
			$file['View Sessions'] = GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uicurrentsessions.list_sessions');
		}

		if (! GlobalService::get('phpgw')->acl->check('access_log_access',1,'admin'))
		{
			$file['View Access Log'] = GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiaccess_history.list_history');
		}

		if (! GlobalService::get('phpgw')->acl->check('error_log_access',1,'admin'))
		{
			$file['View Error Log']  = GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uilog.list_log');
		}

		if (! GlobalService::get('phpgw')->acl->check('applications_access',16,'admin'))
		{
			$file['Find and Register all Application Hooks'] = GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiapplications.register_all_hooks');
		}

		if (! GlobalService::get('phpgw')->acl->check('asyncservice_access',1,'admin'))
		{
			$file['Asynchronous timed services'] = GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiasyncservice.index');
		}

		if (! GlobalService::get('phpgw')->acl->check('info_access',1,'admin'))
		{
			$file['phpInfo']         = "javascript:openwindow('" . GlobalService::get('phpgw')->link('/admin/phpinfo.php') . "')";
		}

		display_sidebox($appname,$menu_title,$file);
	}
