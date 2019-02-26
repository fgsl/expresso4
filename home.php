<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* eGroupWare                                                               *
	* http://www.egroupware.org                                                *
	* The file written by Joseph Engo <jengo@phpgroupware.org>                 *
	* This file modified by Greg Haygood <shrykedude@bellsouth.net>            *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/
	$phpgw_info = array();
	$current_url = substr($_SERVER["SCRIPT_NAME"], 0, strpos($_SERVER["SCRIPT_NAME"],'home.php'));

	if (!is_file('header.inc.php'))
	{
		Header('Location: '.$current_url.'setup/index.php');
		exit;
	}

	GlobalService::set('sessionid', @$_GET['sessionid'] ? $_GET['sessionid'] : @$_COOKIE['sessionid']);
	if (!isset(GlobalService::get('sessionid')) || !GlobalService::get('sessionid'))
	{
		Header('Location: '.$current_url.'login.php?cd=10');
		exit;
	}

	GlobalService::get('phpgw_info')['flags'] = array(
		'noheader'                => True,
		'nonavbar'                => True,
		'currentapp'              => 'home',
		'enable_network_class'    => True,
		'enable_contacts_class'   => True,
		'enable_nextmatchs_class' => True
	);
	
	include('header.inc.php');
	
	if ( isset( GlobalService::get('phpgw_info')['server']['display_widgets_only']) && trim(GlobalService::get('phpgw_info')['server']['display_widgets_only']) !== '')
	{
		$display_widgets_only = GlobalService::get('phpgw_info')['server']['display_widgets_only'];
		$display_widgets_only = ( !is_array($display_widgets_only) ) ? explode(";", $display_widgets_only) : $display_widgets_only;
		$account_dn = trim(strtolower(GlobalService::get('phpgw_info')['user']['account_dn']));
		foreach ( $display_widgets_only as $value)
		{
			if( stripos( $account_dn, $value . "," ) !== false )
			{
				$file_widgets =  "widgets/index.php";

				if (file_exists("./" . $file_widgets)) {

						if (GlobalService::get('phpgw_info')['server']['use_https'] != '0') {
							$url_add = "https://";
						} else {
							$url_add = "http://";
						}

						$redirect_url = $url_add . $_SERVER['SERVER_NAME'] . "/" . $file_widgets;
						Header('Location: '.$redirect_url);
						exit;
				} 
			} 
		}
	}

	GlobalService::get('phpgw_info')['flags']['app_header']=lang('home');

	if (GlobalService::get('phpgw_info')['server']['force_default_app'] && GlobalService::get('phpgw_info')['server']['force_default_app'] != 'user_choice')
	{
		GlobalService::get('phpgw_info')['user']['preferences']['common']['default_app'] = GlobalService::get('phpgw_info')['server']['force_default_app'];
	}

	if ($_GET['cd']=='yes' && GlobalService::get('phpgw_info')['user']['preferences']['common']['default_app'] &&
		GlobalService::get('phpgw_info')['user']['apps'][GlobalService::get('phpgw_info')['user']['preferences']['common']['default_app']])
	{
		GlobalService::get('phpgw')->redirect(GlobalService::get('phpgw')->link('/' . GlobalService::get('phpgw_info')['user']['preferences']['common']['default_app'] . '/' . 'index.php'));
	}
	else
	{

		GlobalService::get('phpgw')->common->phpgw_header();
		echo parse_navbar();
	}

	// Default Applications (Home Page) 
	$default_apps = Array(			
			'workflow',			
			'expressoMail1_2',
			'calendar',
			'news_admin'
		);
	$sorted_apps = array();
	$user_apps = GlobalService::get('phpgw_info')['user']['apps']; 
	@reset($user_apps);	
	for($i = 0; $i < count($default_apps);$i++) {
		if(array_key_exists($default_apps[$i], $user_apps)){
			$sorted_apps[] = $default_apps[$i];
		}		
	}
	
	foreach(GlobalService::get('phpgw_info')['user']['apps'] as $i => $p) {
		$sorted_apps[] = $p['name'];
	}
	
	$portal_oldvarnames = array('mainscreen_showevents', 'homeShowEvents','homeShowLatest','mainscreen_showmail','mainscreen_showbirthdays','mainscreen_show_new_updated');
	$done = array();
	// Display elements, within appropriate table cells	
	@reset($sorted_apps);
	$idx = 1;
	echo "<table width='100%' cellpadding=5>";
	foreach($sorted_apps as $appname)
	{
		if((int)$done[$appname] == 1 || empty($appname)){
			continue;
		}		
		$varnames = $portal_oldvarnames;
		$varnames[] = 'homepage_display';
		$thisd = 0;
		$tmp = '';
		
		foreach($varnames as $varcheck)
		{
										

			if(array_search($appname, $default_apps) !== False){
				$thisd = 1;
				break;
			}
			if(GlobalService::get('phpgw_info')['user']['preferences'][$appname][$varcheck]=='True') {
				$thisd = 1;
				break;
			}
			else  {
				$_thisd = (int)GlobalService::get('phpgw_info')['user']['preferences'][$appname][$varcheck];
				if($_thisd > 0) {
					$thisd = $_thisd;
					break;
				}
			}
		}

		if($thisd > 0)
		{
			if($tmp) {
               	$appname = $tmp;
				$tmp = '';
			}
			if($idx == 0) {				
				print '<tr>';
			}
			print '<td style="vertical-align:top;" width="45%">';
			GlobalService::get('phpgw')->hooks->single('home',$appname);			
			print '</td>';
			
			if($idx == 2){
				$idx = 0;
				print '</tr>';
			}			
			$idx++;
			$neworder[] = $appname;
		}
		$done[$appname] = 1;
	} 
	print '</table>';
	GlobalService::get('phpgw')->common->phpgw_footer();
?>
