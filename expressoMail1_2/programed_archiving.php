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

	
	if($_POST["save"]=="save") {
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['keep_after_auto_archiving'])
			GlobalService::get('phpgw')->preferences->change('expressoMail','keep_after_auto_archiving',$_POST['keep_after_auto_archiving']);
		else
			GlobalService::get('phpgw')->preferences->add('expressoMail','keep_after_auto_archiving',$_POST['keep_after_auto_archiving']);

		GlobalService::get('phpgw')->preferences->save_repository();
		$url = (GlobalService::get('phpgw')->link('/'.'expressoMail1_2'));
		GlobalService::get('phpgw')->redirect($url);
	}
	else {
		GlobalService::get('phpgw')->preferences->read_repository();
		if (GlobalService::get('phpgw_info')['user']['preferences']['expressoMail']['keep_after_auto_archiving'])
			GlobalService::get('phpgw')->template->set_var('keep_after_auto_archiving_Yes_selected','selected');
		else {
			GlobalService::get('phpgw')->template->set_var('keep_after_auto_archiving_No_selected','');
			GlobalService::get('phpgw')->template->set_var('keep_after_auto_archiving_Yes_selected','');
		}
		$_SESSION['phpgw_info']['expressomail']['email_server'] = CreateObject('emailadmin.bo')->getProfile();
		$_SESSION['phpgw_info']['expressomail']['user'] = GlobalService::get('phpgw_info')['user'];
		$_SESSION['phpgw_info']['expressomail']['server'] = GlobalService::get('phpgw_info')['server'];
		$_SESSION['phpgw_info']['expressomail']['user']['email'] = GlobalService::get('phpgw')->preferences->values['email'];
		
		GlobalService::get('phpgw')->common->phpgw_header();
		print parse_navbar();
	
		GlobalService::get('phpgw')->template->set_file(array(
			'expressoMail_prefs' => 'programed_archiving.tpl'
		));
		
		//Checa gears instalado
		$check_gears = "if (!window.google || !google.gears) {
					temp = confirm('".lang('To use local messages you have to install google gears. Would you like to be redirected to gears installation page?')."');
					if (temp) {
						location.href = \"http://gears.google.com/?action=install&message=\"+
						\"Para utilizar o recurso de mensagens locais, instale o google gears&return=\" + document.location.href;
					}
					else {
						alert('".lang('Impossible install offline without Google Gears')."');
						location.href='../preferences/';
					}
			}";
		
		//Bibliotecas JS.
		$obj = createobject("expressoMail1_2.functions");
		echo "<script src='js/gears_init.js'></script>";
		$libs =  $obj -> getFilesJs("js/main.js," .
								"js/local_messages.js," .
								"js/offline_access.js," .
								"js/mail_sync.js," .
								"js/md5.js,",
								GlobalService::get('phpgw_info')['flags']['update_version']);
		
		GlobalService::get('phpgw')->template->set_var('libs',$libs);
		GlobalService::get('phpgw')->template->set_var('lib_modal',"<script src='js/modal/modal.js'>");
	
	
		//combo folders
		$imap_functions = CreateObject('expressoMail1_2.imap_functions');
		$all_folders = $imap_functions->get_folders_list();
		$options = " ";
		foreach($all_folders as $folder) {
			if(strpos($folder['folder_id'],'user')===false && is_array($folder)) {
				$folder_name = (strtoupper($folder['folder_name'])=="INBOX" ||
								strtoupper($folder['folder_name'])=="SENT" ||
								strtoupper($folder['folder_name'])=="TRASH" ||
								strtoupper($folder['folder_name'])=="DRAFTS")?lang($folder['folder_name']):$folder['folder_name'];
				
				$folder['folder_id'] = str_replace(" ","#",$folder['folder_id']);
				
				$options.="<option value='".$folder['folder_id']."'>".$folder_name."</option>";
			}
				
		}
		GlobalService::get('phpgw')->template->set_var('all_folders',$options);
		echo '<script language="javascript">var array_lang = new Array();</script>';
		include("inc/load_lang.php");	
	
		GlobalService::get('phpgw')->template->set_var('lang_Would_you_like_to_keep_messages_on_server_?',lang("Would you like to keep archived messages?"));
		GlobalService::get('phpgw')->template->set_var('lang_check_redirect',$check_gears);
		GlobalService::get('phpgw')->template->set_var('lang_folders_to_sync',lang('Folders to sync'));
		GlobalService::get('phpgw')->template->set_var('lang_add',lang('Add'));
		GlobalService::get('phpgw')->template->set_var('lang_save',lang('Save'));
		GlobalService::get('phpgw')->template->set_var('lang_Yes',lang('Yes'));
		GlobalService::get('phpgw')->template->set_var('lang_No',lang('No'));
		GlobalService::get('phpgw')->template->set_var('account_id',GlobalService::get('phpgw_info')['user']['account_id']);
		GlobalService::get('phpgw')->template->set_var('lang_rem',lang('Remove'));
		GlobalService::get('phpgw')->template->set_var('go_back','../preferences/');
	
		GlobalService::get('phpgw')->template->set_var('value_save_in_folder',$o_folders);
		GlobalService::get('phpgw')->template->set_var('lang_save',lang('Save'));
		GlobalService::get('phpgw')->template->set_var('lang_cancel',lang('Cancel'));
		
		GlobalService::get('phpgw')->template->set_var('save_action',GlobalService::get('phpgw')->link('/'.'expressoMail1_2'.'/programed_archiving.php'));
		GlobalService::get('phpgw')->template->set_var('th_bg',GlobalService::get('phpgw_info')["theme"][th_bg]);
	
		$tr_color = GlobalService::get('phpgw')->nextmatchs->alternate_row_color($tr_color);
		GlobalService::get('phpgw')->template->set_var('tr_color1',GlobalService::get('phpgw_info')['theme']['row_on']);
		GlobalService::get('phpgw')->template->set_var('tr_color2',GlobalService::get('phpgw_info')['theme']['row_off']);
	
		GlobalService::get('phpgw')->template->parse('out','expressoMail_prefs',True);
		GlobalService::get('phpgw')->template->p('out');
	}
	
	
?>
