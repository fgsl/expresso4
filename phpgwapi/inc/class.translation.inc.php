<?php 
	use Expresso\Core\GlobalService;

	if (empty(GlobalService::get('phpgw_info')['server']['translation_system']))
	{
		GlobalService::get('phpgw_info')['server']['translation_system'] = 'sql';
	}
	include(PHPGW_API_INC.'/class.translation_' . GlobalService::get('phpgw_info')['server']['translation_system'].'.inc.php'); 
?>
