<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* eGroupWare - News                                                        *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	* --------------------------------------------                             *
	\**************************************************************************/


	class uiacl
	{
		var $start = 0;
		var $query = '';
		var $sort  = '';
		var $order = '';
		var $bo;
		var $accounts;
		var $nextmatchs = '';
		var $rights;
		var $public_functions = array(
			'acllist' 	=> True,
			);

		function uiacl()
		{
			$this->bo = createobject('news_admin.boacl',True);
			$this->accounts = GlobalService::get('phpgw')->accounts->get_list('groups');
			$this->nextmatchs = createobject('phpgwapi.nextmatchs');
			$this->start = $this->bo->start;
			$this->query = $this->bo->query;
			$this->order = $this->bo->order;
			$this->sort = $this->bo->sort;
			$this->cat_id = $this->bo->cat_id;
		}
		
		function acllist()
		{
			if (!GlobalService::get('phpgw')->acl->check('run',1,'admin'))
			{
				$this->deny();
			}

			if ($_POST['btnDone'])
			{
				GlobalService::get('phpgw')->redirect_link('/admin/index.php');
			}

			GlobalService::get('phpgw')->common->phpgw_header();
			echo parse_navbar();

			if ($_POST['btnSave'])
			{
				foreach($_POST['catids'] as $cat_id)
				{
					$this->bo->set_rights($cat_id,$_POST['inputread'][$cat_id],$_POST['inputwrite'][$cat_id]);
				}
			}

			GlobalService::get('phpgw')->template->set_file('acl', 'acl.tpl');
			GlobalService::get('phpgw')->template->set_block('acl','cat_list','Cblock');
			GlobalService::get('phpgw')->template->set_var(array(
				'title' => GlobalService::get('phpgw_info')['apps']['news_admin']['title'] . ' - ' . lang('Configure Access Permissions'),
				'lang_search' => lang('Search'),
				'lang_save' => lang('Save'),
				'lang_done' => lang('Done'),
				'lang_read' => lang('Read permissions'),
				'lang_write' => lang('Write permissions'),
				'lang_implies' => lang('implies read permission'),
			));

			$left  = $this->nextmatchs->left('/index.php',$this->start,$this->bo->catbo->total_records,'menuaction=news_admin.uiacl.acllist');
			$right = $this->nextmatchs->right('/index.php',$this->start,$this->bo->catbo->total_records,'menuaction=news_admin.uiacl.acllist');
			
			GlobalService::get('phpgw')->template->set_var(array(
				'left' => $left,
				'right' => $right,
				'lang_showing' => $this->nextmatchs->show_hits($this->bo->catbo->total_records,$this->start),
				'th_bg' => GlobalService::get('phpgw_info')['theme']['th_bg'],
				'sort_cat' => $this->nextmatchs->show_sort_order(
					$this->sort,'cat_name','cat_name','/index.php',lang('Category'),'&menuaction=news_admin.uiacl.acllist'
				),
				'query' => $this->query,
			));

			@reset($this->bo->cats);
			while (list(,$cat) = @each($this->bo->cats))
			{
				$this->rights = $this->bo->get_rights($cat['id']);

				$tr_color = $this->nextmatchs->alternate_row_color($tr_color);
				GlobalService::get('phpgw')->template->set_var(array(
					'tr_color' => $tr_color,
					'catname' => $cat['name'],
					'catid' => $cat['id'],
					'read' => $this->selectlist(PHPGW_ACL_READ),
					'write' => $this->selectlist(PHPGW_ACL_ADD)
				));
				GlobalService::get('phpgw')->template->parse('Cblock','cat_list',True);
			}
			GlobalService::get('phpgw')->template->pfp('out','acl',True);
		}

		function selectlist($right)
		{
			reset($this->bo->accounts);
			while (list($null,$account) = each($this->bo->accounts))
			{
 				$selectlist .= '<option value="' . $account['account_id'] . '"';
  				if($this->rights[$account['account_id']] & $right)
 				{
 					$selectlist .= ' selected="selected"';
 				}
				$selectlist .= '>' . $account['account_firstname'] . ' ' . $account['account_lastname']
										. ' [ ' . $account['account_lid'] . ' ]' . '</option>' . "\n";
			}
			return $selectlist;
		}

		function deny()
		{
			echo '<p><center><b>'.lang('Access not permitted').'</b></center>';
			GlobalService::get('phpgw')->common->phpgw_exit(True);
		}
	}
?>
