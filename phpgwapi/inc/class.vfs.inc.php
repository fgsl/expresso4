<?php
	use Expresso\Core\GlobalService;

	if (empty (GlobalService::get('phpgw_info')['server']['file_repository']))
	{
		GlobalService::get('phpgw_info')['server']['file_repository'] = 'sql';
	}

	include (PHPGW_API_INC . '/class.vfs_shared.inc.php');
	include (PHPGW_API_INC . '/class.vfs_' . GlobalService::get('phpgw_info')['server']['file_repository'] . '.inc.php');
?>
