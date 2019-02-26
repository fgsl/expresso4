<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* eGroupWare - administration                                              *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/


	GlobalService::get('phpgw_info')['flags'] = array(
		'noheader'   => True,
		'nonavbar'   => True,
		'currentapp' => 'admin'
	);
	include('../header.inc.php');

	if (GlobalService::get('phpgw')->acl->check('info_access',1,'admin'))
	{
		GlobalService::get('phpgw')->redirect_link('/index.php');
	}

// Throw a little notice out if PHPaccelerator is enabled.
	if(GlobalService::get('_PHPA')['ENABLED'])
	{
		echo 'PHPaccelerator enabled:</br>'."\n";
		echo 'PHPaccelerator Version: '.GlobalService::get('_PHPA')['VERSION'].'</br></p>'."\n";
	}

	phpinfo();
//	$phpgw->common->phpgw_footer();
?>
