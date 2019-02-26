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

	function check_logoutcode($code)
	{
		switch($code)
		{
			case 1:
				return lang('You have been successfully logged out');
				
			case 2:
				return lang('Sorry, your login has expired');
				
			case 4:
				return lang('Cookies are required to login to this site.');
				
			case 5:
				return '<font color="FF0000">' . lang('Bad login or password') . '</font>';

			case 6:
				return '<font color="FF0000">' . lang('Your password has expired, and you do not have access to change it') . '</font>';
				
			case 98:
				return '<font color="FF0000">' . lang('Account is expired') . '</font>';
				
			case 99:
                                return '<font color="FF0000">' . lang('Blocked, too many attempts(%1)! Retry in %2 minute(s)',GlobalService::get('phpgw_info')['server']['num_unsuccessful_id'],GlobalService::get('phpgw_info')['server']['block_time']) . '</font>';
				
			case 10:
				GlobalService::get('phpgw')->session->phpgw_setcookie('sessionid');
				GlobalService::get('phpgw')->session->phpgw_setcookie('kp3');
				GlobalService::get('phpgw')->session->phpgw_setcookie('domain');

				//fix for bug php4 expired sessions bug
				if(GlobalService::get('phpgw_info')['server']['sessions_type'] == 'php4')
				{
					GlobalService::get('phpgw')->session->phpgw_setcookie(PHPGW_PHPSESSID);
				}

				return '<font color="#FF0000">' . lang('Your session could not be verified.') . '</font>';
				
			default:
				return '&nbsp;';
		}
	}
	
	/* Program starts here */

	if(GlobalService::get('phpgw_info')['server']['auth_type'] == 'http' && isset($_SERVER['PHP_AUTH_USER']))
	{
		$submit = True;
		$login  = $_SERVER['PHP_AUTH_USER'];
		$passwd = $_SERVER['PHP_AUTH_PW'];
		$passwd_type = 'text';
	}
	else
	{
		$passwd = $_POST['passwd'];
		$passwd_type = $_POST['passwd_type'];
	}

	# Apache + mod_ssl style SSL certificate authentication
	# Certificate (chain) verification occurs inside mod_ssl
	if(GlobalService::get('phpgw_info')['server']['auth_type'] == 'sqlssl' && isset($_SERVER['SSL_CLIENT_S_DN']) && !isset($_GET['cd']))
	{
		# an X.509 subject looks like:
		# /CN=john.doe/OU=Department/O=Company/C=xx/Email=john@comapy.tld/L=City/
		# the username is deliberately lowercase, to ease LDAP integration
		$sslattribs = explode('/',$_SERVER['SSL_CLIENT_S_DN']);
		# skip the part in front of the first '/' (nothing)
		while($sslattrib = next($sslattribs))
		{
			list($key,$val) = explode('=',$sslattrib);
			$sslattributes[$key] = $val;
		}

		if(isset($sslattributes['Email']))
		{
			$submit = True;

			# login will be set here if the user logged out and uses a different username with
			# the same SSL-certificate.
			if(!isset($_POST['login'])&&isset($sslattributes['Email']))
			{
				$login = $sslattributes['Email'];
				# not checked against the database, but delivered to authentication module
				$passwd = $_SERVER['SSL_CLIENT_S_DN'];
			}
		}
		unset($key);
		unset($val);
		unset($sslattributes);
	}

	if(isset($passwd_type) || $_POST['submitit_x'] || $_POST['submitit_y'] || $submit)
	{
		if($_POST['user']) {
			$_POST['login'] = $_POST['user'];
		}
		if(getenv('REQUEST_METHOD') != 'POST' && $_SERVER['REQUEST_METHOD'] != 'POST' &&
			!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['SSL_CLIENT_S_DN']))
		{
			GlobalService::get('phpgw')->redirect(GlobalService::get('phpgw')->link('/login.php','cd=5'));
		}
		
		// don't get login data again when $submit is true
		if($submit == false)
		{
			$login = $_POST['login'];
		}
		
		if(strstr($login,'@') === False && isset($_POST['logindomain']))
		{
			$login .= '@' . $_POST['logindomain'];
		}
		elseif(!isset(GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['user']['domain']]))
		{
			$login .= '@'.GlobalService::get('phpgw_info')['server']['default_domain'];
		}
		GlobalService::set('sessionid',GlobalService::get('phpgw')->session->create(strtolower($login),$passwd,$passwd_type,'u'));

		if(!isset(GlobalService::get('sessionid')) || ! GlobalService::get('sessionid'))
		{
			GlobalService::get('phpgw')->redirect(GlobalService::get('phpgw_info')['server']['webserver_url'] . '/login.php?cd=' . GlobalService::get('phpgw')->session->cd_reason);
		}
		else
		{
			if ($_POST['lang'] && preg_match('/^[a-z]{2}(-[a-z]{2}){0,1}$/',$_POST['lang']) &&
			    $_POST['lang'] != GlobalService::get('phpgw_info')['user']['preferences']['common']['lang'])
			{
				GlobalService::get('phpgw')->preferences->add('common','lang',$_POST['lang'],'session');
			}

			if(!GlobalService::get('phpgw_info')['server']['disable_autoload_langfiles'])
			{
				GlobalService::get('phpgw')->translation->autoload_changed_langfiles();
			}

			GlobalService::get('phpgw')->redirect_forward();
		}
	}
	else
	{
		// !!! DONT CHANGE THESE LINES !!!
		// If there is something wrong with this code TELL ME!
		// Commenting out the code will not fix it. (jengo)
		if(isset($_COOKIE['last_loginid']))
		{
			$accounts = CreateObject('phpgwapi.accounts');
			$prefs = CreateObject('phpgwapi.preferences', $accounts->name2id($_COOKIE['last_loginid']));

			if($prefs->account_id)
			{
				GlobalService::get('phpgw_info')['user']['preferences'] = $prefs->read_repository();
			}
		}
		
		$_GET['lang'] = addslashes($_GET['lang']);
		if ($_GET['lang'])
		{
			GlobalService::get('phpgw_info')['user']['preferences']['common']['lang'] = $_GET['lang'];
		}
		elseif(!isset($_COOKIE['last_loginid']) || !$prefs->account_id)
		{
			// If the lastloginid cookies isn't set, we will default to the first language,
			// the users browser accepts.
			list($lang) = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			/*
			if(strlen($lang) > 2)
			{
				$lang = substr($lang,0,2);
			}
			*/
			GlobalService::get('phpgw_info')['user']['preferences']['common']['lang'] = $lang;
		}
		#print 'LANG:' . GlobalService::get('phpgw_info')['user']['preferences']['common']['lang'] . '<br>';

		GlobalService::get('phpgw')->translation->init();	// this will set the language according to the (new) set prefs
		GlobalService::get('phpgw')->translation->add_app('login');
		GlobalService::get('phpgw')->translation->add_app('loginscreen');
		if(lang('loginscreen_message') == 'loginscreen_message*')
		{
			GlobalService::get('phpgw')->translation->add_app('loginscreen','en');	// trying the en one
		}
		if(lang('loginscreen_message') != 'loginscreen_message*')
		{
			$tmpl->set_var('lang_message',stripslashes(lang('loginscreen_message')));
		}
	}
	
	$domain_select = '&nbsp;';
	$last_loginid = $_COOKIE['last_loginid'];
	if($last_loginid !== '')
	{
		reset(GlobalService::get('phpgw_domain'));
		list($default_domain) = each(GlobalService::get('phpgw_domain'));

		if($_COOKIE['last_domain'] != $default_domain && !empty($_COOKIE['last_domain']))
		{
			$last_loginid .= '@' . $_COOKIE['last_domain'];
		}
	}
	$tmpl->set_var('select_domain',$domain_select);

	foreach($_GET as $name => $value)
	{
		if(preg_match('/phpgw_/',$name))
		{
			$extra_vars .= '&' . $name . '=' . urlencode($value);
		}
	}

	if($extra_vars)
	{
		$extra_vars = '?' . substr($extra_vars,1);
	}

	/********************************************************\
	* Check is the registration app is installed, activated  *
	* And if the register link must be placed                *
	\********************************************************/
	
	$cnf_reg = createobject('phpgwapi.config','registration');
	$cnf_reg->read_repository();
	$config_reg = $cnf_reg->config_data;

	if($config_reg[enable_registration]=='True' && $config_reg[register_link]=='True')
	{
		$reg_link='&nbsp;<a href="registration/">'.lang('Not a user yet? Register now').'</a><br/>';
	}

	GlobalService::get('phpgw_info')['server']['template_set'] = GlobalService::get('phpgw_info')['login_template_set'];

	$tmpl->set_var('register_link',$reg_link);
	$tmpl->set_var('charset',GlobalService::get('phpgw')->translation->charset());
	$tmpl->set_var('login_url', GlobalService::get('phpgw_info')['server']['webserver_url'] . '/login.php' . $extra_vars);
	$tmpl->set_var('registration_url',GlobalService::get('phpgw_info')['server']['webserver_url'] . '/registration/');
	$tmpl->set_var('version',GlobalService::get('phpgw_info')['server']['versions']['phpgwapi']);
	$tmpl->set_var('cd',check_logoutcode($_GET['cd']));
	$tmpl->set_var('cookie',$last_loginid);

	$tmpl->set_var('lang_username',lang('username'));
	$tmpl->set_var('lang_password',lang('password'));
	$tmpl->set_var('lang_login',lang('login'));

	$tmpl->set_var('website_title', GlobalService::get('phpgw_info')['server']['site_title']);
	$tmpl->set_var('template_set',GlobalService::get('phpgw_info')['login_template_set']);
	GlobalService::get('phpgw')->translation->add_app('loginhelp',$_GET['lang']);		
	if(lang('loginhelp_message') != 'loginhelp_message*' && trim(lang('loginhelp_message')) != ""){					
		$tmpl->set_var('lang_help',lang("Help"));	
	}
	else 
		$tmpl->set_var('display_help','none');
	$tmpl->set_var('bg_color',(GlobalService::get('phpgw_info')['server']['login_bg_color']?GlobalService::get('phpgw_info')['server']['login_bg_color']:'FFFFFF'));
	$tmpl->set_var('bg_color_title',(GlobalService::get('phpgw_info')['server']['login_bg_color_title']?GlobalService::get('phpgw_info')['server']['login_bg_color_title']:'486591'));

	if(GlobalService::get('phpgw_info')['server']['use_frontend_name'])
		$tmpl->set_var('frontend_name', " - ".GlobalService::get('phpgw_info')['server']['use_frontend_name']);

	if (substr(GlobalService::get('phpgw_info')['server']['login_logo_file'],0,4) == 'http')
	{
		$var['logo_file'] = GlobalService::get('phpgw_info')['server']['login_logo_file'];
	}
	else
	{
		$var['logo_file'] = GlobalService::get('phpgw')->common->image('phpgwapi',GlobalService::get('phpgw_info')['server']['login_logo_file']?GlobalService::get('phpgw_info')['server']['login_logo_file']:'logo');
	}
	$var['logo_url'] = GlobalService::get('phpgw_info')['server']['login_logo_url']?GlobalService::get('phpgw_info')['server']['login_logo_url']:'http://www.eGroupWare.org';
	if (substr($var['logo_url'],0,4) != 'http')
	{
		$var['logo_url'] = 'http://'.$var['logo_url'];
	}
	$var['logo_title'] = GlobalService::get('phpgw_info')['server']['login_logo_title']?GlobalService::get('phpgw_info')['server']['login_logo_title']:'www.eGroupWare.org';
	$tmpl->set_var($var);

	if (@GlobalService::get('phpgw_info')['server']['login_show_language_selection'])
	{
		$select_lang = '<select name="lang" onchange="'."location.href=location.href+(location.search?'&':'?')+'lang='+this.value".'">';
		$langs = GlobalService::get('phpgw')->translation->get_installed_langs();
		uasort($langs,'strcasecmp');
		foreach ($langs as $key => $name)	// if we have a translation use it
		{
			$select_lang .= "\n\t".'<option value="'.$key.'"'.($key == GlobalService::get('phpgw_info')['user']['preferences']['common']['lang'] ? ' selected="1"' : '').'>'.$name.'</option>';
		}
		$select_lang .= "\n</select>\n";
		$tmpl->set_var(array(
			'lang_language' => lang('Language'),
			'select_language' => $select_lang,
		));
	}
	else
	{
		$tmpl->set_block('login_form','language_select');
		$tmpl->set_var('language_select','');
	}

	$tmpl->set_var('autocomplete', (GlobalService::get('phpgw_info')['server']['autocomplete_login'] ? 'autocomplete="off"' : ''));

	$tmpl->pfp('loginout','login_form');
?>
