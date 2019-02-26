<?php
	use Expresso\Core\GlobalService;

	/************************************************************************************\
	* Expresso Administra��o                 			                                 *
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)  	 *
	* -----------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it			 *
	*  under the terms of the GNU General Public License as published by the			 *
	*  Free Software Foundation; either version 2 of the License, or (at your			 *
	*  option) any later version.														 *
	\************************************************************************************/

	GlobalService::get('phpgw_info') = array();
	GlobalService::get('phpgw_info')['flags']['currentapp'] = 'expressoAdmin1_2';
	include('../../header.inc.php');

	$template = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
	$template->set_file(Array('expressoadmin' => 'access_denied.tpl'));
	
	$template->set_block('expressoadmin','main');
	
	$var = Array(
		//'lang_access_denied' => lang('You do not have access to this module') . '.',
		'lang_access_denied' => lang('You do not have access to this module or you are accessing this module from a denied network') . '.',
		'lang_back'	=> lang('Back'),
		'back_url'	=> GlobalService::get('phpgw')->link('/expressoAdmin1_2/index.php')
	);
	$template->set_var($var);
	
	$template->pfp('out','main');
	GlobalService::get('phpgw')->common->phpgw_footer();	
?>