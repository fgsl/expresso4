<?php
  use Expresso\Core\GlobalService;

  /**************************************************************************\
  * eGroupWare - Setup                                                       *
  * http://www.egroupware.org                                                *
  * --------------------------------------------                             *
  * This file written by Tony Puglisi (Angles) <angles@aminvestments.com>    *
  *  and Miles Lott<milosch@groupwhere.org>                                  *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/


	class setup_html
	{
		/*!
		@function generate_header
		@abstract generate header.inc.php file output - NOT a generic html header function
		*/
		function generate_header()
		{
			// PHP will automatically replace any dots in incoming
			// variable names with underscores.

			GlobalService::get('header_template')->set_file(array('header' => 'header.inc.php.template'));
			GlobalService::get('header_template')->set_block('header','domain','domain');
			$var = Array();

			$deletedomain = get_var('deletedomain',Array('POST'));
			$domains = get_var('domains',Array('POST'));
			@reset($domains);
			while($domains && list($k,$v) = @each($domains))
			{
				if(isset($deletedomain[$k]))
				{
					continue;
				}
				$variableName = str_replace('.','_',$k);
				$dom = get_var('setting_'.$variableName,Array('POST'));
				GlobalService::get('header_template')->set_var('DB_DOMAIN',$v);
				while(list($x,$y) = @each($dom))
				{
					if(strtoupper($x) == 'CONFIG_PASS')
					{
						GlobalService::get('header_template')->set_var(strtoupper($x),md5($y));
					}
					else
					{
						GlobalService::get('header_template')->set_var(strtoupper($x),$y);
					}
				}
				/* Admin did not type a new password, so use the old one from the hidden field,
				 * which is already md5 encoded.
				 */
				if($dom['config_password'] && !$dom['config_pass'])
				{
					/* Real == hidden */
					GlobalService::get('header_template')->set_var('CONFIG_PASS',$dom['config_password']);
				}
				/* If the admin didn't select a db_port, set to the default */
				if(!$dom['db_port'])
				{
					GlobalService::get('header_template')->set_var('DB_PORT',GlobalService::get('default_db_ports')[$dom['db_type']]);
				}
				GlobalService::get('header_template')->parse('domains','domain',True);
			}

			GlobalService::get('header_template')->set_var('domain','');

			$setting = get_var('setting',Array('POST'));
			
			while($setting && list($k,$v) = @each($setting))
			{
				if(strtoupper($k) == 'HEADER_ADMIN_PASSWORD')
				{
					$var[strtoupper($k)] = md5($v);
				}
				else
				{
					$var[strtoupper($k)] = $v;
				}
			}

			/* Admin did not type a new header password, so use the old one from the hidden field,
			 * which is already md5 encoded.
			 */
			if($var['HEADER_ADMIN_PASS'] && empty($setting['HEADER_ADMIN_PASSWORD']))
			{
				/* Real == hidden */
				$var['HEADER_ADMIN_PASSWORD'] = $var['HEADER_ADMIN_PASS'];
			}
			GlobalService::get('header_template')->set_var($var);

			if ( trim(GlobalService::get('header_template')->get_var('domains')) === '' )
			{
				GlobalService::get('header_template')->set_var('domains', "/* no domains defined */");
			}

			return GlobalService::get('header_template')->parse('out','header');
		}

		function setup_tpl_dir($app_name='setup')
		{
			/* hack to get tpl dir */
			if (is_dir(PHPGW_SERVER_ROOT))
			{
				$srv_root = PHPGW_SERVER_ROOT . SEP . "$app_name" . SEP;
			}
			else
			{
				$srv_root = '';
			}

			$tpl_typical = 'templates' . SEP . 'default';
			$tpl_root = "$srv_root" ."$tpl_typical";
			return $tpl_root;
		}

		function show_header($title='',$nologoutbutton=False, $logoutfrom='config', $configdomain='')
		{
			// add a content-type header to overwrite an existing default charset in apache (AddDefaultCharset directiv)
			header('Content-type: text/html; charset='.lang('charset'));

			GlobalService::get('setup_tpl')->set_var('charset',lang('charset'));
			$style = array(
				'th_bg'		=> '#486591',
				'th_text'	=> '#FFFFFF',
				'row_on'	=> '#DDDDDD',
				'row_off'	=> '#EEEEEE',
				'banner_bg'	=> '#4865F1',
				'msg'		=> '#FF0000',
			);
			GlobalService::get('setup_tpl')->set_var($style);
			if ($nologoutbutton)
			{
				GlobalService::get('setup_tpl')->set_block('T_head','loged_in');
				GlobalService::get('setup_tpl')->set_var('loged_in','');
			}
			else
			{
				$btn_logout = '<a href="index.php?FormLogout=' . $logoutfrom . '" class="link">' . lang('Logout').'</a>';
				$check_install = '<a class="textsidebox" href="check_install.php">'.lang('Check installation').'</a>';
			}

			GlobalService::get('setup_tpl')->set_var('lang_setup', lang('setup'));
			GlobalService::get('setup_tpl')->set_var('page_title',$title);
			if ($configdomain == '')
			{
				GlobalService::get('setup_tpl')->set_var('configdomain','');
			}
			else
			{
				GlobalService::get('setup_tpl')->set_var('configdomain',' - ' . lang('Domain') . ': ' . $configdomain);
			}

			if(basename($_SERVER['SCRIPT_FILENAME']) != 'index.php')
			{
				$index_btn = '<a href="index.php" class="link">' . lang('Setup Main Menu') . '</a>';
				$index_img = '<img src="templates/default/images/orange-ball.png" alt="ball" />';				
			}

			GlobalService::get('setup_tpl')->set_var('lang_version',lang('version'));
                        if(!is_file(dirname( __FILE__ ) . '/../../infodist/ultima-revisao-svn.php'))
                            {
                                GlobalService::get('setup_tpl')->set_var('pgw_ver',@GlobalService::get('phpgw_info')['server']['versions']['phpgwapi']);
                            }
                        else
                            {
                                $aux = '';
                                include_once(dirname( __FILE__ ) . '/../../infodist/ultima-revisao-svn.php');
                                if(isset($ultima_revisao))
                                    {
                                        $aux =  '<br>' . $ultima_revisao;
                                    }
                                GlobalService::get('setup_tpl')->set_var('pgw_ver',@GlobalService::get('phpgw_info')['server']['versions']['phpgwapi'] . $aux);
                            }
                        
			GlobalService::get('setup_tpl')->set_var(array(
				'logoutbutton'  => $btn_logout,
				'indexbutton'   => $index_btn,
				'indeximg'      => $index_img,
				'check_install' => $check_install,
				'main_menu'     => lang('Setup Main Menu'),
				'user_login'    => lang('Back to user login')
			));
			GlobalService::get('setup_tpl')->pparse('out','T_head');
			/* $setup_tpl->set_var('T_head',''); */
		}

		function show_footer()
		{
			GlobalService::get('setup_tpl')->pparse('out','T_footer');
			unset(GlobalService::get('setup_tpl'));
		}

		function show_alert_msg($alert_word='Setup alert',$alert_msg='setup alert (generic)')
		{
			GlobalService::get('setup_tpl')->set_var('V_alert_word',$alert_word);
			GlobalService::get('setup_tpl')->set_var('V_alert_msg',$alert_msg);
			GlobalService::get('setup_tpl')->pparse('out','T_alert_msg');
		}

		function make_frm_btn_simple($pre_frm_blurb='',$frm_method='POST',$frm_action='',$input_type='submit',$input_value='',$post_frm_blurb='')
		{
			/* a simple form has simple components */
			$simple_form = $pre_frm_blurb  ."\n"
				. '<form method="' . $frm_method . '" action="' . $frm_action  . '">' . "\n"
				. '<input type="'  . $input_type . '" value="'  . $input_value . '">' . "\n"
				. '</form>' . "\n"
				. $post_frm_blurb . "\n";
			return $simple_form;
		}

		function make_href_link_simple($pre_link_blurb='',$href_link='',$href_text='default text',$post_link_blurb='')
		{
			/* a simple href link has simple components */
			$simple_link = $pre_link_blurb
				. '<a href="' . $href_link . '">' . $href_text . '</a> '
				. $post_link_blurb . "\n";
			return $simple_link;
		}

		function login_form()
		{
			/* begin use TEMPLATE login_main.tpl */
			GlobalService::get('setup_tpl')->set_var('ConfigLoginMSG',@GlobalService::get('phpgw_info')['setup']['ConfigLoginMSG']);
			GlobalService::get('setup_tpl')->set_var('HeaderLoginMSG',@GlobalService::get('phpgw_info')['setup']['HeaderLoginMSG']);
			GlobalService::get('setup_tpl')->set_var('lang_header_username',lang('Header Username'));
			GlobalService::get('setup_tpl')->set_var('lang_header_password',lang('Header Password'));
			GlobalService::get('setup_tpl')->set_var('lang_header_login',lang('Header Admin Login'));
			GlobalService::get('setup_tpl')->set_var('lang_config_login',lang('Setup/Config Admin Login'));
			GlobalService::get('setup_tpl')->set_var('lang_config_username',lang('Config Username'));
			GlobalService::get('setup_tpl')->set_var('lang_config_password',lang('Config Password'));
			GlobalService::get('setup_tpl')->set_var('lang_domain',lang('Domain'));

			GlobalService::get('setup_tpl')->set_var('lang_select',lang_select());

			if (GlobalService::get('phpgw_info')['setup']['stage']['header'] == '10')
			{
				/*
				 Begin use SUB-TEMPLATE login_stage_header,
				 fills V_login_stage_header used inside of login_main.tpl
				*/
				if (count(GlobalService::get('phpgw_domain')) > 1)
				{
					foreach(GlobalService::get('phpgw_domain') as $domain => $data)
					{
						$domains .= "<option value=\"$domain\" ".($domain == @GlobalService::get('phpgw_info')['setup']['LastDomain'] ? ' SELECTED' : '').">$domain</option>\n";
					}
					GlobalService::get('setup_tpl')->set_var('domains',$domains);

					// use BLOCK B_multi_domain inside of login_stage_header
					GlobalService::get('setup_tpl')->parse('V_multi_domain','B_multi_domain');
					// in this case, the single domain block needs to be nothing
					GlobalService::get('setup_tpl')->set_var('V_single_domain','');
				}
				else
				{
					reset(GlobalService::get('phpgw_domain'));
					$default_domain = each(GlobalService::get('phpgw_domain'));
					GlobalService::get('setup_tpl')->set_var('default_domain_zero',$default_domain[0]);

					/* Use BLOCK B_single_domain inside of login_stage_header */
					GlobalService::get('setup_tpl')->parse('V_single_domain','B_single_domain');
					/* in this case, the multi domain block needs to be nothing */
					GlobalService::get('setup_tpl')->set_var('V_multi_domain','');
				}
				/*
				 End use SUB-TEMPLATE login_stage_header
				 put all this into V_login_stage_header for use inside login_main
				*/
				GlobalService::get('setup_tpl')->parse('V_login_stage_header','T_login_stage_header');
			}
			else
			{
				/* begin SKIP SUB-TEMPLATE login_stage_header */
				GlobalService::get('setup_tpl')->set_var('V_multi_domain','');
				GlobalService::get('setup_tpl')->set_var('V_single_domain','');
				GlobalService::get('setup_tpl')->set_var('V_login_stage_header','');
			}
			/*
			 end use TEMPLATE login_main.tpl
			 now out the login_main template
			*/
			GlobalService::get('setup_tpl')->pparse('out','T_login_main');
		}

		function get_template_list()
		{
			$d = dir(PHPGW_SERVER_ROOT . '/phpgwapi/templates');

			while($entry = $d->read())
			{
				if ($entry != 'CVS' && $entry != '.' && $entry != '..')
				{
					$list[$entry]['name'] = $entry;
					$f = PHPGW_SERVER_ROOT . '/phpgwapi/templates/' . $entry . '/details.inc.php';
					if (file_exists ($f))
					{
						include($f);
						$list[$entry]['title'] = 'Use ' . GlobalService::get('phpgw_info')['template'][$entry]['title'] . 'interface';
					}
					else
					{
						$list[$entry]['title'] = $entry;
					}
				}
			}
			$d->close();
			reset ($list);
			return $list;
		}

		function list_themes()
		{
			$dh = dir(PHPGW_SERVER_ROOT . '/phpgwapi/themes');
			while ($file = $dh->read())
			{
				if (preg_match("/\.theme$/i", $file))
				{
					$list[] = substr($file,0,strpos($file,'.'));
				}
			}
			$dh->close();
			reset ($list);
			return $list;
		}
	}
?>
