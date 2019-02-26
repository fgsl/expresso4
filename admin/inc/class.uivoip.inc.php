<?php

 use Expresso\Core\GlobalService;

 /**************************************************************************\
  * Expresso Livre - Voip - administration                                   *
  *															                 *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

class uivoip
{
	var $public_functions = array(
		'add'      => True,
		'edit_conf' => True,
	);

	var $bo;

	final function __construct()
	{
		if(!isset($_SESSION['admin']['ldap_host']))
		{
			$_SESSION['admin']['server']['ldap_host'] = GlobalService::get('phpgw_info')['server']['ldap_host'];
			$_SESSION['admin']['server']['ldap_root_dn'] = GlobalService::get('phpgw_info')['server']['ldap_root_dn'];
			$_SESSION['admin']['server']['ldap_host_pw'] = GlobalService::get('phpgw_info')['server']['ldap_root_pw'];
			$_SESSION['admin']['server']['ldap_context'] = GlobalService::get('phpgw_info')['server']['ldap_context'];
		}
		$this->bo = CreateObject('admin.bovoip');
	}

	final function edit_conf()
	{
		if(GlobalService::get('phpgw')->acl->check('applications_access',1,'admin'))
		{
			GlobalService::get('phpgw')->redirect_link('/index.php');
		}		

		GlobalService::get('phpgw_info')['flags']['app_header'] = lang('Admin') .' - ' . lang('Configuration Service VoIP');

		if(!@is_object(GlobalService::get('phpgw')->js))
		{
			GlobalService::get('phpgw')->js = CreateObject('phpgwapi.javascript');
		}

		$webserver_url = GlobalService::get('phpgw_info')['server']['webserver_url'];
		$webserver_url = ( !empty($webserver_url) ) ? $webserver_url : '/';

		if(strrpos($webserver_url,'/') === false || strrpos($webserver_url,'/') != (strlen($webserver_url)-1))
			$webserver_url .= '/';

		$js = array('connector','xtools','functions');
		
		foreach( $js as $tmp )
			GlobalService::get('phpgw')->js->validate_file('voip',$tmp,'admin');
		
		GlobalService::get('phpgw')->common->phpgw_header();
		echo parse_navbar();
		echo '<script type="text/javascript">var path_adm="'.$webserver_url .'"</script>';

		$ous = "<option value='-1'>-- ".lang('Select Organization')." --</option>";	
		if( ($LdapOus = $this->bo->getOuLdap()) )
		{
			foreach($LdapOus as $tmp )
				$ous .= "<option value='".$tmp."'>".$tmp."</option>";
		}
		
		$groups_voip = GlobalService::get('phpgw_info')['server']['voip_groups']; 

		if( $groups_voip )
		{
			$gvoip = explode(',', $groups_voip);
			natcasesort($gvoip);
			
			foreach( $gvoip as $tmp ){
				$option = explode(";",$tmp);
				$gvoip .= "<option value='".$tmp."'>".$option[0]."</option>";
			}
		}

		GlobalService::get('phpgw')->template->set_file(array('voip' => 'voip.tpl'));
		GlobalService::get('phpgw')->template->set_block('voip','voip_page','voip_page');	
		GlobalService::get('phpgw')->template->set_var(array(
										'action_url' => GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uivoip.add'),
										'lang_Email_Voip' => "Caixa Voip (Email) para habilitar o alerta telef�nico",//lang('Email Voip'),
										'lang_VoIP_settings' => lang('Configuration Service VoIP'),
										'lang_Enter_your_VoIP_server_address' => lang('Enter your VoIP server address'),	
										'lang_Enter_your_VoIP_server_url' => lang('Enter your VoIP server url'),	
										'lang_Enter_your_VoIP_server_port' => lang('Enter your VoIP server port'),
										'lang_save' => lang('Save'),
										'lang_cancel' => lang('Cancel'),
										'value_voip_email_redirect' => (GlobalService::get('phpgw_info')['server']['voip_email_redirect']) ? GlobalService::get('phpgw_info')['server']['voip_email_redirect'] : '',
										'value_voip_server' => (GlobalService::get('phpgw_info')['server']['voip_server']) ? GlobalService::get('phpgw_info')['server']['voip_server'] : '',
										'value_voip_url' => (GlobalService::get('phpgw_info')['server']['voip_url']) ? GlobalService::get('phpgw_info')['server']['voip_url'] : '',
										'value_voip_port' => (GlobalService::get('phpgw_info')['server']['voip_port']) ? GlobalService::get('phpgw_info')['server']['voip_port'] : '',
										'lang_load' => lang('Wait Loading...!'),
										'lang_grupos_ldap' => 'Grupos Ldap',
										'lang_grupos_liberados' => 'Grupos Liberados',
										'lang_groups_ldap' => lang('groups ldap'),
										'lang_organizations' => lang('Organizations'),
										'groups_voip' => $gvoip,
										'ous_ldap' => $ous
										));
	
		GlobalService::get('phpgw')->template->pparse('out','voip_page');
	}
	
	function display_row($label, $value)
	{
		GlobalService::get('phpgw')->template->set_var('tr_color',$this->nextmatchs->alternate_row_color());
		GlobalService::get('phpgw')->template->set_var('label',$label);
		GlobalService::get('phpgw')->template->set_var('value',$value);
		GlobalService::get('phpgw')->template->parse('rows','row',True);
	}

	function add()
	{
		
		if(GlobalService::get('phpgw')->acl->check('applications_access',1,'admin'))
		{
			GlobalService::get('phpgw')->redirect_link('/index.php');
		}		
		
		if ($_POST['cancel'])
		{
			GlobalService::get('phpgw')->redirect_link('/admin/index.php');
		}

		if ( $_POST['save'] )
		{
			$conf['voip_server']= $_POST['voip_server'];
			$conf['voip_url']	= $_POST['voip_url'];
			$conf['voip_port']	= $_POST['voip_port'];
			$conf['voip_email_redirect'] = $_POST['voip_email_redirect'];
			
			if( is_array($_POST['voip_groups']) )
				foreach($_POST['voip_groups'] as $tmp)
					$conf['voip_groups'] = (count($conf['voip_groups']) > 0 ) ? $conf['voip_groups'] . "," . $tmp : $tmp;
			else{
				$conf['voip_groups'] = '';
			}
			$this->bo->setConfDB($conf);
		}

		GlobalService::get('phpgw')->redirect_link('/admin/index.php');
	}
}
?>
