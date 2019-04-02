<?php
use Expresso\Core\GlobalService;

/**************************************************************************\
* eGroupWare - Online User manual                                          *
* http://www.eGroupWare.org                                                *
* Written and (c) by RalfBecker@outdoor-training.de                        *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

/* $Id: index.php,v 1.13 2004/04/13 08:19:10 ralfbecker Exp $ */

GlobalService::get('phpgw_info')['flags'] = array(
	'currentapp' => 'help',
	'nonavbar'   => true,
	'noheader'   => true,
);

include( '../header.inc.php' );

if ( $_POST ) {
	$params['input_to']      = GlobalService::get('phpgw_info')['server']['sugestoes_email_to'];
	$params['input_cc']      = GlobalService::get('phpgw_info')['server']['sugestoes_email_cc'];
	$params['input_cc']      = GlobalService::get('phpgw_info')['server']['sugestoes_email_bcc'];
	$params['input_subject'] = lang( 'Suggestions' );
	$params['body']          = $_POST['body'];
	$params['type']          = 'plain';

	GlobalService::get('phpgw')->preferences->read_repository();

	$_SESSION['phpgw_info']['expressomail']['user']          = GlobalService::get('phpgw_info')['user'];
	$_SESSION['phpgw_info']['expressomail']['email_server']  = CreateObject('emailadmin.bo')->getProfile();
	$_SESSION['phpgw_info']['expressomail']['server']        = GlobalService::get('phpgw_info')['server'];
	$_SESSION['phpgw_info']['expressomail']['user']['email'] = GlobalService::get('phpgw')->preferences->values['email'];

	$expressoMail = CreateObject( 'expressoMail1_2.imap_functions' );
	if ( !$expressoMail->send_mail( $params ) ) {
		echo $to.'<Br>'.$subject.'<br>'.$tmpbody.'<br>'.$sender.'<br>'."\n";
		echo '<i>'.$send->err['desc'].'</i><br>'."\n";
		exit;
	} else ExecMethod( 'help.uihelp.viewSuccess' );
} else ExecMethod( 'help.uihelp.viewSuggestions' );

