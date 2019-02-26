<?php
  use Expresso\Core\GlobalService;

  /**************************************************************************\
  * eGroupWare API - phpgwapi footer                                         *
  * This file written by Dan Kuykendall <seek3r@phpgroupware.org>            *
  * and Joseph Engo <jengo@phpgroupware.org>                                 *
  * Closes out interface and db connections                                  *
  * Copyright (C) 2000, 2001 Dan Kuykendall                                  *
  * -------------------------------------------------------------------------*
  * This library is part of the eGroupWare API                               *
  * http://www.egroupware.org/api                                            * 
  * ------------------------------------------------------------------------ *
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


	$d1 = strtolower(substr(PHPGW_APP_INC,0,3));
	if($d1 == 'htt' || $d1 == 'ftp')
	{
		echo "Failed attempt to break in via an old Security Hole!<br>\n";
		exit;
	} unset($d1);

	/**************************************************************************\
	* Include the apps footer files if it exists                               *
	\**************************************************************************/
	if (PHPGW_APP_INC != PHPGW_API_INC &&	// this prevents an endless inclusion on the homepage 
		                                	// (some apps set currentapp in hook_home => it's not releyable)
		(file_exists (PHPGW_APP_INC . '/footer.inc.php') || isset($_GET['menuaction'])) &&
		GlobalService::get('phpgw_info')['flags']['currentapp'] != 'home' &&
		GlobalService::get('phpgw_info')['flags']['currentapp'] != 'login' &&
		GlobalService::get('phpgw_info')['flags']['currentapp'] != 'logout' &&
		!@GlobalService::get('phpgw_info')['flags']['noappfooter'])
	{
		if ($_GET['menuaction'])
		{
			list($app,$class,$method) = explode('.',$_GET['menuaction']);
			if (is_array(GlobalService::get($class)->public_functions) && isset(GlobalService::get($class)->public_functions['footer']))
			{
//				eval("\GlobalService::get($class)->footer();");
				GlobalService::get($class)->footer();
			}
			elseif(file_exists(PHPGW_APP_INC.'/footer.inc.php'))
			{
				include(PHPGW_APP_INC . '/footer.inc.php');
			}
		}
		elseif(file_exists(PHPGW_APP_INC.'/footer.inc.php'))
		{
			include(PHPGW_APP_INC . '/footer.inc.php');
		}
	}
	if (GlobalService::get('phpgw_info')['flags']['need_footer'])
	{
		echo GlobalService::get('phpgw_info')['flags']['need_footer'];
	}
	if(function_exists('parse_navbar_end'))
	{
		parse_navbar_end();
	}
	if (DEBUG_TIMER)
	{
		GlobalService::set('debug_timer_stop',perfgetmicrotime());
		echo 'Page loaded in ' . (GlobalService::get('debug_timer_stop') - GlobalService::get('debug_timer_start')) . ' seconds.';
	}