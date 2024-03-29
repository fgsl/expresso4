<?php
	/**************************************************************************\
	* eGroupWare - PHPBrain                                                    *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* Basic information about this app */
	$setup_info['workflow']['name']      = 'workflow';
	$setup_info['workflow']['title']     = 'Workflow Management';
	$setup_info['workflow']['version']   = '2.5.2';
	$setup_info['workflow']['app_order'] = 10;
	$setup_info['workflow']['enable']    = 1;
	
	$setup_info['workflow']['author'] = 'Regis Leroy';
	
	$setup_info['workflow']['maintainer'][] = array(
		'name'  => 'ExpressoLivre coreteam',
		'email' => 'webmaster@expressolivre.org',
		'url'   => 'www.expressolivre.org'
	);

	$setup_info['workflow']['license']     = 'GPL';
	$setup_info['workflow']['description'] = 'Workflow Application';
	$setup_info['workflow']['note']        = 'Based on Galaxia Workflow Engine';

	$setup_info['workflow']['tables']		= array(
								'egw_wf_activities',
								'egw_wf_activity_roles',
								'egw_wf_instance_activities',
								'egw_wf_instances',
								'egw_wf_processes',
								'egw_wf_roles',
								'egw_wf_transitions',
								'egw_wf_user_roles',
								'egw_wf_workitems',
								'egw_wf_process_config',
								'egw_wf_activity_agents',
								'egw_wf_agent_mail_smtp',
								'egw_wf_interinstance_relations',
								'egw_wf_external_application',
								'egw_wf_admin_access',
								'egw_wf_user_cache',
								'egw_wf_jobs',
								'egw_wf_job_logs'
							);

	/* The hooks this app includes, needed for hooks registration */
	$setup_info['workflow']['hooks'][] = 'about';
	$setup_info['workflow']['hooks'][] = 'admin';
	$setup_info['workflow']['hooks'][] = 'add_def_pref';
	$setup_info['workflow']['hooks'][] = 'config';
	$setup_info['workflow']['hooks'][] = 'manual';
	$setup_info['workflow']['hooks'][] = 'preferences';
	$setup_info['workflow']['hooks'][] = 'settings';
	$setup_info['workflow']['hooks'][] = 'sidebox_menu';
	$setup_info['workflow']['hooks'][] = 'acl_manager';
	$setup_info['workflow']['hooks'][] = 'deleteaccount';
	$setup_info['workflow']['hooks'][] = 'home';

	/* Dependencies for this app to work */
	$setup_info['workflow']['depends'][] = array(
		'appname' => 'phpgwapi',
		'versions' => Array('1.2', '2.0', '2.2', '2.4', '2.5')
	);
	$setup_info['workflow']['depends'][] = array(
		'appname' => 'preferences',
		'versions' => Array('0.9.13.002', '2.0', '2.2', '2.5.0')
	);
?>
