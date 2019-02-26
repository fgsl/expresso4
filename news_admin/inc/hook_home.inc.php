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


	$showevents = (int)GlobalService::get('phpgw_info')['user']['preferences']['news_admin']['homeShowLatest'];
	if($showevents > 0)
	{
		GlobalService::get('phpgw')->translation->add_app('news_admin');
		$title = lang('News Admin');
		$portalbox = CreateObject('phpgwapi.listbox',array(
			'title'     => $title,
			'primary'   => GlobalService::get('phpgw_info')['theme']['navbar_bg'],
			'secondary' => GlobalService::get('phpgw_info')['theme']['navbar_bg'],
			'tertiary'  => GlobalService::get('phpgw_info')['theme']['navbar_bg'],
			'width'     => '100%',
			'outerborderwidth' => '0',
			'header_background_image' => GlobalService::get('phpgw')->common->image('phpgwapi/templates/default','bg_filler')
		));

		$latestcount = (int)GlobalService::get('phpgw_info')['user']['preferences']['news_admin']['homeShowLatestCount'];
		if($latestcount<=0) 
		{
			$latestcount = 10;
		}
		print_debug("showing $latestcount news items");
		$app_id = GlobalService::get('phpgw')->applications->name2id('news_admin');
		GlobalService::get('portal_order')[] = $app_id;

		$news = CreateObject('news_admin.uinews');

		$newslist = $news->bo->get_newslist('all',0,'','',$latestcount,True);

		$image_path = GlobalService::get('phpgw')->common->get_image_path('news_admin');

		if(is_array($newslist))
		{
			foreach($newslist as $newsitem)
			{
				$text = $newsitem['subject'];
				if($showevents == 1)
				{
					$text .= ' - ' . lang('Submitted by') . ' ' . GlobalService::get('phpgw')->common->grab_owner_name($newsitem['submittedby']) . ' ' . lang('on') . ' ' . GlobalService::get('phpgw')->common->show_date($newsitem['date']);
				}
				$portalbox->data[] = array(
					'text' => $text,
					'link' => GlobalService::get('phpgw')->link('/index.php','menuaction=news_admin.uinews.read_news&news_id=' . $newsitem['id'])
				);
			}
			unset($text);
		}
		else
		{
			$portalbox->data[] = array('text' => lang('no news'));
		}

		GlobalService::get('portal_order')[] = $app_id;
		$var = Array(
				'up'    => Array('url'  => '/set_box.php', 'app'        => $app_id),
				'down'  => Array('url'  => '/set_box.php', 'app'        => $app_id),
				'close' => Array('url'  => '/set_box.php', 'app'        => $app_id),
				'question'      => Array('url'  => '/set_box.php', 'app'        => $app_id),
				'edit'  => Array('url'  => '/set_box.php', 'app'        => $app_id)
		);

		while(list($key,$value) = each($var))
		{
			$portalbox->set_controls($key,$value);
		}

		$tmp = "\r\n"
			. '<!-- start News Admin -->' . "\r\n"
			. $portalbox->draw()
			. '<!-- end News Admin -->'. "\r\n";
		print $tmp;
	}
?>
