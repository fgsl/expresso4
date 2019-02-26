<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* eGroupWare API - Auth from LDAP                                          *
	* This file written by Lars Kneschke <lkneschke@linux-at-work.de>          *
	* and Joseph Engo <jengo@phpgroupware.org>                                 *
	* Authentication based on LDAP Server                                      *
	* Copyright (C) 2000, 2001 Joseph Engo                                     *
	* Copyright (C) 2002, 2003 Lars Kneschke                                   *
	* -------------------------------------------------------------------------*
	* This library is part of the eGroupWare API                               *
	* http://www.egroupware.org/api                                            * 
	* ------------------------------------------------------------------------ *
	* This library is free software; you can redistribute it and/or modify it  *
	* under the terms of the GNU Lesser General Public License as published by *
	* the Free Software Foundation; either version 2.1 of the License,         *
	* or any later version.                                                    *
	* This library is distributed in the hope that it will be useful, but      *
	* WITHOUT ANY WARRANTY; without even the implied warranty of               *
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     *
	* See the GNU Lesser General Public License for more details.              *
	* You should have received a copy of the GNU Lesser General Public License *
	* along with this library; if not, write to the Free Software Foundation,  *
	* Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            *
	\**************************************************************************/

  
	class auth_
	{
		var $previous_login = -1;

		function authenticate($username, $passwd)
		{
			if (preg_match('/[()|&=*,<>!~]/',$username))
			{
				return False;
			}

			if(!$ldap = @ldap_connect(GlobalService::get('phpgw_info')['server']['ldap_host']))
			{
				GlobalService::get('phpgw')->log->message('F-Abort, Failed connecting to LDAP server for authenication, execution stopped');
				GlobalService::get('phpgw')->log->commit();
				return False;
			}

			if(GlobalService::get('phpgw_info')['server']['ldap_version3'])
			{
				ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
			}

			ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

			/* Login with the LDAP Admin. User to find the User DN.  */
			if(!@ldap_bind($ldap, GlobalService::get('phpgw_info')['server']['ldap_root_dn'], GlobalService::get('phpgw_info')['server']['ldap_root_pw']))
			{
				return False;
			}
			/* find the dn for this uid, the uid is not always in the dn */
			$attributes	= array('uid','dn','givenName','sn','mail','uidNumber','gidNumber');
			
			$filter = GlobalService::get('phpgw_info')['server']['ldap_search_filter'] ? GlobalService::get('phpgw_info')['server']['ldap_search_filter'] : '(uid=%user)';
			$filter = str_replace(array('%user','%domain'),array($username,GlobalService::get('phpgw_info')['user']['domain']),$filter);

			if (GlobalService::get('phpgw_info')['server']['account_repository'] == 'ldap')
			{
				$filter = "(&$filter(phpgwaccountstatus=A))";
			}

			$sri = ldap_search($ldap, GlobalService::get('phpgw_info')['server']['ldap_context'], $filter, $attributes);
			$allValues = ldap_get_entries($ldap, $sri);

			if ($allValues['count'] > 0)
			{
				if(GlobalService::get('phpgw_info')['server']['case_sensitive_username'] == true)
				{
					if($allValues[0]['uid'][0] != $username)
					{
						return false;
					}
				}
				/* we only care about the first dn */
				$userDN = $allValues[0]['dn'];

				GlobalService::get('phpgw')->session->phpgw_setcookie('last_dn', $userDN ,time()+1209600); /* For 2 weeks */
				/*
				generate a bogus password to pass if the user doesn't give us one 
				this gets around systems that are anonymous search enabled
				*/
				if (empty($passwd))
				{
					$passwd = crypt(microtime());
				}
				/* try to bind as the user with user suplied password */
				if (@ldap_bind($ldap, $userDN, $passwd))
				{
					if (GlobalService::get('phpgw_info')['server']['account_repository'] != 'ldap')
					{
						$account = CreateObject('phpgwapi.accounts',$username,'u');
						if (!$account->account_id && GlobalService::get('phpgw_info')['server']['auto_create_acct'])
						{
							// create a global array with all availible info about that account
							GlobalService::set('auto_create_acct', array());
							foreach(array(
								'givenname' => 'firstname',
								'sn'        => 'lastname',
								'uidnumber' => 'id',
								'mail'      => 'email',
								'gidnumber' => 'primary_group',
							) as $ldap_name => $acct_name)
							{
								GlobalService::get('auto_create_acct')[$acct_name] =
									GlobalService::get('phpgw')->translation->convert($allValues[0][$ldap_name][0],'utf-8');
							}
							return True;
						}
						$data = $account->read_repository();
						return $data['status'] == 'A';
					}
					return True;
				}
			}
			/* dn not found or password wrong */
			return False;
		}

		function change_password($old_passwd, $new_passwd, $_account_id='') 
		{
			if ('' == $_account_id)
			{
				$username = GlobalService::get('phpgw_info')['user']['account_lid'];
			}
			else
			{
				$username = GlobalService::get('phpgw')->accounts->id2name($_account_id);
			}
			$filter = GlobalService::get('phpgw_info')['server']['ldap_search_filter'] ? GlobalService::get('phpgw_info')['server']['ldap_search_filter'] : '(uid=%user)';
			$filter = str_replace(array('%user','%domain'),array($username,GlobalService::get('phpgw_info')['user']['domain']),$filter);

			// LDAP Replication mode. 
			if ( (!empty(GlobalService::get('phpgw_info')['server']['ldap_master_host'])) &&
				 (!empty(GlobalService::get('phpgw_info')['server']['ldap_master_root_dn'])) &&
			 	 (!empty(GlobalService::get('phpgw_info')['server']['ldap_master_root_pw'])) )
			{
				$ds = GlobalService::get('phpgw')->common->ldapConnect(
											   GlobalService::get('phpgw_info')['server']['ldap_master_host'],
											   GlobalService::get('phpgw_info')['server']['ldap_master_root_dn'],
											   GlobalService::get('phpgw_info')['server']['ldap_master_root_pw']
											   );
			}
			else
			{
				$ds = GlobalService::get('phpgw')->common->ldapConnect();
			}

			$sri = ldap_search($ds, GlobalService::get('phpgw_info')['server']['ldap_context'], $filter);
			$allValues = ldap_get_entries($ds, $sri);
			$entry['userpassword'] = $this->encrypt_password($new_passwd);
			$entry['phpgwlastpasswdchange'] = time();
			
			/* SAMBA Begin's*/
			foreach ($allValues[0]['objectclass'] as $objectclass)
			{
				if ($objectclass == 'sambaSamAccount')
				{
					$entry['sambaLMPassword'] = exec( "/home/expressolivre/mkntpwd -L '{$new_passwd}'" );
					$entry['sambaNTPassword'] = exec( "/home/expressolivre/mkntpwd -N '{$new_passwd}'" );
				}
			}
			/* SAMBA End's*/
			
			$dn = $allValues[0]['dn'];
			
			/* userPasswordRFC2617 Begin's*/
			$c = CreateObject('phpgwapi.config','expressoAdmin1_2');
			$c->read_repository();
			$current_config = $c->config_data;
			if ($current_config['expressoAdmin_userPasswordRFC2617'] == 'true')
			{
				$realm		= $current_config['expressoAdmin_realm_userPasswordRFC2617'];
				$uid		= $allValues[0]['uid'][0];
				$password	= $new_passwd;
				$passUserRFC2617 = $realm . ':      ' . md5("$uid:$realm:$password");

				if ($allValues[0]['userpasswordrfc2617'][0] != '')
					$entry['userPasswordRFC2617'] = $passUserRFC2617;
				else
				{
					$ldap_add['userPasswordRFC2617'] = $passUserRFC2617;
					if (!@ldap_mod_add($ds, $dn, $ldap_add)) 
					{
						return false;
					}
				}
			}
			/* userPasswordRFC2617 End's*/
			
			if (!@ldap_modify($ds, $dn, $entry))
			{
				return false;
			}
			GlobalService::get('phpgw')->session->appsession('password','phpgwapi',base64_encode($new_passwd));
			return $new_passwd;
		}

                function change_password_user ($old_passwd, $new_passwd, $dn, $referrals=false)
		{
			//Use with RHDS
			//system('echo "CP old_passwd: '.$old_passwd.'" >>/tmp/controle');
			//system('echo "CP new_passwd: '.$new_passwd.'" >>/tmp/controle');
			//system('echo "CP dn: '.$dn.'" >>/tmp/controle');
			//system('echo "CP referrals: '.$referrals.'" >>/tmp/controle');
			$ds=ldap_connect(GlobalService::get('phpgw_info')['server']['ldap_host']);
			if (!$ds)
				{
				//system('echo "CP Nao conectou no ldap" >>/tmp/controle');
				$this->auth_reason = ldap_errno($ldap);
				return False;
				}
			else
			{
				if ($referrals)
					{
					//system('echo "CP Entrou referrals" >>/tmp/controle');
					$this->passwd=$old_passwd;
					$this->dn=$dn;
					ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
					ldap_set_option($ds, LDAP_OPT_REFERRALS, 1);
					if(GlobalService::get('phpgw_info')['server']['diretorioescravo'])
						{
						ldap_set_rebind_proc($ds, array($this, '_rebindProc'));
						}
					}
				$modify["userpassword"]=$new_passwd;
				if (!@ldap_bind($ds,$dn,$old_passwd))
					{
					//system('echo "CP nao conseguiu dar bind" >>/tmp/controle');
					//Se a politica estiver no diretorio eh necessario tentar alterar a senha mesmo que nao haja um bind, pois a negacao de bind pode ser proveniente de uma expiracao
					if(GlobalService::get('phpgw_info')['server']['politicasenhas']=='diretorio')
						{
						//system('echo "CP politica eh no diretorio" >>/tmp/controle');
						if (!@ldap_mod_replace($ds,$dn,$modify))
							{
							//system('echo "CP nao conseguiu fazer replace!" >>/tmp/controle');
							$this->auth_reason = ldap_errno($ds);
							return false;
							}
							else
							{
							//system('echo "CP replace funcionou!" >>/tmp/controle');
                                                        GlobalService::get('phpgw')->session->appsession('password','phpgwapi',base64_encode($new_passwd));
							return $new_passwd;
							}
						}
					$this->auth_reason = ldap_errno($ds);
					return False;
					}
					else
					{
					//system('echo "CP Conseguiu dar bind" >>/tmp/controle');
					if (!ldap_mod_replace($ds,$dn,$modify))
						{
						$this->auth_reason = ldap_errno($ds);
						return False;
						}
						else
						{
						GlobalService::get('phpgw')->session->appsession('password','phpgwapi',base64_encode($new_passwd));
                                                return $new_passwd;
						}
					}
			}
		}


		function update_lastlogin($_account_id, $ip)
		{
			if (GlobalService::get('phpgw_info')['server']['account_repository'] == 'ldap')
			{
				$entry['phpgwaccountlastlogin']     = time();
				$entry['phpgwaccountlastloginfrom'] = $ip;
	
				$ds = GlobalService::get('phpgw')->common->ldapConnect();
				$sri = ldap_search($ds, GlobalService::get('phpgw_info')['server']['ldap_context'], 'uidnumber=' . (int)$_account_id);
				$allValues = ldap_get_entries($ds, $sri);
	
				$dn = $allValues[0]['dn'];
				$this->previous_login = $allValues[0]['phpgwaccountlastlogin'][0];
	
				@ldap_modify($ds, $dn, $entry);
			}
			else
			{
				GlobalService::get('phpgw')->db->query("select account_lastlogin from phpgw_accounts where account_id='$_account_id'",__LINE__,__FILE__);
				GlobalService::get('phpgw')->db->next_record();
				$this->previous_login = GlobalService::get('phpgw')->db->f('account_lastlogin');
	
				GlobalService::get('phpgw')->db->query("update phpgw_accounts set account_lastloginfrom='"
					. "$ip', account_lastlogin='" . time()
					. "' where account_id='$_account_id'",__LINE__,__FILE__);
			}
		}
	}
?>
