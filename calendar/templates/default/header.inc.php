<?php
  use Expresso\Core\GlobalService;

  /**************************************************************************\
  * eGroupWare                                                               *
  * http://www.egroupware.org                                                *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/


	function add_col(&$tpl,$str)
	{
		$tpl->set_var('str',$str);
		$tpl->parse('header_column','head_col',True);
	}

	function add_image_ahref($link,$image,$alt)
	{
		return '<a href="'.$link.'"><img src="'.GlobalService::get('phpgw')->common->image('calendar',$image).'" alt="'.$alt.'" title="'.$alt.'" border="0"></a>';
	}

	$refer = explode('.',GlobalService::get('HTTP_GET_VARS')['menuaction']);
	$referrer = $refer[2];

	$templates = Array(
		'head_tpl'	=> 'head.tpl',
		'form_button_dropdown'	=> 'form_button_dropdown.tpl',
		'form_button_script'	=> 'form_button_script.tpl'
	);
	$tpl->set_file($templates);
	$tpl->set_block('head_tpl','head','head');
	$tpl->set_block('head_tpl','head_table','head_table');
	$tpl->set_block('head_tpl','head_col','head_col');
	$tpl->set_block('form_button_script','form_button');

	if(floor(phpversion()) >= 4)
	{
		$tpl->set_var('cols',8);
	}
	else
	{
		$tpl->set_var('cols',7);
	}

	$today = date('Ymd',GlobalService::get('phpgw')->datetime->users_localtime);

	$col_width = 12;

	add_col($tpl,'  <td width="2%">&nbsp;</td>');

	add_col($tpl,'  <td width="2%">'.add_image_ahref($this->page('day','&date='.$today),'today',lang('Today')).'</td>');

	add_col($tpl,'  <td width="2%" align="left">'.add_image_ahref($this->page('week','&date='.$today),'week',lang('This week')).'</td>');

	add_col($tpl,'  <td width="2%" align="left">'.add_image_ahref($this->page('month','&date='.$today),'month',lang('This month')).'</td>');

	add_col($tpl,'  <td width="2%" align="left">'.add_image_ahref($this->page('year','&date='.$today),'year',lang('This Year')).'</td>');

	if(floor(phpversion()) >= 4)
	{
		add_col($tpl,'  <td width="2%" align="left">'.add_image_ahref($this->page('planner','&date='.$today),'planner',lang('Planner')).'</td>');
		$col_width += 2;
	}

	//add_col($tpl,'  <td width="2%" align="left">'.add_image_ahref($this->page('matrixselect'),'view',lang('Daily Matrix View')).'</td>');

	add_col($tpl,'  <td width="'.(100 - $col_width).'%" align="left"'.(floor(phpversion()) < 4?' colspan="2"':'').'>&nbsp;</td>');

	$tpl->parse('row','head_table',True);

	$tpl->set_var('header_column','');
	$tpl->set_var('cols',$cols);

	if($referrer!='view')
	{
		$remainder = 72;
		
		$date = (isset(GlobalService::get('date'))?GlobalService::get('date'):'');
		$date = (isset(GlobalService::get('HTTP_GET_VARS')['date'])?GlobalService::get('HTTP_GET_VARS')['date']:$date);
		$date = ($date=='' && isset(GlobalService::get('HTTP_POST_VARS')['date'])?GlobalService::get('HTTP_POST_VARS')['date']:$date);

		$base_hidden_vars = '<input type="hidden" name="from" value="'.GlobalService::get('HTTP_GET_VARS')['menuaction'].'">'."\n";
		if(isset(GlobalService::get('HTTP_GET_VARS')['cal_id']) && GlobalService::get('HTTP_GET_VARS')['cal_id'] != 0)
		{
			$base_hidden_vars .= '    <input type="hidden" name="cal_id" value="'.GlobalService::get('HTTP_GET_VARS')['cal_id'].'">'."\n";
		}
		if(isset(GlobalService::get('HTTP_POST_VARS')['keywords']) && GlobalService::get('HTTP_POST_VARS')['keywords'])
		{
			$base_hidden_vars .= '    <input type="hidden" name="keywords" value="'.GlobalService::get('HTTP_POST_VARS')['keywords'].'">'."\n";
		}
		if(isset(GlobalService::get('HTTP_POST_VARS')['matrixtype']) && GlobalService::get('HTTP_POST_VARS')['matrixtype'])
		{
			$base_hidden_vars .= '    <input type="hidden" name="matrixtype" value="'.GlobalService::get('HTTP_POST_VARS')['matrixtype'].'">'."\n";
		}
		if($date)
		{
			$base_hidden_vars .= '    <input type="hidden" name="date" value="'.$date.'">'."\n";
		}
		$base_hidden_vars .= '    <input type="hidden" name="month" value="'.$this->bo->month.'">'."\n";
		$base_hidden_vars .= '    <input type="hidden" name="day" value="'.$this->bo->day.'">'."\n";
		$base_hidden_vars .= '    <input type="hidden" name="year" value="'.$this->bo->year.'">'."\n";
		
		if(isset(GlobalService::get('HTTP_POST_VARS')['participants']) && GlobalService::get('HTTP_POST_VARS')['participants'])
		{
			for ($i=0;$i<count(GlobalService::get('HTTP_POST_VARS')['participants']);$i++)
			{
				$base_hidden_vars .= '    <input type="hidden" name="participants[]" value="'.GlobalService::get('HTTP_POST_VARS')['participants'][$i].'">'."\n";
			}
		}

		$var = Array(
			'form_width' => '28',
			'form_link'	=> $this->page($referrer),
			'form_name'	=> 'cat_id',
			'title'	=> lang('Category'),
			'hidden_vars'	=> $base_hidden_vars,
			'form_options'	=> '<option value="0">'.lang('All').'</option>'.$this->cat->formated_list('select','all',$this->bo->cat_id,'True'),
			'button_value'	=> lang('Go!')
		);
		$tpl->set_var($var);
		$tpl->set_var('str',$tpl->fp('out','form_button_dropdown'));
		$tpl->parse('header_column','head_col',True);

		if(GlobalService::get('HTTP_GET_VARS')['menuaction'] == 'calendar.uicalendar.planner')
		{
			$remainder -= 28;
			print_debug('Sort By',$this->bo->sortby);

			$form_options = '<option value="user"'.($this->bo->sortby=='user'?' selected':'').'>'.lang('User').'</option>'."\n";
			$form_options .= '     <option value="category"'.((!isset($this->bo->sortby) || !$this->bo->sortby) || $this->bo->sortby=='category'?' selected':'').'>'.lang('Category').'</option>'."\n";
		
			$var = Array(
				'form_width' => '28',
				'form_link'	=> $this->page($referrer),
				'form_name'	=> 'sortby',
				'title'	=> lang('Sort By'),
				'hidden_vars'	=> $base_hidden_vars,
				'form_options'	=> $form_options,
				'button_value'	=> lang('Go!')
			);
			$tpl->set_var($var);
			$tpl->set_var('str',$tpl->fp('out','form_button_dropdown'));
			$tpl->parse('header_column','head_col',True);
		}

		if($this->bo->check_perms(PHPGW_ACL_PRIVATE))
		{
			$remainder -= 28;
			$form_options = '<option value=" all "'.($this->bo->filter==' all '?' selected':'').'>'.lang('All').'</option>'."\n";
			$form_options .= '     <option value=" private "'.((!isset($this->bo->filter) || !$this->bo->filter) || $this->bo->filter==' private '?' selected':'').'>'.lang('Private Only').'</option>'."\n";
		
			$var = Array(
				'form_width' => '28',
				'form_link'	=> $this->page($referrer),
				'form_name'	=> 'filter',
				'title'	=> lang('Filter'),
				'hidden_vars'	=> $base_hidden_vars,
				'form_options'	=> $form_options,
				'button_value'	=> lang('Go!')
			);
			$tpl->set_var($var);
			$tpl->set_var('str',$tpl->fp('out','form_button_dropdown'));
			$tpl->parse('header_column','head_col',True);
		}
//aqui !!

		if((!isset(GlobalService::get('phpgw_info')['server']['deny_user_grants_access']) || !GlobalService::get('phpgw_info')['server']['deny_user_grants_access']) && count($this->bo->grants) > 0)
		{
			$form_options = '';
			
			$drop_down = $this->bo->list_cals(); //hehe
			
			foreach($drop_down as $key => $grant)
			{					
				if(!strstr(($grant['value']),'g_')) 
					$form_options .= '    <option value="'.$grant['value'].'"'.($grant['grantor']==$this->bo->owner?' selected':'').'>'.$grant['name'].'</option>'."\n";
			}
		
			$var = Array(
				'form_width' => $remainder,
				'form_link'	=> $this->page($referrer),
				'form_name'	=> 'owner',
				'title'	=> lang('User'),
				'hidden_vars'	=> $base_hidden_vars,
				'form_options'	=> $form_options,
				'button_value'	=> lang('Go!')
			);
			$tpl->set_var($var);
			$tpl->set_var('str',$tpl->fp('out','form_button_dropdown'));
			$tpl->parse('header_column','head_col',True);
		}
	}

	$hidden_vars = '    <input type="hidden" name="from" value="'.GlobalService::get('HTTP_GET_VARS')['menuaction'].'">'."\n";
	if(isset(GlobalService::get('HTTP_GET_VARS')['date']) && GlobalService::get('HTTP_GET_VARS')['date'])
	{
		$hidden_vars .= '    <input type="hidden" name="date" value="'.GlobalService::get('HTTP_GET_VARS')['date'].'">'."\n";
	}
	$hidden_vars .= '    <input type="hidden" name="month" value="'.$this->bo->month.'">'."\n";
	$hidden_vars .= '    <input type="hidden" name="day" value="'.$this->bo->day.'">'."\n";
	$hidden_vars .= '    <input type="hidden" name="year" value="'.$this->bo->year.'">'."\n";
	if(isset($this->bo->filter) && $this->bo->filter)
	{
		$hidden_vars .= '    <input type="hidden" name="filter" value="'.$this->bo->filter.'">'."\n";
	}
	if(isset($this->bo->sortby) && $this->bo->sortby)
	{
		$hidden_vars .= '    <input type="hidden" name="sortby" value="'.$this->bo->sortby.'">'."\n";
	}
	if(isset($this->bo->num_months) && $this->bo->num_months)
	{
		$hidden_vars .= '    <input type="hidden" name="num_months" value="'.$this->bo->num_months.'">'."\n";
	}
	$hidden_vars .= '    <input name="keywords"'.(GlobalService::get('HTTP_POST_VARS')['keywords']?' value="'.GlobalService::get('HTTP_POST_VARS')['keywords'].'"':'').'>';

	$var = Array(
		'action_url_button'	=> $this->page('search'),
		'action_text_button'	=> lang('Search'),
		'action_confirm_button'	=> '',
		'action_extra_field'	=> $hidden_vars
	);
	$tpl->set_var($var);
	$button = $tpl->fp('out','form_button');
	$tpl->set_var('str','<td align="right" valign="bottom">'.$button.'</td>');
	$tpl->parse('header_column','head_col',True);
	$tpl->parse('row','head_table',True);
?>
