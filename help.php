<?php

use Expresso\Core\GlobalService;

GlobalService::get('phpgw_info')['flags'] = array(
	'disable_Template_class' => True,
	'login'                  => True,
	'currentapp'             => 'login',
	'noheader'               => True
);
	require_once('./header.inc.php');
	GlobalService::get('phpgw_info')['server']['template_dir'] = PHPGW_SERVER_ROOT . '/phpgwapi/templates/' . GlobalService::get('phpgw_info')['login_template_set'];
	$tmpl = CreateObject('phpgwapi.Template', GlobalService::get('phpgw_info')['server']['template_dir']);
	$tmpl->set_file(array('login_form' => 'help.tpl'));
	$tmpl->set_var('website_title', GlobalService::get('phpgw_info')['server']['site_title']);
    $tmpl->set_var('template_set',GlobalService::get('phpgw_info')['login_template_set']);
	GlobalService::get('phpgw')->translation->init();
	GlobalService::get('phpgw')->translation->add_app('loginhelp');
	GlobalService::get('phpgw')->translation->add_app('loginhelp',$_GET['lang']);
	if(lang('loginhelp_message') != 'loginhelp_message*')
	{
		$tmpl->set_var('login_help',lang('loginhelp_message'));
	}
	$tmpl->pfp('loginout','login_form');
?>

