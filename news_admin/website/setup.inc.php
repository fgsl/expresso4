<?php
  use Expresso\Core\GlobalService;

  /**************************************************************************\
  * eGroupWare - Webpage news admin                                          *
  * http://www.egroupware.org                                                *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  * --------------------------------------------                             *
  * This program was sponsered by Golden Glair productions                   *
  * http://www.goldenglair.com                                               *
  \**************************************************************************/


	$path_to_header = '../../';
	$template_path  = $path_to_header . 'news_admin/website/templates/';
	$domain         = 'default';

	/* ********************************************************************\
	* Don't change anything after this line                                *
	\******************************************************************** */

	error_reporting(error_reporting() & ~E_NOTICE);

	function copyobj($a,&$b)
	{
		if(floor(phpversion()) > 4)
		{
			$b = $a->__clone();
		}
		else
		{
			$b = $a;
		}
		return;
	}

	GlobalService::get('phpgw_info')['flags']['noapi'] = True;
	include($path_to_header . 'header.inc.php');
	include(PHPGW_SERVER_ROOT . '/phpgwapi/inc/class.Template.inc.php');
	$tpl = new Template($template_path);
	include(PHPGW_SERVER_ROOT . '/phpgwapi/inc/class.db_egw.inc.php');

	GlobalService::get('phpgw')->db = new db_egw();
	GlobalService::get('phpgw')->db->Host     = GlobalService::get('phpgw_domain')[$domain]['server']['db_host'];
	GlobalService::get('phpgw')->db->Type     = GlobalService::get('phpgw_domain')[$domain]['db_type'];
	GlobalService::get('phpgw')->db->Database = GlobalService::get('phpgw_domain')[$domain]['db_name'];
	GlobalService::get('phpgw')->db->User     = GlobalService::get('phpgw_domain')[$domain]['db_user'];
	GlobalService::get('phpgw')->db->Password = GlobalService::get('phpgw_domain')[$domain]['db_pass'];

	include(PHPGW_SERVER_ROOT . '/news_admin/inc/class.sonews.inc.php');
	$news_obj = new sonews();

	include(PHPGW_SERVER_ROOT . '/news_admin/inc/class.soexport.inc.php');
	$export_obj = new soexport();
