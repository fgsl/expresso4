<?php
use Expresso\Core\GlobalService;

if(!isset(GlobalService::get('phpgw_info'))){
        GlobalService::get('phpgw_info')['flags'] = array(
                'currentapp' => 'expressoMail1_2',
                'nonavbar'   => true,
                'noheader'   => true
        );
}
require_once '../header.inc.php';


	$title = $appname;
	$file = array(
		'Preferences'     		=> GlobalService::get('phpgw')->link('/preferences/preferences.php','appname='.$appname),
		'Expresso Offline'			=> GlobalService::get('phpgw')->link('/expressoMail1_2/offline_preferences.php'),
		'Programed Archiving' => GlobalService::get('phpgw')->link('/expressoMail1_2/programed_archiving.php')
	);
	//Do not modify below this line
	display_section($appname,$title,$file);
?>
