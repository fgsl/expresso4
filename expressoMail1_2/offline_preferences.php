<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************/
	ini_set("display_errors","1");
	GlobalService::get('phpgw_info')['flags'] = array(
		'currentapp' => 'expressoMail1_2',
		'noheader'   => True, 
		'nonavbar'   => True,
		'enable_nextmatchs_class' => True
	);

	
	require_once('../header.inc.php');
	
	
	GlobalService::get('phpgw')->common->phpgw_header();
	print parse_navbar();

	GlobalService::get('phpgw')->template->set_file(array(
		'expressoMail_prefs' => 'offline_preferences.tpl'
	));

	GlobalService::get('phpgw')->template->set_var('url_offline','offline.php');
	GlobalService::get('phpgw')->template->set_var('url_icon','templates/default/images/offline.png');
	GlobalService::get('phpgw')->template->set_var('user_uid',GlobalService::get('phpgw_info')['user']['account_id']);
	GlobalService::get('phpgw')->template->set_var('user_login',GlobalService::get('phpgw_info')['user']['account_lid']);
	GlobalService::get('phpgw')->template->set_var('lang_install_offline',lang('Install Offline'));
	GlobalService::get('phpgw')->template->set_var('lang_pass_offline',lang('Offline Pass'));
	GlobalService::get('phpgw')->template->set_var('lang_expresso_offline',lang('Expresso Offline'));
	GlobalService::get('phpgw')->template->set_var('lang_uninstall_offline',lang('Uninstall Offline'));
	GlobalService::get('phpgw')->template->set_var('lang_gears_redirect',lang('To use local messages you have to install google gears. Would you like to be redirected to gears installation page?'));
	GlobalService::get('phpgw')->template->set_var('lang_offline_installed',lang('Offline success installed'));
	GlobalService::get('phpgw')->template->set_var('lang_offline_uninstalled',lang('Offline success uninstalled'));
	GlobalService::get('phpgw')->template->set_var('lang_only_spaces_not_allowed',lang('The password cant have only spaces'));
	GlobalService::get('phpgw')->template->set_var('go_back','../preferences/');

	GlobalService::get('phpgw')->template->set_var('value_save_in_folder',$o_folders);
	GlobalService::get('phpgw')->template->set_var('lang_save',lang('Save'));
	GlobalService::get('phpgw')->template->set_var('lang_cancel',lang('Cancel'));

	$proxies=explode(',',$_SERVER['HTTP_X_FORWARDED_HOST']);
        if (GlobalService::get('phpgw_info')['server']['use_https'] != 2)
            {
                $fwConstruct = 'http://';
            }
        else
            {
                $fwConstruct = 'https://';
            }
        $fwConstruct .= isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $proxies[0] : $_SERVER['HTTP_HOST'];
        GlobalService::get('phpgw')->template->set_var('root',$fwConstruct);
	GlobalService::get('phpgw')->template->set_var('offline_install_msg',lang("If you want to install a desktop shortcut for accessing the offline ExpressoMail please confirm it after pressing the Install offline button. </br> The application also can be accessed using the URL:" ));
	GlobalService::get('phpgw')->template->set_var('save_action',GlobalService::get('phpgw')->link('/'.'expressoMail1_2'.'/preferences.php'));
	GlobalService::get('phpgw')->template->set_var('th_bg',GlobalService::get('phpgw_info')["theme"][th_bg]);

	$tr_color = GlobalService::get('phpgw')->nextmatchs->alternate_row_color($tr_color);
	GlobalService::get('phpgw')->template->set_var('tr_color1',GlobalService::get('phpgw_info')['theme']['row_on']);
	GlobalService::get('phpgw')->template->set_var('tr_color2',GlobalService::get('phpgw_info')['theme']['row_off']);

	GlobalService::get('phpgw')->template->parse('out','expressoMail_prefs',True);
	GlobalService::get('phpgw')->template->p('out');
	// Com o M�dulo do IM habilitado, ocorre um erro no IE
	//GlobalService::get('phpgw')->common->phpgw_footer();
?>
