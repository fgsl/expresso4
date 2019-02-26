<?php
  use Expresso\Core\GlobalService;

  /**************************************************************************\
  * eGroupWare - Webpage news admin                                          *
  * http://www.egroupware.org                                                *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  * --------------------------------------------                             *
  * This program was sponsered by Golden Glair productions                   *
  * http://www.goldenglair.com                                               *
  \**************************************************************************/


	$phpgw_info = array();
	GlobalService::get('phpgw_info')['flags'] = array(
		'currentapp'              => 'news_admin',
		'enable_nextmatchs_class' => True
	);
	if($submit)
	{
		GlobalService::get('phpgw_info')['flags']['noheader'] = True;
		GlobalService::get('phpgw_info')['flags']['nonavbar'] = True;
	}
	include('../header.inc.php');

	GlobalService::get('phpgw')->sbox = CreateObject('phpgwapi.sbox');

	if($submit)
	{
		// Its possiable that this could get messed up becuase of there timezone offset
		if($date_ap == 'pm')
		{
			$date_hour = $date_hour + 12;
		}
		$date = mktime($date_hour,$date_min,$date_sec,$date_month,$date_day,$date_year);
		GlobalService::get('phpgw')->db->query("UPDATE phpgw_news SET news_subject='" . addslashes($subject) . "',"
			. "news_content='" . addslashes($content) . "',news_status='$status',news_date='$date' "
			. "WHERE news_id='$news_id'",__LINE__,__FILE__);
		Header('Location: ' . GlobalService::get('phpgw')->link('/news_admin/index.php'));
		GlobalService::get('phpgw')->common->phpgw_exit();
	}

	GlobalService::get('phpgw')->template->set_file(array(
		'form' => 'form.tpl',
		'row'  => 'form_row.tpl'
	));

	GlobalService::get('phpgw')->db->query("select * from phpgw_news where news_id='$news_id'",__LINE__,__FILE__);
	GlobalService::get('phpgw')->db->next_record();

	GlobalService::get('phpgw')->template->set_var('th_bg',GlobalService::get('phpgw_info')['theme']['th_bg']);
	GlobalService::get('phpgw')->template->set_var('bgcolor',GlobalService::get('phpgw_info')['theme']['bgcolor']);

	GlobalService::get('phpgw')->template->set_var('lang_header',lang('Edit news item'));
	GlobalService::get('phpgw')->template->set_var('form_action',GlobalService::get('phpgw')->link('/news_admin/edit.php','news_id=' . GlobalService::get('phpgw')->db->f('news_id')));
	GlobalService::get('phpgw')->template->set_var('form_button','<input type="submit" name="submit" value="' . lang("Edit") . '">');

	GlobalService::get('phpgw')->template->set_var('tr_color',GlobalService::get('phpgw')->nextmatchs->alternate_row_color());
	GlobalService::get('phpgw')->template->set_var('label',lang('subject') . ':');
	GlobalService::get('phpgw')->template->set_var('value','<input name="subject" size="60" value="' . GlobalService::get('phpgw')->db->f('news_subject') . '">');
	GlobalService::get('phpgw')->template->parse('rows','row',True);

	GlobalService::get('phpgw')->template->set_var('tr_color',GlobalService::get('phpgw')->nextmatchs->alternate_row_color());
	GlobalService::get('phpgw')->template->set_var('label',lang('Content') . ':');
	GlobalService::get('phpgw')->template->set_var('value','<textarea cols="60" rows="6" name="content" wrap="virtual">' . stripslashes(GlobalService::get('phpgw')->db->f('news_content')) . '</textarea>');
	GlobalService::get('phpgw')->template->parse('rows','row',True);

	GlobalService::get('phpgw')->template->set_var('tr_color',GlobalService::get('phpgw')->nextmatchs->alternate_row_color());
	GlobalService::get('phpgw')->template->set_var('label',lang('Status') . ':');
	$s[GlobalService::get('phpgw')->db->f('news_status')] = ' selected';
	GlobalService::get('phpgw')->template->set_var("value",'<select name="status"><option value="Active"' . $s['Active'] . '>'
		. lang('active') . '</option><option value="Disabled"' . $s['Disabled'] . '>'
		. lang('Disabled') . '</option></select>');
	GlobalService::get('phpgw')->template->parse('rows','row',True);

	GlobalService::get('phpgw')->template->set_var('tr_color',GlobalService::get('phpgw')->nextmatchs->alternate_row_color());
	GlobalService::get('phpgw')->template->set_var('label',lang('Date') . ':');

	$d_html = GlobalService::get('phpgw')->common->dateformatorder(GlobalService::get('phpgw')->sbox->getYears('date_year', date('Y',GlobalService::get('phpgw')->db->f('news_date'))),
		GlobalService::get('phpgw')->sbox->getMonthText('date_month', date('m',GlobalService::get('phpgw')->db->f('news_date'))),
		GlobalService::get('phpgw')->sbox->getDays('date_day', date('d',GlobalService::get('phpgw')->db->f('news_date')))
	);
	$d_html .= " - ";
	$d_html .= GlobalService::get('phpgw')->sbox->full_time('date_hour',GlobalService::get('phpgw')->common->show_date(GlobalService::get('phpgw')->db->f('news_date'),'h'),
		'date_min',GlobalService::get('phpgw')->common->show_date(GlobalService::get('phpgw')->db->f('news_date'),'i'),
		'date_sec',GlobalService::get('phpgw')->common->show_date(GlobalService::get('phpgw')->db->f('news_date'),'s'),
		'date_ap',GlobalService::get('phpgw')->common->show_date(GlobalService::get('phpgw')->db->f('news_date'),'a')
	);
	GlobalService::get('phpgw')->template->set_var('value',$d_html);

	$h = '<select name="status"><option value="Active"' . $s['Active'] . '>'
		. lang('Active') . '</option><option value="Disabled"' . $s['Disabled'] . '>'
		. lang('Disabled') . '</option></select>';
	GlobalService::get('phpgw')->template->parse('rows','row',True);

	GlobalService::get('phpgw')->template->pparse('out','form');
	GlobalService::get('phpgw')->common->phpgw_footer();
?>
