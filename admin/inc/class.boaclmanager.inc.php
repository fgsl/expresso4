<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* eGroupWare - Administration                                              *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/


	class boaclmanager
	{
		var $ui;
		var $so;
		var $public_functions = array(
			'submit' => True
		);

		function boaclmanager()
		{
			//$this->so = createobject('admin.soaclmanager');
			$this->ui = createobject('admin.uiaclmanager');
		}

		function submit()
		{
			if (GlobalService::get('cancel'))
			{
				$this->ui->list_apps();
				return False;
			}

			$location = base64_decode(GlobalService::get('location'));

			$total_rights = 0;
			while (is_array(GlobalService::get('acl_rights')) && list(,$rights) = each(GlobalService::get('acl_rights')))
			{
				$total_rights += $rights;
			}

			GlobalService::get('phpgw')->acl->add_repository(GlobalService::get('acl_app'), $location, GlobalService::get('account_id'), $total_rights);

			$this->ui->list_apps();
		}

	}
