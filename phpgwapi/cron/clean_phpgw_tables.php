<?php
use Expresso\Core\GlobalService;
?>
#!/usr/bin/php -q
<?php

$path_to_egroupware = realpath(dirname(__FILE__).'/../..');	//  need to be adapted if this script is moved somewhere else

GlobalService::get('phpgw_info')['flags'] = array(
	'currentapp' => 'login',
	'noapi'      => True		// this stops header.inc.php to include phpgwapi/inc/function.inc.php
);

if (!is_readable($path_to_egroupware.'/header.inc.php'))
{
	echo $msg = "Could not find '$path_to_egroupware/header.inc.php', exiting !!!\n";
	exit(1);
}

include($path_to_egroupware.'/header.inc.php');
unset(GlobalService::get('phpgw_info')['flags']['noapi']);

include(PHPGW_API_INC.'/functions.inc.php');

if (GlobalService::get('phpgw_info')['server']['max_access_log_age'])
	$max_age = time() - (GlobalService::get('phpgw_info')['server']['max_access_log_age'] * 24 * 60 * 60);
else
	$max_age = time() - (30 * 24 * 60 * 60);

GlobalService::get('phpgw')->db->query("DELETE FROM phpgw_access_log WHERE li < $max_age");
GlobalService::get('phpgw')->db->query("DELETE FROM phpgw_log WHERE cast(extract(epoch FROM log_date) AS integer) < $max_age");
GlobalService::get('phpgw')->db->query("DELETE FROM phpgw_log_msg WHERE cast(extract(epoch FROM log_msg_date) AS integer) < $max_age");

$bo = CreateObject( 'admin.bocurrentsessions' );
$login = '';
do {
	$records = array();
	GlobalService::get('phpgw')->db->query( '
		SELECT DISTINCT loginid
		FROM phpgw_access_log
		WHERE lo = 0 AND loginid > \''.$login.'\' AND li > '.(strtotime("-1 day",gmmktime(0,0,0))).'
		ORDER BY loginid
		LIMIT 1000'
	);
	while ( GlobalService::get('phpgw')->db->next_record() ) $records[] = trim( GlobalService::get('phpgw')->db->f( 'loginid' ) );
	foreach ( $records as $login ) $bo->get_sessions( $login );
} while( count( $records ) > 0 );
