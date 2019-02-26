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


	class uimainscreen
	{
		var $public_functions = array('index' => True);

		function uimainscreen()
		{
			GlobalService::get('phpgw')->nextmatchs = CreateObject('phpgwapi.nextmatchs');
		}

		function index()
		{

			$html = createObject('phpgwapi.html');
			$section     = addslashes($_POST['section']);
			$select_lang = addslashes($_POST['select_lang']);
			$message     = addslashes($_POST['message']);

			$acl_ok = array();
			if (!GlobalService::get('phpgw')->acl->check('mainscreen_message_access',1,'admin'))
			{
				$acl_ok['mainscreen'] = True;
			}
			if (!GlobalService::get('phpgw')->acl->check('mainscreen_message_access',2,'admin'))
			{
				$acl_ok['loginscreen'] = True;
			}
			if (!GlobalService::get('phpgw')->acl->check('mainscreen_message_access',3,'admin'))
			{
				$acl_ok['loginhelp'] = True;
			}
			if ($_POST['cancel'] && !isset($_POST['message']) || 
			    !count($acl_ok) || $_POST['submit'] && !isset($acl_ok[$section]))
			{
				GlobalService::get('phpgw')->redirect_link('/admin/index.php');
			}

			GlobalService::get('phpgw')->template->set_file(array('message' => 'mainscreen_message.tpl'));
			GlobalService::get('phpgw')->template->set_block('message','form','form');
			GlobalService::get('phpgw')->template->set_block('message','row','row');
			GlobalService::get('phpgw')->template->set_block('message','row_2','row_2');

			if ($_POST['submit'])
			{
				GlobalService::get('phpgw')->db->query("DELETE FROM phpgw_lang WHERE message_id='$section" . "_message' AND app_name='"
					. "$section' AND lang='$select_lang'",__LINE__,__FILE__);
				GlobalService::get('phpgw')->db->query("INSERT INTO phpgw_lang (message_id,app_name,lang,content)VALUES ('$section" . "_message','$section','$select_lang','"
					. $message . "')",__LINE__,__FILE__);
					$feedback_message = '<center>'.lang('message has been updated').'</center>';
				
				$section = '';
			}
			if ($_POST['cancel'])	// back to section/lang-selection
			{
				$message = $section = '';
			}
			switch ($section)
			{
				case 'mainscreen':
					GlobalService::get('phpgw_info')['flags']['app_header'] = lang('Admin').' - '.lang('Edit main screen message') . ': '.strtoupper($select_lang);
					break;
				case 'loginscreen':
					GlobalService::get('phpgw_info')['flags']['app_header'] = lang('Admin').' - '.lang('Edit login screen message') . ': '.strtoupper($select_lang);
					break;
				case 'loginhelp':
					GlobalService::get('phpgw_info')['flags']['app_header'] = lang('Admin').' - '.lang('Edit login help message') . ': '.strtoupper($select_lang);
                                        break;
				default:
					GlobalService::get('phpgw_info')['flags']['app_header'] = lang('Admin').' - '.lang('Main screen message');
					break;
			}
			if(!@is_object(GlobalService::get('phpgw')->js))
			{
				GlobalService::get('phpgw')->js = CreateObject('phpgwapi.javascript');
			}

			

			
			if (empty($section))
			{

			   GlobalService::get('phpgw')->js->validate_file('jscode','openwindow','admin');
			   GlobalService::get('phpgw')->common->phpgw_header();
			   echo parse_navbar();
				  
			   
			   GlobalService::get('phpgw')->template->set_var('form_action',GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uimainscreen.index'));
				GlobalService::get('phpgw')->template->set_var('tr_color',GlobalService::get('phpgw_info')['theme']['th_bg']);
				GlobalService::get('phpgw')->template->set_var('value','&nbsp;');
				GlobalService::get('phpgw')->template->fp('rows','row_2',True);

				$tr_color = GlobalService::get('phpgw')->nextmatchs->alternate_row_color($tr_color);
				GlobalService::get('phpgw')->template->set_var('tr_color',$tr_color);

				$lang_select = '<select name="select_lang">';
				GlobalService::get('phpgw')->db->query("SELECT lang,phpgw_languages.lang_name,phpgw_languages.lang_id FROM phpgw_lang,phpgw_languages WHERE "
					. "phpgw_lang.lang=phpgw_languages.lang_id GROUP BY lang,phpgw_languages.lang_name,"
					. "phpgw_languages.lang_id ORDER BY lang",__LINE__,__FILE__);
				while (GlobalService::get('phpgw')->db->next_record())
				{
					$lang = GlobalService::get('phpgw')->db->f('lang');
					$lang_select .= '<option value="' . $lang . '"'.($lang == $select_lang ? ' selected' : '').'>' . 
						$lang . ' - ' . GlobalService::get('phpgw')->db->f('lang_name') . "</option>\n";
				}
				$lang_select .= '</select>';
				GlobalService::get('phpgw')->template->set_var('label',lang('Language'));
				GlobalService::get('phpgw')->template->set_var('value',$lang_select);
				GlobalService::get('phpgw')->template->fp('rows','row',True);

				$tr_color = GlobalService::get('phpgw')->nextmatchs->alternate_row_color($tr_color);
				GlobalService::get('phpgw')->template->set_var('tr_color',$tr_color);
				$select_section = '<select name="section">'."\n";
				foreach($acl_ok as $key => $val)
				{
					$select_section .= ' <option value="'.$key.'"'.
						($key == $_POST['section'] ? ' selected' : '') . '>' . 
						($key == 'mainscreen' ? lang('Main screen') : lang($key)) . "</option>\n";
				}
				$select_section .= '</select>';
				GlobalService::get('phpgw')->template->set_var('label',lang('Section'));
				GlobalService::get('phpgw')->template->set_var('value',$select_section);
				GlobalService::get('phpgw')->template->fp('rows','row',True);

				$tr_color = GlobalService::get('phpgw')->nextmatchs->alternate_row_color($tr_color);
				GlobalService::get('phpgw')->template->set_var('tr_color',$tr_color);
				GlobalService::get('phpgw')->template->set_var('value','<input type="submit" value="' . lang('Edit')
					. '"><input type="submit" name="cancel" value="'. lang('cancel') .'">');
				GlobalService::get('phpgw')->template->fp('rows','row_2',True);
			}
			else
			{
			   GlobalService::get('phpgw')->db->query("SELECT content FROM phpgw_lang WHERE lang='$select_lang' AND message_id='$section"
				. "_message'",__LINE__,__FILE__);
				GlobalService::get('phpgw')->db->next_record();
				
				$current_message = GlobalService::get('phpgw')->db->f('content');
				
				if($_POST['htmlarea'])
				{
				   $text_or_htmlarea=$html->htmlarea('message',stripslashes($current_message));
				   $htmlarea_button='<input type="submit" name="no-htmlarea" onclick="self.location.href=\''.GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uimainscreen.index&htmlarea=true').'\'" value="'.lang('disable WYSIWYG-editor').'">';	
				}
				else
				{
				   $text_or_htmlarea='<textarea name="message" style="width:100%; min-width:350px; height:300px;" wrap="virtual">' . stripslashes($current_message) . '</textarea>';
				   $htmlarea_button='<input type="submit" name="htmlarea" onclick="self.location.href=\''.GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uimainscreen.index&htmlarea=true').'\'" value="'.lang('activate WYSIWYG-editor').'">';

				}			   

				GlobalService::get('phpgw')->js->validate_file('jscode','openwindow','admin');
				GlobalService::get('phpgw')->common->phpgw_header();
				echo parse_navbar();
				
				GlobalService::get('phpgw')->template->set_var('form_action',GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uimainscreen.index'));
				GlobalService::get('phpgw')->template->set_var('select_lang',$select_lang);
				GlobalService::get('phpgw')->template->set_var('section',$section);
				GlobalService::get('phpgw')->template->set_var('tr_color',GlobalService::get('phpgw_info')['theme']['th_bg']);
				GlobalService::get('phpgw')->template->set_var('value','&nbsp;');
				GlobalService::get('phpgw')->template->fp('rows','row_2',True);

				$tr_color = GlobalService::get('phpgw')->nextmatchs->alternate_row_color($tr_color);
				GlobalService::get('phpgw')->template->set_var('tr_color',$tr_color);

				
				GlobalService::get('phpgw')->template->set_var('value',$text_or_htmlarea);
				
				
				
				GlobalService::get('phpgw')->template->fp('rows','row_2',True);

				$tr_color = GlobalService::get('phpgw')->nextmatchs->alternate_row_color($tr_color);
				GlobalService::get('phpgw')->template->set_var('tr_color',$tr_color);
				GlobalService::get('phpgw')->template->set_var('value','<input type="submit" name="submit" value="' . lang('Save')
				. '"><input type="submit" name="cancel" value="'. lang('cancel') .'">'.$htmlarea_button);
				GlobalService::get('phpgw')->template->fp('rows','row_2',True);
			}

			GlobalService::get('phpgw')->template->set_var('lang_cancel',lang('Cancel'));
			GlobalService::get('phpgw')->template->set_var('error_message',$feedback_message);
			GlobalService::get('phpgw')->template->pfp('out','form');
		}
	}
?>
