<?php
	use Expresso\Core\GlobalService;

	/**************************************************************************\
	* phpGroupWare - Setup                                                     *
	* http://www.phpgroupware.org                                              *
	* --------------------------------------------                             *
	* This program is free software; you can redistribute it and/or modify it  *
	* under the terms of the GNU General Public License as published by the    *
	* Free Software Foundation; either version 2 of the License, or (at your   *
	* option) any later version.                                               *
	\**************************************************************************/	
	// Since Expresso 1.2 using ContactCenter 1.21
	$test[] = '1.21';
	function contactcenter_upgrade1_21() {
	    GlobalService::get('setup_info')['contactcenter']['currentver'] = '2.0.000';
		// Bug fixing for type cast problem PGSQL version > 8.1. Replacing trigger function:
			GlobalService::get('phpgw_setup')->db->query("CREATE OR REPLACE function share_catalog_delete() returns trigger as '".
					"begin if old.acl_appname = ''contactcenter'' and old.acl_location!=''run'' then delete from ".
					"phpgw_cc_contact_rels where id_contact=old.acl_location::bigint and id_related=old.acl_account ".
					"and id_typeof_contact_relation=1; end if; return new; end;' language 'plpgsql'");
		return GlobalService::get('setup_info')['contactcenter']['currentver'];
	}
	$test[] = '2.0.000';
	function contactcenter_upgrade2_0_000() {
		GlobalService::get('setup_info')['contactcenter']['currentver'] = '2.0.001';
		return GlobalService::get('setup_info')['contactcenter']['currentver'];
	}
	$test[] = '2.0.001';
	function contactcenter_upgrade2_0_001() {
		GlobalService::get('setup_info')['contactcenter']['currentver'] = '2.0.002';
		return GlobalService::get('setup_info')['contactcenter']['currentver'];
	}		
	$test[] = '2.0.002';
	function contactcenter_upgrade2_0_002() {
		GlobalService::get('setup_info')['contactcenter']['currentver'] = '2.0.003';
		return GlobalService::get('setup_info')['contactcenter']['currentver'];
	}	
	$test[] = '2.0.003';
	function contactcenter_upgrade2_0_003() {
		GlobalService::get('setup_info')['contactcenter']['currentver'] = '2.0.004';
		return GlobalService::get('setup_info')['contactcenter']['currentver'];
	}
	$test[] = '2.0.004';
	function contactcenter_upgrade2_0_004() {
		GlobalService::get('setup_info')['contactcenter']['currentver'] = '2.1.000';
		return GlobalService::get('setup_info')['contactcenter']['currentver'];
	}	
	$test[] = '2.1.000';
	function contactcenter_upgrade2_1_000() {		
		GlobalService::get('phpgw_setup')->db->query("ALTER TABLE phpgw_cc_contact ADD COLUMN web_page character varying(100)");
 		GlobalService::get('phpgw_setup')->db->query("ALTER TABLE phpgw_cc_contact ADD COLUMN corporate_name character varying(100)");
		GlobalService::get('phpgw_setup')->db->query("ALTER TABLE phpgw_cc_contact ADD COLUMN job_title character varying(40)");
		GlobalService::get('phpgw_setup')->db->query("ALTER TABLE phpgw_cc_contact ADD COLUMN department character varying(30)");
		GlobalService::get('setup_info')['contactcenter']['currentver'] = '2.2.000';
		return GlobalService::get('setup_info')['contactcenter']['currentver'];
	}	
	$test[] = '2.2.000';
	function contactcenter_upgrade2_2_000() {				
		GlobalService::get('setup_info')['contactcenter']['currentver'] = '2.2.1';
		return GlobalService::get('setup_info')['contactcenter']['currentver'];
	}
	$test[] = '2.2.1';
	function contactcenter_upgrade2_2_1() {				
		GlobalService::get('setup_info')['contactcenter']['currentver'] = '2.2.2';
		return GlobalService::get('setup_info')['contactcenter']['currentver'];
	}
	$test[] = '2.2.2';
	function contactcenter_upgrade2_2_2() {
		GlobalService::get('setup_info')['contactcenter']['currentver'] = '2.2.3';
		return GlobalService::get('setup_info')['contactcenter']['currentver'];
	}
?>
