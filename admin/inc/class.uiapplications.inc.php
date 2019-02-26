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


	class uiapplications
	{
		var $public_functions = array(
			'get_list' => True,
			'add'      => True,
			'edit'     => True,
			'delete'   => True,
			'register_all_hooks' => True
		);

		var $bo;
		var $nextmatchs;

		function uiapplications()
		{
			$this->bo = CreateObject('admin.boapplications');
			$this->nextmatchs = CreateObject('phpgwapi.nextmatchs');
		}

		function get_list()
		{
			if (GlobalService::get('phpgw')->acl->check('applications_access',1,'admin'))
			{
				GlobalService::get('phpgw')->redirect_link('/index.php');
			}
			$can_add    = !GlobalService::get('phpgw')->acl->check('applications_access',2,'admin');
			$can_edit   = !GlobalService::get('phpgw')->acl->check('applications_access',4,'admin');
			$can_delete = !GlobalService::get('phpgw')->acl->check('applications_access',8,'admin');
			
			GlobalService::get('phpgw_info')['flags']['app_header'] = lang('Admin').' - '.lang('Installed applications');
			if(!@is_object(GlobalService::get('phpgw')->js))
			{
				GlobalService::get('phpgw')->js = CreateObject('phpgwapi.javascript');
			}
			GlobalService::get('phpgw')->js->validate_file('jscode','openwindow','admin');
			GlobalService::get('phpgw')->common->phpgw_header();
			echo parse_navbar();

			GlobalService::get('phpgw')->template->set_file(array('applications' => 'applications.tpl'));
			GlobalService::get('phpgw')->template->set_block('applications','list','list');
			GlobalService::get('phpgw')->template->set_block('applications','row','row');
			GlobalService::get('phpgw')->template->set_block('applications','add','add');
			
			$start = get_var('start',array('POST','GET'));
			$sort  = $_GET['sort'];
			$order = $_GET['order'];
			$offset = GlobalService::get('phpgw_info')['user']['preferences']['common']['maxmatchs'];

			$apps = $this->bo->get_list();
			$total = count($apps);

			$sort = $sort ? $sort : 'ASC';

			uasort($apps,create_function('$a,$b','return strcasecmp($a[\'title\'],$b[\'title\'])'.($sort != 'ASC' ? '* -1' : '').';'));

			if ($start && $offset)
			{
				$limit = $start + $offset;
			}
			elseif ($start && !$offset)
			{
				$limit = $start;
			}
			elseif(!$start && !$offset)
			{
				$limit = $total;
			}
			else
			{
				$start = 0;
				$limit = $offset;
			}

			if ($limit > $total)
			{
				$limit = $total;
			}

			$i = 0;
			$applications = array();
			while(list($app,$data) = @each($apps))
			{
				if($i >= $start && $i< $limit)
				{
					$applications[$app] = $data;
				}
				$i++;
			}

			GlobalService::get('phpgw')->template->set_var('bg_color',GlobalService::get('phpgw_info')['theme']['bg_color']);
			GlobalService::get('phpgw')->template->set_var('th_bg',GlobalService::get('phpgw_info')['theme']['th_bg']);

			GlobalService::get('phpgw')->template->set_var('sort_title',$this->nextmatchs->show_sort_order($sort,'title','title','/index.php',lang('Title'),'&menuaction=admin.uiapplications.get_list'));
			GlobalService::get('phpgw')->template->set_var('lang_showing',$this->nextmatchs->show_hits($total,$start));
			GlobalService::get('phpgw')->template->set_var('left',$this->nextmatchs->left('/index.php',$start,$total,'menuaction=admin.uiapplications.get_list'));
			GlobalService::get('phpgw')->template->set_var('right',$this->nextmatchs->right('index.php',$start,$total,'menuaction=admin.uiapplications.get_list'));

			GlobalService::get('phpgw')->template->set_var('lang_edit',lang('Edit'));
			GlobalService::get('phpgw')->template->set_var('lang_delete',lang('Delete'));
			GlobalService::get('phpgw')->template->set_var('lang_enabled',lang('Enabled'));

			GlobalService::get('phpgw')->template->set_var('new_action',GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiapplications.add'));
			GlobalService::get('phpgw')->template->set_var('lang_note',lang('(To install new applications use<br><a href="setup/" target="setup">Setup</a> [Manage Applications] !!!)'));
			GlobalService::get('phpgw')->template->set_var('lang_add',lang('add'));

			foreach($applications as $app)
			{
				$tr_color = $this->nextmatchs->alternate_row_color($tr_color);

				GlobalService::get('phpgw')->template->set_var('tr_color',$tr_color);
				GlobalService::get('phpgw')->template->set_var('name',$app['title']);

				GlobalService::get('phpgw')->template->set_var('edit',$can_edit ? '<a href="' . GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiapplications.edit&app_name=' . urlencode($app['name'])) . '&start='.$start.'"> ' . lang('Edit') . ' </a>' : '&nbsp;');
				GlobalService::get('phpgw')->template->set_var('delete',$can_delete ? '<a href="' . GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiapplications.delete&app_name=' . urlencode($app['name'])) . '&start='.$start.'"> ' . lang('Delete') . ' </a>' : '&nbsp;');

				if ($app['status'])
				{
					$status = lang('Yes');
				}
				else
				{
					$status = '<b>' . lang('No') . '</b>';
				}
				GlobalService::get('phpgw')->template->set_var('status',$status);

				GlobalService::get('phpgw')->template->parse('rows','row',True);
			}
			if ($can_add)
			{
				GlobalService::get('phpgw')->template->parse('addbutton','add');
			}
			else
			{
				GlobalService::get('phpgw')->template->set_var('addbutton','');
			}

			GlobalService::get('phpgw')->template->pparse('out','list');
		}

		function display_row($label, $value)
		{
			GlobalService::get('phpgw')->template->set_var('tr_color',$this->nextmatchs->alternate_row_color());
			GlobalService::get('phpgw')->template->set_var('label',$label);
			GlobalService::get('phpgw')->template->set_var('value',$value);
			GlobalService::get('phpgw')->template->parse('rows','row',True);
		}

		function add()
		{
			if (GlobalService::get('phpgw')->acl->check('applications_access',2,'admin'))
			{
				GlobalService::get('phpgw')->redirect_link('/index.php');
			}
			$start = get_var('start',array('POST','GET'));
	
			GlobalService::get('phpgw')->template->set_file(array('application' => 'application_form.tpl'));
			GlobalService::get('phpgw')->template->set_block('application','form','form');
			GlobalService::get('phpgw')->template->set_block('application','row','row');
			GlobalService::get('phpgw')->template->set_block('form','delete_button');
			GlobalService::get('phpgw')->template->set_var('delete_button','');

			if ($_POST['cancel'])
			{
				GlobalService::get('phpgw')->redirect_link('/index.php','menuaction=admin.uiapplications.get_list&start='.$start);
			}

			if ($_POST['save'])
			{
				$totalerrors = 0;

				$app_order    = $_POST['app_order'] ? $_POST['app_order'] : 0;
				$n_app_name   = chop($_POST['n_app_name']);
				$n_app_status = $_POST['n_app_status'];

				if ($this->bo->exists($n_app_name))
				{
					$error[$totalerrors++] = lang('That application name already exists.');
				}
				if (preg_match("/\D/",$app_order))
				{
					$error[$totalerrors++] = lang('That application order must be a number.');
				}
				if (!$n_app_name)
				{
					$error[$totalerrors++] = lang('You must enter an application name.');
				}

				if (!$totalerrors)
				{
					$this->bo->add(array(
						'n_app_name'   => $n_app_name,
						'n_app_status' => $n_app_status,
						'app_order'    => $app_order
					));

					GlobalService::get('phpgw')->redirect_link('/index.php','menuaction=admin.uiapplications.get_list&start='.$start);
					GlobalService::get('phpgw')->common->phpgw_exit();
				}
				else
				{
					GlobalService::get('phpgw')->template->set_var('error','<p><center>' . GlobalService::get('phpgw')->common->error_list($error) . '</center><br>');
				}
			}
			else
			{	// else submit
				GlobalService::get('phpgw')->template->set_var('error','');
			}

			GlobalService::get('phpgw_info')['flags']['app_header'] = lang('Admin').' - '.lang('Add new application');
			if(!@is_object(GlobalService::get('phpgw')->js))
			{
				GlobalService::get('phpgw')->js = CreateObject('phpgwapi.javascript');
			}
			GlobalService::get('phpgw')->js->validate_file('jscode','openwindow','admin');
			GlobalService::get('phpgw')->common->phpgw_header();
			echo parse_navbar();

			GlobalService::get('phpgw')->template->set_var('th_bg',GlobalService::get('phpgw_info')['theme']['th_bg']);

			GlobalService::get('phpgw')->template->set_var('hidden_vars','<input type="hidden" name="start" value="'.$start.'">');
			GlobalService::get('phpgw')->template->set_var('form_action',GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiapplications.add'));

			$this->display_row(lang('application name'),'<input name="n_app_name" value="' . $n_app_name . '">');

			if(!isset($n_app_status))
			{
				$n_app_status = 1;
			}

			$selected[$n_app_status] = ' selected';
			$status_html = '<option value="0"' . $selected[0] . '>' . lang('Disabled') . '</option>'
				. '<option value="1"' . $selected[1] . '>' . lang('Enabled') . '</option>'
				. '<option value="2"' . $selected[2] . '>' . lang('Enabled - Hidden from navbar') . '</option>'
				. '<option value="4"' . $selected[4] . '>' . lang('Enabled - Popup Window') . '</option>';
			$this->display_row(lang('Status'),'<select name="n_app_status">' . $status_html . '</select>');

			if (!$app_order)
			{
				$app_order = $this->bo->app_order();
			}

			$this->display_row(lang('Select which location this app should appear on the navbar, lowest (left) to highest (right)'),'<input name="app_order" value="' . $app_order . '">');

			GlobalService::get('phpgw')->template->set_var('lang_save_button',lang('Add'));
			GlobalService::get('phpgw')->template->set_var('lang_cancel_button',lang('Cancel'));
			GlobalService::get('phpgw')->template->pparse('phpgw_body','form');
		}

		function edit()
		{
			if (GlobalService::get('phpgw')->acl->check('applications_access',4,'admin'))
			{
				GlobalService::get('phpgw')->redirect_link('/index.php');
			}
			$app_name = get_var('app_name',array('POST','GET'));
			$start = get_var('start',array('POST','GET'));

			GlobalService::get('phpgw')->template->set_file(array('application' => 'application_form.tpl'));
			GlobalService::get('phpgw')->template->set_block('application','form','form');
			GlobalService::get('phpgw')->template->set_block('application','row','row');

			if ($_POST['cancel'])
			{
				GlobalService::get('phpgw')->redirect_link('/index.php','menuaction=admin.uiapplications.get_list&start='.$start);
			}
			
			if ($_POST['delete'])
			{
				return $this->delete();
			}

			if ($_POST['save'])
			{
				$totalerrors = 0;

				$app_order    = $_POST['app_order'] ? $_POST['app_order'] : 0;
				$n_app_status = $_POST['n_app_status'];

				if (! $totalerrors)
				{
					$this->bo->save(array(
						'n_app_status' => $n_app_status,
						'app_order'    => $app_order,
						'app_name' => urldecode($app_name)
					));

					GlobalService::get('phpgw')->redirect_link('/index.php','menuaction=admin.uiapplications.get_list&start='.$start);
				}
			}

			GlobalService::get('phpgw_info')['flags']['app_header'] = lang('Admin').' - '.lang('Edit application');
			if(!@is_object(GlobalService::get('phpgw')->js))
			{
				GlobalService::get('phpgw')->js = CreateObject('phpgwapi.javascript');
			}
			GlobalService::get('phpgw')->js->validate_file('jscode','openwindow','admin');
			GlobalService::get('phpgw')->common->phpgw_header();
			echo parse_navbar();

			if ($totalerrors)
			{
				GlobalService::get('phpgw')->template->set_var('error','<p><center>' . GlobalService::get('phpgw')->common->error_list($error) . '</center><br>');
			}
			else
			{
				GlobalService::get('phpgw')->template->set_var('error','');
				list($n_app_name,$n_app_title,$n_app_status,$old_app_name,$app_order) = $this->bo->read($app_name);
			}
			GlobalService::get('phpgw')->template->set_var('hidden_vars','<input type="hidden" name="start" value="'.$start.'">'.
				'<input type="hidden" name="app_name" value="' . $app_name . '">');
			GlobalService::get('phpgw')->template->set_var('th_bg',GlobalService::get('phpgw_info')['theme']['th_bg']);
			GlobalService::get('phpgw')->template->set_var('form_action',GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiapplications.edit'));

			$this->display_row(lang('application name'), $n_app_name );

			GlobalService::get('phpgw')->template->set_var('lang_status',lang('Status'));
			GlobalService::get('phpgw')->template->set_var('lang_save_button',lang('Save'));
			GlobalService::get('phpgw')->template->set_var('lang_cancel_button',lang('Cancel'));
			GlobalService::get('phpgw')->template->set_var('lang_delete_button',lang('Delete'));

			$selected[$n_app_status] = ' selected';
			$status_html = '<option value="0"' . $selected[0] . '>' . lang('Disabled') . '</option>'
				. '<option value="1"' . $selected[1] . '>' . lang('Enabled') . '</option>'
				. '<option value="2"' . $selected[2] . '>' . lang('Enabled - Hidden from navbar') . '</option>'
				. '<option value="4"' . $selected[4] . '>' . lang('Enabled - Popup Window') . '</option>';

			$this->display_row(lang("Status"),'<select name="n_app_status">' . $status_html . '</select>');
			$this->display_row(lang("Select which location this app should appear on the navbar, lowest (left) to highest (right)"),'<input name="app_order" value="' . $app_order . '">');

			GlobalService::get('phpgw')->template->set_var('select_status',$status_html);
			GlobalService::get('phpgw')->template->pparse('phpgw_body','form');
		}

		function delete()
		{
			if (GlobalService::get('phpgw')->acl->check('applications_access',8,'admin'))
			{
				GlobalService::get('phpgw')->redirect_link('/index.php');
			}
			$app_name = get_var('app_name',array('POST','GET'));
			$start = get_var('start',array('POST','GET'));
			
			if (!$app_name || $_POST['no'] || $_POST['yes'])
			{
				if ($_POST['yes'])
				{
					$this->bo->delete($app_name);
				}
				GlobalService::get('phpgw')->redirect_link('/index.php','menuaction=admin.uiapplications.get_list&start='.$start);
			}

			GlobalService::get('phpgw')->template->set_file(array('body' => 'delete_common.tpl'));

			if(!@is_object(GlobalService::get('phpgw')->js))
			{
				GlobalService::get('phpgw')->js = CreateObject('phpgwapi.javascript');
			}
			GlobalService::get('phpgw')->js->validate_file('jscode','openwindow','admin');
			GlobalService::get('phpgw')->common->phpgw_header();
			echo parse_navbar();

			GlobalService::get('phpgw')->template->set_var('messages',lang('Are you sure you want to delete the application %1 ?',GlobalService::get('phpgw_info')['apps'][$app_name]['title']));
			GlobalService::get('phpgw')->template->set_var('no',lang('No'));
			GlobalService::get('phpgw')->template->set_var('yes',lang('Yes'));
			GlobalService::get('phpgw')->template->set_var('form_action',GlobalService::get('phpgw')->link('/index.php','menuaction=admin.uiapplications.delete'));
			GlobalService::get('phpgw')->template->set_var('hidden_vars','<input type="hidden" name="start" value="'.$start.'">'.
				'<input type="hidden" name="app_name" value="'. urlencode($app_name) . '">');
			GlobalService::get('phpgw')->template->pparse('phpgw_body','body');
		}
		
		function register_all_hooks()
		{
			if (GlobalService::get('phpgw')->acl->check('applications_access',16,'admin'))
			{
				GlobalService::get('phpgw')->redirect_link('/index.php');
			}
			if (!is_object(GlobalService::get('phpgw')->hooks))
			{
				GlobalService::get('phpgw')->hooks = CreateObject('phpgwapi.hooks');
			}
			GlobalService::get('phpgw')->hooks->register_all_hooks();
			
			GlobalService::get('phpgw')->redirect_link('/admin/index.php');
		}
	}
?>
