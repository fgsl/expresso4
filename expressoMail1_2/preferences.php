<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************/
	
	GlobalService::get('phpgw_info')['flags'] = array(
		'currentapp' => 'expressoMail1_2',
		'noheader'   => True, 
		'nonavbar'   => True,
		'enable_nextmatchs_class' => True
	);

	
	require_once('../header.session.inc.php');
	include('inc/class.imap_functions.inc.php');	
	include_once("../prototype/library/fckeditor/fckeditor.php");
	
	if (!$_POST['try_saved'])
	{
		// Read Config and get default values;
		
		GlobalService::get('phpgw')->preferences->read_repository();
		// Loading Admin Config Module
    	$c = CreateObject('phpgwapi.config','expressoMail1_2');
    	$c->read_repository();
    	$current_config = $c->config_data;    
		
		
		if(GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['max_email_per_page'])
			GlobalService::get('phpgw')->template->set_var('option_'.GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['max_email_per_page'].'_selected','selected');
		else
		GlobalService::get('phpgw')->template->set_var('option_50_selected','selected');		

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['save_deleted_msg'])		
			GlobalService::get('phpgw')->template->set_var('checked_save_deleted_msg','checked');
		else
			GlobalService::get('phpgw')->template->set_var('checked_save_deleted_msg','');

		if(GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['delete_trash_messages_after_n_days'])
			GlobalService::get('phpgw')->template->set_var('delete_trash_messages_option_'.GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['delete_trash_messages_after_n_days'].'_selected','selected');
		else
			GlobalService::get('phpgw')->template->set_var('delete_trash_messages_option_0_selected','selected');		
		
		if(GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['delete_spam_messages_after_n_days'])
			GlobalService::get('phpgw')->template->set_var('delete_spam_messages_option_'.GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['delete_spam_messages_after_n_days'].'_selected','selected');
		else
			GlobalService::get('phpgw')->template->set_var('delete_spam_messages_option_0_selected','selected');
		
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['delete_and_show_previous_message'])		
			GlobalService::get('phpgw')->template->set_var('checked_delete_and_show_previous_message','checked');
		else
			GlobalService::get('phpgw')->template->set_var('checked_delete_and_show_previous_message','');

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['alert_new_msg'])		
			GlobalService::get('phpgw')->template->set_var('checked_alert_new_msg','checked');
		else
			GlobalService::get('phpgw')->template->set_var('checked_alert_new_msg','');

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['mainscreen_showmail'])
			GlobalService::get('phpgw')->template->set_var('checked_mainscreen_showmail','checked');
		else
			GlobalService::get('phpgw')->template->set_var('checked_mainscreen_showmail','');

		if (!is_numeric($current_config['expressoMail_Number_of_dynamic_contacts'])) 
		{										
			GlobalService::get('phpgw')->template->set_var('checked_dynamic_contacts','');
			GlobalService::get('phpgw')->template->set_var('checked_dynamic_contacts','disabled');
		}
		else
		{
			if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_dynamic_contacts'])
				GlobalService::get('phpgw')->template->set_var('checked_dynamic_contacts','checked');
			else
				GlobalService::get('phpgw')->template->set_var('checked_dynamic_contacts','');
		}

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_shortcuts'])
			GlobalService::get('phpgw')->template->set_var('checked_shortcuts','checked');
		else
			GlobalService::get('phpgw')->template->set_var('checked_shortcuts','');

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['auto_save_draft'])
			GlobalService::get('phpgw')->template->set_var('checked_auto_save_draft','checked');
		else
			GlobalService::get('phpgw')->template->set_var('checked_auto_save_draft','');


        if(GlobalService::get('phpgw_info')['server']['use_assinar_criptografar'])
		{
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_signature_digital_cripto'])
			{
			GlobalService::get('phpgw')->template->set_var('checked_use_signature_digital_cripto','checked');
			GlobalService::get('phpgw')->template->set_var('display_digital','');
			GlobalService::get('phpgw')->template->set_var('display_cripto','');
			}
		else
			{
			GlobalService::get('phpgw')->template->set_var('checked_use_signature_digital','');
			GlobalService::get('phpgw')->template->set_var('display_digital','style="display: none;"');
			GlobalService::get('phpgw')->template->set_var('display_cripto','style="display: none;"');
			}

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_signature_digital'])
			GlobalService::get('phpgw')->template->set_var('checked_use_signature_digital','checked');
		else
			GlobalService::get('phpgw')->template->set_var('checked_use_signature_digital','');
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_signature_cripto'])
			GlobalService::get('phpgw')->template->set_var('checked_use_signature_cripto','checked');
		else
			GlobalService::get('phpgw')->template->set_var('checked_use_signature_cripto','');
		}
		else
		{
			GlobalService::get('phpgw')->template->set_var('display_digital','style="display: none;"');
			GlobalService::get('phpgw')->template->set_var('display_cripto','style="display: none;"');
			GlobalService::get('phpgw')->template->set_var('display_digital_cripto','style="display: none;"');
		}


		// Insert new expressoMail preference use_signature: defines if the signature will be automatically inserted
		// at the e-mail body
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_signature'])
			GlobalService::get('phpgw')->template->set_var('checked_use_signature','checked');
		else
			GlobalService::get('phpgw')->template->set_var('checked_use_signature','');

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['signature'])
			GlobalService::get('phpgw')->template->set_var('text_signature',GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['signature']);
		else
			GlobalService::get('phpgw')->template->set_var('text_signature','');
		
		if(GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['type_signature']){
			GlobalService::get('phpgw')->template->set_var('type_signature_option_'.GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['type_signature'].'_selected','selected');
			GlobalService::get('phpgw')->template->set_var('type_signature_td_'.(GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['type_signature'] == 'html' ? 'text' : 'html'),'display:none');
		}
		else{
			GlobalService::get('phpgw')->template->set_var('type_signature_option_text_selected','selected');
			GlobalService::get('phpgw')->template->set_var('type_signature_td_html','display:none');
		}

		// BEGIN FCKEDITOR
		$oFCKeditor = new FCKeditor('html_signature') ;
		$oFCKeditor->BasePath 	= '../prototype/library/fckeditor/';
		$oFCKeditor->ToolbarSet = 'ExpressoLivre';
		if(is_array(GlobalService::get('phpgw_info')['user']['preferences']['expressoMail'])) {
			$oFCKeditor->Value 	= GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['signature'];
		}
		// First Time: The user has no preferences. If the template file exists, then it loads a default signature.
		// See signature_example.tpl
		elseif(file_exists(GlobalService::get('phpgw')->template->root.'/signature.tpl')){
			$filein = fopen(GlobalService::get('phpgw')->template->root.'/signature.tpl',"r");
			while (!feof ($filein))
				$oFCKeditor->Value .= fgets($filein, 1024); 
		}
		$oFCKeditor->Value = str_replace("{full_name}",$phpgw_info['user']['fullname'],$oFCKeditor->Value);
		$oFCKeditor->Value = str_replace("{first_name}",$phpgw_info['user']['firstname'],$oFCKeditor->Value);
		
		GlobalService::get('phpgw')->template->set_var('rtf_signature',$oFCKeditor->Create());
		GlobalService::get('phpgw')->template->set_var('text_signature',strip_tags($oFCKeditor->Value));
		// END FCKEDITOR
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['hide_folders'])
			GlobalService::get('phpgw')->template->set_var('checked_menu','checked');
		else
			GlobalService::get('phpgw')->template->set_var('checked_menu','');

		if(GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['line_height'])
			GlobalService::get('phpgw')->template->set_var('line_height_option_'.GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['line_height'].'_selected','selected');
		else
			GlobalService::get('phpgw')->template->set_var('line_height_option_20_selected','selected');
		
		if(GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['font_size'])
			GlobalService::get('phpgw')->template->set_var('font_size_option_'.GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['font_size'].'_selected','selected');
		else
			GlobalService::get('phpgw')->template->set_var('font_size_option_11_selected','selected');
	    $c = CreateObject('phpgwapi.config','expressoMail1_2');
	    $c->read_repository();
	    $current_config = $c->config_data;
		
		if($current_config['enable_local_messages']!='True') {
			GlobalService::get('phpgw')->template->set_var('open_comment_local_messages_config',"<!--");
			GlobalService::get('phpgw')->template->set_var('close_comment_local_messages_config',"-->");
		}
		else {
			GlobalService::get('phpgw')->template->set_var('open_comment_local_messages_config'," ");
			GlobalService::get('phpgw')->template->set_var('close_comment_local_messages_config'," ");
		}
		
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_local_messages'])
			GlobalService::get('phpgw')->template->set_var('use_local_messages_option_Yes_selected','selected');
		else {
			GlobalService::get('phpgw')->template->set_var('use_local_messages_option_No_selected','');
			GlobalService::get('phpgw')->template->set_var('use_local_messages_option_Yes_selected','');
		}
		
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['keep_archived_messages'])
			GlobalService::get('phpgw')->template->set_var('keep_archived_messages_option_Yes_selected','selected');
		else {
			GlobalService::get('phpgw')->template->set_var('keep_archived_messages_option_No_selected','');
			GlobalService::get('phpgw')->template->set_var('keep_archived_messages_option_Yes_selected','');
		}		
		
	}
	else //Save Config
	{
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['max_email_per_page'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','max_email_per_page',$_POST['max_emails_per_page']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','max_email_per_page',$_POST['max_emails_per_page']);
		
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['save_deleted_msg'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','save_deleted_msg',$_POST['save_deleted_msg']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','save_deleted_msg',$_POST['save_deleted_msg']);

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['delete_trash_messages_after_n_days'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','delete_trash_messages_after_n_days',$_POST['delete_trash_messages_after_n_days']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','delete_trash_messages_after_n_days',$_POST['delete_trash_messages_after_n_days']);
		
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['delete_spam_messages_after_n_days'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','delete_spam_messages_after_n_days',$_POST['delete_spam_messages_after_n_days']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','delete_spam_messages_after_n_days',$_POST['delete_spam_messages_after_n_days']);
		
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['delete_and_show_previous_message'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','delete_and_show_previous_message',$_POST['delete_and_show_previous_message']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','delete_and_show_previous_message',$_POST['delete_and_show_previous_message']);
		
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['alert_new_msg'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','alert_new_msg',$_POST['alert_new_msg']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','alert_new_msg',$_POST['alert_new_msg']);

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['mainscreen_showmail'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','mainscreen_showmail',$_POST['mainscreen_showmail']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','mainscreen_showmail',$_POST['mainscreen_showmail']);

		if (!is_numeric($_SESSION['phpgw_info']['server']['expressomail']['expressoMail_Number_of_dynamic_contacts'])) 
		{										
			GlobalService::get('phpgw')->template->set_var('checked_dynamic_contacts','');
			GlobalService::get('phpgw')->template->set_var('checked_dynamic_contacts','disabled');
		}
		else
		{
			if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_dynamic_contacts'])
			{
				GlobalService::get('phpgw')->preferences->change('expressoMail','use_dynamic_contacts',$_POST['use_dynamic_contacts']);
				if($_POST['use_dynamic_contacts'] == '')
				{
					$contacts = CreateObject('expressoMail1_2.dynamic_contacts');
					$contacts->delete_dynamic_contacts();
				}
			}
			else
			GlobalService::get('phpgw')->preferences->add('expressoMail','use_dynamic_contacts',$_POST['use_dynamic_contacts']);
		}

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_shortcuts'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','use_shortcuts',$_POST['use_shortcuts']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','use_shortcuts',$_POST['use_shortcuts']);
			
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['auto_save_draft'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','auto_save_draft',$_POST['auto_save_draft']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','auto_save_draft',$_POST['auto_save_draft']);	

        if(GlobalService::get('phpgw_info')['server']['use_assinar_criptografar'])
		{
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_signature_digital_cripto'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','use_signature_digital_cripto',$_POST['use_signature_digital_cripto']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','use_signature_digital_cripto',$_POST['use_signature_digital_cripto']);
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_signature_digital'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','use_signature_digital',$_POST['use_signature_digital']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','use_signature_digital',$_POST['use_signature_digital']);
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_signature_cripto'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','use_signature_cripto',$_POST['use_signature_cripto']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','use_signature_cripto',$_POST['use_signature_cripto']);
		}
		// Insert new expressoMail preference use_signature: defines if the signature will be automatically inserted
		// at the e-mail body
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_signature'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','use_signature',$_POST['use_signature']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','use_signature',$_POST['use_signature']);

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['signature'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','signature',$_POST['signature']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','signature',$_POST['signature']);

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['type_signature'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','type_signature',$_POST['type_signature']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','type_signature',$_POST['type_signature']);

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['hide_folders'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','hide_folders',$_POST['check_menu']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','hide_folders',$_POST['check_menu']);
			
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['save_in_folder'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','save_in_folder',$_POST['save_in_folder']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','save_in_folder',$_POST['save_in_folder']);

		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['line_height'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','line_height',$_POST['line_height']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','line_height',$_POST['line_height']);
		
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['font_size'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','font_size',$_POST['font_size']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','font_size',$_POST['font_size']);		
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['use_local_messages'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','use_local_messages',$_POST['use_local_messages']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','use_local_messages',$_POST['use_local_messages']);
			
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['keep_archived_messages'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','keep_archived_messages',$_POST['keep_archived_messages']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','keep_archived_messages',$_POST['keep_archived_messages']);			

		GlobalService::get('phpgw')->preferences->save_repository();
		
		// Back to preferences.
		$url = (GlobalService::get('phpgw')->link('/'.'expressoMail1_2'.'/save_preferences.php'));
		GlobalService::get('phpgw')->redirect($url);
	}
	
	GlobalService::get('phpgw')->common->phpgw_header();
	print parse_navbar();

	GlobalService::get('phpgw')->template->set_file(array(
		'expressoMail_prefs' => 'preferences.tpl'
	));

	GlobalService::get('phpgw')->template->set_var('lang_config_expressoMail',lang('Config for ExpressoMail'));
	GlobalService::get('phpgw')->template->set_var('lang_max_emails_per_page',lang('What is the maximum number of messages per page?'));
	GlobalService::get('phpgw')->template->set_var('lang_save_deleted_msg',lang('Save deleted messages in trash folder?'));
	GlobalService::get('phpgw')->template->set_var('lang_delete_trash_messages_after_n_days',lang('Delete trash messages after how many days?'));
	GlobalService::get('phpgw')->template->set_var('lang_delete_spam_messages_after_n_days',lang('Delete spam messages after how many days?'));
	GlobalService::get('phpgw')->template->set_var('lang_delete_and_show_previous_message',lang('Show previous message, after delete actual message?'));
	GlobalService::get('phpgw')->template->set_var('lang_alert_new_msg',lang('Do you wanna receive an alert for new messages?'));
	GlobalService::get('phpgw')->template->set_var('lang_hook_home',lang('Show default view on main screen?'));
	GlobalService::get('phpgw')->template->set_var('lang_save_in_folder',lang('Save sent messages in folder'));
	GlobalService::get('phpgw')->template->set_var('lang_hide_menu',lang('Hide menu folders?'));
	GlobalService::get('phpgw')->template->set_var('lang_line_height',lang('What is the height of the lines in the list of messages?'));
	GlobalService::get('phpgw')->template->set_var('lang_font_size',lang('What the font size in the list of messages?'));
	GlobalService::get('phpgw')->template->set_var('lang_use_dynamic_contacts',lang('Use dynamic contacts?'));
	GlobalService::get('phpgw')->template->set_var('lang_use_shortcuts',lang('Use shortcuts?'));
	GlobalService::get('phpgw')->template->set_var('lang_auto_save_draft',lang('Auto save draft'));
	GlobalService::get('phpgw')->template->set_var('lang_signature',lang('Signature'));
	GlobalService::get('phpgw')->template->set_var('lang_none',lang('None'));
	GlobalService::get('phpgw')->template->set_var('one_day',lang('1 Day'));
	GlobalService::get('phpgw')->template->set_var('two_days',lang('2 Days'));
	GlobalService::get('phpgw')->template->set_var('three_days',lang('3 Days'));
	GlobalService::get('phpgw')->template->set_var('four_days',lang('4 Days'));
	GlobalService::get('phpgw')->template->set_var('five_days',lang('5 Day'));
	GlobalService::get('phpgw')->template->set_var('small',lang('Small'));
	GlobalService::get('phpgw')->template->set_var('medium',lang('Medium'));
	GlobalService::get('phpgw')->template->set_var('normal',lang('Normal'));
	GlobalService::get('phpgw')->template->set_var('simple_text',lang('Simple Text'));
	GlobalService::get('phpgw')->template->set_var('html_text',lang('Rich Text'));
	GlobalService::get('phpgw')->template->set_var('lang_config_signature',lang('Signature Configuration'));
	GlobalService::get('phpgw')->template->set_var('lang_type_signature',lang('Signature type'));
	GlobalService::get('phpgw')->template->set_var('big',lang('Big'));
    //GlobalService::get('phpgw')->template->set_var('lang_use_signature_digital_cripto',lang('Possibilitar <b>assinar/criptografar</b> digitalmente a mensagem?'));
	GlobalService::get('phpgw')->template->set_var('lang_use_signature_digital_cripto',lang('Enable digitally sign/cipher the message?'));
	GlobalService::get('phpgw')->template->set_var('lang_use_signature_digital',lang('Always sign message digitally?'));
	GlobalService::get('phpgw')->template->set_var('lang_use_signature_cripto',lang('Always cipher message digitally?'));
	GlobalService::get('phpgw')->template->set_var('lang_use_signature',lang('Insert signature automatically in new messages?'));
	GlobalService::get('phpgw')->template->set_var('lang_signature',lang('Signature'));
	GlobalService::get('phpgw')->template->set_var('lang_Would_you_like_to_keep_archived_messages_?',lang('Would you like to keep archived messages?'));
	GlobalService::get('phpgw')->template->set_var('lang_Yes',lang('Yes'));
	GlobalService::get('phpgw')->template->set_var('lang_No',lang('No'));
	GlobalService::get('phpgw')->template->set_var('lang_Would_you_like_to_use_local_messages_?',lang('Would you like to use local messages?'));

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
	
	$_SESSION['phpgw_info']['expressomail']['email_server'] = CreateObject('emailadmin.bo')->getProfile();
	$_SESSION['phpgw_info']['expressomail']['user'] = GlobalService::get('phpgw_info')['user'];
	$e_server = $_SESSION['phpgw_info']['expressomail']['email_server'];
 	$imap = CreateObject('expressoMail1_2.imap_functions');	
	$save_in_folder_selected = GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['save_in_folder'];	
	// Load Special Folders (Sent, Trash, Draft, Spam) from EmailAdmin (if exists, else get_lang)
	$specialFolders = array ("Trash" => lang("Trash"), "Drafts" => lang("Drafts"), "Spam" => lang("Spam"), "Sent" => lang("Sent"));	
	foreach ($specialFolders as $key => $value){
		if($e_server['imapDefault'.$key.'Folder'])
			$specialFolders[$key] = $e_server['imapDefault'.$key.'Folder'];
	}        
	// First access on ExpressoMail, load default preferences...
	if(!GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']) {
		GlobalService::get('phpgw')->template->set_var('checked_save_deleted_msg','checked');
		GlobalService::get('phpgw')->template->set_var('checked_delete_and_show_previous_message','checked');
		GlobalService::get('phpgw')->template->set_var('checked_alert_new_msg','checked');
		GlobalService::get('phpgw')->template->set_var('checked_use_signature','checked');
		GlobalService::get('phpgw')->template->set_var('checked_mainscreen_showmail','checked');
		$save_in_folder_selected = "INBOX".$e_server['imapDelimiter'].$specialFolders["Sent"];
	}
	$o_folders = "<option value='-1' ".(!$save_in_folder_selected ? 'selected' : '' ).">".lang("Select on send")."</option>";	
	
	foreach($imap -> get_folders_list() as $id => $folder){
		// Ignores numeric indexes and shared folders....
		if(!is_numeric($id) || (strstr($folder['folder_id'],"user".$e_server['imapDelimiter'])))
			continue;
		// Translate INBOX (root folder)
		elseif (strtolower($folder['folder_name']) == "inbox") 
			$folder['folder_name'] = lang("Inbox");
		// Translate Special Folders
 		elseif (($keyFolder = array_search($folder['folder_name'], $specialFolders)) !== false)
 			$folder['folder_name'] = lang($keyFolder);
		// Identation for subfolders
		$folder_id = explode($e_server['imapDelimiter'],$folder['folder_id']);		
		$level = count($folder_id);
		$ident = '';
		for($i = 2; $level > 2 && $i < $level;$i++)
			$ident .= ' - ';
		$o_folders.= "<option value='".$folder['folder_id']."' ".($save_in_folder_selected == $folder['folder_id'] ? 'selected' : '' ).">".$ident.$folder['folder_name']."</option>";			
	}

	GlobalService::get('phpgw')->template->set_var('value_save_in_folder',$o_folders);
	GlobalService::get('phpgw')->template->set_var('lang_save',lang('Save'));
	GlobalService::get('phpgw')->template->set_var('lang_cancel',lang('Cancel'));
	
	GlobalService::get('phpgw')->template->set_var('save_action',GlobalService::get('phpgw')->link('/'.'expressoMail1_2'.'/preferences.php'));
	GlobalService::get('phpgw')->template->set_var('th_bg',GlobalService::get('phpgw_info')["theme"][th_bg]);

	$tr_color = GlobalService::get('phpgw')->nextmatchs->alternate_row_color($tr_color);
	GlobalService::get('phpgw')->template->set_var('tr_color1',GlobalService::get('phpgw_info')['theme']['row_on']);
	GlobalService::get('phpgw')->template->set_var('tr_color2',GlobalService::get('phpgw_info')['theme']['row_off']);

	GlobalService::get('phpgw')->template->parse('out','expressoMail_prefs',True);
	GlobalService::get('phpgw')->template->p('out');
	// Com o M�dulo do IM habilitado, ocorre um erro no IE
	//GlobalService::get('phpgw')->common->phpgw_footer();
