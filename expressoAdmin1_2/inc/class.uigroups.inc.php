<?php
	use Expresso\Core\GlobalService;

	/***********************************************************************************\
	* Expresso Administra��o                 										    *
	* by Joao Alfredo Knopik Junior (joao.alfredo@gmail.com, jakjr@celepar.pr.gov.br)   *
	* ----------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it		    *
	*  under the terms of the GNU General Public License as published by the			*
	*  Free Software Foundation; either version 2 of the License, or (at your			*
	*  option) any later version.											            *
	\***********************************************************************************/

include_once(PHPGW_API_INC.'/class.aclmanagers.inc.php');

	class uigroups
	{
		var $public_functions = array
		(
			'list_groups'	=> True,
			'add_groups'	=> True,
			'edit_groups'	=> True,
			'css'			=> True
		);

		var $nextmatchs;
		var $group;
		var $functions;
		var $ldap_functions;
		var $db_functions;
			
		function uigroups()
		{
			$this->group		= CreateObject('expressoAdmin1_2.group');
			$this->nextmatchs	= createobject('phpgwapi.nextmatchs');
			$this->functions	= CreateObject('expressoAdmin1_2.functions');
			$this->ldap_functions = CreateObject('expressoAdmin1_2.ldap_functions');
			$this->db_functions = CreateObject('expressoAdmin1_2.db_functions');
			
			$c = CreateObject('phpgwapi.config','expressoAdmin1_2');
			$c->read_repository();
			$this->current_config = $c->config_data;
			
			if(!@is_object(GlobalService::get('phpgw')->js))
			{
				GlobalService::get('phpgw')->js = CreateObject('phpgwapi.javascript');
			}
			GlobalService::get('phpgw')->js->validate_file('jscode','connector','expressoAdmin1_2');#diretorio, arquivo.js, aplicacao
			GlobalService::get('phpgw')->js->validate_file('jscode','expressoadmin','expressoAdmin1_2');
			GlobalService::get('phpgw')->js->validate_file('jscode','groups','expressoAdmin1_2');
			GlobalService::get('phpgw')->js->validate_file('jscode','tabs','expressoAdmin1_2');
		}
		
		function list_groups()
		{
			$account_lid = GlobalService::get('phpgw')->accounts->data['account_lid'];
			$manager_acl = $this->functions->read_acl($account_lid);
			$raw_context = $acl['raw_context'];
			$contexts = $manager_acl['contexts'];
			foreach ($manager_acl['contexts_display'] as $index=>$tmp_context)
			{
				$context_display .= '<br>'.$tmp_context;
			}
			
			// Verifica se tem acesso a este modulo
			if (!$this->functions->check_acl( $account_lid, ACL_Managers::GRP_VIEW_GROUPS ))
			{
				GlobalService::get('phpgw')->redirect(GlobalService::get('phpgw')->link('/expressoAdmin1_2/inc/access_denied.php'));
			}

			if(isset($_POST['query']))
			{
				// limit query to limit characters
				if(preg_match('/^[a-z_0-9_-].+$/i',$_POST['query'])) 
					GlobalService::set('query',$_POST['query']);
			}
						
			unset(GlobalService::get('phpgw_info')['flags']['noheader']);
			unset(GlobalService::get('phpgw_info')['flags']['nonavbar']);
			
			GlobalService::get('phpgw_info')['flags']['app_header'] = GlobalService::get('phpgw_info')['apps']['expressoAdmin1_2']['title'].' - '.lang('User groups');
			GlobalService::get('phpgw')->common->phpgw_header();

			$p = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
			$p->set_file(array('groups'   => 'groups.tpl'));
			$p->set_block('groups','list','list');
			$p->set_block('groups','row','row');
			$p->set_block('groups','row_empty','row_empty');

			// Seta as variaveis padroes.
			$var = Array(
				'th_bg'					=> GlobalService::get('phpgw_info')['theme']['th_bg'],
				'back_url'				=> GlobalService::get('phpgw')->link('/expressoAdmin1_2/index.php'),
				'add_action'			=> GlobalService::get('phpgw')->link('/index.php','menuaction=expressoAdmin1_2.uigroups.add_groups'),
				'add_group_disabled'	=> $this->functions->check_acl( $account_lid, ACL_Managers::ACL_ADD_GROUPS ) ? '' : 'disabled',
				'context_display'		=> $context_display
			);
			$p->set_var($var);
			$p->set_var($this->functions->make_dinamic_lang($p, 'list'));
			
			// Save query
			$p->set_var('query', GlobalService::get('query'));
			
			//Admin make a search
			if (GlobalService::get('query') != '')
			{
				$groups_info = $this->functions->get_list('groups', GlobalService::get('query'), $contexts);
			}
			$total = count($groups_info);

			if (!count($total) && GlobalService::get('query') != '')
			{
				$p->set_var('message',lang('No matches found'));
			}
			else if ($total)
			{
				if ($this->functions->check_acl( $account_lid, ACL_Managers::ACL_MOD_GROUPS ))
				{
					$can_edit = True;
				}
				if ($this->functions->check_acl( $account_lid, ACL_Managers::ACL_ADD_GROUPS ))
				{
					$can_copy = True;
				}
				if ($this->functions->check_acl( $account_lid, ACL_Managers::ACL_DEL_GROUPS ))
				{
					$can_delete = True;
				}

				foreach($groups_info as $group)
				{
					$tr_color = $this->nextmatchs->alternate_row_color($tr_color);
					$var = Array(
						'tr_color'    		=> $tr_color,
						'row_cn'  			=> $group['cn'],
						'row_type'          => lang( $group['type']? 'extern' : 'expresso' ),
						'row_description'	=> $group['description'],
						'row_mail'			=> $group['mail']
					);
					$p->set_var($var);
					$id = base64_encode( $group['dn'] );

					if ($can_edit)
					{
						$p->set_var( 'edit_link',$this->row_action( 'edit', 'groups', array( 'id' => $id ) ) );
					}
					else
					{
						$p->set_var('edit_link','&nbsp;');
					}

					if ($can_copy)
					{
						$p->set_var('copy_link',"<a href='#' onClick='javascript:copy_group( \"".$id."\" );'>".lang('copy')."</a>");
					}
					else
					{
						$p->set_var('copy_link','&nbsp;');
					}

					if ($can_delete)
					{
						$p->set_var('delete_link',"<a href='#' onClick='javascript:delete_group( this, \"".$id."\" );'>".lang('to delete')."</a>");
					}
					else
					{
						$p->set_var('delete_link','&nbsp;');
					}

					$p->fp('rows','row',True);
				}
			}
			$p->parse('rows','row_empty',True);
			$p->set_var($var);

			if (! GlobalService::get('phpgw')->acl->check('run',4,'admin'))
			{
				$p->set_var('input_add','<input type="submit" value="' . lang('Add') . '">');
			}
			if (! GlobalService::get('phpgw')->acl->check('run',2,'admin'))
			{
				$p->set_var('input_search',lang('Search') . '&nbsp;<input name="query" value="'.htmlspecialchars(stripslashes(GlobalService::get('query'))).'">');
			}
			$p->pfp('out','list');
		}
		
		function add_groups()
		{
			GlobalService::get('phpgw')->js->set_onload('get_available_users(document.forms[0].context.value, document.forms[0].ea_check_allUsers.checked);');
			if ($this->current_config['expressoAdmin_samba_support'] == 'true')
				GlobalService::get('phpgw')->js->set_onload('get_available_sambadomains(document.forms[0].context.value, \'create_group\');');

			$manager_lid = GlobalService::get('phpgw')->accounts->data['account_lid'];
			$manager_acl = $this->functions->read_acl($manager_lid);
			$manager_contexts = $manager_acl['contexts'];
			
			// Verifica se tem acesso a este modulo
			if (!$this->functions->check_acl( $manager_lid, ACL_Managers::ACL_ADD_GROUPS ))
			{
				GlobalService::get('phpgw')->redirect(GlobalService::get('phpgw')->link('/expressoAdmin1_2/inc/access_denied.php'));
			}

			unset(GlobalService::get('phpgw_info')['flags']['noheader']);
			unset(GlobalService::get('phpgw_info')['flags']['nonavbar']);
			GlobalService::get('phpgw_info')['flags']['app_header'] = GlobalService::get('phpgw_info')['apps']['expressoAdmin1_2']['title'].' - '.lang('Create Group');
			GlobalService::get('phpgw')->common->phpgw_header();
			
			// Set o template
			$p = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
			$p->set_file(Array('create_group' => 'groups_form.tpl'));
			$p->set_block('create_group','list','list');

			// Pega combo das organiza��es e seleciona um dos setores em caso de um erro na valida�ao dos dados.
			//$combo_manager_org = $this->functions->get_organizations($manager_context, trim(strtolower($group_info['context'])));
			foreach ($manager_contexts as $index=>$context)
				$combo_manager_org .= $this->functions->get_organizations($context, trim(strtolower($group_info['context'])));
			$combo_all_orgs = $this->functions->get_organizations(GlobalService::get('phpgw_info')['server']['ldap_context'], trim(strtolower($group_info['context'])));
			
			// Chama funcao para criar lista de aplicativos disponiveis.
			$apps = $this->functions->make_list_app($manager_lid);
			// Chama funcao para criar lista de campos disponiveis na edicao de dados pessoais.
			$personal_data_fields = $this->functions->make_list_personal_data_fields($manager_lid);
			
			// Cria combo de dominio samba
			if ($this->current_config['expressoAdmin_samba_support'] == 'true')
			{
				$a_sambadomains = $this->db_functions->get_sambadomains_list();
				$sambadomainname_options = '';
				if (count($a_sambadomains))
				{
					foreach ($a_sambadomains as $a_sambadomain)
					{
						// So mostra os sambaDomainName do contexto do manager
						if ($this->ldap_functions->exist_sambadomains($manager_contexts, $a_sambadomain['samba_domain_name']))
							$sambadomainname_options .= "<option value='" . $a_sambadomain['samba_domain_sid'] . "'>" . $a_sambadomain['samba_domain_name'] . "</option>";
					}
				}
			}

			// Seta variaveis utilizadas pelo tpl.
			$var = Array(
				'color_bg1'					=> "#E8F0F0",
				'color_bg2'					=> "#D3DCE3",
				'type'						=> 'create_group',
				'cn'						=> '',
				'restrictionsOnGroup'		=> $this->current_config['expressoAdmin_restrictionsOnGroup'],
				'type'						=> 'create_group',
				'ldap_context'				=> GlobalService::get('phpgw_info')['server']['ldap_context'],
				'ufn_ldap_context'			=> ldap_dn2ufn(GlobalService::get('phpgw_info')['server']['ldap_context']),
				'concatenateDomain'			=> $this->current_config['expressoAdmin_concatenateDomain'],
				'defaultDomain'				=> $this->current_config['expressoAdmin_defaultDomain'],
				'apps'						=> $apps,
				'personal_data_fields'		=> $personal_data_fields,
				'use_attrs_samba_checked'	=> '',
				'disabled_samba'			=> 'disabled',
				'display_samba_options'		=> $this->current_config['expressoAdmin_samba_support'] == 'true' ? '' : '"display:none"',
				'disable_email_groups'		=> $this->functions->check_acl( $manager_lid, ACL_Managers::ACL_MOD_GROUPS_EMAIL ) ? '' : 'disabled',
				'sambadomainname_options'	=> $sambadomainname_options,
				'back_url'					=> GlobalService::get('phpgw')->link('/index.php','menuaction=expressoAdmin1_2.uigroups.list_groups'),
				'combo_manager_org'			=> $combo_manager_org,
				'combo_all_orgs'			=> $combo_all_orgs,
				'grp_of_names_value'        => '',
				'grp_of_names_type_1_value' => 'checked',
				'grp_of_names_type_2_value' => '',
			);
			$p->set_var($var);
			$p->set_var($this->functions->make_dinamic_lang($p, 'list'));
			
			$p->pfp('out','create_group');
		}
		
		function edit_groups()
		{
			GlobalService::get('phpgw')->js->set_onload('get_available_users(document.forms[0].context.value, document.forms[0].ea_check_allUsers.checked);');

			$manager_lid = GlobalService::get('phpgw')->accounts->data['account_lid'];
			$manager_acl = $this->functions->read_acl($manager_lid);
			$manager_contexts = $manager_acl['contexts'];

			// Verifica se tem acesso a este modulo
			if (!$this->functions->check_acl( $manager_lid, ACL_Managers::ACL_MOD_GROUPS ))
			{
				GlobalService::get('phpgw')->redirect(GlobalService::get('phpgw')->link('/expressoAdmin1_2/inc/access_denied.php'));
			}

			// GET all infomations about the group.
			$dn = base64_decode( $_GET['id'] );

			$group_type = $this->group->get_type( $dn );
			$group_type_check = ( $group_type['type'] === 0 );
			$group_info = $group_type_check? $this->group->get_info( $group_type['gidnumber'] ) : $this->group->get_info_groupOfNames( $dn );

			unset(GlobalService::get('phpgw_info')['flags']['noheader']);
			unset(GlobalService::get('phpgw_info')['flags']['nonavbar']);
			GlobalService::get('phpgw_info')['flags']['app_header'] = GlobalService::get('phpgw_info')['apps']['expressoAdmin1_2']['title'].' - '.lang('Edit Group');
			GlobalService::get('phpgw')->common->phpgw_header();

			// Set o template
			$p = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
			$p->set_file(Array('create_group' => 'groups_form.tpl'));
			$p->set_block('create_group','list','list');

			// Obtem combo das organiza��es e seleciona a org do grupo.
			foreach ($manager_contexts as $index=>$context)
				$combo_manager_org .= $this->functions->get_organizations($context, trim(strtolower($group_info['context'])));
			$combo_all_orgs = $this->functions->get_organizations(GlobalService::get('phpgw_info')['server']['ldap_context'], trim(strtolower($group_info['context'])));

			// Usuarios do grupo.
			$user_count = 0;
			if (count($group_info['memberuid_info']) > 0)
			{
				foreach ($group_info['memberuid_info'] as $uid=>$user_data)
				{
					if ($user_data['uidnumber'])
					{
						$array_users[$user_data['uidnumber']] = $user_data['cn'];
						$array_users_uid[$user_data['uidnumber']] = $uid;
						$array_users_type[$user_data['uidnumber']] = $user_data['type'];
					}
					else
					{
						$array_users[$uid] = $user_data['cn'];
					}
				}
				natcasesort($array_users);
				
				foreach ($array_users as $uidnumber=>$cn)
				{
					$user_count++;
					if ($array_users_type[$uidnumber] == 'u')
					{
						$users .= "<option value=" . $uidnumber . ">" . $cn . " [" . $array_users_uid[$uidnumber] . "]</option>";
					}
					else
					{
						$unknow .= "<option value=-1>" . $cn . " [Corrigir manualmente]</option>";
					}
				}
				
				$opt_tmp_users  = '<option  value="-1" disabled>-----------------------------&nbsp;&nbsp;&nbsp;&nbsp;'.lang('users').'&nbsp;&nbsp;&nbsp;&nbsp;---------------------------- </option>'."\n";
				$opt_tmp_unknow = '<option  value="-1" disabled>------------&nbsp;&nbsp;&nbsp;&nbsp;'.lang('users did not find on DB, only on ldap').'&nbsp;&nbsp;&nbsp;&nbsp;------------</option>'."\n";
				$ea_select_usersInGroup = $unknow != '' ? $opt_tmp_unknow . $unknow . $opt_tmp_users . $users : $opt_tmp_users . $users;
			}
			
			//Sending Control Mail
			if (count($group_info['memberuid_scm_info']) > 0)
			{
				foreach ($group_info['memberuid_scm_info'] as $uid=>$sender_info)
				{
					$senders_options .= "<option value=" . $sender_info['uidnumber'] . ">" . $sender_info['cn'] . " [" . $uid . "]</option>";
				}
			}
			
			// Chama funcao para criar lista de aplicativos disponiveis.
			$apps = $this->functions->make_list_app($manager_lid, $group_info['apps']);
			// Chama funcao para criar lista de campos disponiveis na edicao de dados pessoais.
			$personal_data_fields = $this->functions->make_list_personal_data_fields($manager_lid, $group_info['acl_block_personal_data']);
			
			// Cria combo de dominios do samba
			if ($this->current_config['expressoAdmin_samba_support'] == 'true')
			{
				$a_sambadomains = $this->db_functions->get_sambadomains_list();
				$sambadomainname_options = '';
				if (count($a_sambadomains))
				{
					foreach ($a_sambadomains as $a_sambadomain)
					{
						if ($a_sambadomain['samba_domain_sid'] == $group_info['sambasid'])
							$sambadomainname_options .= "<option value='" . $a_sambadomain['samba_domain_sid'] . "' SELECTED>" . $a_sambadomain['samba_domain_name'] . "</option>";
						else
							$sambadomainname_options .= "<option value='" . $a_sambadomain['samba_domain_sid'] . "'>" . $a_sambadomain['samba_domain_name'] . "</option>";
					}
				}
			}
			
			// Seta variaveis utilizadas pelo tpl.
			$var = Array(
				'accountRestrictive_checked'        => $group_info['accountrestrictive'] == 'mailListRestriction' ? 'CHECKED' : '',
				'apps'                              => $apps,
				'back_url'                          => GlobalService::get('phpgw')->link('/index.php','menuaction=expressoAdmin1_2.uigroups.list_groups'),
				'class_div_radio'                   => 'hide',
				'class_form'                        => $group_type_check? '' : 'grp_of_names',
				'cn'                                => $group_info['cn'],
				'color_bg1'                         => "#E8F0F0",
				'color_bg2'                         => "#D3DCE3",
				'combo_all_orgs'                    => $combo_all_orgs,
				'combo_manager_org'                 => $combo_manager_org,
				'defaultDomain'                     => $this->current_config['expressoAdmin_defaultDomain'],
				'description'                       => $group_info['description'],
				'disable_email_groups'              => $this->functions->check_acl( $manager_lid, ACL_Managers::ACL_MOD_GROUPS_EMAIL ) ? '' : 'disabled',
				'disabled_samba'                    => $group_info['use_attrs_samba'] ? '' : 'disabled',
				'display_posix_attrs'               => $group_type_check? '' : 'display:none',
				'display_samba_options'             => $this->current_config['expressoAdmin_samba_support'] == 'true' && $group_type_check ? '' : '"display:none"',
				'dn'                                => $dn,
				'ea_select_usersInGroup'            => $ea_select_usersInGroup,
				'ea_select_users_scm'               => $senders_options,
				'email'                             => $group_info['email'],
				'gidnumber'                         => $group_info['gidnumber'],
				'ldap_context'                      => GlobalService::get('phpgw_info')['server']['ldap_context'],
				'participantCanSendMail_checked'    => $group_info['participantcansendmail'] == 'TRUE' ? 'CHECKED' : '',
				'personal_data_fields'              => $personal_data_fields,
				'phpgwaccountvisible_checked'       => $group_info['phpgwaccountvisible'] == '-1' ? 'CHECKED' : '',
				'sambadomainname_options'           => $sambadomainname_options,
				'type'                              => 'edit_group',
				'use_attrs_samba_checked'           => $group_info['use_attrs_samba'] ? 'CHECKED' : '',
				'user_count'                        => $user_count,
				'grp_of_names_value'                => ( $group_type['type'] === 0 )? '' : 'checked',
				'grp_of_names_type_1_value'         => ( $group_type['type'] !== 2 )? 'checked' : '',
				'grp_of_names_type_2_value'         => ( $group_type['type'] === 2 )? 'checked' : '',
			);
			
			$p->set_var($var);
			$p->set_var($this->functions->make_dinamic_lang($p, 'list'));
			$p->pfp('out','create_group');
		}

		function row_action( $action, $type, $params )
		{
			return '<a href="'.GlobalService::get('phpgw')->link(
				'/index.php',
				array_merge( array( 'menuaction' => 'expressoAdmin1_2.uigroups.'.$action.'_'.$type ), $params )
			).'"> '.lang( $action ).' </a>';
		}
		
		function css()
		{
			$appCSS = 
			'th.activetab
			{
				color:#000000;
				background-color:#D3DCE3;
				border-top-width : 1px;
				border-top-style : solid;
				border-top-color : Black;
				border-left-width : 1px;
				border-left-style : solid;
				border-left-color : Black;
				border-right-width : 1px;
				border-right-style : solid;
				border-right-color : Black;
				font-size: 12px;
				font-family: Tahoma, Arial, Helvetica, sans-serif;
			}
			
			th.inactivetab
			{
				color:#000000;
				background-color:#E8F0F0;
				border-bottom-width : 1px;
				border-bottom-style : solid;
				border-bottom-color : Black;
				font-size: 12px;
				font-family: Tahoma, Arial, Helvetica, sans-serif;				
			}
			
			.td_left {border-left:1px solid Gray; border-top:1px solid Gray; border-bottom:1px solid Gray;}
			.td_right {border-right:1px solid Gray; border-top:1px solid Gray; border-bottom:1px solid Gray;}
			
			div.activetab{ display:inline; }
			div.inactivetab{ display:none; }';
			
			return $appCSS;
		}
	}
?>
