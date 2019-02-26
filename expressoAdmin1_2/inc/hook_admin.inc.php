<?php
	use Expresso\Core\GlobalService;

	/************************************************************************************\
	* Expresso Administra��o                 										    *
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)   *
	* ----------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it			*
	*  under the terms of the GNU General Public License as published by the			*
	*  Free Software Foundation; either version 2 of the License, or (at your			*
	*  option) any later version.														*
	\************************************************************************************/

	if (! GlobalService::get('phpgw')->acl->check('site_config_access',1,'admin'))
	{
		$file = Array(
			'Global Configuration' => GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiconfig.index&appname=' . $appname),
			'Managers'             => GlobalService::get('phpgw')->link('/index.php','menuaction=expressoAdmin1_2.uimanagers.list_managers'),
			'Radius'               => GlobalService::get('phpgw')->link('/index.php','menuaction=expressoAdmin1_2.uiradius.edit'),
			'Expresso Messenger'   => GlobalService::get('phpgw')->link('/index.php','menuaction=expressoAdmin1_2.uimessenger.edit'),
			'Active Directory'     => GlobalService::get('phpgw')->link('/index.php','menuaction=expressoAdmin1_2.uiad.edit'),
		);
		ksort( $file );
	}
	/* Do not modify below this line */
	display_section($appname,$file);
?>
