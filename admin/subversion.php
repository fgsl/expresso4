<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* eGroupWare - administration                                              *
	* http://www.egroupware.org                                                *
	* Written by Joseph Engo <jengo@phpgroupware.org>                          *
	* Modified by Stephen Brown <steve@dataclarity.net>                        *
	*  to distribute admin across the application directories                  *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/


	GlobalService::get('phpgw_info') = array();
	GlobalService::get('phpgw_info')['flags']['currentapp'] = 'admin';
	include('../header.inc.php');

	GlobalService::get('admin_tpl') = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
	GlobalService::get('admin_tpl')->set_file(
		Array(
			'admin' => 'index.tpl'
		)
	);

	GlobalService::get('admin_tpl')->set_block('admin','list');
	GlobalService::get('admin_tpl')->set_block('admin','app_row');
	GlobalService::get('admin_tpl')->set_block('admin','app_row_noicon');
	GlobalService::get('admin_tpl')->set_block('admin','link_row');
	GlobalService::get('admin_tpl')->set_block('admin','spacer_row');

	GlobalService::get('admin_tpl')->set_var('title',lang('Administration'));

	// This func called by the includes to dump a row header
	function section_start($appname='',$icon='')
	{
		GlobalService::get('admin_tpl')->set_var('icon_backcolor',GlobalService::get('phpgw_info')['theme']['row_off']);
		GlobalService::get('admin_tpl')->set_var('link_backcolor',GlobalService::get('phpgw_info')['theme']['row_off']);
		GlobalService::get('admin_tpl')->set_var('app_name',GlobalService::get('phpgw_info')['apps'][$appname]['title']);
		GlobalService::get('admin_tpl')->set_var('a_name',$appname);
		GlobalService::get('admin_tpl')->set_var('app_icon',$icon);
		if ($icon)
		{
			GlobalService::get('admin_tpl')->parse('rows','app_row',True);
		}
		else
		{
			GlobalService::get('admin_tpl')->parse('rows','app_row_noicon',True);
		}
	}

	function section_item($pref_link='',$pref_text='')
	{
		GlobalService::get('admin_tpl')->set_var('pref_link',$pref_link);
		GlobalService::get('admin_tpl')->set_var('pref_text',$pref_text);
		GlobalService::get('admin_tpl')->parse('rows','link_row',True);
	}

	function section_end()
	{
		GlobalService::get('admin_tpl')->parse('rows','spacer_row',True);
	}

	function display_section($appname,$file,$file2=False)
	{
		if ($file2)
		{
			$file = $file2;
		}
		if(is_array($file))
		{
			section_start($appname,
				GlobalService::get('phpgw')->common->image(
					$appname,
					Array(
						'navbar',
						$appname,
						'nonav'
					)
				)
			);

			while(list($text,$url) = each($file))
			{
				// If user doesn't have application configuration access, then don't show the configuration links
				if (strpos($url, 'admin.uiconfig') === False || !GlobalService::get('phpgw')->acl->check('site_config_access',1,'admin'))
				{
					section_item($url,lang($text));
				}
			}
			section_end();
		}
	}
        if (file_exists('../infodist/revisoes-svn.php'))
	  {
	       include('../infodist/revisoes-svn.php');
	  }
	else
	  {
	       echo '<div><h4>Dados do subversion n&atilde;o foram localizados.</h4></div>';
	  }
	GlobalService::get('phpgw')->common->phpgw_footer();
?>
