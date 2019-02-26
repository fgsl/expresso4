<?php
	use Expresso\Core\GlobalService;

	/************************************************************************************************\
	* Administracao de Listas (baseadas no Mailman)							*
	* by Rommel de Brito Cysne (rommel.cysne@serpro.gov.br, rommel.cysne@gmail.com)			*
	* ----------------------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it			*
	*  under the terms of the GNU General Public License as published by the			*
	*  Free Software Foundation; either version 2 of the License, or (at your			*
	*  option) any later version.									*
	\************************************************************************************************/

	GlobalService::get('phpgw_info') = array();
	GlobalService::get('phpgw_info')['flags']['currentapp'] = 'listAdmin';
	include('../header.inc.php');

	$c = CreateObject('phpgwapi.config','listAdmin');
	$c->read_repository();
	$current_config = $c->config_data;
	
	$_SESSION['phpgw_info']['expresso']['user'] = GlobalService::get('phpgw_info')['user'];
	$_SESSION['phpgw_info']['expresso']['server'] = GlobalService::get('phpgw_info')['server'];
	$_SESSION['phpgw_info']['expresso']['listAdmin'] = $current_config;
	$_SESSION['phpgw_info']['expresso']['global_denied_users'] = GlobalService::get('phpgw_info')['server']['global_denied_users'];
	$_SESSION['phpgw_info']['expresso']['global_denied_groups'] = GlobalService::get('phpgw_info')['server']['global_denied_groups'];
	
	$template = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
	$template->set_file(Array('listAdmin' => 'index.tpl'));
	$template->set_block('listAdmin','body');
	

	$exibir = 'inline';
	$ocultar = 'none';

	$var = Array(
		'lang_email_lists'              => lang('Email Lists'),
		'msg'                           => $msg,
		'exibir'                        => $exibir,
		'ocultar'                       => $ocultar
	);
	$template->set_var($var);
	$template->pfp('out','body');

//	$obj = CreateObject('listAdmin.uimaillists');
//	$obj->list_maillists();
	
	GlobalService::get('phpgw')->common->phpgw_footer();
?>
