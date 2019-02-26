<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* eGroupWare login                                                         *
	* http://www.egroupware.org                                                *
	* Originaly written by Dan Kuykendall <seek3r@phpgroupware.org>            *
	*                      Joseph Engo    <jengo@phpgroupware.org>             *
	* Updated by Nilton Emilio Buhrer Neto <niltonneto@celepar.pr.gov.br>      *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	if( file_exists('mobile.php') ){ include 'mobile.php'; }

	$phpgw_info = array();
	$submit = False;			// set to some initial value

	GlobalService::get('phpgw_info')['flags'] = array(
		'disable_Template_class' => True,
		'login'                  => True,
		'currentapp'             => 'login',
		'noheader'               => True
	);

	if(file_exists('./header.inc.php'))
	{
		include('./header.inc.php');
		// Force location to home, while logged in.
		GlobalService::set('sessionid', @$_GET['sessionid'] ? $_GET['sessionid'] : @$_COOKIE['sessionid']);
		if(isset(GlobalService::get('sessionid')) && $_GET['cd'] != 10)
		{
			if( $_GET['cd'] != '66' )
			{
				GlobalService::get('phpgw')->redirect_forward();
			}
		}

		if (GlobalService::get('phpgw_info')['server']['use_https'] > 0)
		{
			if ($_SERVER['HTTPS'] != 'on')
			{
        		Header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
				exit;
			}
		}

		GlobalService::get('phpgw')->session = CreateObject('phpgwapi.sessions');
	}
	else
	{
		Header('Location: setup/index.php');
		exit;
	}

	GlobalService::get('phpgw_info')['server']['template_dir'] = PHPGW_SERVER_ROOT . '/phpgwapi/templates/' . GlobalService::get('phpgw_info')['login_template_set'];
	$tmpl = CreateObject('phpgwapi.Template', GlobalService::get('phpgw_info')['server']['template_dir']);

	// read the images from the login-template-set, not the (maybe not even set) users template-set
	GlobalService::get('phpgw_info')['user']['preferences']['common']['template_set'] = GlobalService::get('phpgw_info')['login_template_set'];

	$tmpl->set_file(array('login_form' => 'login.tpl'));
	$tmpl->set_var('template',GlobalService::get('phpgw_info')['login_template_set']);
	$tmpl->set_var('lang',$_GET['lang']?$_GET['lang']:preg_replace("/\,.*/","",GlobalService::get('_SERVER')['HTTP_ACCEPT_LANGUAGE']));

	if (count(GlobalService::get('phpgw_info')['server']['login_logo_file']) > 0)
		$tmpl->set_var('logo_config',GlobalService::get('phpgw_info')['server']['login_logo_file']);
	else
		$tmpl->set_var('logo_config','<a title="Governo do Paran&aacute;" href="http://www.pr.gov.br" target="_blank"><img src="phpgwapi/templates/'.GlobalService::get('phpgw_info')['login_template_set'].'/images/logo_govparana_93x70.gif" border="0"></a></td>
		<td><div align="center"><font color="#9a9a9a" face="Verdana, Arial, Helvetica, sans-serif" size="1">
<a title="Companhia de Tecnologia da Informa&ccedil;&atilde;o e Comunica&ccedil;&tilde;o do Paran&aacute;" target="_blank" href="http://www.celepar.pr.gov.br/">
<img src="phpgwapi/templates/'.GlobalService::get('phpgw_info')['login_template_set'].'/images/logo_celepar_104x49.png" border="0"></a>');
	// !! NOTE !!
	// Do NOT and I repeat, do NOT touch ANYTHING to do with lang in this file.
	// If there is a problem, tell me and I will fix it. (jengo)

	// whoooo scaring

	// ServerID => Identify the Apache Frontend.
	if(GlobalService::get('phpgw_info')['server']['usecookies'] == True && GlobalService::get('phpgw_info')['server']['use_frontend_id'])
	{
		GlobalService::get('phpgw')->session->phpgw_setcookie('serverID', GlobalService::get('phpgw_info')['server']['use_frontend_id']);
	}
	session_start();

	include(personalize_include_path('phpgwapi','login'));

	echo "<script>window.localStorage.clear();</script>"; 

?>
