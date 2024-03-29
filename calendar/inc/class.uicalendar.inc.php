<?php
  /**************************************************************************\
  * eGroupWare - Calendar                                                    *
  * http://www.eGroupWare.org                                                *
  * Maintained and further developed by RalfBecker@outdoor-training.de       *
  * Based on Webcalendar by Craig Knudsen <cknudsen@radix.net>               *
  *          http://www.radix.net/~cknudsen                                  *
  * Originaly modified by Mark Peters <skeeter@phpgroupware.org>             *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/


	class uicalendar
	{
		var $template;
		var $template_dir;

		var $bo;
		var $cat;

		var $holidays;
		var $holiday_color;

		var $debug = False;
		//var $debug = True;

		var $cat_id;
		var $theme;
		var $link_tpl;

		// planner related variables
		var $planner_header;
		var $planner_rows;

		var $planner_group_members;

		var $planner_firstday;
		var $planner_lastday;
		var $planner_days;

		var $planner_end_month;
		var $planner_end_year;
		var $planner_days_in_end_month;

		var $planner_intervals = array(	// conversation hour and interval depending on intervals_per_day
					//                                  1 1 1 1 1 1 1 1 1 1 2 2 2 2
					//              0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3
						'1' => array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0), // 0=0-23h
						'2' => array(0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,1,1,1,1,0,0,0,0,0), // 0=0-12h, 1=12-23h
						'3' => array(0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,1,1,1,2,2,2,2,2,2), // 0=0-12h, 2=12-18h, 3=18-23h
						'4' => array(0,0,0,0,0,0,0,1,1,1,1,1,2,2,2,2,2,2,3,3,3,3,3,3)  // 0=0-7, 7-12h, 3=12-18h, 4=18-23h
					);

		var $public_functions = array(
			'screen_delegate_event' => True,
			'delegate_event' => True,
			'mini_calendar' => True,
			'index' => True,
			'month' => True,
			'get_month' => True,
			'week'  => True,
			'get_week' => True,
			'year' => True,
			'view' => True,
			'edit' => True,
			'export'	=> True,
			'export_all' => True,
			'reinstate_list'	=> True,
			'reinstate'	=> True,
			'add'  => True,
			'delete' => True,
			'preferences' => True,
			'day' => True,
			'edit_status' => True,
			'set_action' => True,
			'planner' => True,
			'modify_ext_partlist' => True,
			'matrixselect'	=> True,
			'viewmatrix'	=> True,
			'search' => True,
			'header' => True,
			'footer' => True,
			'css'		=> True,
			'accounts_popup' => True,
			'disponibility' => True
		);

		function uicalendar()
		{
			$GLOBALS['phpgw']->nextmatchs = CreateObject('phpgwapi.nextmatchs');

			$this->theme = $GLOBALS['phpgw_info']['theme'];

			$this->bo = CreateObject('calendar.bocalendar',1);
			$this->cat = &$this->bo->cat;

			print_debug('BO Owner',$this->bo->owner);

			$this->template = $GLOBALS['phpgw']->template;
			$this->template_dir = $GLOBALS['phpgw']->common->get_tpl_dir('calendar');

			$this->holiday_color = (substr($this->theme['bg06'],0,1)=='#'?'':'#').$this->theme['bg06'];

			$this->cat_id   = $this->bo->cat_id;

			$this->link_tpl = CreateObject('phpgwapi.Template',$this->template_dir);
			$this->link_tpl->set_unknowns('remove');
			$this->link_tpl->set_file(
				Array(
					'link_picture'	=> 'link_pict.tpl'
				)
			);
			$this->link_tpl->set_block('link_picture','link_pict','link_pict');
			$this->link_tpl->set_block('link_picture','pict','pict');
			$this->link_tpl->set_block('link_picture','link_open','link_open');
			$this->link_tpl->set_block('link_picture','link_close','link_close');
			$this->link_tpl->set_block('link_picture','link_text','link_text');
			
			/*************** EVENTO **************************/
/* Este bloco de codigo foi adicionado pois os eventos de agendamento (exibicao do mes) para impressao serao tratados em outro template
(event_link.tpl) */

			$this->event_tpl = CreateObject('phpgwapi.Template',$this->template_dir);
			$this->event_tpl = CreateObject('phpgwapi.Template',$this->template_dir);
			$this->event_tpl->set_unknowns('remove');
			$this->event_tpl->set_file(
				Array(
					'link_event'	=> 'event_link.tpl'
				)
			);
			$this->event_tpl->set_block('link_event','link_event_pict','link_event_pict');
			$this->event_tpl->set_block('link_event','event_pict','event_pict');
			$this->event_tpl->set_block('link_event','link_event_open','link_event_open');
			$this->event_tpl->set_block('link_event','link_event_close','link_event_close');
			$this->event_tpl->set_block('link_event','link_event_text','link_event_text');


/*************************************************/

			if($this->bo->use_session)
			{
				// save return-fkt for add, view, ...
				list(,,$fkt) = explode('.',$_GET['menuaction']);
				if ($fkt == 'day' || $fkt == 'week' || $fkt == 'month' || $fkt == 'year' || $fkt == 'planner')
				{
					$this->bo->return_to = $_GET['menuaction'].
						sprintf('&date=%04d%02d%02d',$this->bo->year,$this->bo->month,$this->bo->day);
				}
				$this->bo->save_sessiondata();
			}
			$this->always_app_header = $this->bo->prefs['common']['template_set'] == 'idots';

			print_debug('UI',$this->_debug_sqsof());

			if (!is_object($GLOBALS['phpgw']->html))
			{
				$GLOBALS['phpgw']->html = CreateObject('phpgwapi.html');
			}
			$this->html = &$GLOBALS['phpgw']->html;
			
			if (!is_object($GLOBALS['phpgw']->js)) $GLOBALS['phpgw']->js = CreateObject('phpgwapi.javascript');
			$GLOBALS['phpgw']->js->add('src','./prototype/plugins/jquery/jquery-latest.min.js');
		}

		function screen_delegate_event($params=NULL) {
			$module_name = 'expressoMail'.(str_replace("1.","1_",$GLOBALS['phpgw_info']['server']['cal_expressoMail']));

			$sb = CreateObject('phpgwapi.sbox2');
			$jscal = CreateObject('phpgwapi.jscalendar');	// before phpgw_header() !!!
			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			$GLOBALS['phpgw_info']['flags']['noappheader'] = True;
			$GLOBALS['phpgw_info']['flags']['noappfooter'] = True;
			$GLOBALS['phpgw_info']['flags']['app_header'] = $event['id'] ? lang('Calendar - Edit') : lang('Calendar - Add');
			$GLOBALS['phpgw']->common->phpgw_header();

			$ldap_manager = CreateObject('contactcenter.bo_ldap_manager');
			$_SESSION['phpgw_info']['expressomail']['user'] = $GLOBALS['phpgw_info']['user'];
			$_SESSION['phpgw_info']['expressomail']['user']['owner'] = $event['owner'];
			$_SESSION['phpgw_info']['expressomail']['server'] = $GLOBALS['phpgw_info']['server'];
			$_SESSION['phpgw_info']['expressomail']['ldap_server'] = $ldap_manager ? $ldap_manager->srcs[1] : null;
			$context = $GLOBALS['phpgw_info']['server']['ldap_context'];
			$user_context = array();
			foreach(explode(",",$GLOBALS['phpgw_info']['user']['account_dn']) as $i => $dn_part){
				if($i)
					$user_context[] = $dn_part;
			}
			// Prepara o contexto do usuario com sua OU raiz, pois ele pode pertencer a uma OU de nivel N.
			$user_ou = explode(",",str_replace($context,"",implode(",",$user_context)));
			$user_context = trim(strtolower($user_ou[count($user_ou) - 2].",".$context));
			// Fim
			// Verifica o tipo da visualiza��o da �rvore LDAP, configurado no admin da Agenda
			$recursive = $GLOBALS['phpgw_info']['server']['cal_type_tree_participants'] == '1' ? true : false;
			$combo_org = $this->get_organizations(trim(strtolower($context)),$user_context, $recursive);

			$var = array(
				"module_name"		=> $module_name,
				"lang_loading"		=> lang("Loading"),
				"lang_searching"	=> lang("Searching"),
				"lang_users"		=> lang("Users"),
				"lang_groups"		=> lang("Groups"),
				"recursive"		=> ($recursive)?"":"search",
				"options"		=> $combo_org,
				"id_event"		=> $_REQUEST["id_event"],
				"eventArray"		=> $_REQUEST["eventArray"],
				"delegator"		=> $_REQUEST["delegator"],
				"lang_avaliable_users"	=> lang("Available Users and Groups"),
				"lang_search_for"	=> lang("Search for"),
				"lang_ou"		=> lang("Organization"),
				"min_num_characters"	=> $GLOBALS['phpgw_info']['server']['min_num_characters'],
				"date"			=> $_REQUEST["date"],
				"eventDelegation"	=> 1,
				"Delegate"		=> lang("Delegate"),
				'url_template'		=> 'calendar/templates/'.$_SESSION['phpgw_info']['calendar']['user']['preferences']['common']['template_set']
			);

			if(isset($_REQUEST["error"]))
			{
				if($_REQUEST["error"] == 999){
					$var['error_msg'] = lang("Problems delegating. Make sure you have selected a user to delegate the appointment.");
				}else {
					$var['error_msg'] = lang("Problems delegating. Make sure you haven't delegated to a person that was also invited to the meeting");
				}
			}

			$template = CreateObject('phpgwapi.Template',$this->template_dir);
			$template->set_file(
					array(
						"screen"=>"delegate.tpl"
					)
				);
			$template->set_var($var);
			print $template->fp('out','screen');
		}


		function delegate_event($params=NULL) {
			//$ldap = CreateObject('expressoMail1_2.ldap_functions');
			//$delegated = $ldap->uid2uidnumber($_REQUEST['delegated']);

			try {
				$this->bo->delegar($_REQUEST['delegator'],$_REQUEST['delegated']/*$delegated*/,$_REQUEST['event']);
				header("Location: index.php?menuaction=calendar.uicalendar.day&date=".$_REQUEST['date']);
			}catch(Exception $e) {
				if($e->getMessage() == '999') {
					header("Location: index.php?menuaction=calendar.uicalendar.screen_delegate_event&id_event=". $_REQUEST["event"]."&delegator=".$_REQUEST["delegator"]."&date=".$_REQUEST["date"]."&error=999");
				}else {
					header("Location: index.php?menuaction=calendar.uicalendar.screen_delegate_event&id_event=". $_REQUEST["event"]."&delegator=".$_REQUEST["delegator"]."&date=".$_REQUEST["date"]."&error=1");
				}
			}
		}

		/* Public functions */

		function mini_calendar($params)
		{
			static $mini_cal_tpl;
			if(!is_array($params))
			{
				return;
			}

			if($params['month'] == 0)
			{
				$params['month'] = 12;
				$params['year'] = $params['year'] - 1;
			}
			elseif($params['month'] == 13)
			{
				$params['month'] = 1;
				$params['year'] = $params['year'] + 1;
			}

			$this->bo->store_to_cache(
				Array(
					'smonth'	=> $params['month'],
					'sday'	=> 1,
					'syear'	=> $params['year']
				)
			);

			$params['link']			= (!isset($params['link'])?'':$params['link']);
			$params['buttons']		= (!isset($params['buttons'])?'none':$params['buttons']);
			$params['outside_month']	= (!isset($params['outside_month'])?True:$params['outside_month']);

			$this->bo->read_holidays($params['year']);

			$date = $GLOBALS['phpgw']->datetime->makegmttime(0,0,0,$params['month'],$params['day'],$params['year']);
			$month_ago = (int)(date('Ymd',mktime(0,0,0,$params['month'] - 1,$params['day'],$params['year'])));
			$month_ahead = (int)(date('Ymd',mktime(0,0,0,$params['month'] + 1,$params['day'],$params['year'])));
			$monthstart = (int)(date('Ymd',mktime(0,0,0,$params['month'],1,$params['year'])));
			$monthend = (int)(date('Ymd',mktime(0,0,0,$params['month'] + 1,0,$params['year'])));

			$weekstarttime = $GLOBALS['phpgw']->datetime->get_weekday_start($params['year'],$params['month'],1);

			print_debug('mini_calendar:monthstart',$monthstart);
			print_debug('mini_calendar:weekstarttime',date('Ymd H:i:s',$weekstarttime));

			if(!is_object($mini_cal_tpl))
			{
				$mini_cal_tpl = CreateObject('phpgwapi.Template',$this->template_dir);
				$mini_cal_tpl->set_unknowns('remove');
				$mini_cal_tpl->set_file(
					Array(
						'mini_calendar'	=> 'mini_cal.tpl'
					)
				);
				$mini_cal_tpl->set_block('mini_calendar','mini_cal','mini_cal');
				$mini_cal_tpl->set_block('mini_calendar','mini_week','mini_week');
				$mini_cal_tpl->set_block('mini_calendar','mini_day','mini_day');
			}


			if($this->bo->printer_friendly == False)
			{
//NDEE				$month = '<a href="' . $this->page('month','&month='.$GLOBALS['phpgw']->common->show_date($date['raw'],'m').'&year='.$GLOBALS['phpgw']->common->show_date($date['raw'],'Y')). '" class="_minicalendar">' . lang($GLOBALS['phpgw']->common->show_date($date['raw'],'F')).' '.$GLOBALS['phpgw']->common->show_date($date['raw'],'Y').'</a>';
				$month = '<a href="' . $this->page('month','&month='.$GLOBALS['phpgw']->common->show_date($date['raw'],'m').'&year='.$GLOBALS['phpgw']->common->show_date($date['raw'],'Y')). '">' . lang($GLOBALS['phpgw']->common->show_date($date['raw'],'F')).' '.$GLOBALS['phpgw']->common->show_date($date['raw'],'Y').'</a>';
			}
			else
			{
				$month = lang($GLOBALS['phpgw']->common->show_date($date['raw'],'F')).' '.$GLOBALS['phpgw']->common->show_date($date['raw'],'Y');
			}

			$var = Array(
				'cal_img_root'		=>	$GLOBALS['phpgw']->common->image('calendar','mini-calendar-bar'),
				'bgcolor'			=>	$this->theme['bg_color'],
				'bgcolor1'			=>	$this->theme['bg_color'],
				'month'				=>	$month,
				'bgcolor2'			=>	$this->theme['cal_dayview'],
				'holiday_color'	=> $this->holiday_color
			);

			$mini_cal_tpl->set_var($var);

			switch(strtolower($params['buttons']))
			{
				case 'right':
					$var = Array(
						'nextmonth'			=>	'<a href="'.$this->page('month','&date='.$month_ahead).'"><img src="'.$GLOBALS['phpgw']->common->image('phpgwapi','right').'" border="0"></a>'
					);
					break;
				case 'left':
					$var = Array(
						'prevmonth'			=>	'<a href="'.$this->page('month','&date='.$month_ago).'"><img src="'.$GLOBALS['phpgw']->common->image('phpgwapi','left').'" border="0"></a>'
					);
					break;
				case 'both':
					$var = Array(
						'prevmonth'			=>	'<a href="'.$this->page('month','&date='.$month_ago).'"><img src="'.$GLOBALS['phpgw']->common->image('phpgwapi','left').'" border="0"></a>',
						'nextmonth'			=>	'<a href="'.$this->page('month','&date='.$month_ahead).'"><img src="'.$GLOBALS['phpgw']->common->image('phpgwapi','right').'" border="0"></a>'
					);
					break;
				case 'none':
				default:
					$var = Array(
						'prevmonth'			=>	'',
						'nextmonth'			=>	''
					);
					break;
			}
			$mini_cal_tpl->set_var($var);

			if(!$mini_cal_tpl->get_var('daynames'))
			{
				for($i=0;$i<7;$i++)
				{
					$var = Array(
						'dayname'	=> '<b>' . lang($GLOBALS['phpgw']->datetime->days_short[$i]) . '</b>',
						'day_image'	=> ''
					);
					$this->output_template_array($mini_cal_tpl,'daynames','mini_day',$var);
				}
			}
			$today = date('Ymd',$GLOBALS['phpgw']->datetime->users_localtime);
			unset($date);
			for($i=$weekstarttime + $GLOBALS['phpgw']->datetime->tz_offset;date('Ymd',$i)<=$monthend;$i += (24 * 3600 * 7))
			{
				unset($var);
				$daily = $this->set_week_array($i - $GLOBALS['phpgw']->datetime->tz_offset,$cellcolor,$weekly);
				foreach($daily as $date => $day_params)
				{
					print_debug('Mini-Cal Date',$date);
					$year = (int)(substr($date,0,4));
					$month = (int)(substr($date,4,2));
					$day = (int)(substr($date,6,2));
					$str = '';
					if(($date >= $monthstart && $date <= $monthend) || $params['outside_month'] == True)
					{
						if(!$this->bo->printer_friendly && $params['link'])
						{
//NDEE: class def what for?
							$str = '<a href="'.$this->page($params['link'],'&date='.$date).'" class="'.$day_params['class'].'" ' . ($day_params['class']=='bminicalhol' || $day_params['class']=='minicalhol'?' title="' . $day_params['holidays'][0] . '" ' :'') . ' >'.$day.'</a>';
						}
						else
						{

//NDEE: printer-friendly (mini-calendar)
							$str = $day;
						}

					}
					else
					{
						$day_params['day_image'] = '';
					}
					$var[] = Array(
						'day_image'	=> $day_params['day_image'],
						'dayname'	=> $str
					);
				}
				for($l=0;$l<count($var);$l++)
				{
					$this->output_template_array($mini_cal_tpl,'monthweek_day','mini_day',$var[$l]);
				}
				$mini_cal_tpl->parse('display_monthweek','mini_week',True);
				$mini_cal_tpl->set_var('dayname','');
				$mini_cal_tpl->set_var('monthweek_day','');
			}

			$return_value = $mini_cal_tpl->fp('out','mini_cal');
			$mini_cal_tpl->set_var('display_monthweek','');
//			$mini_cal_tpl->set_var('daynames','');
//			unset($p);
			return $return_value;
		}

		function index($params='')
		{
			if (!$params)
			{
				foreach(array('date','year','month','day') as $field)
				{
					if (isset($_GET[$field]))
					{
						$params[$field] = $_GET[$field];
					}
				}
			}
			$GLOBALS['phpgw']->redirect($this->page('',$params));
			
		}
// NDEE: printer-friendly

		function printer_friendly($body,$app_header='')
		{
			$_SESSION['calendar']['categories'] = $this->cat->formated_list('select','all',$this->bo->cat_id,'True');
			if((!isset($GLOBALS['phpgw_info']['server']['deny_user_grants_access']) || !$GLOBALS['phpgw_info']['server']['deny_user_grants_access']) && count($this->bo->grants) > 0)
				$_SESSION['calendar']['cals'] = $this->bo->list_cals();

			if($this->bo->printer_friendly || $_GET['plain'] == 'True')
			{	
			
				$class_name = $_GET["classic_print"]!=1?"calendar_print_main":"calendar_print_line_main";
				
				$new_body = '<html>'."\n"
					.'<head>'."\n"
					.'<LINK href="'.$GLOBALS['phpgw_info']['server']['webserver_url'].'/calendar/templates/'.$_SESSION['phpgw_info']['calendar']['user']['preferences']['common']['template_set'].'/app.css" type=text/css rel=StyleSheet>'."\n"
					.'<LINK href="'.$GLOBALS['phpgw_info']['server']['webserver_url'].'/calendar/templates/'.$_SESSION['phpgw_info']['calendar']['user']['preferences']['common']['template_set'].'/app_print.css" type=text/css rel=StyleSheet media="print">'."\n"
					.'</head>
					<table id="'.$class_name.'" class="'.$class_name.'">						
						<tr>
							<td id="calendar_print_content" class="calendar_print_content">'.$body.'</td>
						</tr>
						<tr>
						  <td>
							<div>
								<input class=button type="button" onClick="javascript:this.style.visibility=\'hidden\';window.print();window.close();" value="Imprimir esta p&aacute;gina">
							</div>
						   </td>
						 </tr>
					</table></body>'."\n"
					.'</html>'."\n";
				//$new_body = utf8_decode($new_body);
			}
			else
			{
				unset($GLOBALS['phpgw_info']['flags']['noheader']);
				unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
				unset($GLOBALS['phpgw_info']['flags']['noappheader']);
				unset($GLOBALS['phpgw_info']['flags']['noappfooter']);
				if ($app_header && $this->always_app_header)
				{
					$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['calendar']['title'].' - '.$app_header;
				}
				
				$GLOBALS['phpgw']->common->phpgw_header();
				$new_body = $this->bo->debug_string.$body;				
				
			}
			return $new_body;
		}

		function month()
		{
			echo $this->printer_friendly($this->get_month(),lang('Monthview'));
			$GLOBALS['phpgw']->common->phpgw_footer();
		}

		function get_month()
		{
			$m = mktime(0,0,0,$this->bo->month,1,$this->bo->year);

			$next = $this->bo->month + 1;
			$prev = $this->bo->month - 1;
			$nextyear = $this->bo->year;
			$prevyear = $this->bo->year;
			if ($this->bo->month == 12)
			{
				$next = 1;
				$nextyear = $nextyear + 1;
			}
			elseif ($this->bo->month == 1)
			{
				$prev = 12;
				$prevyear = $prevyear - 1;
			}

			if (isset($this->bo->prefs['calendar']['display_minicals']) && $this->bo->prefs['calendar']['display_minicals'] == "1" && !$this->bo->printer_friendly)
			{
				$minical_prev = $this->mini_calendar(
					Array(
						'day'	=> 1,
						'month'	=> $this->bo->month - 1,
						'year'	=> $this->bo->year,
						'link'	=> 'day',
						'outside_month'	=> False
					)
				);

				$minical_next = $this->mini_calendar(
					Array(
						'day'	=> 1,
						'month'	=> $this->bo->month + 1,
						'year'	=> $this->bo->year,
						'link'	=> 'day',
						'outside_month'	=> False
					)
				);
			}
			else
			{
				$minical_prev = '';
				$minical_next = '';
			}
                        $this->bo->read_holidays($params['year']);
			if($_POST['year']) {			
	
			}
			
			$hoje = date('Ymd',$GLOBALS['phpgw']->datetime->gmtnow);
			$mes_hoje = (int)substr($hoje,4,2);
			$sday = 1;
			if($_POST['day']) {
				$day_ini = (int)$_POST['day'];
			} 
			elseif($_GET['sday']){
				if($_GET['month'] == $mes_hoje)
				{
					$day_ini = $this->bo->day;
				} 
				
				else
				{
					$day_ini = (int)$_GET['sday'];
				}
			} 
			elseif( ($_POST['dia_ini']) && ($_POST['dia_ini'] == $this->bo->day) )
			{
				$day_ini = (int)substr($hoje,6,2);
			}
			else
			{
				$day_ini = $this->bo->day;
			}
			if($_POST['qtd_dias'])
			{
				$num_dias = (int)$_POST['qtd_dias'];
			} 
			else
			{
				$num_dias = 0;
			}

			if (!$this->bo->printer_friendly)
			{
				$printer = '';
				$prev_month_link = '<a href="'.$this->page('month','&year='.$prevyear.'&month='.$prev.'&sday='.$sday).'">&lt;&lt;</a>';
				$next_month_link = '<a href="'.$this->page('month','&year='.$nextyear.'&month='.$next.'&sday='.$sday).'">&gt;&gt;</a>';
				$param = '&year='.$this->bo->year.'&month='.$this->bo->month.'&friendly=1'.'&day_ini='.$day_ini.'&num_dias='.$num_dias;
				//$param = '&year='.$this->bo->year.'&month='.$this->bo->month.'&friendly=1';
				$print = '';
				//linha abaixo foi adicionada para exibir a pagina do mes em tela
				$large_month = $this->display_month($this->bo->month,$this->bo->year,True,$this->bo->owner);				
			}
			else
			{
				$printer = '<body bgcolor="'.$phpgw_info['theme']['bg_color'].'">';
				$prev_month_link = '';
				$next_month_link = '';
				$print =	'';
				$GLOBALS['phpgw_info']['flags']['nofooter'] = True;
				//linha abaixo foi adicionada para exibir a pagina do mes para impressao

				if($_GET['classic_print']==1)
					$large_month = $this->display_month($this->bo->month,$this->bo->year,True,$this->bo->owner);
				else
					$large_month = $this->display_month_print($this->bo->month,$this->bo->year,True,$this->bo->owner);
			}

			$this->bo->read_holidays();

			$var = Array(
				'printer_friendly'		=>	$printer,
				'bg_text'					=> $this->theme['bg_text'],
				'small_calendar_prev'	=>	$minical_prev,
				'prev_month_link'		=>	$prev_month_link,
				'month_identifier'		=>	lang(strftime("%B",$m)).' '.$this->bo->year,
				'username'					=>	$GLOBALS['phpgw']->common->grab_owner_name($this->bo->owner),
				'next_month_link'		=>	$next_month_link,
				'small_calendar_next'	=>	$minical_next,
				//'large_month'				=>	$this->display_month($this->bo->month,$this->bo->year,True,$this->bo->owner),
				'large_month'		=>	$large_month,
				'print'						=>	$print
			);

			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('remove');
			// Alteracao que desvincula o template para tela do template de impressao do mes (month_print teve que ser criado);
			if($print != '')
			{
				$p->set_file(
					Array(
						'index_t'	=>	'index.tpl'
					)
				);
			}else {
				$p->set_file(
					Array(
						'index_t'	=>	'month_print.tpl'
					)
				);
			}
			$p->set_var($var);
			return $p->fp('out','index_t');
		}

		function week()
		{
			echo $this->printer_friendly($this->get_week(),lang('Weekview'));
			$GLOBALS['phpgw']->common->phpgw_footer();
		}

		function get_week()
		{
			$this->bo->read_holidays();

			$next = $GLOBALS['phpgw']->datetime->makegmttime(0,0,0,$this->bo->month,$this->bo->day + 7,$this->bo->year);
			$prev = $GLOBALS['phpgw']->datetime->makegmttime(0,0,0,$this->bo->month,$this->bo->day - 7,$this->bo->year);

			if (isset($this->bo->prefs['calendar']['display_minicals']) && $this->bo->prefs['calendar']['display_minicals'] == "1" && !$this->bo->printer_friendly)
			{
				$minical_prev = $this->mini_calendar(
					Array(
						'day'	=> $this->bo->day,
						'month'	=> $this->bo->month - 1,
						'year'	=> $this->bo->year,
						'link'	=> 'day',
						'butons'	=> 'left',
						'outside_month'	=> False
					)
				);
				$minical_next = $this->mini_calendar(
					Array(
						'day'	=> $this->bo->day,
						'month'	=> $this->bo->month + 1,
						'year'	=> $this->bo->year,
						'link'	=> 'day',
						'butons'	=> 'right',
						'outside_month'	=> False
					)
				);
                                $minical_this = $this->mini_calendar(
					Array(
						'day'	=> $this->bo->day,
						'month'	=> $this->bo->month,
						'year'	=> $this->bo->year,
						'link'	=> 'day',
						'butons'	=> 'none',
						'outside_month'	=> False
					)
				);
			}
			else
			{

			//NDEE: printer-friendly what?
				$minical_this = '';
				$minical_prev = '';
				$minical_next = '';
			}

			if (!$this->bo->printer_friendly)
			{
				$printer = '';
				$prev_week_link = '<a href="'.$this->page('week','&date='.$prev['full']).'">&lt;&lt;</a>';
				$next_week_link = '<a href="'.$this->page('week','&date='.$next['full']).'">&gt;&gt;</a>';
				$print = '<a href="'.$this->page('week','&friendly=1&date='.sprintf("%04d%02d%02d",$this->bo->year,$this->bo->month,$this->bo->day))."\" TARGET=\"cal_printer_friendly\" onMouseOver=\"window.status = '".lang('Generate printer-friendly version')."'\">[".lang('Printer Friendly').']</a>';
			}
			else
			{

				//NDEE: printer-friendly (weekly-view)
				$printer = '<body bgcolor="'.$this->theme['bg_color'].'">';
				$prev_week_link = '';
				$next_week_link = '';
				$print =	'';
				$GLOBALS['phpgw_info']['flags']['nofooter'] = True;
			}

			$var = Array(
				'printer_friendly'	=>	$print,
				'bg_text'		=> $this->theme['bg_text'],
				'small_calendar_prev'	=>	$minical_prev,
				'prev_week_link'	=>	$prev_week_link,
				'small_calendar_this'	=>	$minical_this,
				'week_identifier'	=>	$this->bo->get_week_label(),
				'next_week_link'	=>	$next_week_link,
				'username'		=>	$GLOBALS['phpgw']->common->grab_owner_name($this->bo->owner),
				'small_calendar_next'	=>	$minical_next,
				'week_display'		=>	$this->display_weekly(
					Array(
						'date'		=> sprintf("%04d%02d%02d",$this->bo->year,$this->bo->month,$this->bo->day),
						'showyear'	=> true,
						'owners'	=> $this->bo->owner
					)
				),
				'print'			=>	$print
			);
			//todo
			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_file(
				Array(
					'week_t' => 'week.tpl'
				)
			);
			$p->set_var($var);
			return $p->fp('out','week_t');

		}

		function year()
		{
			if ($_GET['plain'] == "True")
			{

				echo $this->printer_friendly($this->get_year(),lang('Yearview'));
			}
			else
			{

				if($this->bo->printer_friendly)
				{
					$GLOBALS['phpgw_info']['flags']['nofooter'] = True;
				}
				echo $this->printer_friendly($this->get_year(),lang('Yearview'));
				$GLOBALS['phpgw']->common->phpgw_footer();
			}
		}

		function get_year()
		{
			if(!$this->bo->printer_friendly)
			{
				$print = '';
				$left_link = '<a href="'.$this->page('year','&year='.($this->bo->year - 1)).'">&lt;&lt;</a>';
				$right_link = '<a href="'.$this->page('year','&year='.($this->bo->year + 1)).'">&gt;&gt;</a>';
				$link = 'day';
				$printer = '<a href="'.$this->page('year','&friendly=1&year='.$this->bo->year).'" target="cal_printer_friendly" onMouseOver="window.status = '."'".lang('Generate printer-friendly version')."'".'">['.lang('Printer Friendly').']</a>';
			}
			else
			{
//NDEE: printer-friendly (year-view)
				$print = '<body bgcolor="'.$this->theme['bg_color'].'">';
				$left_link = '';
				$right_link = '';
				$link = '';
				$printer = '';
			}

			$var = Array(
				'print'		=> $print,
				'left_link' => $left_link,
				'font'		=> $this->theme['font'],
				'year_text' => $this->bo->year,
				'right_link'=> $right_link,
				'printer_friendly'=> $printer
			);

			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_file(
				Array(
					'year_t' => 'year.tpl'
				)
			);
			$p->set_block('year_t','year','year');
			$p->set_block('year_t','month','month_handle');
			$p->set_block('year_t','month_sep','month_sep_handle');
			$p->set_var($var);

			for($i=1;$i<=12;$i++)
			{
				if(($i % 3) == 1)
				{
					$p->parse('row','month_sep',True);
				}
				$p->set_var('mini_month',$this->mini_calendar(
						Array(
							'day'	=> 1,
							'month'	=> $i,
							'year'	=> $this->bo->year,
							'link'	=> $link,
							'buttons'	=> 'none',
							'outside_month'	=> False
						)
					)
				);
				$p->parse('row','month',True);
				$p->set_var('mini_month','');
			}
			return $p->fp('out','year_t');
		}

		function view($vcal_id=0,$cal_date=0)
		{
			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['calendar']['title'].' - '.lang('View');
			$GLOBALS['phpgw_info']['flags']['noappheader'] = True;
			$GLOBALS['phpgw']->common->phpgw_header();

			$cal_id = get_var('cal_id',array('GET','POST'),$vcal_id);

			$date = $cal_date ? $cal_date : 0;
			$date = $date ? $date : (int)$_GET['date'];
	
			// First, make sure they have permission to this entry
			if ($cal_id < 1)
			{
				echo '<center>'.lang('Invalid entry id.').'</center>'."\n";
				$GLOBALS['phpgw']->common->phpgw_exit(True);
			}

			if(!$this->bo->check_perms(PHPGW_ACL_READ,$cal_id))
			{
				echo '<center>'.lang('You do not have permission to read this record!').'</center>'."\n";
				$GLOBALS['phpgw']->common->phpgw_exit(True);
			}
			
			$event = $this->bo->read_entry($cal_id);

			if(!isset($event['id']))
			{
				echo '<center>'.lang('Sorry, this event does not exist').'.'.'</center>'."\n";
				$GLOBALS['phpgw']->common->phpgw_exit(True);
			}

			$this->bo->repeating_events = Array();
			$this->bo->cached_events = Array();
			$this->bo->repeating_events[0] = $event;
			$datetime = mktime(0,0,0,$this->bo->month,$this->bo->day,$this->bo->year) - $GLOBALS['phpgw']->datetime->tz_offset;
			$this->bo->check_repeating_events($datetime);
			$check_date = $GLOBALS['phpgw']->common->show_date($datetime,'Ymd');
			if(is_array($this->bo->cached_events[$check_date][0]) &&
				$this->bo->cached_events[$check_date][0]['id'] == $event['id'])
			{
				$starttime = $this->bo->maketime($event['start']);
				$endtime = $this->bo->maketime($event['end']);
				$event['start']['month'] = $this->bo->month;
				$event['start']['mday'] = $this->bo->day;
				$event['start']['year'] = $this->bo->year;
				$temp_end =  $this->bo->maketime($event['start']) + ($endtime - $starttime);
				$event['end']['month'] = date('m',$temp_end);
				$event['end']['mday'] = date('d',$temp_end);
				$event['end']['year'] = date('Y',$temp_end);
			}

			if(!$this->view_event($event,True))
			{
				echo '<center>'.lang('You do not have permission to read this record!').'</center>';
				$GLOBALS['phpgw']->common->phpgw_exit(True);
			}
						
			$p = $GLOBALS['phpgw']->template;
			$p->set_file(
				Array(
					'form_button'	=> 'form_button_script.tpl'
				)
			);

			$button_left = $button_center = $button_right = '';

			if($this->bo->check_perms(PHPGW_ACL_EDIT,$event))
			{
				if($event['recur_type'] != MCAL_RECUR_NONE)
				{
					$var = Array(
						'action_url_button'	=> $this->page('edit','&cal_id='.$cal_id),
						'action_text_button'	=> lang('Edit Single'),
						'action_confirm_button'	=> '',
						'action_extra_field'	=> $this->html->input_hidden(array(
							'edit_type' => 'single',
							'date' => sprintf('%04d%02d%02d',$this->bo->year,$this->bo->month,$this->bo->day)
						))
					);
					$p->set_var($var);
					$button_left .= '<td>'.$p->fp('button','form_button').'</td>';

					$var = Array(
						'action_url_button'	=> $this->page('edit','&cal_id='.$cal_id),
						'action_text_button'	=> lang('Edit Series'),
						'action_confirm_button'	=> '',
						'action_extra_field'	=> $this->html->input_hidden('edit_type','series')
					);
					$p->set_var($var);
					$button_left .= '<td>'.$p->fp('button','form_button').'</td>';
				}
				else
				{
					$var = Array(
						'action_url_button'	=> $this->page('edit','&cal_id='.$cal_id),
						'action_text_button'	=> lang('Edit'),
						'action_confirm_button'	=> '',
						'action_extra_field'	=> ''
					);
					$p->set_var($var);
					$button_left .= '<td>'.$p->fp('button','form_button').'</td>';
				}

				$var = Array(
					'action_url_button'	=> $GLOBALS['phpgw']->link('/index.php','menuaction=calendar.uialarm.manager'),
					'action_text_button'	=> lang('Alarm Management'),
					'action_confirm_button'	=> '',
					'action_extra_field'	=> $this->html->input_hidden(array(
						'cal_id' => $cal_id,
						'return_to' => $this->bo->return_to
					))
				);
				$p->set_var($var);
				$button_center .= '<td>'.$p->fp('button','form_button').'</td>';
			}

			if ($this->bo->check_perms(PHPGW_ACL_DELETE,$event))
			{
				if($event['recur_type'] != MCAL_RECUR_NONE)
				{
					$var = Array(
						'action_url_button'	=> $this->page('delete','&cal_id='.$cal_id),
						'action_text_button'	=> lang('Delete Single'),
						'action_confirm_button'	=> "onClick=\"return confirm('".lang('Are you sure want to delete this single occurence?').'\\n'.lang('This will delete this entry for all users.')."')\"",
						'action_extra_field'	=> $this->html->input_hidden(array(
							'delete_type' => 'single',
							'date' => sprintf('%04d%02d%02d',$this->bo->year,$this->bo->month,$this->bo->day)
						))
					);
					$p->set_var($var);
					$button_right .= '<td>'.$p->fp('button','form_button').'</td>';

					$var = Array(
						'action_url_button'	=> $this->page('delete','&cal_id='.$cal_id),
						'action_text_button'	=> lang('Delete Series'),
						'action_confirm_button'	=> "onClick=\"return confirm('".lang('Are you sure want to delete this entry?\\nThis will delete this entry for all users.')."')\"",
						'action_extra_field'	=> $this->html->input_hidden('delete_type','series')
					);
					$p->set_var($var);
					$button_right .= '<td>'.$p->fp('button','form_button').'</td>';

					if($event['recur_exception'])
					{
						$var = Array(
							'action_url_button'	=> $this->page('reinstate_list','&cal_id='.$cal_id),
							'action_text_button'	=> lang('Reinstate'),
							'action_confirm_button'	=> '',
							'action_extra_field'	=> ''
						);
						$p->set_var($var);
						$button_center .= '<td>'.$p->fp('button','form_button').'</td>';
					}
				}
				else
				{
					$var = Array(
						'action_url_button'	=> $this->page('delete','&cal_id='.$cal_id),
						'action_text_button'	=> lang('Delete'),
						'action_confirm_button'	=> "onClick=\"return confirm('".lang('Are you sure want to delete this entry?\\nThis will delete this entry for all users.')."')\"",
						'action_extra_field'	=> ''
					);
					$p->set_var($var);
					$button_right .= '<td>'.$p->fp('button','form_button').'</td>';
				}
			}
			else
			{
				// allow me (who I am logged in as) to set up an alarm
				// if I am a participant, but not the owner
				reset($event['participants']);
				while (list($user,$short_status) = each($event['participants']))
				{
					if ($GLOBALS['phpgw_info']['user']['account_id'] == $user)
					{
						$var = Array(
							'action_url_button'	=> $GLOBALS['phpgw']->link('/index.php','menuaction=calendar.uialarm.manager'),
							'action_text_button'	=> lang('Alarm Management'),
							'action_confirm_button'	=> '',
							'action_extra_field'	=> $this->html->input_hidden(array(
								'cal_id'    => $cal_id,
								'return_to' => $this->bo->return_to
							))
						);
						$p->set_var($var);
						$button_center .= '<td>'.$p->fp('button','form_button').'</td>';
					}
				}
			}

			$var = Array(
				'action_url_button'	=> $this->page('export'),
				'action_text_button'	=> lang('Export'),
				'action_confirm_button'	=> '',
				'action_extra_field'	=> $this->html->input_hidden('cal_id',$cal_id)
			);
			$p->set_var($var);
			$button_center .= '<td>'.$p->fp('button','form_button').'</td>';

			if ($this->bo->return_to)
			{
				$var = Array(
					'action_url_button'	=> $GLOBALS['phpgw']->link('/index.php','menuaction='.$this->bo->return_to),
					'action_text_button'	=> lang('Done'),
					'action_confirm_button'	=> '',
					'action_extra_field'	=> ''
				);
				$p->set_var($var);
				$button_left .= '<td>'.$p->fp('button','form_button').'</td>';
			}
			$p->set_var(array(
				'button_left'	=> $button_left,
				'button_center'	=> $button_center,
				'button_right'	=> $button_right
			));
			$p->pfp('phpgw_body','view_event');

			$GLOBALS['phpgw']->hooks->process(array(
				'location' => 'calendar_view',
				'cal_id'   => $cal_id
			));
			
			$GLOBALS['phpgw']->common->phpgw_footer();
		}

		function edit($params='')
		{
			if($this->debug)
			{
				echo '<!-- params[readsess] = '.$params['readsess'].' -->'."\n";
				echo '<!-- params[cd] = '.$params['cd'].' -->'."\n";
			}

			if(isset($_GET['readsess']))
			{
				$params['readsess'] = $_GET['readsess'];
				$params['cd'] = 0;
			}

			if($this->debug)
			{
				echo '<!-- params[readsess] = '.$params['readsess'].' -->'."\n";
				echo '<!-- params[cd] = '.$params['cd'].' -->'."\n";
			}

			if($params != '' && @is_array($params) && @isset($params['readsess']))
			{
				$can_edit = True;
				$this->edit_form(
					Array(
						'event' => $this->bo->restore_from_appsession(),
						'cd' => $params['cd']
					)
				);
			}
			elseif(isset($_GET['cal_id']))
			{
				$cal_id = (int)$_GET['cal_id'];
				$event = $this->bo->read_entry($cal_id);

				if(!$this->bo->check_perms(PHPGW_ACL_EDIT,$event))
				{
					Header('Location: '.$this->page('view','&cal_id='.$cal_id));
					$GLOBALS['phpgw']->common->phpgw_exit();
				}
				if(@isset($_POST['edit_type']) && $_POST['edit_type'] == 'single')
				{
					$event['id'] = 0;
					$this->bo->set_recur_date($event,$_POST['date']);
					$event['recur_type'] = MCAL_RECUR_NONE;
					$event['recur_interval'] = 0;
					$event['recur_data'] = 0;
					$event['recur_enddate']['month'] = 0;
					$event['recur_enddate']['mday'] = 0;
					$event['recur_enddate']['year'] = 0;
					$event['recur_exception'] = array();
				}
				$plain = $_GET['plain'];

				$this->edit_form(
					Array(
						'event' => $event,
						'cd'	=> $cd,
						'plain' => $plain
					)
				);
			}
 		}

		function export_all()
		{
			$temp_path = $GLOBALS['phpgw_info']['server']['temp_dir'] . SEP;
			srand((double)microtime()*1000000);
			$random_number = rand(100000000,999999999);
			$newfilename = md5(time() . getenv("REMOTE_ADDR") . $random_number );
			$filename = $temp_path . $newfilename;
			$attach_fd = fopen($filename,"w+");
			$event_ids = $this->bo->search_keywords($_POST['keywords']);
			foreach($event_ids as $key => $id)
			{
				$event[$key]=$this->bo->read_entry($id);
			}
			include_once('class.bocalendar.inc.php');
			$cal = new bocalendar;
			$tmpattach=$cal->create_vcard($event);
			fwrite($attach_fd,$tmpattach);
			header ("Content-Type: text/plain"); 
			header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header('Content-Length: ' . filesize($filename));
			header("Content-disposition: attachment; filename="."export.vcard");
			readfile($filename);
			fclose($attach_fd);
		}

		function export($vcal_id=0)
		{
			$temp_path = $GLOBALS['phpgw_info']['server']['temp_dir'] . SEP;
			srand((double)microtime()*1000000);
			$random_number = rand(100000000,999999999);
			$newfilename = md5(time() . getenv("REMOTE_ADDR") . $random_number );
			$filename = $temp_path . $newfilename;
			$attach_fd = fopen($filename,"w+");
			$cal_id = get_var('cal_id',array('GET','POST'),$vcal_id);
			$event[0] = $this->bo->read_entry($cal_id);
			include_once('class.bocalendar.inc.php');
			$cal = new bocalendar;
			$tmpattach=$cal->create_vcard($event);
			fwrite($attach_fd,$tmpattach);
			header ("Content-Type: text/plain"); 
			header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header('Content-Length: ' . filesize($filename));
			header("Content-disposition: attachment; filename="."export.vcard");
			readfile($filename);
			fclose($attach_fd);
		}

		function reinstate_list($params='')
		{
			if(!$this->bo->check_perms(PHPGW_ACL_EDIT))
			{
				$this->no_edit();
			}
			elseif(!$this->bo->check_perms(PHPGW_ACL_ADD))
			{
				$this->index();
			}

			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['calendar']['title'].' - '.lang('Reinstate');
			$GLOBALS['phpgw']->common->phpgw_header();

			$cal_id = get_var('cal_id',array('GET'),$params['cal_id']);

			if ($cal_id < 1)
			{
				echo '<center>'.lang('Invalid entry id.').'</center>'."\n";
				$GLOBALS['phpgw']->common->phpgw_exit(True);
			}

			if(!$this->bo->check_perms(PHPGW_ACL_READ))
			{
				echo '<center>'.lang('You do not have permission to read this record!').'</center>'."\n";
				$GLOBALS['phpgw']->common->phpgw_exit(True);
			}

			$event = $this->bo->read_entry($cal_id);

			if(!isset($event['id']))
			{
				echo '<center>'.lang('Sorry, this event does not exist').'.'.'</center>'."\n";
				$GLOBALS['phpgw']->common->phpgw_exit(True);
			}
			elseif(!isset($event['recur_exception']))
			{
				echo '<center>'.lang('Sorry, this event does not have exceptions defined').'.'.'</center>'."\n";
				$GLOBALS['phpgw']->common->phpgw_exit(True);
			}

			if(!$this->view_event($event,True))
			{
				echo '<center>'.lang('You do not have permission to read this record!').'</center>';
				$GLOBALS['phpgw']->common->phpgw_exit(True);
			}

			$p = &$GLOBALS['phpgw']->template;
			$p->set_file(
				Array(
					'form_button'	=> 'form_button_script.tpl'
				)
			);

			$str = '';

			for($i=0;$i<count($event['recur_exception']);$i++)
			{
				$str .= '    <option value="'.$i.'">'.$GLOBALS['phpgw']->common->show_date($event['recur_exception'][$i]).'</option>'."\n";
			}
			$this->output_template_array($p,'row','list',array(
				'field'	=> lang('Exceptions'),
				'data'	=> '<select name="reinstate_index[]" multiple size="5">'."\n".$str.'</select>'
			));

			$var = Array(
				'action_url_button'	=> $this->page('reinstate','&cal_id='.$cal_id),
				'action_text_button'	=> lang('Reinstate'),
				'action_confirm_button'	=> '',
				'action_extra_field'	=> ''
			);
			$p->set_var($var);
			$button_left = '<td>'.$p->fp('out','form_button').'</td>';

			$var = Array(
				'action_url_button'	=> $this->bo->return_to ? $GLOBALS['phpgw']->link('/index.php','menuaction='.$this->bo->return_to) : $this->page(''),
				'action_text_button'	=> lang('Cancel'),
				'action_confirm_button'	=> '',
				'action_extra_field'	=> ''
			);
			$p->set_var($var);
			$button_left .= '<td>'.$p->fp('out','form_button').'</td>';

			$p->set_var('button_left',$button_left);
			$p->pfp('phpgw_body','view_event');
		}

		function reinstate($params='')
		{
			if(!$this->bo->check_perms(PHPGW_ACL_EDIT))
			{
				$this->no_edit();
			}
			elseif(!$this->bo->check_perms(PHPGW_ACL_ADD))
			{
				$this->index();
			}
			$cal_id = (isset($params['cal_id']) ? (int)$params['cal_id'] : '');
			$cal_id = ($cal_id == '' ? (int)$_GET['cal_id'] : $cal_id);

			$reinstate_index = (isset($params['reinstate_index']) ? (int)$params['reinstate_index'] : '');
			$reinstate_index = ($reinstate_index == '' ? (int)$_POST['reinstate_index'] : $reinstate_index);
			if($this->debug)
			{
				echo '<!-- Calling bo->reinstate -->'."\n";
			}
			$cd = $this->bo->reinstate(
				Array(
					'cal_id'	=> $cal_id,
					'reinstate_index'	=> $reinstate_index
				)
			);
			if($this->debug)
			{
				echo '<!-- Return Value = '.$cd.' -->'."\n";
			}
			if ($this->bo->return_to)
			{
				Header('Location: '.$GLOBALS['phpgw']->link('/index.php','menuaction='.$this->bo->return_to));
			}
			else
			{
				Header('Location: '.$this->page('',($cd?'&cd='.$cd:'')));
			}
			$GLOBALS['phpgw']->common->phpgw_exit();
		}

		function add($cd=0,$readsess=0)
		{
			if(!$this->bo->check_perms(PHPGW_ACL_ADD))
			{
				$this->index();
			}
			
			if($readsess)
			{
				$event = $this->bo->restore_from_appsession;
				if(!$event['owner'])
				{
					$this->bo->add_attribute('owner',$this->bo->owner);
				}
				$can_edit = True;
			}
			else
			{
				$this->bo->event_init();
				$this->bo->add_attribute('id',0);

				$can_edit = True;
				$participants = (string)(get_var('participants',array('GET'),FALSE));
				$starthour = (int)(get_var('hour',array('GET'),$this->bo->prefs['calendar']['workdaystarts']));
				$startmin  = (int)(get_var('minute',array('GET'),0));
				$endmin    = $startmin + (int)$this->bo->prefs['calendar']['defaultlength'];
				$endhour   = $starthour + $this->bo->normalizeminutes($endmin);
				
				$subject	= (string)(get_var('title',array('GET'),0));
				if ($subject != '0')
				{
					$description	= (string)(get_var('description',array('GET'),0));
					$location	= (string)(get_var('location',array('GET'),0));
				}
				else
					unset($subject);
				
				$this->bo->set_start($this->bo->year,$this->bo->month,$this->bo->day,$starthour,$startmin,0);
				$this->bo->set_end($this->bo->year,$this->bo->month,$this->bo->day,$endhour,$endmin,0);
				$this->bo->set_title($subject);
				$this->bo->set_description($description);
				$this->bo->add_attribute('location',$location);
				$this->bo->set_ex_participants('');
				$this->bo->add_attribute('uid','');
				$this->bo->add_attribute('priority',2);
				if(@$this->bo->prefs['calendar']['default_private'])
				{
					$this->bo->set_class(False);
				}
				else
				{
					$this->bo->set_class(True);
				}

				// Add misc
				$this->bo->add_attribute('participants','A',$this->bo->owner);
				$this->bo->set_recur_none();
				$event = $this->bo->get_cached_event();
				
			}
			$this->edit_form(
				Array(
					'event' => $event,
					'cd' => $cd,
					'plain' => $_GET['plain']
				)
			);
			$GLOBALS['phpgw']->common->phpgw_footer();
		}

		function delete()
		{
			if(!isset($_GET['cal_id']))
			{
				Header('Location: '.$this->page('','&date='.sprintf("%04d%02d%02d",$this->bo->year,$this->bo->month,$this->bo->day)));
				$GLOBALS['phpgw']->common->phpgw_exit();
			}

			$date = sprintf("%04d%02d%02d",$this->bo->year,$this->bo->month,$this->bo->day);
			if($this->bo->check_perms(PHPGW_ACL_DELETE,$cal_id = (int)$_GET['cal_id']))
			{
				if(isset($_POST['delete_type']) && $_POST['delete_type'] == 'single')
				{
					$date = $_POST['date'];
					$cd = $this->bo->delete_single(
						Array(
							'id'	=> $cal_id,
							'year'	=> substr($date,0,4),
							'month'	=> substr($date,4,2),
							'day'	=> substr($date,6,2)
						)
					);
				}
				elseif((isset($_POST['delete_type']) && $_POST['delete_type'] == 'series') || !isset($_POST['delete_type']))
				{
					$cd = $this->bo->delete_entry($cal_id);
					$this->bo->expunge();
				}
			}
			else
			{
				$cd = '';
			}
			if ($this->bo->return_to)
			{
				Header('Location: '.$GLOBALS['phpgw']->link('/index.php','menuaction='.$this->bo->return_to));
			}
			else
			{
				Header('Location: '.$this->page('','&date='.$date.($cd?'&cd='.$cd:'')));
			}
			$GLOBALS['phpgw']->common->phpgw_exit();
		}

		function disponibility()
		{

			if(!isset($_SESSION['phpgw_info']['expresso']['expressoAdmin']))
			{
				$c = CreateObject('phpgwapi.config','expressoAdmin1_2');
				$c->read_repository();
				$current_config = $c->config_data;

				$ldap_manager = CreateObject('contactcenter.bo_ldap_manager');

				$_SESSION['phpgw_info']['expresso']['user'] = $GLOBALS['phpgw_info']['user'];
				$_SESSION['phpgw_info']['expresso']['server'] = $GLOBALS['phpgw_info']['server'];
				$_SESSION['phpgw_info']['expresso']['cc_ldap_server'] = $ldap_manager ? $ldap_manager->srcs[1] : null;
				$_SESSION['phpgw_info']['expresso']['expressoAdmin'] = $current_config;
				$_SESSION['phpgw_info']['expresso']['global_denied_users'] = $GLOBALS['phpgw_info']['server']['global_denied_users'];
				$_SESSION['phpgw_info']['expresso']['global_denied_groups'] = $GLOBALS['phpgw_info']['server']['global_denied_groups'];
			}
			
			$str_participants = substr($_POST['participants'],0,strlen($_POST['participants'])-1); //remove the last comma
			
			$participants= array();

			$participants_splited = explode(",",$str_participants);
			
			foreach($participants_splited as $participant)
			{
				$participant = substr($participant,0,strlen($participant)-1);
				$acct_type = $GLOBALS['phpgw']->accounts->get_type((int)$participant);
				if($acct_type=='g')
				{
					$bo_groups = CreateObject('expressoAdmin1_2.group');
					$users = $bo_groups->get_info($participant,true);
					foreach($users['memberuid_info'] as $user)
					{
						if($user['type']==u)
							$participants[$user['uidnumber']] = 'U';
					}
				}
				else
				{
					$participants[$participant] = 'U';
				}
			}

			$date = explode("/",$_POST['date']);
			
			$freetime = $GLOBALS['phpgw']->datetime->localdates(mktime(0,0,0,$date[1],$date[0],$date[2]) - $GLOBALS['phpgw']->datetime->tz_offset);

			echo json_encode( $this->print_disponibility(
			 	array(
			 		'date'			=> $freetime,
			 		'starttime'		=> $this->bo->splittime('000000',False),
			 		'endtime'		=> 0,
			 		'participants'	=> $participants
			 	)
			));
		}

		function print_disponibility($param)
		{ 
			if(!is_array($param))
			{
				$this->index();
			}

			$date 		= $param['date'];
			$starttime	= $param['starttime'];
			$endtime 	= $param['endtime'];
			$participants = $param['participants'];
			
			foreach($participants as $part => $nul)
			{
				$participants[$part] = $GLOBALS['phpgw']->common->grab_owner_name($part);
			}
			
			// sort them after their fullname
			uasort($participants,'strnatcasecmp');	

			if(!isset($this->bo->prefs['calendar']['interval']))
			{
				$this->bo->prefs['calendar']['interval'] = 15;
				$GLOBALS['phpgw']->preferences->add('calendar','interval',15);
				$GLOBALS['phpgw']->preferences->save_repository();
			}
			$increment = $this->bo->prefs['calendar']['interval'];
			$interval = (int)(60 / $increment);

			$pix = $GLOBALS['phpgw']->common->image('calendar','pix');

			$mapUsers  = lang($GLOBALS['phpgw']->common->show_date($date['raw'],'l')).' , '.$this->bo->long_date($date); 
			$mapUsers .= '<table width="98%" cols="'.((10 * $interval) + 1).'">';
			$mapUsers .= '<tr><td colspan="'.((11 * $interval) + 1).'" bgcolor="#000000"></td></tr>';
			$mapUsers .= '<tr><td width="20%">'.lang('Participants').'</td>';
			
			for( $i = 8 ; $i < 19 ; $i++ )
			{
				for($j=0;$j<$interval;$j++)
				{
					$mapUsers .= '<td ondblclick="clear_time();" onclick="set_time(\''.sprintf('%02d',$i).'\',\''.sprintf('%02d',$j*$increment).'\');" style="cursor:pointer">';
					$mapUsers .= ($j == 0 ? sprintf('%02d',$i).'<br>':'').sprintf('%02d',$j*$increment);
					$mapUsers .= '</td>';
				}
			}

			$mapUsers .= '</tr><tr><td colspan="'.((11 * $interval) + 1).'" bgcolor="#000000"></td></tr>';

			if(!$endtime)
			{
				$endtime = $starttime;
			}
			
			$owner = $this->bo->owner;
			
			foreach( $participants as $part => $fullname )
			{
				if( strpos( $fullname, "[") !== false )
					$mapUsers .= '<tr><td width="20%">'.substr($fullname , 0, strpos( $fullname, "[") ).'</td>';
				else
					$mapUsers .= '<tr><td width="20%">'.$fullname.'</td>';

				$this->bo->cached_events = Array();
				$this->bo->so->owner = $part;
				$this->bo->so->open_box($part);
				$this->bo->store_to_cache(
					array(
						'syear'	=> $date['year'],
						'smonth'=> $date['month'],
						'sday'	=> $date['day'],
						'eyear'	=> 0,
						'emonth'=> 0,
						'eday'	=> $date['day'] + 1
					)
				);

				if( !$this->bo->cached_events[$date['full']] )
				{
					for( $j = 8 ; $j < 19; $j++ )
					{
						for( $k = 0; $k < $interval ; $k++ )
						{
							$mapUsers .= '<td name="'.sprintf('%02d',$j).sprintf('%02d',($k*$increment)).'" bgcolor="'.$this->theme['bg_color'].'" color="#999999">&nbsp;</td>';
						}
					}
				}
				else
				{
					$time_slice = $this->bo->prepare_matrix($interval,$increment,$part,$date['full']);
					
					for( $h = 8 ; $h < 19 ; $h++ )
					{
						$hour = $h * 10000;
						
						for( $m = 0; $m < $interval; $m++ )
						{
							$index = ( $hour + ( ($m * $increment) * 100) );
							
							switch( $time_slice[$index]['marker'] )
							{
								case '&nbsp':
									$time_slice[$index]['color'] = $this->theme['bg_color'];
									break;
								case '-':
									$time_slice[$index]['color'] = $this->theme['bg11'];
									$link = $this->page('view','&cal_id='.$time_slice[$index]['id'].'&date='.$date['full']);
									break;
							}
							
							$mapUsers .= '<td name="'.sprintf('%02d',$h).sprintf('%02d',($m*$increment)).'" bgcolor="'.$time_slice[$index]['color'].'" color="#999999">'.$time_slice[$index]['marker'].'</td>';
						}
					}
				}
				$mapUsers .= '</tr><tr><td colspan="'.((11 * $interval) + 1).'" bgcolor="#999999"></td></tr>';
			}
			
			$mapUsers .= "</table>";

			$this->bo->owner = $owner;
			$this->bo->so->owner = $owner;
			$this->bo->so->open_box($owner);
			
			return array( "map_users" => $mapUsers );			
		}

		function day()
		{
			$this->bo->read_holidays();

			$next = $GLOBALS['phpgw']->datetime->makegmttime(0,0,0,$this->bo->month,$this->bo->day + 1,$this->bo->year);
			$prev = $GLOBALS['phpgw']->datetime->makegmttime(0,0,0,$this->bo->month,$this->bo->day - 1,$this->bo->year);

			if (!$this->bo->printer_friendly || ($this->bo->printer_friendly && @$this->bo->prefs['calendar']['display_minicals']))
			{
				$minical = $this->mini_calendar(
					Array(
						'day'	=> $this->bo->day,
						'month'	=> $this->bo->month,
						'year'	=> $this->bo->year,
						'link'	=> 'day'
					)
				);
			}
			else
			{

				//NDEE: printer-friendly (what?)
				$minical = '';
			}

			if (!$this->bo->printer_friendly)
			{
				$printer = '';
				$prev_day_link = '<a href="'.$this->page('day','&date='.$prev['full']).'">&lt;&lt;</a>';
				$next_day_link = '<a href="'.$this->page('day','&date='.$next['full']).'">&gt;&gt;</a>';
				$param = '&date='.sprintf("%04d%02d%02d",$this->bo->year,$this->bo->month,$this->bo->day).'&friendly=1';
				$print = '<a href="'.$this->page('day'.$param)."\" TARGET=\"cal_printer_friendly\" onMouseOver=\"window.status = '".lang('Generate printer-friendly version')."'\">[".lang('Printer Friendly').']</a>';
			}
			else
			{

				//NDEE: printer-friendly (daily-view)
				$GLOBALS['phpgw_info']['flags']['nofooter'] = True;
				$printer = '<body bgcolor="'.$this->theme['bg_color'].'">';
				$print =	'';
			}

			$now	= $GLOBALS['phpgw']->datetime->makegmttime(0, 0, 0, $this->bo->month, $this->bo->day, $this->bo->year);
			$now['raw'] += $GLOBALS['phpgw']->datetime->tz_offset;
			
			$week_days = array(lang('Sunday'),
								lang('Monday'), 
								lang('Tuesday'),
								lang('Wednesday'),
								lang('Thrusday'), 
								lang('Friday'),
								lang('Saturday'));
			
	
			$p = $GLOBALS['phpgw']->template;
			$p->set_file(
				Array(
					'day_t' => 'day.tpl'
				)
			);
			$p->set_block('day_t','day','day');
			$p->set_block('day_t','day_event','day_event');

			$todos = $this->get_todos($todo_label);
			$var = Array(
				'printer_friendly'	=> $printer,
				'bg_text'			=> $this->theme['bg_text'],
				'daily_events'		=> $this->print_day(
					Array(
						'year'	=> $this->bo->year,
						'month'	=> $this->bo->month,
						'day'	=> $this->bo->day
					)
				),
				'small_calendar'	=> $minical,
				'date'				=> $this->bo->long_date($now),
				'day_of_week'		=> $week_days[$now['dow']],
				'username'			=> $GLOBALS['phpgw']->common->grab_owner_name($this->bo->owner),
				'prev_day_link'		=> $prev_day_link,
				'next_day_link'		=> $next_day_link,
				'print'				=> $print,
				'lang_todos'		=> $todo_label,
				'todos'				=> $this->bo->printer_friendly ? $todos :

					//NDEE: todo's layout
					"<div style=\"overflow: auto; max-height: 200px\">\n$todos</div>\n"
			);

			$p->set_var($var);
			$p->parse('day_events','day_event');
			print $this->printer_friendly($p->fp('out','day'),lang('Dayview'));
			$GLOBALS['phpgw']->common->phpgw_footer();
		}

		function get_todos(&$todo_label)
		{
			$todos_from_hook = $GLOBALS['phpgw']->hooks->process(array(
				'location'  => 'calendar_include_todos',
				'year'      => $this->bo->year,
				'month'     => $this->bo->month,
				'day'       => $this->bo->day,
				'owner'     => $this->bo->owner	// num. id of the user, not necessary current user
			));

			if(is_array($todo_label))
			{
				list($label,$showall)=$todo_label;
			}
			else
			{
				$label=$todo_label;
				$showall=true;
			}
			$maxshow = (int)$GLOBALS['phpgw_info']['user']['preferences']['infolog']['mainscreen_maxshow'];
			if($maxshow<=0)
			{
				$maxshow=10;
			}
			//print_debug("get_todos(): label=$label; showall=$showall; max=$maxshow");

			$content = $todo_label = '';
			if (is_array($todos_from_hook) && count($todos_from_hook))
			{
				$todo_label = !empty($label)?$label:lang("open ToDo's:");

				foreach($todos_from_hook as $todos)
				{
					$i = 0;
					if (is_array($todos) && count($todos))
					{
						foreach($todos as $todo)
						{
							if(!$showall && ($i++>$maxshow))
							{
								break;
							}
							$icons = '';
							foreach($todo['icons'] as $name => $app)
							{
								$icons .= ($icons?' ':'').$GLOBALS['phpgw']->html->image($app,$name,lang($name),'border="0" width="15" height="15"');
							}
							$class = $class == 'row_on' ? 'row_off' : 'row_on';

//NDEE <tr starts here
							$content .= " <tr id=\"debug\" class=\"$class\">\n  <td valign=\"top\" width=\"15%\"nowrap>".
								($this->bo->printer_friendly?$icons:$GLOBALS['phpgw']->html->a_href($icons,$todo['view'])).
								"</td>\n  <td>".($this->bo->printer_friendly?$todo['title']:
								$GLOBALS['phpgw']->html->a_href($todo['title'],$todo['view']))."</td>\n </tr>\n";
						}
					}
				}
			}
			if (!empty($content))
			{
				return "<table border=\"0\" width=\"100%\">\n$content</table>\n";
			}
			return False;
		}

		function edit_status()
		{
			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			$GLOBALS['phpgw_info']['flags']['noappheader'] = True;
			$GLOBALS['phpgw_info']['flags']['noappfooter'] = True;
			$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['calendar']['title'].' - '.lang('Change Status');
			$GLOBALS['phpgw']->common->phpgw_header();

			$event = $this->bo->read_entry($_GET['cal_id']);

			reset($event['participants']);

			if(!$event['participants'][$this->bo->owner])
			{
				echo '<center>'.lang('The user %1 is not participating in this event!',$GLOBALS['phpgw']->common->grab_owner_name($this->bo->owner)).'</center>';
				return;
			}

			if(!$this->bo->check_perms(PHPGW_ACL_EDIT))
			{
				$this->no_edit();
				return;
			}

			$freetime = $GLOBALS['phpgw']->datetime->localdates(mktime(0,0,0,$event['start']['month'],$event['start']['mday'],$event['start']['year']) - $GLOBALS['phpgw']->datetime->tz_offset);
			echo $this->timematrix(
				Array(
					'date'		=> $freetime,
					'starttime'	=> $this->bo->splittime('000000',False),
					'endtime'	=> 0,
					'participants'	=> $event['participants']
				)
			).'<br>';

			$event = $this->bo->read_entry($_GET['cal_id']);
			$this->view_event($event);
			$GLOBALS['phpgw']->template->pfp('phpgw_body','view_event');

			echo $this->get_response($event['id']);
		}
		
		function confirm_action($duplicated_action)
		{
				if($_GET['response'] == 1)
				{
					if(!$duplicated_action)
						$notify_message = lang('The commitment was accepted successfully!');
					else
						$notify_message = lang('This commitment has already been accepted!');
				}	
				else
				{					
					if(!$duplicated_action)						
						$notify_message = lang('The commitment was rejected successfully!');
					else
						$notify_message = lang('This commitment has already been rejected!');
				}
				
				$body1 = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
				$body1->set_file(Array('calendar' => 'confirm.tpl'));
				$body1->set_block('calendar','list');
				$var = Array( 'notify_message'	=> $notify_message);
				$body1->set_var($var);
				$tmpbody1 = $body1->pfp('out','list');
		}
		
		function confirm_conflict($user_id,$event,$overlapping_events)
		{
			if(!$overlapping_events){					
				$notify_message = lang('This event conflicts with your other event.');
	
				$action_ignore = $GLOBALS['phpgw']->link('/index.php',Array('menuaction'=>'calendar.uicalendar.set_action','cal_id' => $event[id], 'action' => 3, 'response' => 1,'user_id' => $user_id, 'ignore_conflict' => 1));
				$action_reject = $GLOBALS['phpgw']->link('/index.php',Array('menuaction'=>'calendar.uicalendar.set_action','cal_id' => $event[id], 'action' => 0, 'response' => 0));
	
				$body1 = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
				$body1->set_file(Array('calendar' => 'conflict.tpl'));
				$body1->set_block('calendar','list');
				$var = Array( 'notify_message'	=> $notify_message,
							'action_url' => $action_url,
							'ignore_conflict' => 'Ignorar Conflito',
							'reject_event' => 'Rejeitar Evento',
							'action_reject' => $action_reject,
							'action_ignore' => $action_ignore);
				$body1->set_var($var);
				$tmpbody1 = $body1->pfp('out','list');
			}
			else
			{				
				$month = $event['start']['month'];
				$mday = $event['start']['mday'];
				$year = $event['start']['year'];
	
				$start = mktime($event['start']['hour'],$event['start']['min'],$event['start']['sec'],$month,$mday,$year) - $GLOBALS['phpgw']->datetime->tz_offset;
				$end = $this->bo->maketime($event['end']) - $GLOBALS['phpgw']->datetime->tz_offset;
	
				$overlap = '';
				for($i=0;$i<count($overlapping_events);$i++)
				{
					$overlapped_event = $this->bo->read_entry($overlapping_events[$i],True);
					foreach($overlapped_event['participants'] as $id => $status)
					{
						$conflict = isset($event['participants'][$user_id]);
						$overlap .= '<li>'.($conflict?'<b>':'').
							$GLOBALS['phpgw']->common->grab_owner_name($id).
							($conflict?'</b> - '.lang('Scheduling conflict'):'')."</li>";
					}
				 if ($this->bo->prefs['calendar']['hide_event_conflict'])
					 $overlap .= '<ul><font size="1"><span>'.
						 $this->normDec($overlapped_event['start']['hour']).":".
						 $this->normDec($overlapped_event['start']['min'])."-".
						 $this->normDec($overlapped_event['end']['hour']).":".
						 $this->normDec($overlapped_event['end']['min']).
						 '<br><b>'.lang('title').": ".lang("Hidden").
						 '</b><br>'.lang('description').": ".lang("Hidden").'<br></span></ul><br>';
				 else
					 $overlap .= '<ul>'.$this->link_to_entry($overlapped_event,$month,$mday,$year)."</ul><br>";	
				}
	
				unset($GLOBALS['phpgw_info']['flags']['noheader']);
				unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
				$GLOBALS['phpgw_info']['flags']['noappheader'] = True;
				$GLOBALS['phpgw_info']['flags']['noappfooter'] = True;
				$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['calendar']['title'].' - '.lang('Scheduling Conflict');
				$GLOBALS['phpgw']->common->phpgw_header();
	
				$p = $GLOBALS['phpgw']->template;
				$p->set_file(
					Array(
						'overlap'	=> 'overlap.tpl',
	   					'form_button'	=> 'form_button_script.tpl'
					)
				);
	
				$var = Array(
					'color'		=> $this->theme['bg_text'],
					'overlap_title' => lang('Scheduling Conflict'),
					'overlap_text'	=> lang('Your suggested time of <B> %1 - %2 </B> conflicts with the following existing calendar entries:',$GLOBALS['phpgw']->common->show_date($start),$GLOBALS['phpgw']->common->show_date($end)),
					'overlap_list'	=> $overlap
				);
				$p->set_var($var);
	
				$date = sprintf("%04d%02d%02d",$this->bo->year,$this->bo->month,$this->bo->mday);
				$var = Array(
					'action_url_button'	=> $GLOBALS['phpgw']->link('/index.php',Array('menuaction'=>'calendar.uicalendar.set_action','cal_id' => $event[id], 'action' => 3, 'user_id' => $user_id, 'ignore_conflict' => 1)),
					'action_text_button'	=> lang('Ignore Conflict'),
					'action_confirm_button'	=> '',
					'action_extra_field'	=> '',
					'button_id'		=> 'ignore_button'
				);				
				$action_reject = $GLOBALS['phpgw']->link('/index.php',Array('menuaction'=>'calendar.uicalendar.set_action','cal_id' => $event[id], 'action' => 0, 'response' => 0));
	
				$this->output_template_array($p,'resubmit_button','form_button',$var);
	
				$var = Array(
					'action_url_button'	=> $GLOBALS['phpgw']->link('/index.php',Array('menuaction'=>'calendar.uicalendar.set_action','cal_id' => $event[id], 'action' => 0)),
					'action_text_button'	=> lang('Reject'),
					'action_confirm_button'	=> '',
					'action_extra_field'	=> '',
					'button_id'		=> 'redit_button'
				);
				$this->output_template_array($p,'reedit_button','form_button',$var);
				$p->pparse('out','overlap');
			}
		}
		
		function set_action()
		{
			if(!$this->bo->check_perms(PHPGW_ACL_EDIT))
			{
				$this->no_edit();
				return;
			}
			
			$overlapping_events = false;
			$event = $this->bo->read_entry((int)$_GET['cal_id']);
			if((!$_GET['response'] == 0 && !$_GET['ignore_conflict'] == 1) || 
				(!isset($_GET['response']) && $_GET['action'] == 3 && !$_GET['ignore_conflict'] == 1))
			{
				if($event['id'])
				{
					$event_ids[] = $event['id'];
				}
				if($event['reference'])
				{
					$event_ids[] = $event['reference'];
				}
				
                                $event['participants'] = Array($GLOBALS['phpgw_info']['user']['account_id'] => $event['participants'][$GLOBALS['phpgw_info']['user']['account_id']]);

				$overlapping_events = $this->bo->overlap(
					$this->bo->maketime($event['start']),
					$this->bo->maketime($event['end']),
					$event['participants'],
					$event['owner'],
					$event_ids
				);
			}

			if(isset($_GET['response']))
			{
				if($overlapping_events)
				{
					$this->confirm_conflict($_GET['user_id'],$event);
				}else
				{
					$confirm_status = $this->bo->set_status((int)$_GET['cal_id'],(int)$_GET['action']);
					$this->confirm_action($confirm_status);
					$GLOBALS['phpgw']->common->phpgw_exit(False);
				}
			}else
			{
				if($overlapping_events)
				{					
					$this->confirm_conflict($_GET['user_id'],$event,$overlapping_events);
				}else
				{
				$confirm_status = $this->bo->set_status((int)$_GET['cal_id'],(int)$_GET['action']);
					if ($this->bo->return_to)
					{
						Header('Location: '.$GLOBALS['phpgw']->link('/index.php','menuaction='.$this->bo->return_to));
					}
					else
					{
						Header('Location: '.$this->page('',''));
					}
					$GLOBALS['phpgw']->common->phpgw_exit();
				}
			}
			
			
		}
		function planner()
		{
			if(floor(phpversion()) < 4)
			{
				return;
			}
			$home = strstr($_SERVER['PHP_SELF'],'home') !== False;
			// generate header and set global/member variables
			//
			$this->planner_prepare($home);

			// process events within selected interval
			//
			$this->planner_process_interval();

			// generate the planner view
			//
			if (!$home)
			{
				echo '<p>'.$this->planner_print_rows();
			}
			else
			{
				return $this->planner_print_rows();
			}
		}

		function set_planner_group_members()
		{
			$type = $GLOBALS['phpgw']->accounts->get_type($this->bo->owner);

			if ($type == 'g') // display schedule of all group members
			{
				$members = array();
				$ids = $GLOBALS['phpgw']->acl->get_ids_for_location($this->bo->owner, 1, 'phpgw_group');
				while (list(,$id) = each($ids))
				{
					if ($this->bo->check_perms(PHPGW_ACL_READ,0,$id))
					{
						$members[$GLOBALS['phpgw']->common->grab_owner_name($id)] = $id;
					}
				}
				ksort($members);
				$this->planner_group_members = $members;
			}
			else
			{
				$this->planner_group_members = array(
					$GLOBALS['phpgw']->common->grab_owner_name($this->bo->owner) => $this->bo->owner
				);
			}
		}

		/**
		 * planner_prepare - prepare the planner view
		 *
		 * - sets global environment variables
		 * - initializes class member variables used in multiple planner related functions
		 * - generates header lines for the planner view (month, calendar week, days)
		 */
		function planner_prepare($no_header = False)
		{
			// set some globals
			//
			if (!$no_header)
			{
				unset($GLOBALS['phpgw_info']['flags']['noheader']);
				unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
				if ($this->always_app_header) $GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['calendar']['title'].' - '.lang('Group Planner');
				$GLOBALS['phpgw']->common->phpgw_header();
			}

			// intervals_per_day can be configured in preferences now :-)
			//
			if (! $this->bo->prefs['calendar']['planner_intervals_per_day'])
			{
				$GLOBALS['phpgw']->preferences->add('calendar','planner_intervals_per_day',3);
				$GLOBALS['phpgw']->preferences->save_repository();
				$this->bo->prefs['calendar']['planner_intervals_per_day'] = 3;
			}
			$intervals_per_day = $this->bo->prefs['calendar']['planner_intervals_per_day'];
			$this->bo->save_sessiondata();	// need to save $this->bo->save_owner

			// set title for table and rows of planner view
			//
			if ($this->bo->sortby == 'category')
			{
				$title = lang('Category');
			}
			else
			{
				$title = lang('User');

				$this->set_planner_group_members();
			}

			// create/initialize variables directly used for HTML code generation
			//
			$this->planner_header = array();
			$this->planner_rows   = array();

			// generate header lines with days and associated months
			//
			$hdr = &$this->planner_header;
			$hdr[0]['0']  = $title;
			$hdr[0]['.0'] = 'rowspan="3"';

			$this->planner_days = 0; // reset

			$m = $this->bo->month;
			$y = $this->bo->year;
			$this->bo->read_holidays($y);
			for ($i=1; $i<=$this->bo->num_months; $i++,$m++)
			{
				if ($m == 13)
				{
					$m = 1; $y++; // "wrap-around" into new year
					$this->bo->read_holidays($y);
				}
				$days = $GLOBALS['phpgw']->datetime->days_in_month($m,$y);

				$d     = mktime(0,0,0,$m,1,$y);
				$month = lang(date('F', $d)).strftime(' %Y', $d);
				$color = $this->theme[$m % 2 || $this->bo->num_months == 1 ? 'th_bg' : 'row_on'];
				$cols  = $days * $intervals_per_day;

				$hdr[0]['.'.$i] = 'bgcolor="'.$color.'" colspan="'.$cols.'" align="center"';
				$prev_month = sprintf('%04d%02d01',$y-($m==1),$m > 1?$m-1:12);
				$next_month = sprintf('%04d%02d01',$y+($m==12),$m < 12?$m+1:1);
				$prev_link = $GLOBALS['phpgw']->link('/index.php',"menuaction=calendar.uicalendar.planner&date=$prev_month");
				$next_link = $GLOBALS['phpgw']->link('/index.php',"menuaction=calendar.uicalendar.planner&date=$next_month");
				$hdr[0][$i] = "<b><a href=\"$prev_link\">&lt;&lt;</a> &nbsp $month &nbsp <a href=\"$next_link\">&gt;&gt;</a></b>";

				$add_owner = array();	// if no add-rights on the showed cal use own cal
				if (!$this->bo->save_owner && !$this->bo->check_perms(PHPGW_ACL_ADD) ||
					!$this->bo->check_perms(PHPGW_ACL_ADD,0,$this->bo->save_owner))
				{
					$add_owner = array(
						'owner' => $GLOBALS['phpgw_info']['user']['account_id']
					);
				}
				for ($d=1; $d<=$days; $d++)
				{
					$dayname = substr(lang(date('D',mktime(0,0,0,$m,$d,$y))),0,2);
					$index = $d + $this->planner_days;

					$hdr[2]['.'.$index] = 'colspan="'.$intervals_per_day.'" align="center"';

					// highlight today, saturday, sunday and holidays
					//
					$color = $this->theme['row_off'];
					$dow = $GLOBALS['phpgw']->datetime->day_of_week($y,$m,$d);
					$date = sprintf("%04d%02d%02d",$y,$m,$d);
					if ($date == date('Ymd'))
					{
						$color = $GLOBALS['phpgw_info']['theme']['cal_today'];
					}
					elseif ($this->bo->cached_holidays[$date])
					{
						$color = $this->bo->holiday_color;
						$hdr[2]['.'.$index] .= ' title="'.$this->bo->cached_holidays[$date][0]['name'].'"';
					}
					elseif ($dow == 0 || $dow == 6)
					{
						$color = $this->bo->theme['th_bg'];
					}

					$hdr[2]['.'.$index] .= " bgcolor=\"$color\"";

					$hdr[2][$index] = '<a href="'.$this->html->link('/index.php',
								array(
									'menuaction' => 'calendar.uicalendar.add',
									'date' => $date
								) + $add_owner
							).'">'.$dayname.'<br>'.$d.'</a>';
				}
				$this->planner_days += $days;
			}

			// create/initialize member variables describing the time interval to be displayed
			//
			$this->planner_end_month = $m - 1;
			$this->planner_end_year  = $y;
			$this->planner_days_in_end_month = $GLOBALS['phpgw']->datetime->days_in_month($this->planner_end_month,$this->planner_end_year);
			$this->planner_firstday = (int)(date('Ymd',mktime(0,0,0,$this->bo->month,1,$this->bo->year)));
			$this->planner_lastday  = (int)(date('Ymd',mktime(0,0,0,$this->planner_end_month,$this->planner_days_in_end_month,$this->planner_end_year)));

			// generate line with calendar weeks in observed interval
			//
			$d      = mktime(0,0,0,$this->bo->month,1,$this->bo->year);
			$w      = date('W', $d);
			if ($w == 'W')	// php < 4.1
			{
				$w = 1 + (int)(date('z',$d) / 7);	// a bit simplistic
			}
			$offset = (7-date('w', $d)+1)%7;
			$offset = $offset == 0 ? 7 : $offset;
			$color = $this->theme[$w % 2 ? 'th_bg' : 'row_on'];

			$hdr[1]['.'.$w] = 'bgcolor="'.$color.'" colspan="'.$intervals_per_day * $offset.'" align="left"';
			$hdr[1][$w] = '';
			if ($offset >= 3)
			{

//NDEE: style! (groupplanner)
				$hdr[1][$w] .= '<font size="-2"> '.lang('week').' '.$w.' </font>';
			}
			$days_left = $this->planner_days - $offset;

			$colspan = 7 * $intervals_per_day;
			while ($days_left > 0)
			{
				$colspan = ($days_left < 7) ? $days_left*$intervals_per_day : $colspan;
				$d += 604800; // 7 days whith 24 hours (1h == 3600 seconds) each
				$w = date('W', $d);
				if ($w == 'W')	// php < 4.1
				{
					$w = 1 + (int)(date('z',$d) / 7);	// a bit simplistic
				}
				$w += (isset($hdr[1][$w]))?1:0; // bug in "date('W')" ?

				$color = $this->theme[$w % 2 ? 'th_bg' : 'row_on'];
				$hdr[1]['.'.$w] = 'bgcolor="'.$color.'" colspan="'.$colspan.'" align="left"';
				$hdr[1][$w] = '';
				if ($days_left >= 3)
				{

//NDEE: style! (groupplanner)
					$hdr[1][$w] .= '<font size="-2"> '.lang('week').' '.$w.' </font>';
				}

				$days_left -= 7;
			}
			return $hdr;
		}

		/**
		 * planner_update_row - update a row of the planner view
		 *
		 * parameters are:
		 *   - index (e.g. user id, category id, ...) of the row
		 *   - name/title of the row (e.g. user name, category name)
		 *   - the event to be integrated
		 *   - list of categories associated with the event
		 *   - first and last cell of the row
		 */
		function planner_update_row($index,$name,$event,$cat,$start_cell,$end_cell)
		{
			$rows              = &$this->planner_rows;
			$intervals_per_day = $this->bo->prefs['calendar']['planner_intervals_per_day'];
			$is_private        = !$this->bo->check_perms(PHPGW_ACL_READ,$event);

			$view = $this->html->link('/index.php',
				array(
					'menuaction' => 'calendar.uicalendar.view',
					'cal_id' => $event['id'],
					'date' => date('Ymd',$this->bo->maketime($event['start']))
				)
			);

			// check how many lines are needed for this "row" (currently: user or category)
			$i = 0;
			do {
				++$i;

				$k = $index.'_'.$i;
				$ka = '.nr_'.$k;

				if (!isset($rows[$k]))
				{
					if ($i > 1)				// further line - no name
					{
						$rows[$k] = array();
						$rows[$index.'_1']['._name'] = 'rowspan="'.$i.'"';
					}
					else
					{
						$rows[$k]['_name'] = $name;
					}
					$rows[$ka] = 0;
				}
				$rows[$index.'_1']['._name'] .= ' nowrap'; // title must be one row

				$row = &$rows[$k];
				$akt_cell = &$rows[$ka];
			} while ($akt_cell > $start_cell);

			$id = $event['id'].'-'.date('Ymd',$this->bo->maketime($event['start']));
			if ($akt_cell < $start_cell)
			{
				$row[$id.'_1'] = '&nbsp;';
				$row['.'.$id.'_1'] = 'colspan="'.($start_cell-$akt_cell).'"';
			}
			$opt = &$row['.'.$id.'_2'];
			$cel = &$row[$id.'_2'];

			// if possible, display information about event within cells representing it
			//
			if ($start_cell < $end_cell)
			{
				$colspan = $end_cell - $start_cell;
				$opt .= "colspan=".(1 + $colspan);

				if (!$is_private)
				{
					$max_chars = (int)(3*$colspan/$intervals_per_day-2);

					$min_chars = 3; // minimum for max_chars to display -> this should be configurable
					if ($max_chars >= $min_chars)
					{
						$len_title = strlen($event['title']);

						if ($len_title <= $max_chars)
						{
							$title = $event['title'];
							$max_chars -= $len_title + 3; // 3 chars for separator: " - "
							$len_descr = strlen($event['description']);

							if ($len_descr > 0 && $len_descr <= $max_chars)
							{
								$event['print_description'] = 'yes';
							}
						}
						else
						{
							$has_amp = strpos($event['title'],'&amp;');
							
//NDEE: event title gets cut here							
							$title = substr($event['title'], 0 , $max_chars-1+($has_amp!==False&&$has_amp<$max_chars?4:0)).'...';
						}
						$event['print_title'] = 'yes';
					}
				}
			}

			if ($bgcolor=$cat['color'])
			{
				$opt .= ' bgcolor="'.$bgcolor.'"';
			}
			if (!$is_private)
			{
				$opt .= ' title="'.lang('Title').": ".$event['title'];
				if ($event['description'])
				{
					$opt .= "\n".lang('Description').": ".$event['description'];
				}
			}
			else
			{
				$opt .= ' title="'.lang('You do not have permission to read this record!');
			}

			$start = $GLOBALS['phpgw']->common->show_date($this->bo->maketime($event['start']) - $GLOBALS['phpgw']->datetime->tz_offset);
			$end = $GLOBALS['phpgw']->common->show_date($this->bo->maketime($event['end']) - $GLOBALS['phpgw']->datetime->tz_offset);
			$opt .= "\n".lang('Start Date/Time').": ".$start."\n".lang('End Date/Time').": ".$end;

			if ($event['location'] && !$is_private)
			{
				$opt .= " \n".lang('Location').": ".$event['location'];
			}

			if (!$is_private)
			{
				$opt .= '" onClick="location=\''.$view.'\'"';
				$cel = '<a href="'.$view.'">';
			}
			else
			{
				$opt .= '"';
				$cel = '';
			}
			$opt .= ' class="planner-cell"';

			if ($event['priority'] == 3)
			{
				$cel .= $this->html->image('calendar','mini-calendar-bar.gif','','border="0"');
			}
			if ($event['recur_type'])
			{
				$cel .= $this->html->image('calendar','recur.gif','','border="0"');
			}
			$cel .= $this->html->image('calendar',count($event['participants'])>1?'multi_3.gif':'single.gif',$this->planner_participants($event['participants']),'border="0"');
			$cel .= '</a>';

			if (isset($event['print_title']) && $event['print_title'] == 'yes')
			{

//NDEE: style! where?
				$cel .= '<font size="-2"> '.$title.' </font>';
			}
			if (isset($event['print_description']) && $event['print_description'] == 'yes')
			{

//NDEE: style! where ?
				$cel .= '<font size="-2"> - '.$event['description'].' </font>';
			}

			$akt_cell = $end_cell + 1;

			return $rows;
		}

		function planner_process_event($event)
		{
			$intervals_per_day = $this->bo->prefs['calendar']['planner_intervals_per_day'];
			$interval = $this->planner_intervals[$intervals_per_day];
			$last_cell = $intervals_per_day * $this->planner_days - 1;

			$rows = &$this->planner_rows;

			// caluculate start and end of event
			//
			$event_start = (int)(date('Ymd',mktime(
				0,0,0,
				$event['start']['month'],
				$event['start']['mday'],
				$event['start']['year']
			)));
			$event_end   = (int)(date('Ymd',mktime(
				0,0,0,
				$event['end']['month'],
				$event['end']['mday'],
				$event['end']['year']
			)));

			// calculate first cell of event within observed interval
			//
			if ($event_start >= $this->planner_firstday)
			{
				$days_between = $GLOBALS['phpgw']->datetime->days_between($this->bo->month,1,$this->bo->year,$event['start']['month'],$event['start']['mday'],$event['start']['year']);

				$start_cell = $intervals_per_day * $days_between + $interval[$event['start']['hour']];
			}
			else
			{
				$start_cell = 0;
			}

			// calculate last cell of event within observed interval
			//
			if ($event_end <= $this->planner_lastday)
			{
				$days_between = $GLOBALS['phpgw']->datetime->days_between($this->bo->month,1,$this->bo->year,$event['end']['month'],$event['end']['mday'],$event['end']['year']);
				$end_cell = $intervals_per_day * $days_between + $interval[$event['end']['hour']];
				if ($end_cell == $start_cell && $end_cell < $last_cell)
				{
					$end_cell++;	// min. width 1 interval
				}
			}
			else
			{
				$end_cell = $last_cell;
			}
			// get the categories associated with event
			//
			if ($c = $event['category'])
			{
				list($cat)   = $this->planner_category($event['category']);
				if ($cat['parent'])
				{
					list($pcat) = $this->planner_category($c = $cat['parent']);
				}
				else
				{
					$pcat = $cat;
				}
			}
			else
			{
				$cat = $pcat = array( 'name' => lang('none'));
			}

			// add the event to it`s associated row(s)
			//
			if ($this->bo->sortby == 'category')
			{
				// event needs to show up in it`s category`s row
				//
				$this->planner_update_row($c,$pcat['name'],$event,$cat,$start_cell,$end_cell);
			}
			elseif ($this->bo->sortby == 'user')
			{
				// event needs to show up in rows of all participants that are also owners
				//
				reset($this->planner_group_members);
				while(list($user_name,$id) = each($this->planner_group_members))
				{
					$status = $event['participants'][$id];

					if (isset($status) && $status != 'R')
					{
						$this->planner_update_row($user_name,$user_name,$event,$cat,$start_cell,$end_cell);
					}
				}
			}
		}

		function planner_pad_rows()
		{
			$rows = &$this->planner_rows;

			if ($this->bo->sortby == 'user')
			{
				// add empty rows for users that do not participante in any event
				//
				reset($this->planner_group_members);
				while(list($user_name,$id) = each($this->planner_group_members))
				{
					$k  = $user_name.'_1';
					$ka = '.nr_'.$k;

					if (!isset($rows[$k]))
					{
						$rows[$k]['_name'] = $user_name;
						$rows[$k]['._name'] .= ' nowrap';
						$rows[$ka] = 0;
					}
				}
			}

			// fill the remaining cols
			//
			$last_cell = $this->bo->prefs['calendar']['planner_intervals_per_day'] * $this->planner_days - 1;

			ksort($rows);
			while (list($k,$r) = each($rows))
			{
				if (is_array($r))
				{
					$rows['.'.$k] = 'bgcolor="'.$GLOBALS['phpgw']->nextmatchs->alternate_row_color().'"';
					$row = &$rows[$k];
					$akt_cell = &$rows['.nr_'.$k];
					if ($akt_cell < $last_cell)
					{
						$row['3'] = '&nbsp';
						$row['.3'] = 'colspan="'.(1+$last_cell-$akt_cell).'"';
					}
				}
			}
		}

		function planner_print_rows()
		{
			$bgcolor = 'bgcolor="'.$this->theme['th_bg'].'"';
			$intervals_per_day = $this->bo->prefs['calendar']['planner_intervals_per_day'];

			if ($this->debug)
			{
				_debug_array($this->planner_rows);
				reset($this->planner_rows);
			}
			return $this->html->table(
				array(
					'_hdr0' => $this->planner_header[0],
					'._hdr0' => $bgcolor,
					'_hdr1' => $this->planner_header[1],
					'._hdr1' => $bgcolor,
					'_hdr2' => $this->planner_header[2],
					'._hdr2' => $bgcolor
				)+$this->planner_rows,
				'width="100%" cols="'.(1+$this->planner_days_in_end_month*$intervals_per_day).'"');
		}

		function planner_process_interval()
		{
			// generate duplicate free list of events within observed interval
			//
			$this->bo->store_to_cache(
				Array(
					'syear'	=> $this->bo->year,
					'smonth'	=> $this->bo->month,
					'sday'	=> 1,
					'eyear'	=> $this->planner_end_year,
					'emonth'	=> $this->planner_end_month,
					'eday'	=> $this->planner_days_in_end_month
				)
			);
			$this->bo->remove_doubles_in_cache($this->planner_firstday,$this->planner_lastday);

			// process all events within observed interval
			//
			for($v=$this->planner_firstday;$v<=$this->planner_lastday;$v++)
			{
				$daily = $this->bo->cached_events[$v];

				print_debug('For Date',$v);
				print_debug('Count of items',count($daily));

				// process all events on day $v
				//
				if (is_array($daily)) foreach($daily as $event)
				{
					if ($event['recur_type'])	// calculate start- + end-datetime for recuring events
					{
						$this->bo->set_recur_date($event,$v);
					}
					if (!$this->bo->rejected_no_show($event))
					{
						$this->planner_process_event($event);
					}
				}
			}
			$this->planner_pad_rows();
		}

		function matrixselect()
		{
			$datetime = mktime(0,0,0,$this->bo->month,$this->bo->day,$this->bo->year) - $GLOBALS['phpgw']->datetime->tz_offset;

			$sb = CreateObject('phpgwapi.sbox');

			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			if ($this->always_app_header) $GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['calendar']['title'].' - '.lang('Matrixview');
			$GLOBALS['phpgw']->common->phpgw_header();

			$p = &$GLOBALS['phpgw']->template;
			$p->set_file(
				Array(
					'mq'		=> 'matrix_query.tpl',
					'form_button'	=> 'form_button_script.tpl'
				)
			);
			$p->set_block('mq','matrix_query','matrix_query');
			$p->set_block('mq','list','list');

			$p->set_var(array(
				'title'			=> lang('Daily Matrix View'),
				'th_bg'			=> $this->theme['th_bg'],
				'action_url'	=> $this->page('viewmatrix')
			));

// Date
			$var[] = Array(
				'field'	=>	lang('Date'),
				'data'	=>	$GLOBALS['phpgw']->common->dateformatorder(
					$sb->getYears('year',(int)$GLOBALS['phpgw']->common->show_date($datetime,'Y'),(int)$GLOBALS['phpgw']->common->show_date($datetime,'Y')),
					$sb->getMonthText('month',(int)$GLOBALS['phpgw']->common->show_date($datetime,'n')),
					$sb->getDays('day',(int)$GLOBALS['phpgw']->common->show_date($datetime,'d'))
				)
			);

// View type
			$var[] = Array(
				'field'	=>	lang('View'),
				'data'	=>	'<select name="matrixtype">'."\n"
					. '<option value="free/busy" selected>'.lang('free/busy').'</option>'."\n"
					. '<option value="weekly">'.lang('Weekly').'</option>'."\n"
					. '</select>'."\n"
			);

// Participants
			$accounts = $GLOBALS['phpgw']->acl->get_ids_for_location('run',1,'calendar');
			$users = Array();
			for($i=0;$i<count($accounts);$i++)
			{
				$user = $accounts[$i];
				if(!isset($users[$user]))
				{
					$users[$user] = $GLOBALS['phpgw']->common->grab_owner_name($user);
					if($GLOBALS['phpgw']->accounts->get_type($user) == 'g')
					{
						$group_members = $GLOBALS['phpgw']->acl->get_ids_for_location($user,1,'phpgw_group');
						if($group_members != False)
						{
							for($j=0;$j<count($group_members);$j++)
							{
								if(!isset($users[$group_members[$j]]))
								{
									$users[$group_members[$j]] = $GLOBALS['phpgw']->common->grab_owner_name($group_members[$j]);
								}
							}
						}
					}
				}
			}

			$num_users = count($users);

			if ($num_users > 50)
			{
				$size = 15;
			}
			elseif ($num_users > 5)
			{
				$size = 5;
			}
			else
			{
				$size = $num_users;
			}
			$str = '';
			@asort($users);
			@reset($users);
			while ($user = each($users))
			{
				if(($GLOBALS['phpgw']->accounts->exists($user[0]) && $this->bo->check_perms(PHPGW_ACL_READ,0,$user[0])) || $GLOBALS['phpgw']->accounts->get_type($user[0]) == 'g')
				{
					$str .= '    <option value="'.$user[0].'">('.$GLOBALS['phpgw']->accounts->get_type($user[0]).') '.$user[1].'</option>'."\n";
				}
			}
			$var[] = Array(
				'field'	=>	lang('Participants'),
				'data'	=>	"\n".'   <select name="participants[]" multiple size="'.$size.'">'."\n".$str.'   </select>'."\n"
			);

			for($i=0;$i<count($var);$i++)
			{
				$this->output_template_array($p,'rows','list',$var[$i]);
			}

			$vars = Array(
				'submit_button'		=> lang('View'),
				'action_url_button'	=> $this->bo->return_to ? $GLOBALS['phpgw']->link('/index.php','menuaction='.$this->bo->return_to) : $this->page(''),
				'action_text_button'	=> lang('Cancel'),
				'action_confirm_button'	=> '',
				'action_extra_field'	=> ''
			);

			$p->set_var($vars);
			$p->parse('cancel_button','form_button');
			$p->pparse('out','matrix_query');
		}

		function viewmatrix()
		{
			if ($_POST['cancel'])
			{
				$this->index();
			}
			$participants = $_POST['participants'];
			$parts = Array();
			$acct = CreateObject('phpgwapi.accounts',$this->bo->owner);

			if (is_array($participants))
			{
				foreach($participants as $participant)
				{
					switch ($GLOBALS['phpgw']->accounts->get_type($participant))
					{
						case 'g':
							if ($members = $acct->member((int)$participant))
							{
								foreach($members as $member)
								{
									if($this->bo->check_perms(PHPGW_ACL_READ,0,$member['account_id']))
									{
										$parts[$member['account_id']] = True;
									}
								}
							}
							break;
						case 'u':
							if($this->bo->check_perms(PHPGW_ACL_READ,0,$participant))
							{
								$parts[$participant] = 1;
							}
							break;
					}
				}
				unset($acct);
			}
			$participants = array_keys($parts);	// get id's as values and a numeric index

			// Defined - into session - who participates
			$GLOBALS['phpgw']->session->appsession("participants", NULL, implode(";", $participants));

			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			if ($this->always_app_header) $GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['calendar']['title'].' - '.lang('Matrixview');
			$GLOBALS['phpgw']->common->phpgw_header();

			switch($_POST['matrixtype'])
			{
				case 'free/busy':
					$freetime = $GLOBALS['phpgw']->datetime->gmtdate(mktime(0,0,0,$this->bo->month,$this->bo->day,$this->bo->year));
					echo '<br>'.$this->timematrix(
						Array(
							'date'		=> $freetime,
							'starttime'	=> $this->bo->splittime('000000',False),
							'endtime'	=> 0,
							'participants'	=> $parts
						)
					);
					break;
				case 'weekly':
					echo '<br>'.$this->display_weekly(
						Array(
							'date'		=> sprintf("%04d%02d%02d",$this->bo->year,$this->bo->month,$this->bo->day),
							'showyear'	=> true,
							'owners'	=> $participants
						)
					);
					break;
			}
			echo "\n<br>\n".'<form action="'.$this->page('viewmatrix').'" method="post" name="matrixform">'."\n";
			echo ' <table cellpadding="5"><tr><td>'."\n";
			echo '  <input type="hidden" name="year" value="'.$this->bo->year.'">'."\n";
			echo '  <input type="hidden" name="month" value="'.$this->bo->month.'">'."\n";
			echo '  <input type="hidden" name="day" value="'.$this->bo->day.'">'."\n";
			echo '  <input type="hidden" name="matrixtype" value="'.$_POST['matrixtype'].'">'."\n";
			foreach($participants as $part)
			{
				echo '  <input type="hidden" name="participants[]" value="'.$part.'">'."\n";
			}
			echo '  <input type="submit" name="refresh" value="'.lang('Refresh').'">'."\n";
			echo ' </td><td>'."\n";
			echo '  <input type="submit" name="cancel" value="'.lang('Cancel').'">'."\n";
			echo ' </td></tr></table>'."\n";
			echo '</form>'."\n";
		}

		function search()
		{
			if (empty($_POST['keywords']))
			{
				// If we reach this, it is because they didn't search for anything
				// or they used one of the selectboxes (year, month, week) in the search-result
				// attempt to send them back to where they came from.

				$vars['menuaction'] = isset($_POST['from']) && $_POST['from'] != 'calendar.uicalendar.search' ?
					$_POST['from'] : 'calendar.uicalendar.index';

				foreach(array('date','year','month','day') as $field)
				{
					if (isset($_POST[$field]))
					{
						$vars[$field] = $_POST[$field];
					}
				}
				$GLOBALS['phpgw']->redirect_link('/index.php',$vars);
			}

			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['calendar']['title'].' - '.lang('Search Results');
			$GLOBALS['phpgw']->common->phpgw_header();

			$error = '';

			$matches = 0;

			// There is currently a problem searching in with repeated events.
			// It spits back out the date it was entered.  I would like to to say that
			// it is a repeated event.

			// This has been solved by the little icon indicator for recurring events.

			$event_ids = $this->bo->search_keywords($_POST['keywords']);
			foreach($event_ids as $key => $id)
			{
				$event = $this->bo->read_entry($id);

				if(!$this->bo->check_perms(PHPGW_ACL_READ,$event))
				{
					continue;
				}

				$datetime = $this->bo->maketime($event['start']) - $GLOBALS['phpgw']->datetime->tz_offset;

				$info[strval($event['id'])] = array(
					'tr_color'	=> $GLOBALS['phpgw']->nextmatchs->alternate_row_color(),
					'date'		=> $GLOBALS['phpgw']->common->show_date($datetime),
					'link'		=> $this->link_to_entry($event,$event['start']['month'],$event['start']['mday'],$event['start']['year'])
				);

			}
			$matches = count($event_ids);

			if ($matches == 1)
			{
				$quantity = lang('1 match found').'.';
			}
			elseif ($matches > 0)
			{
				$quantity = lang('%1 matches found',$matches).'.';
			}
			else
			{
				echo '<b>'.lang('Error').':</b>'.lang('no matches found');
				return;
			}

			$p = $GLOBALS['phpgw']->template;
			$p->set_file(
				Array(
					'search_form'	=> 'search.tpl'
				)
			);
			$p->set_block('search_form','search','search');
			$p->set_block('search_form','search_list_header','search_list_header');
			$p->set_block('search_form','search_list','search_list');
			$p->set_block('search_form','search_list_footer','search_list_footer');

			$var = Array(
				'th_bg'		=> $this->theme['th_bg'],
				'search_text'	=> lang('Search Results'),
				'quantity'	=> $quantity
			);
			$p->set_var($var);

			if($matches > 0)
			{
				$p->parse('rows','search_list_header',True);
			}
			foreach($info as $id => $data)
			{
				$p->set_var($data);
				$p->parse('rows','search_list',True);
			}

			if($matches > 0)
			{
				$p->parse('rows','search_list_footer',True);
			}

			$p->pparse('out','search');
		}

		/* Private functions */
		function _debug_sqsof()
		{
			$data = array(
				'filter'     => $this->bo->filter,
				'cat_id'     => $this->bo->cat_id,
				'owner'      => $this->bo->owner,
				'year'       => $this->bo->year,
				'month'      => $this->bo->month,
				'day'        => $this->bo->day,
				'sortby'     => $this->bo->sortby,
				'num_months' => $this->bo->num_months
			);
			Return _debug_array($data,False);
		}

		function output_template_array(&$p,$row,$list,$var)
		{
			if (!isset($var['hidden_vars']))
			{
				$var['hidden_vars'] = '';
			}
			if (!isset($var['tr_color']))
			{
				$var['tr_color'] = $GLOBALS['phpgw']->nextmatchs->alternate_row_color();
			}
			$p->set_var($var);
			$p->parse($row,$list,True);
		}

		function page($_page='',$params='')
		{
			if($_page == '')
			{
				$page_ = explode('.',$this->bo->prefs['calendar']['defaultcalendar']);
				$_page = $page_[0];

				if ($_page=='planner_cat' || $_page=='planner_user')
				{
					$_page = 'planner';
				}
				elseif ($_page=='index' || ($_page != 'day' && $_page != 'week' && $_page != 'month' && $_page != 'year' && $_page != 'planner'))
				{
					$_page = 'month';
					$GLOBALS['phpgw']->preferences->add('calendar','defaultcalendar','month');
					$GLOBALS['phpgw']->preferences->save_repository();
				}
			}
			if($GLOBALS['phpgw_info']['flags']['currentapp'] == 'home' ||
				strstr($GLOBALS['phpgw_info']['flags']['currentapp'],'mail'))	// email, felamimail, ...
			{
				$page_app = 'calendar';
			}
			else
			{
				$page_app = $GLOBALS['phpgw_info']['flags']['currentapp'];
			}
			if (is_array($params))
			{
				$params['menuaction'] = $page_app.'.ui'.$page_app.'.'.$_page;
			}
			else
			{
				$params = 'menuaction='.$page_app.'.ui'.$page_app.'.'.$_page.$params;
			}
			return $GLOBALS['phpgw']->link('/index.php',$params);
		}

		function header()
		{
			$cols = 8;
			if($this->bo->check_perms(PHPGW_ACL_PRIVATE) == True)
			{
				$cols++;
			}

			$tpl = $GLOBALS['phpgw']->template;
			$tpl->set_unknowns('remove');

			if (!file_exists($file = $this->template_dir.'/header.inc.php'))
			{
				$file = PHPGW_SERVER_ROOT . '/calendar/templates/default/header.inc.php';
			}
			
			include($file);
			$refer = explode('.',$GLOBALS['HTTP_GET_VARS']['menuaction']);
			if($refer[2] != 'view') {
			$header = $tpl->fp('out','head');
			unset($tpl);
			echo $header;
		}
			unset($tpl);
		}

		function footer()
		{
			$menuaction = $_GET['menuaction'];
			list(,,$method) = explode('.',$menuaction);

			if (@$this->bo->printer_friendly)
			{
				return;
			}

			$p = $GLOBALS['phpgw']->template;

			$p->set_file(
				Array(
					'footer'	=> 'footer.tpl',
					'form_button'	=> 'form_button_script.tpl'
				)
			);
			$p->set_block('footer','footer_table','footer_table');
			$p->set_block('footer','footer_row','footer_row');
			$p->set_block('footer','blank_row','blank_row');
			$p->set_block('footer','num_dias','num_dias');

			$m = $this->bo->month;
			$y = $this->bo->year;

			$hoje = date('Ymd',$GLOBALS['phpgw']->datetime->gmtnow);
			$mes_hoje = (int)substr($hoje,4,2);
			$mes_footer = (int)substr($_POST['date'],4,2);
			if($_GET['sday'])
			{
				if($_GET['month'] == $mes_hoje)
				{
					$day = $this->bo->day;
				} 
				else
				{
					$day = $_GET['sday'];
				}
			}
			else
			{
				$day = $this->bo->day;
				$dia_ini = $day;
			}
			if($mes_footer == $mes_hoje)
			{
				$dia_ini = (int)substr($hoje,6,2);
				$day = $dia_ini;
			}
			/********************************************************************************************/
			/* Bloco adicionado para receber o dia a partir do qual a versao para impressao sera gerada */
			$str_ini = '';
 			for ($i = 1; $i <= ($GLOBALS['phpgw']->datetime->days_in_month($m, $y)); $i++)
			{
				$str_ini .= '<option value="'.$i.'"'.($i == $day?' selected':'').'>'.$i.'</option>'."\n";
			}
			$str_qtd = '<option value="'.''.'"'.($i == $_POST['qtd_dias']?' selected':'').'>'.''.'</option>'."\n";
			for ($i = 1; $i <= 45; $i++)
			{
				$str_qtd .= '<option value="'.$i.'"'.($i == $_POST['qtd_dias']?' selected':'').'>'.$i.'</option>'."\n";
			}
			$display = '';
			$display_view = '';
			if ( ($menuaction == 'calendar.uicalendar.week') || ($menuaction == 'calendar.uicalendar.day') )
			{
				$display = 'none';
			}
			else if( ($menuaction == 'calendar.uicalendar.view') )
			{
				$display = 'none';
				$display_view = 'none';
			}
			$var = Array(
				'acao'			=> $this->page($method,''),
				'formname'		=> 'SelectDay',
				'formonchange'		=> 'document.SelectDay.submit()',
				'day_ini_label'		=> lang('print starting in'),
				'num_dias_label'	=> lang('quantity of days'),
				'tip'			=> lang('empty prints from the day chosen below to the end of the current month'),
				'day_ini_name'		=> 'day',
				'num_dias_name'		=> 'qtd_dias',
				'row_ini'		=> $str_ini,
				'row_qtd'		=> $str_qtd,
				'display'		=> $display,
				'display_view'	=> $display_view,
				'year'			=> $y,
				'month'			=> $m,
				'lang_print_in'	=> lang("print in"),
				'lines'			=>	lang('lines'),
				'columns'		=>	lang('columns'),
				'go'			=> lang('Go!')
				);
			$this->output_template_array($p,'options','num_dias',$var);
			
			/********************************************************************************************/

			$thisdate = date('Ymd',mktime(0,0,0,$m,1,$y));
			$y--;

			$str = '';
			for ($i = 0; $i < 20; $i++)
			{
				$m++;
				if ($m > 12)
				{
					$m = 1;
					$y++;
				}
				$d = mktime(0,0,0,$m,1,$y);
				$d_ymd = date('Ymd',$d);
				$str .= '<option value="'.$d_ymd.'"'.($d_ymd == $thisdate?' selected':'').'>'.lang(date('F', $d)).strftime(' %Y', $d).'</option>'."\n";
			}

			$var = Array(
				'action_url'	=> $this->page($method,''),
				'form_name'	=> 'SelectMonth',
				'label'		=> lang('Month'),
				'form_label'	=> 'date',
				'form_onchange'	=> 'document.SelectMonth.submit()',
				'row'		=> $str,
				'dia_ini'	=> $dia_ini,
				'go'		=> lang('Go!')
			);
			$this->output_template_array($p,'table_row','footer_row',$var);

			if($menuaction == 'calendar.uicalendar.week')
			{
				unset($thisdate);
				$thisdate = mktime(0,0,0,$this->bo->month,$this->bo->day,$this->bo->year) - $GLOBALS['phpgw']->datetime->tz_offset;
				$sun = $GLOBALS['phpgw']->datetime->get_weekday_start($this->bo->year,$this->bo->month,$this->bo->day) - $GLOBALS['phpgw']->datetime->tz_offset;

				$str = '';
				for ($i = -7; $i <= 7; $i++)
				{
					$begin = $sun + (7*24*60*60 * $i) + 12*60*60;	// we use midday, that changes in daylight-saveing does not effect us
					$end = $begin + 6*24*60*60;
//					echo "<br>$i: ".date('d.m.Y H:i',$begin).' - '.date('d.m.Y H:i',$end);
                    $str .= '<option value="' . $GLOBALS['phpgw']->common->show_date($begin,'Ymd') . '"'.($begin <= $thisdate+12*60*60 && $end >= $thisdate+12*60*60 ? ' selected':'').'>'                    
						. $GLOBALS['phpgw']->common->show_date($begin,$GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']) . ' - '
                        . $GLOBALS['phpgw']->common->show_date($end,$GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'])
                        . '</option>' . "\n";                        
				}

				$var = Array(
					'action_url'	=> $this->page($method,''),
					'form_name'	=> 'SelectWeek',
					'label'		=> lang('Week'),
					'form_label'	=> 'date',
					'form_onchange'	=> 'document.SelectWeek.submit()',
					'row'		=> $str,
					'go'		=> lang('Go!')
				);

				$this->output_template_array($p,'table_row','footer_row',$var);
			}

			$str = '';
			for ($i = ($this->bo->year - 3); $i < ($this->bo->year + 3); $i++)
			{
				$str .= '<option value="'.$i.'"'.($i == $this->bo->year?' selected':'').'>'.$i.'</option>'."\n";
			}

			$var = Array(
				'action_url'	=> $this->page($method,''),
				'form_name'	=> 'SelectYear',
				'label'		=> lang('Year'),
				'form_label'	=> 'year',
				'form_onchange'	=> 'document.SelectYear.submit()',
				'row'		=> $str,
				'go'		=> lang('Go!')
			);
			$this->output_template_array($p,'table_row','footer_row',$var);

			if($menuaction == 'calendar.uicalendar.planner')
			{
				$str = '';
				$date_str = '';

				if(isset($_GET['date']) && $_GET['date'])
				{
					$date_str .= '    <input type="hidden" name="date" value="'.$_GET['date'].'">'."\n";
				}
				$date_str .= '    <input type="hidden" name="month" value="'.$this->bo->month.'">'."\n";
				$date_str .= '    <input type="hidden" name="day" value="'.$this->bo->day.'">'."\n";
				$date_str .= '    <input type="hidden" name="year" value="'.$this->bo->year.'">'."\n";

				for($i=1; $i<=6; $i++)
				{
					$str .= '<option value="'.$i.'"'.($i == $this->bo->num_months?' selected':'').'>'.$i.'</option>'."\n";
				}

				$var = Array(
					'action_url'	=> $this->page($method,''),
					'form_name'	=> 'SelectNumberOfMonths',
					'label'		=> lang('Number of Months'),
					'hidden_vars' => $date_str,
					'form_label'	=> 'num_months',
					'form_onchange'	=> 'document.SelectNumberOfMonths.submit()',
					'action_extra_field'	=> $date_str,
					'row'		=> $str,
					'go'		=> lang('Go!')
				);
				$this->output_template_array($p,'table_row','footer_row',$var);
			}

			$var = Array(
				'submit_button'		=> lang('Submit'),
				'action_url_button'	=> $GLOBALS['phpgw']->link('/index.php','menuaction=calendar.uiicalendar.import'),
				'action_text_button'	=> lang('Import'),
				'action_confirm_button'	=> '',
				'action_extra_field'	=> ''
			);
			$this->output_template_array($p,'b_row','form_button',$var);
			
			$var = Array(
				'submit_button'		=> lang('Submit'),
				'action_url'	=> $this->page($method,''),
				'action_url_button'	=> $GLOBALS['phpgw']->link('/index.php','menuaction=calendar.uicalendar.export_all'),
				'action_text_button'	=> lang('Export all'),
				'action_confirm_button'	=> '',
				'action_extra_field'	=> ''
			);
			$this->output_template_array($p,'b_row2','form_button',$var);
					
			$p->parse('table_row','blank_row',True);

			$p->pparse('out','footer_table');
			unset($p);
		}

		function css()
		{
			require_once PHPGW_SERVER_ROOT . '/prototype/library/mobileDetect/Mobile_Detect.php';
			
			$detectBrowser = new Mobile_Detect();

			if( $detectBrowser->version('Gecko') )
			{
				$time_width = ((int)($this->bo->prefs['common']['time_format']) == 12?12:8);
			}
			else
			{
				$time_width = ((int)($this->bo->prefs['common']['time_format']) == 12?10:7);
			}
		}

		function no_edit()
		{
			if(!isset($GLOBALS['phpgw_info']['flags']['noheader']))
			{
				unset($GLOBALS['phpgw_info']['flags']['noheader']);
				unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
				$GLOBALS['phpgw_info']['flags']['noappheader'] = True;
				$GLOBALS['phpgw_info']['flags']['noappfooter'] = True;
				$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['calendar']['title'].' - '.lang('Permission denied');
				$GLOBALS['phpgw']->common->phpgw_header();
			}
			echo '<center>You do not have permission to edit this appointment!</center>';
			return;
		}

		function link_to_entry($event,$month,$day,$year)
		{
			$str = '';
			$is_private = !$event['public'] && !$this->bo->check_perms(PHPGW_ACL_READ,$event);
			$viewable = !$this->bo->printer_friendly && $this->bo->check_perms(PHPGW_ACL_READ,$event);

			$starttime = $this->bo->maketime($event['start']) - $GLOBALS['phpgw']->datetime->tz_offset;
			$endtime = $this->bo->maketime($event['end']) - $GLOBALS['phpgw']->datetime->tz_offset;
			$rawdate = mktime(0,0,0,$month,$day,$year);
			$rawdate_offset = $rawdate - $GLOBALS['phpgw']->datetime->tz_offset;
			$nextday = mktime(0,0,0,$month,$day + 1,$year) - $GLOBALS['phpgw']->datetime->tz_offset;
			if ((int)$GLOBALS['phpgw']->common->show_date($starttime,'Hi') && $starttime == $endtime)
			{
				$time = $GLOBALS['phpgw']->common->show_date($starttime,$this->bo->users_timeformat);
			}
			elseif ($starttime <= $rawdate_offset && $endtime >= $nextday - 60)
			{
				$time = '[ '.lang('All Day').' ]';
			}
			elseif ((int)$GLOBALS['phpgw']->common->show_date($starttime,'Hi') || $starttime != $endtime)
			{
				if($starttime < $rawdate_offset && $event['recur_type'] == MCAL_RECUR_NONE)
				{
					$start_time = $GLOBALS['phpgw']->common->show_date($rawdate_offset,$this->bo->users_timeformat);
				}
				else
				{
					$start_time = $GLOBALS['phpgw']->common->show_date($starttime,$this->bo->users_timeformat);
				}

				if($endtime >= ($rawdate_offset + 86400))
				{
					$end_time = $GLOBALS['phpgw']->common->show_date(mktime(23,59,59,$month,$day,$year) - $GLOBALS['phpgw']->datetime->tz_offset,$this->bo->users_timeformat);
				}
				else
				{
					$end_time = $GLOBALS['phpgw']->common->show_date($endtime,$this->bo->users_timeformat);
				}
				$time = $start_time.'-'.$end_time;
			}
			else
			{
				$time = '';
			}
			
			$texttitle = $texttime = $textdesc = $textlocation = $textstatus = '';

			if(!$is_private)
			{
				$textstatus=$this->bo->display_status($event['users_status']); 
			}
			
			$texttime=$time;
			$texttitle=$this->bo->get_short_field($event,$is_private,'title');

			$account_name = $_POST['user'] ?
							$_POST['user'] :
							($_GET['account_name'] ?
							 $_GET['account_name'] : '');

			$accounts = CreateObject('phpgwapi.accounts');
			$accountId = $accounts->name2id($account_name);

			$prefs = CreateObject('phpgwapi.preferences', $accountId);
			$account_prefs = $prefs->read();

			$titleView = $account_prefs['calendar']['title_view'];
			if($titleView == "1"){
				$textdesc = '';
			}else{
			$textdesc=(!$is_private && $event['description'] ? $this->bo->get_short_field($event,$is_private,'description'):'');
			$textdesc=nl2br($textdesc);
			}

			// added $textlocation but this must be activated in the actual pict_link.tpl file of the used template set
			$textlocation=$this->bo->get_short_field($event,$is_private,'location');

			if ($viewable)
			{
				$date = sprintf('%04d%02d%02d',$year,$month,$day);
				$this->link_tpl->set_var('link_link',$this->page('view','&cal_id='.$event['id'].'&date='.$date));
				$this->link_tpl->set_var('lang_view',lang('View this entry'));
				$this->link_tpl->set_var('desc', $textdesc);
				$this->link_tpl->set_var('location', $textlocation);
				$this->link_tpl->parse('picture','link_open',True);
			}
			if (!$is_private)
			{
				if($event['priority'] == 3)
				{
					$picture[] = Array(
						'pict'	=> $GLOBALS['phpgw']->common->image('calendar','high'),
						'width'	=> 16,
						'height'=> 16,
						'title' => lang('high priority')
					);
				}
				if($event['recur_type'] == MCAL_RECUR_NONE)
				{
					$picture[] = Array(
						'pict'	=> $GLOBALS['phpgw']->common->image('calendar','circle'),
						'width'	=> 9,
						'height'=> 9,
						'title' => lang('single event')
					);
				}
				else
				{
					$picture[] = Array(
						'pict'	=> $GLOBALS['phpgw']->common->image('calendar','recur'),
						'width'	=> 12,
						'height'=> 12,
						'title' => lang('recurring event')
					);
				}
			}
			$participants = $this->planner_participants($event['participants']);
			if(count($event['participants']) > 1)
			{
				$picture[] = Array(
					'pict'	=> $GLOBALS['phpgw']->common->image('calendar','multi_3'),
					'width'	=> 14,
					'height'=> 14,
					'title' => $participants
				);
			}
			else
			{
				$picture[] = Array(
					'pict'	=>  $GLOBALS['phpgw']->common->image('calendar','single'),
					'width'	=> 14,
					'height'=> 14,
					'title' => $participants
				);
			}
			if($event['public'] == 0)
			{
				$picture[] = Array(
					'pict'	=> $GLOBALS['phpgw']->common->image('calendar','private'),
					'width'	=> 13,
					'height'=> 13,
					'title' => lang('private')
				);
			}
			if(@isset($event['alarm']) && count($event['alarm']) >= 1 && !$is_private)
			{
				// if the alarm is to go off the day before the event
				// the icon does not show up because of 'alarm_today'
				// - TOM
				if($this->bo->alarm_today($event,$rawdate_offset,$starttime))
				{
					$picture[] = Array(
						'pict'  => $GLOBALS['phpgw']->common->image('calendar','alarm'),
						'width' => 13,
						'height'=> 13,
						'title' => lang('alarm')
					);
				}
			}

			$description = $this->bo->get_short_field($event,$is_private,'description');
			for($i=0;$i<count($picture);$i++)
			{
				$var = Array(
					'pic_image' => $picture[$i]['pict'],
					'width'     => $picture[$i]['width'],
					'height'    => $picture[$i]['height'],
					'title'     => $picture[$i]['title']
				);
				$this->output_template_array($this->link_tpl,'picture','pict',$var);
			}
			if ($texttitle)
			{
				$var = Array(
			//		'text' => $text,
					'time'=> $texttime,
					'title'=> $texttitle,
					'users_status'=>$textstatus,
					'desc'=> $textdesc,
					'location'=> "<br><b>Local:</b> ".$textlocation
				);
				$this->output_template_array($this->link_tpl,'picture','link_text',$var);
			}

			if ($viewable)
			{
				$this->link_tpl->parse('picture','link_close',True);
			}
			$str = $this->link_tpl->fp('out','link_pict');
			$this->link_tpl->set_var('picture','');
			$this->link_tpl->set_var('out','');
//			unset($p);
			return $str;
		}

/*****************************************************************************************/
/*Funcao foi duplicada e alterada para tratar os eventos de agendamento (exibicao do mes) para impressao;
utiliza o template event_link.tpl*/

		function link_to_month_entry($event,$month,$day,$year)
		{
			$str = '';
			$is_private = !$event['public'] && !$this->bo->check_perms(PHPGW_ACL_READ,$event);
			$viewable = !$this->bo->printer_friendly && $this->bo->check_perms(PHPGW_ACL_READ,$event);

			$starttime = $this->bo->maketime($event['start']) - $GLOBALS['phpgw']->datetime->tz_offset;
			$endtime = $this->bo->maketime($event['end']) - $GLOBALS['phpgw']->datetime->tz_offset;
			$rawdate = mktime(0,0,0,$month,$day,$year);
			$rawdate_offset = $rawdate - $GLOBALS['phpgw']->datetime->tz_offset;
			$nextday = mktime(0,0,0,$month,$day + 1,$year) - $GLOBALS['phpgw']->datetime->tz_offset;
			if ((int)$GLOBALS['phpgw']->common->show_date($starttime,'Hi') && $starttime == $endtime)
			{
				$time = $GLOBALS['phpgw']->common->show_date($starttime,$this->bo->users_timeformat);
			}
			elseif ($starttime <= $rawdate_offset && $endtime >= $nextday - 60)
			{
				$time = '[ '.lang('All Day').' ]';
			}
			elseif ((int)$GLOBALS['phpgw']->common->show_date($starttime,'Hi') || $starttime != $endtime)
			{
				if($starttime < $rawdate_offset && $event['recur_type'] == MCAL_RECUR_NONE)
				{
					$start_time = $GLOBALS['phpgw']->common->show_date($rawdate_offset,$this->bo->users_timeformat);
				}
				else
				{
					$start_time = $GLOBALS['phpgw']->common->show_date($starttime,$this->bo->users_timeformat);
				}

				if($endtime >= ($rawdate_offset + 86400))
				{
					$end_time = $GLOBALS['phpgw']->common->show_date(mktime(23,59,59,$month,$day,$year) - $GLOBALS['phpgw']->datetime->tz_offset,$this->bo->users_timeformat);
				}
				else
				{
					$end_time = $GLOBALS['phpgw']->common->show_date($endtime,$this->bo->users_timeformat);
				}
				$time = $start_time.' - '.$end_time;
			}
			else
			{
				$time = '';
			}
			
			$texttitle = $texttime = $textdesc = $textlocation = $textstatus = '';

			
			
			if(!$is_private)
			{
				//$text .= $this->bo->display_status($event['users_status']);
				
				// split text for better display by templates, also see $texttime $texttitle $textdesc $textlocation	
				$textstatus=$this->bo->display_status($event['users_status']); 
				
			}

			$text = '<nobr>&nbsp;'.$time.'&nbsp;</nobr> '.$this->bo->get_short_field($event,$is_private,'title').$text.
			
			$texttime=$time;
			$texttitle=$this->bo->get_short_field($event,$is_private,'title');
			$textdesc=(!$is_private && $event['description'] ? $this->bo->get_short_field($event,$is_private,'description'):'');
			// added $textlocation but this must be activated in the actual pict_link.tpl file of the used template set
			$textlocation=$this->bo->get_short_field($event,$is_private,'location');

			if ($viewable)
			{
				$date = sprintf('%04d%02d%02d',$year,$month,$day);
				$this->event_tpl->set_var('link_link',$this->page('view','&cal_id='.$event['id'].'&date='.$date));
				$this->event_tpl->set_var('lang_view',lang('View this entry'));
				$this->event_tpl->set_var('desc', $textdesc);
				$this->event_tpl->set_var('location', $textlocation);
				$this->event_tpl->parse('picture','link_event_open',True);
			}
			if (!$is_private)
			{
				if($event['priority'] == 3)
				{
					$picture[] = Array(
						'pict'	=> $GLOBALS['phpgw']->common->image('calendar','high'),
						'width'	=> 16,
						'height'=> 16,
						'title' => lang('high priority')
					);
				}
				if($event['recur_type'] == MCAL_RECUR_NONE)
				{
					$picture[] = Array(
						'pict'	=> $GLOBALS['phpgw']->common->image('calendar','circle'),
						'width'	=> 9,
						'height'=> 9,
						'title' => lang('single event')
					);
				}
				else
				{
					$picture[] = Array(
						'pict'	=> $GLOBALS['phpgw']->common->image('calendar','recur'),
						'width'	=> 12,
						'height'=> 12,
						'title' => lang('recurring event')
					);
				}
			}
			$participants = $this->planner_participants($event['participants']);
			if(count($event['participants']) > 1)
			{
				$picture[] = Array(
					'pict'	=> $GLOBALS['phpgw']->common->image('calendar','multi_3'),
					'width'	=> 14,
					'height'=> 14,
					'title' => $participants
				);
			}
			else
			{
				$picture[] = Array(
					'pict'	=>  $GLOBALS['phpgw']->common->image('calendar','single'),
					'width'	=> 14,
					'height'=> 14,
					'title' => $participants
				);
			}
			if($event['public'] == 0)
			{
				$picture[] = Array(
					'pict'	=> $GLOBALS['phpgw']->common->image('calendar','private'),
					'width'	=> 13,
					'height'=> 13,
					'title' => lang('private')
				);
			}
			if(@isset($event['alarm']) && count($event['alarm']) >= 1 && !$is_private)
			{
				// if the alarm is to go off the day before the event
				// the icon does not show up because of 'alarm_today'
				// - TOM
				if($this->bo->alarm_today($event,$rawdate_offset,$starttime))
				{
					$picture[] = Array(
						'pict'  => $GLOBALS['phpgw']->common->image('calendar','alarm'),
						'width' => 13,
						'height'=> 13,
						'title' => lang('alarm')
					);
				}
			}

			$description = $this->bo->get_short_field($event,$is_private,'description');
			for($i=0;$i<count($picture);$i++)
			{
				$var = Array(
					'pic_image' => $picture[$i]['pict'],
					'width'     => $picture[$i]['width'],
					'height'    => $picture[$i]['height'],
					'title'     => $picture[$i]['title']
				);
				$this->output_template_array($this->event_tpl,'picture','event_pict',$var);
			}
			if ($texttitle)
			{
				$var = Array(
			//		'text' => $text,
					'time'=> $texttime,
					'title'=> $texttitle,
					'users_status'=>$textstatus,
					'desc'=> $textdesc,
					'location'=> "<br><b>Local:</b> ".$textlocation
				);
				$this->output_template_array($this->event_tpl,'picture','link_event_text',$var);
			}

			if ($viewable)
			{
				$this->event_tpl->parse('picture','link_event_close',True);
			}
			$str = $this->event_tpl->fp('out','link_event_pict');
			$this->event_tpl->set_var('picture','');
			$this->event_tpl->set_var('out','');
//			unset($p);
			return $str;
		}

/*****************************************************************************************/
		function normDec($num)
		{
			if ($num > 9)
				return $num;
			else
				return "0".$num;
		}
		function overlap($params)
		{
			if(!is_array($params))
			{
			}
			else
			{
				$overlapping_events = $params['o_events'];
				$event = $params['this_event'];
			}

			$month = $event['start']['month'];
			$mday = $event['start']['mday'];
			$year = $event['start']['year'];

			$start = mktime($event['start']['hour'],$event['start']['min'],$event['start']['sec'],$month,$mday,$year) - $GLOBALS['phpgw']->datetime->tz_offset;
			$end = $this->bo->maketime($event['end']) - $GLOBALS['phpgw']->datetime->tz_offset;

			$overlap = '';

			for($i=0;$i<count($overlapping_events);$i++)
			{
				$overlapped_event = $this->bo->read_entry($overlapping_events[$i],True);
				foreach($overlapped_event['participants'] as $id => $status)
				{
					if( trim($status) !== "R" )					
					{
						$conflict = isset($event['participants'][$id]);
						$typeConflict = ( $status == "A" ) ? lang("accepted") : lang("no response");
						$typeConflict = '<label style="font-weight:bold;color:red">'.$typeConflict.'</label>';

						$overlap .= '<li>'.( $conflict ? '<b>' : '' );
						$overlap .= $GLOBALS['phpgw']->common->grab_owner_name($id);
						$overlap .=	( $conflict ? '</b> - '.lang('Scheduling conflict') . " : " . $typeConflict : '' )."</li>";
					}
				}
			 if ($this->bo->prefs['calendar']['hide_event_conflict'])
				 $overlap .= '<ul><font size="1"><span>'.
					 $this->normDec($overlapped_event['start']['hour']).":".
					 $this->normDec($overlapped_event['start']['min'])."-".
					 $this->normDec($overlapped_event['end']['hour']).":".
					 $this->normDec($overlapped_event['end']['min']).
					 '<br><b>'.lang('title').": ".lang("Hidden").
					 '</b><br>'.lang('description').": ".lang("Hidden").'<br></span></ul><br>';
			 else
				 $overlap .= '<ul>'.$this->link_to_entry($overlapped_event,$month,$mday,$year)."</ul><br>";	
			}

			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			$GLOBALS['phpgw_info']['flags']['noappheader'] = True;
			$GLOBALS['phpgw_info']['flags']['noappfooter'] = True;
			$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['calendar']['title'].' - '.lang('Scheduling Conflict');
			$GLOBALS['phpgw']->common->phpgw_header();

			$p = $GLOBALS['phpgw']->template;
			$p->set_file(
				Array(
					'overlap'	=> 'overlap.tpl',
   					'form_button'	=> 'form_button_script.tpl'
				)
			);

			$var = Array(
				'color'		=> $this->theme['bg_text'],
				'overlap_title' => lang('Scheduling Conflict'),
				'overlap_text'	=> lang('Your suggested time of <B> %1 - %2 </B> conflicts with the following existing calendar entries:',$GLOBALS['phpgw']->common->show_date($start),$GLOBALS['phpgw']->common->show_date($end)),
				'overlap_list'	=> $overlap
			);
			$p->set_var($var);

			$date = sprintf("%04d%02d%02d",$this->bo->year,$this->bo->month,$this->bo->mday);
			$var = Array(
				'action_url_button'	=> $GLOBALS['phpgw']->link('/index.php',Array('menuaction'=>'calendar.bocalendar.update','readsess'=>1)),
				'action_text_button'	=> lang('Ignore Conflict'),
				'action_confirm_button'	=> '',
				'action_extra_field'	=> '',
				'button_id'		=> 'ignore_button'
			);
			$this->output_template_array($p,'resubmit_button','form_button',$var);

			$var = Array(
				'action_url_button'	=> $GLOBALS['phpgw']->link('/index.php',Array('menuaction'=>'calendar.uicalendar.edit','readsess'=>1,'date'=>$date)),
				'action_text_button'	=> lang('Re-Edit Event'),
				'action_confirm_button'	=> '',
				'action_extra_field'	=> '',
				'button_id'		=> 'redit_button'
			);
			$this->output_template_array($p,'reedit_button','form_button',$var);
			$p->pparse('out','overlap');
		}

		function planner_participants($parts)
		{
			static $id2lid;

			$names = '';
			while (list($id,$status) = each($parts))
			{
				$status = substr($this->bo->get_long_status($status),0,1);

				if (!isset($id2lid[$id]))
				{
					$id2lid[$id] = $GLOBALS['phpgw']->common->grab_owner_name($id);
				}
				if (strlen($names))
				{
					$names .= ",\n";
				}
				$names .= $id2lid[$id]." ($status)";
			}
			if($this->debug)
			{
				echo '<!-- Inside participants() : '.$names.' -->'."\n";
			}
			return $names;
		}

		function planner_category($ids)
		{
			static $cats;
			if(!is_array($ids))
			{
				if (strpos($ids,','))
				{
					$id_array = explode(',',$ids);
				}
				else
				{
					$id_array[0] = $ids;
				}
			}
			@reset($id_array);
			$ret_val = Array();
			while(list($index,$id) = each($id_array))
			{
				if (!isset($cats[$id]))
				{
					$cat_arr = $this->cat->return_single( $id );
					$cats[$id] = $cat_arr[0];
					$cats[$id]['color'] = strstr($cats[$id]['description'],'#');
				}
				$ret_val[] = $cats[$id];
			}
			return $ret_val;
		}

		function week_header($month,$year,$display_name = False)
		{
			$this->weekstarttime = $GLOBALS['phpgw']->datetime->get_weekday_start($year,$month,1);

			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('remove');
			$p->set_file(
				Array (
					'month_header' => 'month_header.tpl'
				)
			);
			$p->set_block('month_header','monthly_header','monthly_header');
			$p->set_block('month_header','column_title','column_title');

			$var = Array(
				'bgcolor'	=> $this->theme['th_bg'],
				'font_color'	=> $this->theme['th_text']
			);
			if($this->bo->printer_friendly && @$this->bo->prefs['calendar']['print_black_white'])
			{
				$var = Array(
					'bgcolor'	=> '',
					'font_color'	=> ''
				);
			}
			$p->set_var($var);

			$p->set_var('col_width','14');
			if($display_name == True)
			{
				$p->set_var('col_title',lang('name'));
				$p->parse('column_header','column_title',True);
				$p->set_var('col_width','12');
			}

			for($i=0;$i<7;$i++)
			{
				$p->set_var('col_title',lang($GLOBALS['phpgw']->datetime->days[$i]));
				$p->parse('column_header','column_title',True);
			}
			return $p->fp('out','monthly_header');
		}

		function display_week($startdate,$weekly,$cellcolor,$display_name = False,$owner=0,$monthstart=0,$monthend=0)
		{
			if($owner == 0)
			{
				$owner = $GLOBALS['phpgw_info']['user']['account_id'];
			}

			$temp_owner = $this->bo->owner;

			$str = '';
			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('keep');

			$p->set_file(
				Array(
					'month_header'	=> 'month_header.tpl',
					'month_day'	=> 'month_day.tpl'
				)
			);
			$p->set_block('month_header','monthly_header','monthly_header');
			$p->set_block('month_header','month_column','month_column');
			$p->set_block('month_day','month_daily','month_daily');
			$p->set_block('month_day','day_event','day_event');
			$p->set_block('month_day','event','event');

			$p->set_var('extra','');
			$p->set_var('col_width','14');
			if($display_name)
			{
				$p->set_var('column_data',$GLOBALS['phpgw']->common->grab_owner_name($owner));
				$p->parse('column_header','month_column',True);
				$p->set_var('col_width','12');
			}
			$today = date('Ymd',$GLOBALS['phpgw']->datetime->users_localtime);
			$daily = $this->set_week_array($startdate - $GLOBALS['phpgw']->datetime->tz_offset,$cellcolor,$weekly);
			foreach($daily as $date => $day_params)
			{
				$year  = (int)substr($date,0,4);
				$month = (int)substr($date,4,2);
				$day   = (int)substr($date,6,2);
				$var   = Array(
					'column_data' => '',
					'extra' => ''
				);
				$p->set_var($var);
				if ($weekly || ($date >= $monthstart && $date <= $monthend))
				{
					if ($day_params['new_event'])
					{
						$new_event_link = ' <a href="'.$this->page('add','&date='.$date).'">'
							. '<img src="'.$GLOBALS['phpgw']->common->image('calendar','new3').'" width="10" height="10" title="'.lang('New Entry').'" border="0" align="center">'
							. '</a>';
						$day_number = '<a href="'.$this->page('day','&date='.$date).'">'.$day.'</a>';
					}
					else
					{
						$new_event_link = '';
						$day_number = $day;
					}

					$var = Array(
						'extra'		=> $day_params['extra'],
						'new_event_link'=> $new_event_link,
						'day_number'	=> $day_number
					);

					if( $day_params['week'] )
					{

						$var['new_event_link'] .= ' &nbsp; '.
							(!$this->bo->printer_friendly?'<a href="'.$this->page('week','&date='.$date).'"><span id="calendar_weekinfo" class="calendar_weekinfo">' .$day_params['week'].'</span></a>' : '<span id="calendar_weekinfo" class="calendar_weekinfo">'.$day_params['week'].'</span>');
					}

					$p->set_var($var);

					if(@$day_params['holidays'])
					{
						foreach($day_params['holidays'] as $key => $value)
						{
							$var = Array(
								'day_events' => '<font face="'.$this->theme['font'].'" size="-1">'.$value.'</font><br>'
							);
							$this->output_template_array($p,'daily_events','event',$var);
						}
					}

					if($day_params['appts'])
					{
						$var = Array(
							'week_day_font_size'	=> '2',
							'events'		=> ''
						);
						$p->set_var($var);
						$events = $this->bo->cached_events[$date];
						foreach($events as $event)
						{
							if ($this->bo->rejected_no_show($event))
							{
								continue;	// user does not want to see rejected events
							}
							$p->set_var('day_events',$this->link_to_entry($event,$month,$day,$year));
							$p->parse('events','event',True);
							$p->set_var('day_events','');
						}
					}
					$p->parse('daily_events','day_event',True);
					$p->parse('column_data','month_daily',True);
					$p->set_var('daily_events','');
					$p->set_var('events','');
/*					if($day_params['week'])
					{
						$var = Array(
							'week_day_font_size'	=> '-2',
							'events'		=> (!$this->bo->printer_friendly?'<a href="'.$this->page('week','&date='.$date).'">' .$day_params['week'].'</a>':$day_params['week'])
						);
						$this->output_template_array($p,'column_data','day_event',$var);
						$p->set_var('events','');
					} */
				}
				$p->parse('column_header','month_column',True);
				$p->set_var('column_data','');
			}
			$this->bo->owner = $temp_owner;
			return $p->fp('out','monthly_header');
		}

/***************************************************************************************/
/*As funcoes abaixo
	month_week_header()
	month_display_week()
	display_month_print()
	month_day_of_week() - esta foi criada mas parte do codigo e de outra funcao da API (day_of_week());

  foram duplicadas e modificadas para atender aas modificacoes do layout de impressao mensal da agenda de eventos;
  TODO: otimizar o codigo

  Rommel Cysne
*/

		function month_week_header($month,$year,$display_name = False)
		{
			$this->weekstarttime = $GLOBALS['phpgw']->datetime->get_weekday_start($year,$month,1);
			$p->set_unknowns('remove');
		}


		function month_day_of_week($year,$month,$day)
		{
			$dia = Array(
				0 => 'Domingo',
				1 => 'Segunda',
				2 => 'Ter&ccedil;a',
				3 => 'Quarta',
				4 => 'Quinta',
				5 => 'Sexta',
				6 => 'S&aacute;bado'
			);

			if($month > 2)
			{
				$month -= 2;
			}
				else
			{
				$month += 10;
				$year--;
			}
			$day = (floor((13 * $month - 1) / 5) + $day + ($year % 100) + floor(($year % 100) / 4) + floor(($year / 100) / 4) - 2 * floor($year / 100) + 77);

			$month_day = ($day - 7 * floor($day / 7)); //retorna o numero do dia da semana (Ex: domingo eh dia 0, terca eh 2);
			$weekday = $dia[$month_day]; //retorna o nome do dia da semana de acordo com seu numero;

			return ($weekday);

		}

		function month_display_week($startdate,$weekly,$cellcolor,$display_name = False,$owner=0,$monthstart=0,$monthend=0)
		{
			if($owner == 0)
			{
				$owner = $GLOBALS['phpgw_info']['user']['account_id'];
			}

			$temp_owner = $this->bo->owner;

			$str = '';
			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('keep');

			$p->set_file(
				Array(
					'month_header'	=> 'month_header_print.tpl',
					'month_day'	=> 'month_day_print.tpl'
				)
			);
			$p->set_block('month_header','monthly_header','monthly_header');
			$p->set_block('month_header','month_column','month_column');
			$p->set_block('month_day','month_daily','month_daily');
			$p->set_block('month_day','day_event','day_event');
			$p->set_block('month_day','event','event');

			$p->set_var('extra','');
			$p->set_var('col_width','14');
			
			$today = date('Ymd',$GLOBALS['phpgw']->datetime->users_localtime);
			$daily = $this->set_week_array($startdate - $GLOBALS['phpgw']->datetime->tz_offset,$cellcolor,$weekly);

			foreach($daily as $date => $day_params)
			{
				$year  = (int)substr($date,0,4);
				$month = (int)substr($date,4,2);
				$day   = (int)substr($date,6,2);
				$var   = Array(
					'column_data' => '',
					'extra' => ''
				);
				$p->set_var($var);
				if ($weekly || ($date >= $monthstart && $date <= $monthend))
				{
					$new_event_link = '';
					$day_num = $day;
					$dia_semana = $this->month_day_of_week($year,$month,$day_num);
					$id_aux = mktime(0,0,0,$month,$day,$year);
					$month_identifier = substr(lang(strftime("%B",$id_aux)),0,3);
					$day_number = $month_identifier . " - " . $dia_semana . ",  " . $day_num;

					$var = Array(
						'extra'		=> $day_params['extra'],
						'new_event_link'=> $new_event_link,
						'day_number'	=> $day_number
					);

					$p->set_var($var);

					if($day_params['appts'])
					{
						$var = Array(
							'week_day_font_size'	=> '2',
							'events'		=> ''
						);
						$p->set_var($var);
						$events = $this->bo->cached_events[$date];
						foreach($events as $event)
						{
							if ($this->bo->rejected_no_show($event))
							{
								continue;	// user does not want to see rejected events
							}
							$p->set_var('day_events',$this->link_to_month_entry($event,$month,$day,$year));
							$p->parse('events','event',True);
							$p->set_var('day_events','');
						}
					}
					$p->parse('daily_events','day_event',True);
					$p->parse('column_data','month_daily',True);
					$p->set_var('daily_events','');
					$p->set_var('events','');
				}
				$p->parse('column_header','month_column',True);
				$p->set_var('column_data','');
			}
			$this->bo->owner = $temp_owner;
			return $p->fp('out','monthly_header');

		}

		function display_month_print($month,$year,$showyear,$owner=0)
		{
			if($this->debug)
			{
				echo '<!-- datetime:gmtdate = '.$GLOBALS['phpgw']->datetime->cv_gmtdate.' -->'."\n";
			}

/*			$this->bo->store_to_cache(
				Array(
					'syear'	=> $year,
					'smonth'=> $month,
					'sday'	=> 1
				)
			);
*/
			$day_ini = $_GET['day_ini'];// dia do inicio da impressao; o padrao e o dia atual;
			$num_dias = $_GET['num_dias'];// quantidade de dias a partir do dia inicial;

			$monthstart = (int)(date('Ymd',mktime(0,0,0,$month    ,1,$year)));
//			$monthend   = (int)(date('Ymd',mktime(0,0,0,$month + 1,0,$year)));

			if($num_dias != 0)
			{
				//se a qtde de dias for informada, a data final para impressao considera a soma $day_ini+$num_dias;
				$monthend   = (int)(date('Ymd',mktime(0,0,0,$month,$day_ini+($num_dias-1),$year)));
			}
			else
			{
				//se a qtde de dias nao for informada, a data final para impressao vai ate o fim do mes corrente;
				$monthend   = (int)(date('Ymd',mktime(0,0,0,$month + 1,0,$year)));
			}
			
			if($day_ini != 0)
			{
				//o dia de inicio para impressao passa a ser o informado em $day_ini do mes corrente;
				$start = mktime(0,0,0,$month,$day_ini,$year);
			}
			else
			{
				//se nao for informado o dia para inicio da impressao, esta comeca no dia 1 do mes corrente;
				$start = $GLOBALS['phpgw']->datetime->get_weekday_start($year, $month, 1);
			}
			//$start = $GLOBALS['phpgw']->datetime->get_weekday_start($year, $month, 15);
			$start_d = (int)(date('Ymd',$start));//variavel auxiliar que recebe a data inicial de impressao no formato 'Ymd';
			$saux = (int)(substr($start_d,4,2));//variavel auxiliar que recebe o mes do inicio da impressao;
			$eaux = (int)(substr($monthend,4,2)) + 1;//variavel auxiliar que recebe o mes do fim da impressao;
			//A funcao store_to_cache devolve todos os eventos existentes de determinado usuario em um periodo de tempo
			//especifico. Originalmente, na impressao mensal, esse periodo de tempo era o mes corrente exibido na tela.
			//A mudanca consiste no fato de que uma nova variavel ($saux) e enviada com o mes de inicio do periodo
			//atraves do indice 'smonth' e uma segunda variavel ($eaux) envia o mes final do periodo de tempo atraves
			//do indice 'emonth'. Vide mais comentarios sobre esses valores em class.bocalendar.inc.php, 
			//function store_to_cache($params);
			$this->bo->store_to_cache(
			Array(
				'syear'	=> $year,//ano atual
				'smonth'=> $saux,//mes inicial
				'emonth'=> $eaux,//mes final
				'sday'	=> 1
			));

			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('keep');

			$p->set_file(
				Array(
					'week' => 'month_day_print.tpl'
				)
			);
			$p->set_block('week','m_w_table','m_w_table');
			$p->set_block('week','event','event');

			$var = Array(
				'cols'      => 1,
				'day_events'=> '' //$this->month_week_header($month,$year,False)
			);
//			$this->output_template_array($p,'row','event',$var);

			$cellcolor = $this->theme['row_on'];

			for($i = (int)($start + $GLOBALS['phpgw']->datetime->tz_offset);(int)(date('Ymd',$i)) <= $monthend;$i += 604800)
			{
				$cellcolor = $GLOBALS['phpgw']->nextmatchs->alternate_row_color($cellcolor);
				$var = Array(
					'day_events' => $this->month_display_week($i,False,$cellcolor,False,$owner,$monthstart,$monthend)
				);
				$this->output_template_array($p,'row','event',$var);
			}
			return $p->fp('out','m_w_table');
		}


/***************************************************************************************/

		function display_month($month,$year,$showyear,$owner=0)
		{
			if($this->debug)
			{
				echo '<!-- datetime:gmtdate = '.$GLOBALS['phpgw']->datetime->cv_gmtdate.' -->'."\n";
			}

			$this->bo->store_to_cache(
				Array(
					'syear'	=> $year,
					'smonth'=> $month,
					'sday'	=> 1
				)
			);

			$monthstart = (int)(date('Ymd',mktime(0,0,0,$month    ,1,$year)));
			$monthend   = (int)(date('Ymd',mktime(0,0,0,$month + 1,0,$year)));

			$start = $GLOBALS['phpgw']->datetime->get_weekday_start($year, $month, 1);

			if($this->debug)
			{
				echo '<!-- display_month:monthstart = '.$monthstart.' -->'."\n";
				echo '<!-- display_month:start = '.date('Ymd H:i:s',$start).' -->'."\n";
			}

			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('keep');

			$p->set_file(
				Array(
					'week' => 'month_day.tpl'
				)
			);
			$p->set_block('week','m_w_table','m_w_table');
			$p->set_block('week','event','event');

			$var = Array(
				'cols'      => 1,
				'day_events'=> $this->week_header($month,$year,False)
			);
			$this->output_template_array($p,'row','event',$var);

			$cellcolor = $this->theme['row_on'];

			for($i = (int)($start + $GLOBALS['phpgw']->datetime->tz_offset);(int)(date('Ymd',$i)) <= $monthend;$i += 604800)
			{
				$cellcolor = $GLOBALS['phpgw']->nextmatchs->alternate_row_color($cellcolor);
				$var = Array(
					'day_events' => $this->display_week($i,False,$cellcolor,False,$owner,$monthstart,$monthend)
				);
				$this->output_template_array($p,'row','event',$var);
			}
			return $p->fp('out','m_w_table');
		}

		function display_weekly($params)
		{
			if(!is_array($params))
			{
				$this->index();
			}

			$year = substr($params['date'],0,4);
			$month = substr($params['date'],4,2);
			$day = substr($params['date'],6,2);
			$showyear = $params['showyear'];
			$owners = $params['owners'];

			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('keep');

			$p->set_file(
				Array(
					'week'	=> 'month_day.tpl'
				)
			);
			$p->set_block('week','m_w_table','m_w_table');
			$p->set_block('week','event','event');

			$start = $GLOBALS['phpgw']->datetime->get_weekday_start($year, $month, $day) + $GLOBALS['phpgw']->datetime->tz_offset;

			$cellcolor = $this->theme['row_off'];

			$true_printer_friendly = $this->bo->printer_friendly;

			if(is_array($owners))
			{
				$display_name = True;
				$counter = count($owners);
				$owners_array = $owners;
				$cols = 8;
			}
			else
			{
				$display_name = False;
				$counter = 1;
				$owners_array[0] = $owners;
				$cols = 7;
			}
			$var = Array(
				'cols'         => $cols,
				'day_events'   => $this->week_header($month,$year,$display_name)
			);
			$this->output_template_array($p,'row','event',$var);

			$tstart = $start - $GLOBALS['phpgw']->datetime->tz_offset;
			$tstop = $tstart + 604800;
			$original_owner = $this->bo->so->owner;
			for($i=0;$i<$counter;$i++)
			{
				$this->bo->so->owner = $owners_array[$i];
				$this->bo->so->open_box($owners_array[$i]);
				$this->bo->store_to_cache(
					Array(
						'syear'  => date('Y',$tstart),
						'smonth' => date('m',$tstart),
						'sday'   => date('d',$tstart),
						'eyear'  => date('Y',$tstop),
						'emonth' => date('m',$tstop),
						'eday'   => date('d',$tstop)
					)
				);
				$p->set_var('day_events',$this->display_week($start,True,$cellcolor,$display_name,$owners_array[$i]));
				$p->parse('row','event',True);
			}
			$this->bo->so->owner = $original_owner;
			$this->bo->printer_friendly = $true_printer_friendly;
			return $p->fp('out','m_w_table');
		}

		function view_event($event,$alarms=False)
		{
			if((!$event['participants'][$this->bo->owner] && !$this->bo->check_perms(PHPGW_ACL_READ,$event)))
			{
				return False;
			}

			$p = &$GLOBALS['phpgw']->template;

			$p->set_file(
				Array(
					'view'	=> 'view.tpl'
				)
			);
			$p->set_block('view','view_event','view_event');
			$p->set_block('view','list','list');
			$p->set_block('view','hr','hr');

			$vars = $this->bo->event2array($event);

			$vars['title']['tr_color'] = $this->theme['th_bg'];

			foreach($vars['participants']['data'] as $user => $str)
			{
			if ($this->bo->check_perms(PHPGW_ACL_EDIT,0,$user) && preg_match('/^(.*) \((.*)\)$/',$str,$parts))
				{
					$vars['participants']['data'][$user] = $parts[1].' (<a href="'.$this->page('edit_status','&cal_id='.$event['id'].'&owner='.$user).'">'.$parts[2].'</a>)';
					$vars['participants']['data'][$user].='&nbsp;(<a href=\'index.php?menuaction=calendar.uicalendar.screen_delegate_event&id_event='.$event['id'].'&delegator='.$user.'&date='.$_REQUEST["date"].'&eventArray='.$event.'\'>'.lang("Delegate event").'</a>)';
//system('echo "event: '.$event['start'].'" >>/tmp/controle');
				}
			}
			$vars['participants']['data'] = implode("<br>\n",$vars['participants']['data']);
			foreach($vars as $var)
			{
				if (strlen($var['data']))
				{
					$this->output_template_array($p,'row','list',$var);
				}
			}

			if($alarms && count($event['alarm']))
			{
				$p->set_var('th_bg',$this->theme['th_bg']);
				$p->set_var('hr_text',lang('Alarms'));
				$p->parse('row','hr',True);
				// Bug Fix - Nilton Neto - 19/10/2008
 				$format = $GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'] . ' - H:i';	
 				$tz_offset = (60*60*$GLOBALS['phpgw_info']['user']['preferences']['common']['tz_offset']);
				foreach($event['alarm'] as $key => $alarm)
				{
					if (($this->bo->so->owner == $alarm['owner']) || ($this->bo->so->cal->event['owner'] == $this->bo->so->owner)) // Show only alert from user who is requesting or alert owner
					{	
						$icon = '<img src="'.$GLOBALS['phpgw']->common->image('calendar',($alarm['enabled']?'enabled':'disabled')).'" width="13" height="13">';
						$var = Array(					
							'field'	=> $icon.date($format, $alarm['time']+$tz_offset),
							'data'	=> lang('Email Notification for %1',$GLOBALS['phpgw']->common->grab_owner_name($alarm['owner']))
						);
						$this->output_template_array($p,'row','list',$var);
					}
				}
			}
			return True;
		}

		function nm_on_off()
		{
			if($GLOBALS['phpgw']->nextmatchs->alternate_row_color() == $this->theme['row_on'])
			{
				return '_on';
			}
			return '_off';
		}

		function slot_num($time,$set_day_start=0,$set_day_end=0)
		{
			static $day_start, $day_end, $interval=0;

			if ($set_day_start) $day_start = $set_day_start;
			if ($set_day_end)   $day_end   = $set_day_end;
			if (!$interval)     $interval  = 60*$this->bo->prefs['calendar']['interval'];

			if ($time > $day_end)
			{
				$time = $day_end;
			}
			$slot = (int)(($time - $day_start) / $interval);

			return $slot < 0 ? 0 : 1+$slot;
		}

		function print_day($params)
		{
			if(!is_array($params))
			{
				$this->index();
			}

			print_debug('in print_day()');

			$this->bo->store_to_cache(
				Array(
					'syear'  => $params['year'],
					'smonth' => $params['month'],
					'sday'   => $params['day'],
					'eyear'  => $params['year'],
					'emonth' => $params['month'],
					'eday'   => $params['day']
				)
			);

			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_unknowns('keep');

			$tpl = 'day_cal.tpl';
			if((int)($GLOBALS['phpgw_info']['user']['preferences']['calendar']['mainscreen_showevents'])==2 &&
				$GLOBALS['phpgw_info']['flags']['currentapp'] == 'home')
			{
				$tpl = 'day_list.tpl';
			}
			$templates = Array(
				'day_cal'   => $tpl
			);

			$p->set_file($templates);
			$p->set_block('day_cal','day','day');
			$p->set_block('day_cal','day_row','day_row');
			$p->set_block('day_cal','day_event_on','day_event_on');
			$p->set_block('day_cal','day_event_off','day_event_off');
			$p->set_block('day_cal','day_event_holiday','day_event_holiday');
			$p->set_block('day_cal','day_time','day_time');

			$date_to_eval = sprintf("%04d%02d%02d",$params['year'],$params['month'],$params['day']);

			$day_start = mktime((int)($this->bo->prefs['calendar']['workdaystarts']),0,0,$params['month'],$params['day'],$params['year']);
			$day_end = mktime((int)($this->bo->prefs['calendar']['workdayends']),0,1,$params['month'],$params['day'],$params['year']);
			$daily = $this->set_week_array($GLOBALS['phpgw']->datetime->get_weekday_start($params['year'],$params['month'],$params['day']),$this->theme['row_on'],True);
			print_debug('Date to Eval',$date_to_eval);
			$events_to_show = array();
			if($daily[$date_to_eval]['appts'])
			{
				$events = $this->bo->cached_events[$date_to_eval];
				print_debug('Date',$date_to_eval);
				print_debug('Count',count($events));
				foreach($events as $event)
				{
					if ($this->bo->rejected_no_show($event))
					{
						continue;	// user does not want to see rejected events
					}
					if ($event['recur_type'])	// calculate start- + end-datetime for recuring events
					{
						$this->bo->set_recur_date($event,$date_to_eval);
					}
					$events_to_show[] = array(
						'starttime' => $this->bo->maketime($event['start']),
						'endtime'   => $this->bo->maketime($event['end']),
						'content'   => $this->link_to_entry($event,$params['month'],$params['day'],$params['year'])
					);
				}
			}
			//echo "events_to_show=<pre>"; print_r($events_to_show); echo "</pre>\n";
			$other = $GLOBALS['phpgw']->hooks->process(array(
				'location'  => 'calendar_include_events',
				'year'      => $params['year'],
				'month'     => $params['month'],
				'day'       => $params['day'],
				'owner'     => $this->bo->owner	// num. id of the user, not necessary current user
			));

			if (is_array($other))
			{
				foreach($other as $evts)
				{
					if (is_array($evts))
					{
						$events_to_show = array_merge($events_to_show,$evts);
					}
				}
				usort($events_to_show,create_function('$a,$b','return $a[\'starttime\']-$b[\'starttime\'];'));
				//echo "events_to_show=<pre>"; print_r($events_to_show); echo "</pre>\n";
			}

			if (count($events_to_show))
			{
				$last_slot_end = -1;
				foreach($events_to_show as $event)
				{
					$slot = $this->slot_num($event['starttime'],$day_start,$day_end);
					$slot_end = isset($event['endtime']) ? $this->slot_num($event['endtime']-1) : $slot;	// -1 to not occupy eg. the 18.00 slot for a 17-18h date

					if ($slot <= $last_slot_end)
					{
						$slot = $last_slot;
						$slot_end = max($last_slot_end,$slot_end);
					}
					$rows[$slot] .= $event['content'];

					print_debug('slot',$slot);
					print_debug('row',$rows[$slot]);

					$row_span[$slot] = 1 + $slot_end - $slot;

					$last_slot = $slot;
					$last_slot_end = $slot_end;
					print_debug('Time',$GLOBALS['phpgw']->common->show_date($this->bo->maketime($events[$i]['start']) - $GLOBALS['phpgw']->datetime->tz_offset).' - '.$GLOBALS['phpgw']->common->show_date($this->bo->maketime($events[$i]['end']) - $GLOBALS['phpgw']->datetime->tz_offset));
					print_debug('Slot',$slot);
				}
				//echo "rows=<pre>"; print_r($rows); echo "<br>row_span="; print_r($row_span); echo "</pre>\n";
			}
			$holiday_names = $daily[$date_to_eval]['holidays'];
			if(!$holiday_names)
			{
				$row_to_print = $this->nm_on_off();
			}
			else
			{
				$row_to_print = '_holiday';
				foreach($holiday_names as $name)
				{
					$rows[0] = '<center>'.$name.'</center>' . $rows[0];
				}
			}
			$last_slot = $this->slot_num($day_end,$day_start,$day_end);
			$rowspan = 0;
			for ($slot = 0; $slot <= $last_slot; ++$slot)
			{
				$p->set_var('extras','');
				if ($rowspan > 1)
				{
					$p->set_var('event','');
					$rowspan--;
				}
				elseif (!isset($rows[$slot]))
				{
					$p->set_var('event','&nbsp;');
					$row_to_print = $this->nm_on_off();
					$p->parse('event','day_event'.$row_to_print);
				}
				else
				{
					$rowspan = (int)$row_span[$slot];
					if ($rowspan > 1)
					{
						$p->set_var('extras',' rowspan="'.$rowspan.'"');
					}
					$p->set_var('event',$rows[$slot]);
					$row_to_print = $this->nm_on_off();
					$p->parse('event','day_event'.$row_to_print);
				}
				$open_link = $close_link = '';
				$time = '&nbsp;';

				if (0 < $slot && $slot < $last_slot)	// normal time-slot not before or after day_start/end
				{
					$time = $day_start + ($slot-1) * 60 * $this->bo->prefs['calendar']['interval'];
					$hour = date('H',$time);
					$min  = date('i',$time);
					$time = $GLOBALS['phpgw']->common->formattime($hour,$min);

					if(!$this->bo->printer_friendly && $this->bo->check_perms(PHPGW_ACL_ADD))
					{
						$open_link = ' <a href="'.$this->page('add',"&date=$date_to_eval&hour=$hour&minute=$min").'">';
						$close_link = '</a> ';
					}
				}
				$p->set_var(Array(
					'open_link'  => $open_link,
					'time'       => $time,
					'close_link' => $close_link,
					'tr_color'   => ''	// dummy to stop output_template_array to set it
				));
				$p->parse('time','day_time');

				$p->parse('row','day_row',True);
			}
			return $p->fp('out','day');
		}	// end function

		function timematrix($param)
		{
			if(!is_array($param))
			{
				$this->index();
			}

			$date = $param['date'];
			$starttime = $param['starttime'];
			$endtime = $param['endtime'];
			$participants = $param['participants'];
			foreach($participants as $part => $nul)
			{
				$participants[$part] = $GLOBALS['phpgw']->common->grab_owner_name($part);
				// Much better for processor  :)
				$participants_id[]  .= $part;
			}
			uasort($participants,'strnatcasecmp');	// sort them after their fullname

			if(!isset($this->bo->prefs['calendar']['interval']))
			{
				$this->bo->prefs['calendar']['interval'] = 15;
				$GLOBALS['phpgw']->preferences->add('calendar','interval',15);
				$GLOBALS['phpgw']->preferences->save_repository();
			}
			$increment = $this->bo->prefs['calendar']['interval'];
			$interval = (int)(60 / $increment);

			$pix = $GLOBALS['phpgw']->common->image('calendar','pix');

			$str = '<center>'.lang($GLOBALS['phpgw']->common->show_date($date['raw'],'l'))
				. ', '.$this->bo->long_date($date).'<br>'
				. '<table width="85%" border="0" cellspacing="0" cellpadding="0" cols="'.((24 * $interval) + 1).'">'
				. '<tr><td height="1" colspan="'.((24 * $interval) + 1).'" bgcolor="black"><img src="'.$pix.'"></td></tr>'
				. '<tr><td width="15%"><font color="'.$this->theme['bg_text'].'" face="'.$this->theme['font'].'" size="-2">'.lang('Participant').'</font></td>';
			for($i=0;$i<24;$i++)
			{
				for($j=0;$j<$interval;$j++)
				{
					$k = ($j == 0 ? sprintf('%02d',$i).'<br>':'').sprintf('%02d',$j*$increment);

					$str .= '<td align="left" bgcolor="'.$this->theme['bg_color'].'"><font color="'.$phpgw_info['theme']['bg_text'].'" face="'.$this->theme['font'].'" size="-2">'
						. '<a href="'.$this->page('add','&date='.$date['full'].'&hour='.$i.'&minute='.($increment * $j))."\" onMouseOver=\"window.status='".$i.':'.(($increment * $j)<=9?'0':'').($increment * $j)."'; return true;\">"
						. $k."</a>&nbsp;</font></td>\n";
				}
			}
			$str .= '</tr>'
				. '<tr><td height="1" colspan="'.((24 * $interval) + 1).'" bgcolor="black"><img src="'.$pix.'"></td></tr>';
			if(!$endtime)
			{
				$endtime = $starttime;
			}
			$owner = $this->bo->owner;
			foreach($participants as $part => $fullname)
			{
				$str .= '<tr align="center">'
					. '<td width="15%" align="left"><font color="'.$this->theme['bg_text'].'" face="'.$this->theme['font'].'" size="-2">'.$fullname.'</font></td>';

				$this->bo->cached_events = Array();
				$this->bo->so->owner = $part;
				$this->bo->so->open_box($part);
				$this->bo->store_to_cache(
					Array(
						'syear'	=> $date['year'],
						'smonth'=> $date['month'],
						'sday'	=> $date['day'],
						'eyear'	=> 0,
						'emonth'=> 0,
						'eday'	=> $date['day'] + 1
					)
				);

				if(!$this->bo->cached_events[$date['full']])
				{
					for($j=0;$j<24;$j++)
					{
						for($k=0;$k<$interval;$k++)
						{
							$str .= '<td height="1" align="left" bgcolor="'.$this->theme['bg_color'].'" color="#999999">&nbsp;</td>';
						}
						$str .= "\n";
					}
				}
				else
				{
					$time_slice = $this->bo->prepare_matrix($interval,$increment,$part,$date['full']);
					for($h=0;$h<24;$h++)
					{
						$hour = $h * 10000;
						for($m=0;$m<$interval;$m++)
						{
							$index = ($hour + (($m * $increment) * 100));
							switch($time_slice[$index]['marker'])
							{
								case '&nbsp':
									$time_slice[$index]['color'] = $this->theme['bg_color'];
									$extra = '';
									break;
								case '-':
									$time_slice[$index]['color'] = $this->theme['bg01'];
									$link = $this->page('view','&cal_id='.$time_slice[$index]['id'].'&date='.$date['full']);
									$extra =' title="'.$time_slice[$index]['description'].'" onClick="location.href=\''.$link.'\';" style="cursor:pointer; cursor:hand;"';
									break;
							}
							$str .= '<td bgcolor="'.$time_slice[$index]['color'].'" color="#999999"'.$extra.'><font color="'.$this->theme['bg_text'].'" face="'.$this->theme['font'].'" size="-2">'.$time_slice[$index]['marker'].'</font></td>';
						}
						$str .= "\n";
					}
				}
				$str .= '</tr>'
					. '<tr><td height="1" colspan="'.((24 * $interval) + 1).'" bgcolor="#999999"><img src="'.$pix.'"></td></tr>';
			}
			$this->bo->owner = $owner;
			$this->bo->so->owner = $owner;
			$this->bo->so->open_box($owner);
			return $str.'</table></center>'."\n";
		}

		function get_response($cal_id)
		{
			$p = &$GLOBALS['phpgw']->template;
			$p->set_file(
				Array(
  					'form_button'	=> 'form_button_script.tpl'
				)
			);

			$ev = $this->bo->get_cached_event();
			$response_choices = Array(
				ACCEPTED	=> lang('Accept'),
				REJECTED	=> lang('Reject'),
				TENTATIVE	=> lang('Tentative')				
			);
			$str = '';
			while(list($param,$text) = each($response_choices))
			{
				$var = Array(
					'action_url_button'	=> $this->page('set_action','&cal_id='.$cal_id.'&action='.$param),
					'action_text_button'	=> '  '.$text.'  ',
					'action_confirm_button'	=> '',
					'action_extra_field'	=> ''
				);
				$p->set_var($var);
				$str .= '<td>'.$p->fp('out','form_button').'</td>'."\n";
			}
			if ($this->bo->return_to)
			{
				$var = Array(
					'action_url_button'	=> $GLOBALS['phpgw']->link('/index.php','menuaction='.$this->bo->return_to),
					'action_text_button'	=> lang('cancel'),
					'action_confirm_button'	=> '',
					'action_extra_field'	=> ''
				);
				$p->set_var($var);
				$str .= '<td>'.$p->fp('out','form_button').'</td>'."\n";
			}
			$str = '<td><b>'.$GLOBALS['phpgw']->common->grab_owner_name($this->bo->owner).":</b></td>\n".$str;

			return '<table width="100%"><tr align="center">'."\n".$str.'</tr></table>'."\n";
		}

		function accounts_popup()
		{
			$GLOBALS['phpgw']->accounts->accounts_popup('calendar');
			
		}

		function edit_form($param)
		{
			if(!is_array($param))
			{
				$this->index();
			}
			
			if(isset($param['event']))
			{
				$event = $param['event'];
			}
			
			if(!@is_object($GLOBALS['phpgw']->sms)) $GLOBALS['phpgw']->sms = CreateObject('phpgwapi.sms');
			$sms_enabled = ($GLOBALS['phpgw']->sms->isEnabled() && $GLOBALS['phpgw']->sms->hasSendAuth())? '' : ' display: none;';
			
			$hourformat = substr($this->bo->users_timeformat,0,1);
			
			// $sb = CreateObject('phpgwapi.sbox');
			$sb = CreateObject('phpgwapi.sbox2');
			$jscal = CreateObject('phpgwapi.jscalendar');	// before phpgw_header() !!!
			
			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			$GLOBALS['phpgw_info']['flags']['noappheader'] = True;
			$GLOBALS['phpgw_info']['flags']['noappfooter'] = True;
			$GLOBALS['phpgw_info']['flags']['app_header'] = $event['id'] ? lang('Editing event') : lang('Adding event');
			if ($param['plain'] != "True"){
				$GLOBALS['phpgw']->common->phpgw_header();
			}
			else{
				$GLOBALS['phpgw_info']['theme']['row_on'] = "F8F8F8";
				$GLOBALS['phpgw_info']['theme']['row_off'] = "F8F8F8";
			}

			$p = &$GLOBALS['phpgw']->template;
			$p->set_file(
				Array(
					'edit'		=> 'edit.tpl',
					'form_button'	=> 'form_button_script.tpl'
				)
			);
			$p->set_block('edit','edit_entry','edit_entry');
			$p->set_block('edit','list','list');
			$p->set_block('edit','hr','hr');
			
			$vars = Array(
				'font'			=> $this->theme['font'],
				'bg_color'		=> $this->theme['bg_text'],
				'action_url'		=> $GLOBALS['phpgw']->link('/index.php',Array('menuaction'=>'calendar.bocalendar.update')),
				'accounts_link'		=> $GLOBALS['phpgw']->link('/index.php','menuaction=calendar.uicalendar.accounts_popup'),
				'common_hidden'	=> '<input type="hidden" name="cal[id]" value="'.$event['id'].'">'."\n"
					. '<input type="hidden" name="cal[owner]" value="'.$event['owner'].'">'."\n"
					. '<input type="hidden" name="cal[uid]" value="'.$event['uid'].'">'."\n"
					. '<input type="hidden" name="lang_disponibility_map" value="'.lang('Disponibility map').'">'."\n"
					. '<input type="hidden" name="lang_your_search_argument_must_be_longer_than_4_characters" value="'.lang('Your search argument must be longer than 4 characters.').'">'."\n"
					. '<input type="hidden" name="lang_event_without_participants" value="'.lang('Event without participants').'">'."\n"
					. ($_GET['cal_id'] && $event['id'] == 0?'<input type="hidden" name="cal[reference]" value="'.$_GET['cal_id'].'">'."\n":
					(@isset($event['reference'])?'<input type="hidden" name="cal[reference]" value="'.$event['reference'].'">'."\n":''))
					. (@isset($GLOBALS['phpgw_info']['server']['deny_user_grants_access']) && $GLOBALS['phpgw_info']['server']['deny_user_grants_access']?
					'<input type="hidden" name="participants[]" value="'.$this->bo->owner.'">'."\n":''),
				'errormsg'		=> ($param['cd']?$GLOBALS['phpgw']->common->check_code($param['cd']):'')
			);
			
			$p->set_var($vars);

// Brief Description
			$var['title'] = Array(
				'tr_color' => $this->theme['th_bg'],
				'field'	=> lang('Title'),
				'data'	=> '<input name="cal[title]" size="45" maxlength="80" value="'.$event['title'].'">'
			);

// Full Description
			$var['description'] = Array(
				'field'	=> lang('Full Description'),
				'data'	=> '<textarea name="cal[description]" rows="5" cols="40" wrap="virtual" maxlength="2048">'.$event['description'].'</textarea>'
			);

// WebConference
			$accountDN = strtolower($GLOBALS['phpgw_info']['user']['account_dn']);

			if( !$event['id'] && strpos($accountDN, "ou=celepar") !== FALSE )
			{
				$checkedWebConf = ( array_key_exists('webconf', $event) ) ? "checked" : "";

				$var['webconf'] = Array(
					'field' => lang('web conference'),
					'data'	=>	'<div style="width:450px;height:35px; border:1px solid #cecece;">'.
									'<table style="margin-top:5px;">'.
										'<tr>'.
										'<td><input type="checkbox" name="cal[webconf]" '.$checkedWebConf.' value="true"></td>'.
										'<td><span style="color:red;font-weight:bold;font-size:11px">'.lang('check for meetings with web conferencing system').'</span></td>'.
										'</tr>'.
									'</table>'.
								'</div>'
				);
			}

// Display Categories
			if(strpos($event['category'],','))
			{
				$temp_cats = explode(',',$event['category']);
				@reset($temp_cats);
				while(list($key,$value) = each($temp_cats))
				{
					$check_cats[] = (int)$value;
				}
			}
			elseif($event['category'])
			{
				$check_cats[] = (int)$event['category'];
			}
			else
			{
				$check_cats[] = 0;
			}
			$var['category'] = Array(
				'field'	=> lang('Category'),
				'data'	=> '<select name="categories[]" onchange="javascript:updateTitleField(this)" multiple size="5">'.$this->cat->formated_list('select','all',$check_cats,True).'</select>'
			);

// Location
			$var['location'] = Array(
				'field'	=> lang('Location'),
				'data'	=> '<input name="cal[location]" size="45" maxlength="255" value="'.$event['location'].'">'
			);

// Date

			$start = $this->bo->maketime($event['start']) - $GLOBALS['phpgw']->datetime->tz_offset;
			$var['startdate'] = Array(
				'field'	=> lang('Start Date'),
/*
				'data'	=> $GLOBALS['phpgw']->common->dateformatorder(
				   $sb->getYears('start[year]',(int)$GLOBALS['phpgw']->common->show_date($start,'Y')),
				   $sb->getMonthText('start[month]',(int)$GLOBALS['phpgw']->common->show_date($start,'n')),
				   $sb->getDays('start[mday]',(int)$GLOBALS['phpgw']->common->show_date($start,'d'))
				)
*/
				'data' => $jscal->input('start[str]',$start)
			);

// Time
			if ($this->bo->prefs['common']['timeformat'] == '12')
			{
				$str .= '<input type="radio" name="start[ampm]" value="am"'.($event['start']['hour'] >= 12?'':' checked').'>am'."\n"
					. '<input type="radio" name="start[ampm]" value="pm"'.($event['start']['hour'] >= 12?' checked':'').'>pm'."\n";
			}
			$var['starttime'] = Array(
				'field'	=> lang('Start Time'),
				'data'	=> '<input name="start[hour]" id="start_hour" size="2" VALUE="'.$GLOBALS['phpgw']->common->show_date($start,$hourformat).'" maxlength="2">:<input name="start[min]" id="start_minute" size="2" value="'.$GLOBALS['phpgw']->common->show_date($start,'i').'" maxlength="2">'."\n".$str
			);

// End Date
			$end = $this->bo->maketime($event['end']) - $GLOBALS['phpgw']->datetime->tz_offset;
			$var['enddate'] = Array(
				'field'	=> lang('End Date'),
/*
				'data'	=> $GLOBALS['phpgw']->common->dateformatorder(
				   $sb->getYears('end[year]',(int)$GLOBALS['phpgw']->common->show_date($end,'Y')),
				   $sb->getMonthText('end[month]',(int)$GLOBALS['phpgw']->common->show_date($end,'n')),
				   $sb->getDays('end[mday]',(int)$GLOBALS['phpgw']->common->show_date($end,'d'))
				)
*/
				'data' => $jscal->input('end[str]',$end)
			);

// End Time
			if ($this->bo->prefs['common']['timeformat'] == '12')
			{
				$str = '<input type="radio" name="end[ampm]" value="am"'.($event['end']['hour'] >= 12?'':' checked').'>am'."\n"
					. '<input type="radio" name="end[ampm]" value="pm"'.($event['end']['hour'] >= 12?' checked':'').'>pm'."\n";
			}
			$var['endtime'] = Array(
				'field'	=> lang('End Time'),
				'data'	=> '<input name="end[hour]" id="end_hour" size="2" VALUE="'.$GLOBALS['phpgw']->common->show_date($end,$hourformat).'" maxlength="2">:<input name="end[min]" id="end_minute" size="2" value="'.$GLOBALS['phpgw']->common->show_date($end,'i').'" maxlength="2">'."\n".$str
			);

// Priority
			$var['priority'] = Array(
				'field'	=> lang('Priority'),
				'data'	=> $sb->getPriority('cal[priority]',$event['priority'])
			);

			// Access
			$var['access'] = Array(
			'field' => lang('Type'),
			'data'  => '<select onchange="javascript:changeViewMode(this.value);" id="cal[type]" name="cal[type]" ><option value="normal" '.(($event['public'] && $event['type'] == 'E')?'SELECTED':'').' >'.lang('Normal').'</option><option value="private" '.((!$event['public'] && $event['type'] == 'E')?'SELECTED':'').' >'. lang('Restrict').'</option><option value="privateHiddenFields" '.($event['type']=='P'?'SELECTED':'').' >'. lang('Private').'</option><option value="hourAppointment" '.($event['type'] == 'H'?'SELECTED':'').' >'.lang('Hours Appointment').'</option></select>'
			); //event['public']

			// Participants
			if(!isset($GLOBALS['phpgw_info']['server']['deny_user_grants_access']) || !$GLOBALS['phpgw_info']['server']['deny_user_grants_access'])
			{
				switch($GLOBALS['phpgw_info']['user']['preferences']['common']['account_selection'])
				{
					case 'popup':
						while (is_array($event['participants']) && list($id) = each($event['participants']))
						{
							if($id != (int)$event['owner'])
							{
								$str .= '<option value="' . $id.$event['participants'][$id] . '"'.($event['participants'][$id]?' selected':'').'>('.$GLOBALS['phpgw']->accounts->get_type($id)
										.') ' . $GLOBALS['phpgw']->common->grab_owner_name($id) . '</option>' . "\n";
							}
						}
						$var['participants'] = array
						(
							'field'	=> '<input type="button" value="' . lang('Participants') . '" onClick="accounts_popup();">' . "\n"
									. '<input type="hidden" name="accountid" value="' . $accountid . '">',
							'data'	=> "\n".'   <select name="participants[]" multiple size="7">' . "\n" . $str . '</select>'
						);
						break;
					default:
						foreach($event['participants'] as $id => $participant)
						{
							if (($id != $event['owner']) && ($id != ""))
							{
								$GLOBALS['phpgw']->accounts->get_account_name($id, $lid, $fname, $lname);
								$cn = $fname.' '.$lname;							
								$option = '    <option  value="' . $id.$participant . '">'.$cn.'</option>'."\n";
								$str .= $option;
							} 								
						}
						$str = utf8_decode($str);
						$footer_ext_participantes =	'<br>&nbsp;&nbsp;'.lang("The email addresses must be separated by ','");						
						
						$var['participants'] = array
						(
							'field'	=> lang('Participants'),							
							'data'	=> "
									<table width='100%' border='0'>
										<tr>
											<td width='30%'>										
												<center>Participantes</center>												
											</td>
											<td width='8%' >&nbsp;</td>
											<td width='40%'>&nbsp;</td>
										</tr>																		
										<tr>		
											<td width='30%'>											
												<center><select id='user_list' name='participants[]' style='width: 220px' multiple size='7'>".$str."</select></center>										
											</td>				
											<td width='8%'>
											<center>
												<table width='100%' border='0'>									
													<tr height='5'><td>&nbsp;</td></tr>									
													<tr><td align='center'>									
														<button type='button' onClick='javascript:openListUsers(340,533, "
														.$event['owner'].
														")'><img src='calendar/templates/".$_SESSION['phpgw_info']['calendar']['user']['preferences']['common']['template_set']."/images/add.png' style='vertical-align: middle;' >&nbsp;Adicionar</button>
														</td>
													</tr>							
													<tr height='5'><td>&nbsp;</td></tr>
													<tr><td  align='center'>
														<button type='button' onClick='javascript:rem()'><img src='calendar/templates/".$_SESSION['phpgw_info']['calendar']['user']['preferences']['common']['template_set']."/images/rem.png' style='vertical-align: middle;' >&nbsp;Remover</button>
														</td>
													</tr>
												</table>
											</center>
											</td>
											<td width='40%'>&nbsp;</td>
										</tr>
										</table>
										<script src='calendar/js/edit.js' type='text/javascript'></script>
									"
						);
						// if ExpressoMail 1.2 has been installed and enabled, show the plugin using AJAX. 
						if($GLOBALS['phpgw_info']['server']['cal_expressoMail']) {							
							$module_name = 'expressoMail'.(str_replace("1.","1_",$GLOBALS['phpgw_info']['server']['cal_expressoMail']));

							if($GLOBALS['phpgw_info']['user']['apps'][$module_name]){								
								$ldap_manager = CreateObject('contactcenter.bo_ldap_manager');
								$_SESSION['phpgw_info']['expressomail']['user'] = $GLOBALS['phpgw_info']['user'];
								$_SESSION['phpgw_info']['expressomail']['user']['owner'] = $event['owner'];
								$_SESSION['phpgw_info']['expressomail']['server'] = $GLOBALS['phpgw_info']['server'];
								$_SESSION['phpgw_info']['expressomail']['ldap_server'] = $ldap_manager ? $ldap_manager->srcs[1] : null;
								$context = $GLOBALS['phpgw_info']['server']['ldap_context'];
								$user_context = array();
								foreach(explode(",",$GLOBALS['phpgw_info']['user']['account_dn']) as $i => $dn_part){
									if($i)
										$user_context[] = $dn_part;
								}
								// Prepara o contexto do usuario com sua OU raiz, pois ele pode pertencer a uma OU de nivel N.
								$user_ou = explode(",",str_replace($context,"",implode(",",$user_context)));								
								$user_context = trim(strtolower($user_ou[count($user_ou) - 2].",".$context));
								// Fim		
								// Verifica o tipo da visualiza��o da �rvore LDAP, configurado no admin da Agenda
								$recursive = $GLOBALS['phpgw_info']['server']['cal_type_tree_participants'] == '1' ? true : false;
								//$combo_org = $this->get_organizations(trim(strtolower($context)),$user_context, $recursive);
								$footer_ext_participantes = lang("Tip: To search in the <b>Global Catalog</b>, type the <b>F9</b> key, like the ExpressoMail.");
								//Caso o minimo de caracteres para a busca nao tenha sido preenchido ele sera por padrao = 3
								if(!$GLOBALS['phpgw_info']['server']['min_num_characters']){
									$GLOBALS['phpgw_info']['server']['min_num_characters'] = 0;
								}

								if ($GLOBALS['phpgw_info']['server']['cal_type_tree_participants'] == '3'){
									$combo_org = '<option value='.$context.'>'.strtoupper($context).'</option>';
								}else{
									$combo_org = $this->get_organizations(trim(strtolower($context)),$user_context, $recursive);
								}

								if ($param['plain'] != "True"){

									// Begin load array lang
									ob_start();
									//@include($module_name.'/inc/load_lang.php');
									$load_lang_vars = ob_get_contents();
									ob_end_clean();
									// End load array_lang
								}

								$var['participants'] = array
								(
									'field'	=> '
									<script src="calendar/templates/'.$_SESSION['phpgw_info']['calendar']['user']['preferences']['common']['template_set'].'/js/edit.js" type="text/javascript"></script><div id=\'disponibility\' style=\'display:none;position:absolute\' bgcolor=\'#FFFFFF\'>
	<iframe src=\'#\' id=\'frame_disponibility\' width=\'500\' marginHeight=\'0\' marginWidth=\'0\' height=\'200px\'></iframe>
</div>
									'.lang('Participants'),
									'data'	=> '
											<input type="hidden" id="txt_loading" value="'.lang("Loading").'">' .
											'<input type="hidden" id="txt_searching" value="'.lang("Searching").'">' .
											'<input type="hidden" id="txt_users" value="'.lang("Users").'">' .							
											'<input type="hidden" id="txt_groups" value="'.lang("Groups").'">' .
											'<table width="100%" border="0">'.
											'<tr>'.
											'<td width="25%"><br><br>'.
											'<br><br>&nbsp;&nbsp;<b>'.lang("Event's participants").'</b><br>'.
											'	<select id="user_list" name="participants[]" style="width: 300px" multiple size="13">'.$str.'</select>'.
											'</td>'.
											'<td width="30px" valign="middle" align="center">&nbsp;'.
											'</td>'.
											'<td valign="bottom">'.
											'	'.lang("Organization").': '.
											'	<select name="org_context" id="combo_org" onchange="javascript:get_available_users(\''.$module_name.'\',this.value,\''.($recursive ? "" : "search").'\',\''.$GLOBALS['phpgw_info']['server']['auto_search'].'\');">'.$combo_org.'</select>'.
											'	<br>'.
											'	<font color="red"><span id="cal_span_searching">&nbsp;</span></font>'.									
											'	<br>'.lang("Search for").':'.
											'	<input value="" id="cal_input_searchUser" size="35" autocomplete="off" onkeyup="javascript:optionFinderTimeout(this,'.$GLOBALS['phpgw_info']['server']['min_num_characters'].',\''.($recursive ? "" : "search").'\',\''.$GLOBALS['phpgw_info']['server']['auto_search'].'\')"><br>'.
											'	<b>'.lang("Available users and groups").'</b><br>'.
											'	<select id="user_list_in" style="width: 300px" multiple size="13"></select>'.
											'</td>'.
											'</tr>'.
                                            '<tr>'.
                                            '<td width="25%">'.
                                            '<button type="button" id="time_map" onClick="javascript:show_disponibility();">'.lang("Disponibility map").'</button>'.
                                            '</td>'.
                                            '<td width="30px" valign="middle" align="center">&nbsp;'.
											'</td>'.
                                            '<td valign="bottom">'.
                                            '<button type="button" onClick="javascript:add_user();"><img src="calendar/templates/'.$GLOBALS['phpgw_info']['user']['preferences']['common']['template_set'].'/images/add.png" style="vertical-align: middle;" >&nbsp;'.lang("Add").'</button>'.
											'&nbsp;&nbsp;<button type="button" onClick="javascript:remove_user();"><img src="calendar/templates/'.$GLOBALS['phpgw_info']['user']['preferences']['common']['template_set'].'/images/rem.png" style="vertical-align: middle;" >&nbsp;'.lang("Remove").'</button>'.
                                            '</td>'.
											'</tr>'.
											'</table>'. 
											'<script type="text/javascript" src="phpgwapi/js/wz_dragdrop/wz_dragdrop.js"></script>'.
											'<script type="text/javascript" src="phpgwapi/js/dJSWin/dJSWin.js"></script>'.		
											"<script src='calendar/js/edit_exmail.js' type='text/javascript'></script>" .
											$load_lang_vars.																			
											"<script src='".$module_name."/js/connector.js' type='text/javascript'></script>".
											"<script type='text/javascript'>var DEFAULT_URL = '".$module_name."/controller.php?action=';</script> ".											
											"<script type='text/javascript'>" .
											"var timeout_get_available_users = setTimeout('get_available_users(\"".$module_name."\",\'".$user_context."\',\'".($recursive ? "" : "search")."\',\'".$GLOBALS['phpgw_info']['server']['auto_search']."\')',1000);". 
											"</script> "
								);
							}						
						}						
						
						$var['participants']['data'] .= '<br /><a id="a_ext_participants" title="'.lang("It types below the email addresses, if you want to invite other people out" .
							" of this system").'" name="a_ext_participants" onClick="javascript:showExParticipants(this,\''.$module_name.'\')" href="#a_ext_participants"><b>'.lang("Inform").' '.lang("external participants").'</b>&nbsp;<img align="top" ' .
							'src="calendar/templates/'.$GLOBALS['phpgw_info']['user']['preferences']['common']['template_set'].'/images/sent.gif"></a><table id="tbl_ext_participants" width="100%" border="0" style="display:none">'.
							'<tr>'.
							'<td>'.	
							'&nbsp;&nbsp;<b>'.lang("external participants").'</b>&nbsp;<img align="top" src="calendar/templates/'.$GLOBALS['phpgw_info']['user']['preferences']['common']['template_set'].'/images/sent.gif">' .
							'&nbsp;&nbsp;<a title="'.lang("Close").'" name="b_ext_participants" onClick="javascript:hideExParticipants(this,\''.$module_name.'\')" href="#a_ext_participants">['.lang("Close").']</a>&nbsp;'.														
							'</td>'.
							'</tr>'.
							'<tr>'.
							'<td>'.	
							'&nbsp;&nbsp;>> '.lang("It types below the email addresses, if you want to invite other people out of this system").':'.
							'&nbsp;&nbsp;<br><textarea name="ex_participants" id="ex_participants" cols="70" rows="2">'.$event['ex_participants'].'</textarea><br>&nbsp;&nbsp;'.
							$footer_ext_participantes.
							'</tr>'.							
							'</table>';
						break;
						
				}
				if (isset($var['participants']['data']))
					$var['participants']['data'] .=
					'<div id="sms-content" style="margin:4px;'.$sms_enabled.'">'.
						'<table class="prefTable" style="padding:4px; width:100%">'.
							'<tr><td colspan="2"><b>'.lang('notify by sms').':</b></td></tr>'.
							'<tr><td><input type="checkbox" name="sms_check_owner"/></td><td style="width:100%">'.lang('owner').' ('.$GLOBALS['phpgw']->common->grab_owner_name($event['owner']).')</td></tr>'.
							'<tr><td><input type="checkbox" name="sms_check_parts" checked/></td><td>'.lang('Event\'s Participants').'</td></tr>'.
						'</table>'.
					'</div>';
				
				if((($event['id'] > 0) && isset($event['participants'][$event['owner']])) || !$event['id'])
				{
					$checked = ' checked';
				}
				else
				{
					$checked = '';
				}
				$var['owner'] = Array(
					'field'	=> $GLOBALS['phpgw']->common->grab_owner_name($event['owner']).' '.lang('Participates'),
					'data'	=> '<input type="checkbox" id="usuarioParticipa" name="participants[]" value="'.$event['owner'].$event['participants'][$event['owner']].'"'.$checked.'>'
				);
			}

// Reminder
			// The user must use "Alarm Management" to change/modify an alarm
			// so only display the email reminder fields if this is a new event
			// i.e. not editing an existing event

			if ($event['id'] == 0) {
				// get defaults
				$days = $this->bo->prefs['calendar']['default_email_days'];
				$hours = $this->bo->prefs['calendar']['default_email_hours'];
				$min = $this->bo->prefs['calendar']['default_email_min'];
				if (count($event['alarm']) > 1)
				{
					// this should not happen because when creating a new event
					// only 1 alarm is displayed on the screen
					// if the user wants more than 1 alarm they should
					// use "Alarm Management"
					echo '<!-- how did this happen, too many alarms -->'."\n";
				}
				// if there was an error pick up what the user entered
				if (@isset($event['alarm']))
				{
					@reset($event['alarm']);
					// just get the first one see above!!!
					list($key,$alarm) = @each($event['alarm']);


					//if abaixo trata o caso de um evento repetido nao ter uma data final;
					//se isto acontecer, nao havera alarmes definidos;
					//$alarm['time'] recebe $start para $days, $hours e $min ficarem iguais a 0
					if(!$alarm['time']) {
						$alarm['time'] = $start;
					}

					$diff  = $start - $alarm['time'];
					$days  = (int)($diff / (24*3600));
					$hours = (int)(($diff - ($days * 24 * 3600))/3600);
					$min   = (int)(($diff - ($days * 24 * 3600) - ($hours * 3600))/60);

					if(@isset($_POST['edit_type']) && $_POST['edit_type'] == 'single') {
						$days = $diff;
					}
				}

				// days
				$dout = '<select name="cal[alarmdays]">'."\n";
				for($i=0;$i<32;$i++)
				{
					$dout .= '<option value="'.$i.'"'.($i==$days?' selected':'').'>'.$i.'</option>'."\n";
				}
				$dout .= '</select>'."\n".' '.lang('days').' ';
				// hours
				$hout = '<select name="cal[alarmhours]">'."\n";
				for($i=0;$i<25;$i++)
				{
					$hout .= '<option value="'.$i.'"'.($i==$hours?' selected':'').'>'.$i.'</option>'."\n";
				}
				$hout .= '</select>'."\n".' '.lang('hours').' ';
				// minutes
				$mout = '<select name="cal[alarmminutes]">'."\n";
				for($i=0;$i<61;$i++)
				{
					$mout .= '<option value="'.$i.'"'.($i==$min?' selected':'').'>'.$i.'</option>'."\n";
				}
				$mout .= '</select>'."\n".' '.lang('minutes').' ';

				$var['alarm'] = Array(
					'field' => lang('Alarm'),
					'data'	=> $dout.$hout.$mout.lang('before the event')
				);

			}

// Repeat Type
			$str = '';
			foreach($this->bo->rpt_type as $type => $label)
			{
				$str .= '<option value="'.$type.'"'.($event['recur_type']==$type?' selected':'').'>'.lang($label).'</option>';
			}
			$var['recure_type'] = Array(
				'field'	=> lang('Repeat Type'),
				'data'	=> '<select name="cal[recur_type]">'."\n".$str.'</select>'."\n"
			);

			if($event['recur_enddate']['year'] != 0 && $event['recur_enddate']['month'] != 0 && $event['recur_enddate']['mday'] != 0)
			{
				$checked = ' checked';
				$recur_end = $this->bo->maketime($event['recur_enddate']) - $GLOBALS['phpgw']->datetime->tz_offset;
			}
			else
			{
				$checked = '';
				$recur_end = $this->bo->maketime($event['start']) + 86400 - $GLOBALS['phpgw']->datetime->tz_offset;
			}

			$var['recure_enddate'] = Array(
				'field'	=> lang('Repeat End Date'),
				'data'	=> '<input type="checkbox" name="cal[rpt_use_end]" value="y"'.$checked.'>'.lang('Use End Date').'  '.
/*
					$GLOBALS['phpgw']->common->dateformatorder(
						$sb->getYears('recur_enddate[year]',(int)$GLOBALS['phpgw']->common->show_date($recur_end,'Y')),
						$sb->getMonthText('recur_enddate[month]',(int)$GLOBALS['phpgw']->common->show_date($recur_end,'n')),
						$sb->getDays('recur_enddate[mday]',(int)$GLOBALS['phpgw']->common->show_date($recur_end,'d'))
					)
*/
					$jscal->input('recur_enddate[str]',$recur_end)
			);

			$i = 0; $boxes = '';
			foreach ($this->bo->rpt_day as $mask => $name)
			{
				$boxes .= '<input type="checkbox" name="cal[rpt_day][]" value="'.$mask.'"'.($event['recur_data'] & $mask ? ' checked' : '').'>&nbsp;'.lang($name)."\n";
				if (++$i == 5) $boxes .= '<br>';
			}
			$var['recure_day'] = Array(
				'field'	=> lang('Repeat Day').'<br>'.lang('(required for weekly recursivity)'),
				'data'	=> $boxes
			);

			$var['recure_interval'] = Array(
				'field'	=> lang('Interval'),
				'data'	=> '<input name="cal[recur_interval]" size="4" maxlength="4" value="'.$event['recur_interval'].'">'
			);
//			$this->output_template_array($p,'row','list',Array('data' => '<script src="simple_show_hide.js" type="text/javascript"></script>'));

			if (!isset($this->fields))
			{
				$this->custom_fields = CreateObject('calendar.bocustom_fields');
				$this->fields = &$this->custom_fields->fields;
				$this->stock_fields = &$this->custom_fields->stock_fields;
			}
			$this->output_template_array($p,'row','list',$var['access']);
			unset($var['access']);

			$preserved = False;
			foreach($this->fields as $field => $data)
			{
				if (!$data['disabled'])
				{
					if (isset($var[$field]))
					{
						switch($field)
						{
							case 'startdate':
								$this->output_template_array($p,'row','list',$var['startdate']);
								$this->output_template_array($p,'row','list',$var['starttime']);
								break;
							case 'enddate':
								$this->output_template_array($p,'row','list',$var['enddate']);
								$this->output_template_array($p,'row','list',$var['endtime']);
								break;
							case 'recure_type':
								$p->set_var('tr_color',$this->theme['th_bg']);
								$p->set_var('hr_text','<center id="rpt_label"><b>'.lang('Repeating Event Information').'</b></center>');
								$p->parse('row','hr',True);
								$this->output_template_array($p,'row','list',$var['recure_type']);
								$this->output_template_array($p,'row','list',$var['recure_enddate']);
								$this->output_template_array($p,'row','list',$var['recure_day']);
								$this->output_template_array($p,'row','list',$var['recure_interval']);
								break;
							default:
								$this->output_template_array($p,'row','list',$var[$field]);
						}
					}
					elseif (!isset($this->stock_fields[$field]))	// Custom field
					{
						$lang = lang($name = substr($field,1));
						$size = 'SIZE='.($data['shown'] ? $data['shown'] : ($data['length'] ? $data['length'] : 30)).
							' MAXLENGTH='.($data['length'] ? $data['length'] : 255);
						$v = array(
							'field'	=> $lang == $name.'*' ? $name : $lang,
							'data'	=> '<input name="cal['.htmlspecialchars($field).']" '.$size.' value="'.$event['#'.$name].'">'
						);
						if ($data['title'])
						{
							$v['tr_color'] = $this->theme['th_bg'];
						}
						if (!$data['length'] && $data['title'])
						{
							$p->set_var('tr_color',$this->theme['th_bg']);
							$p->set_var('hr_text','<center><b>'.$v['field'].'</b></center>');
							$p->parse('row','hr',True);
						}
						else
						{
							$this->output_template_array($p,'row','list',$v);
						}
					}
				}
				else	// preserve disabled fields
				{
					switch ($field)
					{
						case 'owner':
							$preserved[$field] = $event['id'] ? $event['participants'][$event['owner']] : 'A';
							break;
						case 'recure_type':
							foreach(array('recur_type','recur_enddate','recur_data','recur_interval') as $field)
							{
								$preserved[$field] = $event[$field];
							}
							break;
						case 'startdate':
						case 'enddate':
							$field = substr($field,0,-4);
						default:
							$preserved[$field] = $event[$field];
					}
				}
			}
			unset($var);
			if (is_array($preserved))
			{
				//echo "preserving<pre>"; print_r($preserved); echo "</pre>\n";
				$p->set_var('common_hidden',$p->get_var('common_hidden').'<input type="hidden" name="preserved" value="'.htmlspecialchars(serialize($preserved)).'">'."\n");
			}
			$p->set_var('submit_button',lang('Save'));

			$delete_button = $cancel_button = '';
			if ($event['id'] > 0)
			{
				$var = Array(
					'action_url_button'	=> $this->page('delete','&cal_id='.$event['id']),
					'action_text_button'	=> lang('Delete'),
					'action_confirm_button'	=> "onClick=\"return confirm('".lang('Are you sure want to delete this entry?\\nThis will delete this entry for all users.')."')\"",
					'action_extra_field'	=> '',
					'button_id'	=>	'delete_button'
				);
				$p->set_var($var);
				$delete_button = $p->fp('out','form_button');
			}
			$p->set_var('delete_button',$delete_button);
			$p->set_var('alert_msg',lang('Required field (category) is empty'));

			if ($this->bo->return_to)
			{
				$var = Array(
					'action_url_button'	=> $GLOBALS['phpgw']->link('/index.php','menuaction='.$this->bo->return_to),
					'action_text_button'	=> lang('Cancel'),
					'action_confirm_button'	=> '',
					'action_extra_field'	=> '',
					'name_button'			=> 'name="back_calendar"',
					'button_id'	=>	'cancel_button'
				);
				$p->set_var($var);
				$cancel_button = $p->fp('out','form_button');
			}
			$p->set_var('cancel_button',$cancel_button);
			$p->pparse('out','edit_entry');
		}
		
		// modify list of an event's external participants (i.e. non pgpgw users)
		//
		function modify_ext_partlist()
		{
			$GLOBALS['phpgw_info']['flags']['noheader'] = True;
			$GLOBALS['phpgw_info']['flags']['nonavbar'] = True;
			$GLOBALS['phpgw_info']['flags']['noappheader'] = True;
			$GLOBALS['phpgw_info']['flags']['noappfooter'] = True;

			$total_contacts = 0;
			$participant = array();
			$control_data= array();

			$control_data['action'] = '';
			$control_data['delete'] = array();
			$control_data['part'] = array();

			$p = CreateObject('phpgwapi.Template',$this->template_dir);
			$p->set_file(
				Array(
					'T_edit_partlist' => 'edit_partlist.tpl',
					'T_edit_partlist_blocks' => 'edit_partlist_blocks.tpl'
				)
			);

			$p->set_block('T_edit_partlist_blocks','B_alert_msg','V_alert_msg');
			$p->set_block('T_edit_partlist_blocks','B_partlist','V_partlist');
			$p->set_block('T_edit_partlist_blocks','B_participants_none','V_participants_none');
			$p->set_block('T_edit_partlist_blocks','B_delete_btn','V_delete_btn');

			global $query_addr;
			$sb = CreateObject('phpgwapi.sbox2');
			$addy = $sb->getAddress('addr','',$query_addr);

			$add_ext  = $addy['doSearchFkt'];
			$add_ext .= $addy['addr_title']!=lang('Address Book')?$addy['addr_title']:'';
			$add_ext .= "&nbsp;".$addy['addr'].$addy['addr_nojs'];

			$p->set_var('text_add_name',$add_ext);

			if(isset($_GET['part']) && $_GET['part'])
			{
				$control_data['part'] = explode(",", $_GET['part']);
			}
			else
			{
				$control_data['part'] = $_POST['participant'];
				$control_data['action'] = $_POST['action'];
				$control_data['delete'] = $_POST['delete'];
			}

			for ($i=0; $i<count($control_data['part']); $i++)
			{
				$id = $control_data['part'][$i];
				list($contact) = $this->read_contact($id);

				$participant[$id] = array();
				$participant[$id]['name'] = $contact['n_given'].' '.$contact['n_family'];
			}

			if ($control_data['action'] == lang('Delete selected contacts'))
			{
				for ($i=0; $i<count($control_data['delete']); $i++)
				{
					$id = $control_data['delete'][$i];
					unset($participant[$id]);
				}
			}

			if ($control_data['action'] == lang('Add Contact'))
			{
				$id = $_POST['id_addr'];
				if (isset($id) && (int)$id != 0)
				{
					list($contact) = $this->read_contact($id);
					$participant[$id] = array();
					$participant[$id]['name'] = $contact['n_given'].' '.$contact['n_family'];
				}
			}

			// create list of currently selected contacts
			//
			while(list($id,$contact) = each($participant))
			{
				$p->set_var('hidden_delete_name','participant[]');
				$p->set_var('hidden_delete_value',$id);
				$p->set_var('ckbox_delete_name','delete[]');
				$p->set_var('ckbox_delete_value',$id);
				$p->set_var('ckbox_delete_participant',$contact['name']);
				$p->parse('V_partlist','B_partlist',True);
				$total_contacts++;
			}

			if ($total_contacts == 0)
			{
				// no contacts have been selected
				// => clear the delete form, remove delete button and show the none block
				//
				$p->set_var('V_partlist','');
				$p->set_var('V_delete_btn','');
				$p->set_var('text_none',lang('None'));
				$p->parse('V_participants_none','B_participants_none');
			}
			else
			{
				// at least one contact has been selected
				// => clear the none block, fill the delete form and add delete button
				//
				$p->set_var('V_participants_none','');
				$p->set_var('btn_delete_name','action');
				$p->set_var('btn_delete_value',lang('Delete selected contacts'));
				$p->parse('V_delete_btn','B_delete_btn');
			}

			$body_tags  = 'bgcolor="'.$GLOBALS['phpgw_info']['theme']['bg_color']
							. '" alink="'.$GLOBALS['phpgw_info']['theme']['alink']
							. '" link="'.$GLOBALS['phpgw_info']['theme']['link']
							.'" vlink="'.$GLOBALS['phpgw_info']['theme']['vlink'].'"';

			$form_action = $GLOBALS['phpgw']->link('/index.php', array('menuaction' => 'calendar.uicalendar.modify'));

			$charset = lang('charset');
			$p->set_var('charset',$charset);
			$p->set_var('page_title',$GLOBALS['phpgw_flags']['currentapp']
				. ' - ' .lang('External Participants'));
			$p->set_var('font_family',$GLOBALS['phpgw_info']['theme']['font']);
			$p->set_var('body_tags',$body_tags);
			$p->set_var('form_method','POST');
			$p->set_var('form_action',$form_action);
			$p->set_var('text_add_contact',lang('External Participants'));
			$p->set_var('text_contacts_selected',lang('Selected contacts (%1)',$total_contacts));
			$p->set_var('btn_add_name','action');
			$p->set_var('btn_add_value',lang('Add Contact'));
			$p->set_var('btn_done_name','done');
			$p->set_var('btn_done_value',lang('Done'));
			$p->set_var('btn_done_js','copyback()');
			$p->set_var('form1_name','ext_form');

			$p->pfp('out','T_edit_partlist');
		}

		function read_contact($id)
		{
			$query_fields = Array(
				'n_given' => 'n_given',
				'n_family' => 'n_family',
				'email' => 'email',
				'email_home' => 'email_home'
			);

			/*
			if ($this->rights & PHPGW_ACL_READ)
			{
				return $this->contacts->read_single_entry($id,$fields);
			}
			else
			{
				$rtrn = array(0 => array('No access' => 'No access'));
				return $rtrn;
			}
			*/

			$contacts = CreateObject('phpgwapi.contacts', False);
			return $contacts->read_single_entry($id,$query_fields);
		}

		function build_part_list(&$users,$accounts,$owner)
		{
			if(!is_array($accounts))
			{
				return;
			}
			foreach($accounts as $id)
			{
				$id = (int)$id;
				if($id == $owner)
				{
					continue;
				}
				elseif(!isset($users[$id]))
				{
					if($GLOBALS['phpgw']->accounts->exists($id) == True)
					{
						$users[$id] = Array(
							'name'	=> $GLOBALS['phpgw']->common->grab_owner_name($id),
							'type'	=> $GLOBALS['phpgw']->accounts->get_type($id)
						);
					}
					if($GLOBALS['phpgw']->accounts->get_type($id) == 'g')
					{
						//$this->build_part_list($users,$GLOBALS['phpgw']->acl->get_ids_for_location($id,1,'phpgw_group'),$owner);
					}
				}
			}
			if (!function_exists('strcmp_name'))
			{
				function strcmp_name($arr1,$arr2)
				{
					if ($diff = strcmp($arr1['type'],$arr2['type']))
					{
						return $diff;	// groups before users
					}
					return strnatcasecmp($arr1['name'],$arr2['name']);
				}
			}
			uasort($users,'strcmp_name');
		}

		function set_week_array($startdate,$cellcolor,$weekly)
		{
			$data = date("m");
			for ($j=0,$datetime=$startdate;$j<7;$j++,$datetime += 86400)
			{
				$date = date('Ymd',$datetime + (60 * 60 * 2)); // +2h to be save when switching to and from dst, $datetime is alreay + TZ-Offset
				print_debug('set_week_array : Date ',$date);

				if($events = $this->bo->cached_events[$date])
				{
					print_debug('Date',$date);
					print_debug('Appointments Found',count($events));

					if (!$this->bo->prefs['calendar']['show_rejected'])
					{
						$appts = False;
						foreach($events as $event)	// check for a not-rejected event
						{
							if (!$this->bo->rejected_no_show($event))
							{
								$appts = True;
								break;
							}
						}
					}
					else
					{
						$appts = True;
					}
				}
				else
				{
					$appts = False;
				}
				$holidays = $this->bo->cached_holidays[$date];
				if($weekly)
				{
					$cellcolor = $GLOBALS['phpgw']->nextmatchs->alternate_row_color($cellcolor);
				}

				$day_image = '';
				if($holidays)
				{
					$extra = ' bgcolor="'.$this->bo->holiday_color.'"';
					$class = ($appts?'b':'').'minicalhol';
					if ($date == $this->bo->today)
					{
						$day_image = ' background="'.$GLOBALS['phpgw']->common->image('calendar','mini_day_block').'"';
					}
				}
				elseif ($date != $this->bo->today)
				{
					$extra = ' bgcolor="'.$cellcolor.'"';
					$class = ($appts?'b':'').'minicalendar';
				}
				else
				{
					$extra = ' bgcolor="'.$GLOBALS['phpgw_info']['theme']['cal_today'].'"';
					$class = ($appts?'b':'').'minicalendar';
					$day_image = ' background="'.$GLOBALS['phpgw']->common->image('calendar','mini_day_block').'"';
				}

				if($this->bo->printer_friendly && @$this->bo->prefs['calendar']['print_black_white'])
				{
					$extra = '';
				}

				if(!$this->bo->printer_friendly && $this->bo->check_perms(PHPGW_ACL_ADD))
				{
					$new_event = True;
				}
				else
				{
					$new_event = False;
				}
				$holiday_name = Array();
				if($holidays)
				{
					for($k=0;$k<count($holidays);$k++)
					{
						$holiday_name[] = $holidays[$k]['name'];
					}
				}
				$week = '';
				if (!$j || (!$weekly && $j && substr($date,6,2) == '01'))
				{
					$week = lang('week').' '.(int)((date('z',($startdate+(24*3600*4)))+7)/7);
				}
				$daily[$date] = Array(
					'extra'		=> $extra,
					'new_event'	=> $new_event,
					'holidays'	=> $holiday_name,
					'appts'		=> $appts,
					'week'		=> $week,
					'day_image'	=> $day_image,
					'class'		=> $class
				);
			}

			if($this->debug)
			{
				_debug_array($daily);
			}

			return $daily;
		}
		
		function get_organizations($context, $selected='', $recursive = false)
		{
			$s = CreateObject('phpgwapi.sector_search_ldap');
						
			return ($recursive ? 
				$s->get_organizations($context, $selected, false , false, false, true) :
				$s->get_sectors($selected, false, false, true));
		}		
	}
?>
