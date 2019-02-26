<?php
use Expresso\Core\GlobalService;
use Expresso\Setup\Bootstrap;
use Expresso\Setup\Template;
use Expresso\Setup\ManageHeader;
use Expresso\Setup\CheckInstall;

/**
 *
 * @package Setup
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright Copyright (c) 2019 FGSL (http://www.fgsl.eti.br)
 *
 */
require __DIR__ . '/../vendor/autoload.php';

GlobalService::set('DEBUG',False);
GlobalService::set('phpgw_info',[]);
GlobalService::get('phpgw_info')['flags'] = array(
    'noheader' => True,
    'nonavbar' => True,
    'currentapp' => 'home',
    'noapi' => True,
    'nocachecontrol' => True
);
Bootstrap::start();

@set_time_limit(0);

$tpl_root = GlobalService::get('phpgw_setup')->html->setup_tpl_dir('setup');
$setup_tpl = new Template($tpl_root);
$setup_tpl->set_file(array(
    'T_head' => 'head.tpl',
    'T_footer' => 'footer.tpl',
    'T_alert_msg' => 'msg_alert_msg.tpl',
    'T_login_main' => 'login_main.tpl',
    'T_login_stage_header' => 'login_stage_header.tpl',
    'T_setup_main' => 'setup_main.tpl',
    'T_setup_db_blocks' => 'setup_db_blocks.tpl'
));

$setup_tpl->set_block('T_login_stage_header', 'B_multi_domain', 'V_multi_domain');
$setup_tpl->set_block('T_login_stage_header', 'B_single_domain', 'V_single_domain');

$setup_tpl->set_block('T_setup_db_blocks', 'B_db_stage_1', 'V_db_stage_1');
$setup_tpl->set_block('T_setup_db_blocks', 'B_db_stage_2', 'V_db_stage_2');
$setup_tpl->set_block('T_setup_db_blocks', 'B_db_stage_3', 'V_db_stage_3');
$setup_tpl->set_block('T_setup_db_blocks', 'B_db_stage_4', 'V_db_stage_4');
$setup_tpl->set_block('T_setup_db_blocks', 'B_db_stage_5', 'V_db_stage_5');
$setup_tpl->set_block('T_setup_db_blocks', 'B_db_stage_6_pre', 'V_db_stage_6_pre');
$setup_tpl->set_block('T_setup_db_blocks', 'B_db_stage_6_post', 'V_db_stage_6_post');
$setup_tpl->set_block('T_setup_db_blocks', 'B_db_stage_10', 'V_db_stage_10');
$setup_tpl->set_block('T_setup_db_blocks', 'B_db_stage_default', 'V_db_stage_default');

// Check header and authentication
GlobalService::get('phpgw_info')['setup']['stage']['header'] = GlobalService::get('phpgw_setup')->detection->check_header();
if (GlobalService::get('phpgw_info')['setup']['stage']['header'] != '10') {
    if (GlobalService::get('phpgw_info')['setup']['stage']['header'] == 4) // header needs update, go there direct
    {
        new ManageHeader();
    } else // run check-install first
    {
        new CheckInstall(1);
    }
    exit();
} elseif (! GlobalService::get('phpgw_setup')->auth('Config')) {
    GlobalService::get('phpgw_setup')->html->show_header(lang('Please login'), True);
    GlobalService::get('phpgw_setup')->html->login_form();
    GlobalService::get('phpgw_setup')->html->show_footer();
    exit();
}

GlobalService::get('phpgw_setup')->loaddb();

/* Add cleaning of app_sessions per skeeter, but with a check for the table being there, just in case */
/* GlobalService::get('phpgw_setup')->clear_session_cache(); */

// Database actions
$setup_info = GlobalService::get('phpgw_setup')->detection->get_versions();
GlobalService::get('phpgw_info')['setup']['stage']['db'] = GlobalService::get('phpgw_setup')->detection->check_db($setup_info);
if (GlobalService::get('phpgw_info')['setup']['stage']['db'] != 1) {
    $setup_info = GlobalService::get('phpgw_setup')->detection->get_versions();
    $setup_info = GlobalService::get('phpgw_setup')->detection->get_db_versions($setup_info);
    GlobalService::get('phpgw_info')['setup']['stage']['db'] = GlobalService::get('phpgw_setup')->detection->check_db($setup_info);
    if (GlobalService::get('DEBUG')) {
        _debug_array($setup_info);
    }
}

if (GlobalService::get('DEBUG')) {
    echo 'Stage: ' . GlobalService::get('phpgw_info')['setup']['stage']['db'];
}
// begin DEBUG code
// GlobalService::get('phpgw_info')['setup']['stage']['db'] = 0;
// $action = 'Upgrade';
// end DEBUG code

switch (@get_var('action', Array(
    'POST'
))) {
    case 'Uninstall all applications':
        $subtitle = lang('Deleting Tables');
        $submsg = lang('Are you sure you want to delete your existing tables and data?') . '.';
        $subaction = lang('uninstall');
        GlobalService::get('phpgw_info')['setup']['currentver']['phpgwapi'] = 'predrop';
        GlobalService::get('phpgw_info')['setup']['stage']['db'] = 5;
        break;
    case 'Create Database':
        $subtitle = lang('Create Database');
        $submsg = lang('At your request, this script is going to attempt to create the database and assign the db user rights to it');
        $subaction = lang('created');
        GlobalService::get('phpgw_info')['setup']['currentver']['phpgwapi'] = 'dbcreate';
        GlobalService::get('phpgw_info')['setup']['stage']['db'] = 6;
        break;
    case 'REALLY Uninstall all applications':
        $subtitle = lang('Deleting Tables');
        $submsg = lang('At your request, this script is going to take the evil action of uninstalling all your apps, which deletes your existing tables and data') . '.';
        $subaction = lang('uninstalled');
        GlobalService::get('phpgw_info')['setup']['currentver']['phpgwapi'] = 'drop';
        GlobalService::get('phpgw_info')['setup']['stage']['db'] = 6;
        break;
    case 'Upgrade':
        $subtitle = lang('Upgrading Tables');
        $submsg = lang('At your request, this script is going to attempt to upgrade your old applications to the current versions') . '.';
        $subaction = lang('upgraded');
        GlobalService::get('phpgw_info')['setup']['currentver']['phpgwapi'] = 'oldversion';
        GlobalService::get('phpgw_info')['setup']['stage']['db'] = 6;
        break;
    case 'Install':
        $subtitle = lang('Creating Tables');
        $submsg = lang('At your request, this script is going to attempt to install the core tables and the admin and preferences applications for you') . '.';
        $subaction = lang('installed');
        GlobalService::get('phpgw_info')['setup']['currentver']['phpgwapi'] = 'new';
        GlobalService::get('phpgw_info')['setup']['stage']['db'] = 6;
        break;
}
$setup_tpl->set_var('subtitle', @$subtitle);
$setup_tpl->set_var('submsg', @$submsg);
$setup_tpl->set_var('subaction', @$subaction);

// Old PHP
if (! function_exists('version_compare')) // version_compare() is only available in PHP4.1+
{
    GlobalService::get('phpgw_setup')->html->show_header(GlobalService::get('phpgw_info')['setup']['header_msg'], True);
    GlobalService::get('phpgw_setup')->html->show_alert_msg('Error', lang('You appear to be running an old version of PHP <br>It its recommend that you upgrade to a new version. <br>Older version of PHP might not run eGroupWare correctly, if at all. <br><br>Please upgrade to at least version %1', '4.1'));
    GlobalService::get('phpgw_setup')->html->show_footer();
    exit();
}

// BEGIN setup page

// GlobalService::get('phpgw_setup')->app_status();
GlobalService::get('phpgw_info')['server']['app_images'] = 'templates/default/images';
$incomplete = GlobalService::get('phpgw_info')['server']['app_images'] . '/incomplete.png';
$completed = GlobalService::get('phpgw_info')['server']['app_images'] . '/completed.png';

$setup_tpl->set_var('img_incomplete', $incomplete);
$setup_tpl->set_var('img_completed', $completed);

$setup_tpl->set_var('db_step_text', lang('Step %1 - Simple Application Management', 1));

switch (GlobalService::get('phpgw_info')['setup']['stage']['db']) {
    case 1:
        $setup_tpl->set_var('dbnotexist', lang('Your Database is not working!'));
        $setup_tpl->set_var('makesure', lang('makesure'));
        $setup_tpl->set_var('notcomplete', lang('not complete'));
        $setup_tpl->set_var('oncesetup', lang('Once the database is setup correctly'));
        $setup_tpl->set_var('createdb', lang('Or we can attempt to create the database for you:'));
        $setup_tpl->set_var('create_database', lang('Create database'));

        $info = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_setup')->ConfigDomain];

        $setup_tpl->set_var('db_root', $info['db_user']);
        $setup_tpl->set_var('db_pass', $info['db_pass']);

        switch ($info['db_type']) {
            case 'mysql':
                $setup_tpl->set_var('instr', lang("Instructions for creating the database in %1:", 'MySql') . '<br>' . lang('Login to mysql -') . '<br><i>[user@server user]# mysql -u root -p</i><br>' . lang('Create the empty database and grant user permissions -') . "<br><i>mysql> create database $info[db_name];</i>" . "<br><i>mysql> grant all on " . $info['db_name'] . ".* to " . $info['db_user'] . "@localhost identified by '" . $info['db_pass'] . "';</i>");
                break;
            case 'pgsql':
                $setup_tpl->set_var('instr', lang('Instructions for creating the database in %1:', 'PostgreSQL') . '<br>' . lang('Start the postmaster') . "<br><i>[user@server user]# postmaster -i -D /home/[username]/[dataDir]</i><br>" . lang('Create the empty database -') . "<br><i>[user@server user]# createdb " . $info['db_name'] . "</i>");
                break;
            default:
                $setup_tpl->set_var('instr', '');
        }
        $setup_tpl->parse('V_db_stage_1', 'B_db_stage_1');
        $db_filled_block = $setup_tpl->get_var('V_db_stage_1');
        $setup_tpl->set_var('V_db_filled_block', $db_filled_block);
        break;
    case 2:
        $setup_tpl->set_var('prebeta', lang('You appear to be running a pre-beta version of eGroupWare.<br>These versions are no longer supported, and there is no upgrade path for them in setup.<br> You may wish to first upgrade to 0.9.10 (the last version to support pre-beta upgrades) <br>and then upgrade from there with the current version.'));
        $setup_tpl->set_var('notcomplete', lang('not complete'));
        $setup_tpl->parse('V_db_stage_2', 'B_db_stage_2');
        $db_filled_block = $setup_tpl->get_var('V_db_stage_2');
        $setup_tpl->set_var('V_db_filled_block', $db_filled_block);
        break;
    case 3:
        $setup_tpl->set_var('dbexists', lang('Your database is working, but you dont have any applications installed'));
        $setup_tpl->set_var('install', lang('Install'));
        $setup_tpl->set_var('proceed', lang('We can proceed'));
        $setup_tpl->set_var('coreapps', lang('all applications'));
        $setup_tpl->set_var('lang_debug', lang('enable for extra debug-messages'));
        $setup_tpl->parse('V_db_stage_3', 'B_db_stage_3');
        $db_filled_block = $setup_tpl->get_var('V_db_stage_3');
        $setup_tpl->set_var('V_db_filled_block', $db_filled_block);
        break;
    case 4:
        $setup_tpl->set_var('oldver', lang('You appear to be running version %1 of eGroupWare', $setup_info['phpgwapi']['currentver']));
        $setup_tpl->set_var('automatic', lang('We will automatically update your tables/records to %1', $setup_info['phpgwapi']['version']));
        $setup_tpl->set_var('backupwarn', lang('backupwarn'));
        $setup_tpl->set_var('lang_debug', lang('enable for extra debug-messages'));
        $setup_tpl->set_var('upgrade', lang('Upgrade'));
        $setup_tpl->set_var('goto', lang('Go to'));
        $setup_tpl->set_var('configuration', lang('configuration'));
        $setup_tpl->set_var('admin_account', lang('Create admin account'));
        $setup_tpl->set_var('applications', lang('Manage Applications'));
        $setup_tpl->set_var('language_management', lang('Manage Languages'));
        $setup_tpl->set_var('uninstall_all_applications', lang('Uninstall all applications'));
        $setup_tpl->set_var('dont_touch_my_data', lang('Dont touch my data'));
        $setup_tpl->set_var('dropwarn', lang('Your tables may be altered and you may lose data'));

        $setup_tpl->parse('V_db_stage_4', 'B_db_stage_4');
        $db_filled_block = $setup_tpl->get_var('V_db_stage_4');
        $setup_tpl->set_var('V_db_filled_block', $db_filled_block);
        break;
    case 5:
        $setup_tpl->set_var('are_you_sure', lang('ARE YOU SURE?'));
        $setup_tpl->set_var('really_uninstall_all_applications', lang('REALLY Uninstall all applications'));
        $setup_tpl->set_var('dropwarn', lang('Your tables will be dropped and you will lose data'));
        $setup_tpl->set_var('cancel', lang('cancel'));
        $setup_tpl->parse('V_db_stage_5', 'B_db_stage_5');
        $db_filled_block = $setup_tpl->get_var('V_db_stage_5');
        $setup_tpl->set_var('V_db_filled_block', $db_filled_block);
        break;
    case 6:
        $setup_tpl->set_var('status', lang('Status'));
        $setup_tpl->set_var('notcomplete', lang('not complete'));
        $setup_tpl->set_var('tblchange', lang('Table Change Messages'));
        $setup_tpl->parse('V_db_stage_6_pre', 'B_db_stage_6_pre');
        $db_filled_block = $setup_tpl->get_var('V_db_stage_6_pre');

        // FIXME : CAPTURE THIS OUTPUT
        GlobalService::get('phpgw_setup')->db->Halt_On_Error = 'report';

        switch (GlobalService::get('phpgw_info')['setup']['currentver']['phpgwapi']) {
            case 'dbcreate':
                GlobalService::get('phpgw_setup')->db->create_database($_POST['db_root'], $_POST['db_pass']);
                break;
            case 'drop':
                $setup_info = GlobalService::get('phpgw_setup')->detection->get_versions($setup_info);
                $setup_info = GlobalService::get('phpgw_setup')->process->droptables($setup_info);
                break;
            case 'new':
					/* process all apps and langs(last param True), excluding apps with the no_mass_update flag set. */
					$setup_info = GlobalService::get('phpgw_setup')->detection->upgrade_exclude($setup_info);
                $setup_info = GlobalService::get('phpgw_setup')->process->pass($setup_info, 'new', $_REQUEST['debug'], True);
                GlobalService::get('phpgw_info')['setup']['currentver']['phpgwapi'] = 'oldversion';
                break;
            case 'oldversion':
                $setup_info = GlobalService::get('phpgw_setup')->process->pass($setup_info, 'upgrade', $_REQUEST['debug']);
                GlobalService::get('phpgw_info')['setup']['currentver']['phpgwapi'] = 'oldversion';
                break;
        }

        GlobalService::get('phpgw_setup')->db->Halt_On_Error = 'no';

        $setup_tpl->set_var('tableshave', lang('If you did not receive any errors, your applications have been'));
        $setup_tpl->set_var('re-check_my_installation', lang('Re-Check My Installation'));
        $setup_tpl->parse('V_db_stage_6_post', 'B_db_stage_6_post');
        $db_filled_block = $db_filled_block . $setup_tpl->get_var('V_db_stage_6_post');
        $setup_tpl->set_var('V_db_filled_block', $db_filled_block);
        /* Hack to fix database inconsistency */
        GlobalService::get('phpgw_setup')->db->query("ALTER TABLE phpgw_access_log ADD COLUMN browser varchar(200);");
        GlobalService::get('phpgw_setup')->db->query("CREATE TABLE phpgw_async (
				id character varying(255) NOT NULL,
				next integer NOT NULL,
				times character varying(255) NOT NULL,
				method character varying(80) NOT NULL,
				data text NOT NULL,
				account_id integer DEFAULT 0 NOT NULL);");

        break;
    case 10:
        $setup_tpl->set_var('tablescurrent', lang('Your applications are current'));
        $setup_tpl->set_var('uninstall_all_applications', lang('Uninstall all applications'));
        $setup_tpl->set_var('insanity', lang('Insanity'));
        $setup_tpl->set_var('dropwarn', lang('Your tables will be dropped and you will lose data'));
        $setup_tpl->set_var('deletetables', lang('Uninstall all applications'));
        $setup_tpl->parse('V_db_stage_10', 'B_db_stage_10');
        $db_filled_block = $setup_tpl->get_var('V_db_stage_10');
        $setup_tpl->set_var('V_db_filled_block', $db_filled_block);
        break;
    default:
        $setup_tpl->set_var('dbnotexist', lang('Your database does not exist'));
        $setup_tpl->parse('V_db_stage_default', 'B_db_stage_default');
        $db_filled_block = $setup_tpl->get_var('V_db_stage_default');
        $setup_tpl->set_var('V_db_filled_block', $db_filled_block);
        break;
}

// Config Section
$setup_tpl->set_var('config_step_text', lang('Step %1 - Configuration', 2));
GlobalService::get('phpgw_info')['setup']['stage']['config'] = GlobalService::get('phpgw_setup')->detection->check_config();

// begin DEBUG code
// GlobalService::get('phpgw_info')['setup']['stage']['config'] = 10;
// end DEBUG code

switch (GlobalService::get('phpgw_info')['setup']['stage']['config']) {
    case 1:
        $setup_tpl->set_var('config_status_img', $incomplete);
        $setup_tpl->set_var('config_status_alt', lang('not completed'));
        $btn_config_now = GlobalService::get('phpgw_setup')->html->make_frm_btn_simple(lang('Please configure eGroupWare for your environment'), 'POST', 'config.php', 'submit', lang('Configure Now'), '');
        $setup_tpl->set_var('config_table_data', $btn_config_now);
        $setup_tpl->set_var('ldap_table_data', '&nbsp;');
        break;
    case 10:
        $setup_tpl->set_var('config_status_img', $completed);
        $setup_tpl->set_var('config_status_alt', lang('completed'));
        $btn_edit_config = GlobalService::get('phpgw_setup')->html->make_frm_btn_simple(lang('Configuration completed'), 'POST', 'config.php', 'submit', lang('Edit Current Configuration'), '');
        GlobalService::get('phpgw_setup')->db->query("select config_value FROM phpgw_config WHERE config_name='auth_type' and config_app='phpgwapi'");
        GlobalService::get('phpgw_setup')->db->next_record();
        if (GlobalService::get('phpgw_setup')->db->f(0) == 'ldap') {
            GlobalService::get('phpgw_setup')->db->query("select config_value FROM phpgw_config WHERE config_name='ldap_host' and config_app='phpgwapi'");
            GlobalService::get('phpgw_setup')->db->next_record();
            if (GlobalService::get('phpgw_setup')->db->f(0) != '') {
                $btn_config_ldap = GlobalService::get('phpgw_setup')->html->make_frm_btn_simple(lang('LDAP account import/export'), 'POST', 'ldap.php', 'submit', lang('Configure Now'), '');
            } else {
                $btn_config_ldap = '';
            }
            /*
             * GlobalService::get('phpgw_setup')->db->query("select config_value FROM phpgw_config WHERE config_name='webserver_url'");
             * GlobalService::get('phpgw_setup')->db->next_record();
             * if (GlobalService::get('phpgw_setup')->db->f(0))
             * {
             * $link_make_accts = GlobalService::get('phpgw_setup')->html->make_href_link_simple(
             * '<br>',
             * 'setup_demo.php',
             * lang('Click Here'),
             * '<b>'.lang('to setup 1 admin account and 3 demo accounts.').'</b>'
             * );
             * }
             * else
             * {
             * $link_make_accts = '&nbsp;';
             * }
             * }
             * else
             * {
             * $btn_config_ldap = '';
             * $link_make_accts = GlobalService::get('phpgw_setup')->html->make_href_link_simple(
             * '<br>',
             * 'setup_demo.php',
             * lang('Click Here'),
             * '<b>'.lang('to setup 1 admin account and 3 demo accounts.').'</b>'
             * );
             * }
             * $config_td = "$btn_edit_config"."$link_make_accts";
             */
        }
        $setup_tpl->set_var('config_table_data', $btn_edit_config);
        $setup_tpl->set_var('ldap_table_data', $btn_config_ldap);
        break;
    default:
        $setup_tpl->set_var('config_status_img', $incomplete);
        $setup_tpl->set_var('config_status_alt', lang('not completed'));
        $setup_tpl->set_var('config_table_data', lang('Not ready for this stage yet'));
        $setup_tpl->set_var('ldap_table_data', '&nbsp;');
        break;
}
// Admin Account Section
$setup_tpl->set_var('admin_step_text', lang('Step %1 - Admin Account', 3));
switch (GlobalService::get('phpgw_info')['setup']['stage']['config']) {
    case 10:
        // check if there is already a user account (not the anonymous account of sitemgr or a group)
        // Note: this does not check the availiblitly of accounts via other auth-methods then sql and ldap!!

        GlobalService::get('phpgw_setup')->db->query("select config_value FROM phpgw_config WHERE config_name='auth_type' and config_app='phpgwapi'");
        GlobalService::get('phpgw_setup')->db->next_record();
        if (GlobalService::get('phpgw_setup')->db->f(0) == 'ldap') {
            GlobalService::get('phpgw_setup')->db->query("select config_value FROM phpgw_config WHERE config_name='ldap_host' and config_app='phpgwapi'");
            GlobalService::get('phpgw_setup')->db->next_record();
            $ldap_host = GlobalService::get('phpgw_setup')->db->f(0);

            GlobalService::get('phpgw_setup')->db->query("select config_value FROM phpgw_config WHERE config_name='ldap_context' and config_app='phpgwapi'");
            GlobalService::get('phpgw_setup')->db->next_record();
            $ldap_context = GlobalService::get('phpgw_setup')->db->f(0);

            GlobalService::get('phpgw_setup')->db->query("select config_value FROM phpgw_config WHERE config_name='ldap_root_dn' and config_app='phpgwapi'");
            GlobalService::get('phpgw_setup')->db->next_record();
            $ldap_root_dn = GlobalService::get('phpgw_setup')->db->f(0);

            GlobalService::get('phpgw_setup')->db->query("select config_value FROM phpgw_config WHERE config_name='ldap_root_pw' and config_app='phpgwapi'");
            GlobalService::get('phpgw_setup')->db->next_record();
            $ldap_root_pw = GlobalService::get('phpgw_setup')->db->f(0);

            $ldap_connection = ldap_connect($ldap_host);
            ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
            $ldap_bind = ldap_bind($ldap_connection, $ldap_root_dn, $ldap_root_pw);

            $filter = "(&(phpgwaccounttype=u)(uid=expresso-admin))";
            $justthese = array(
                "uidnumber"
            );
            $sr = ldap_search($ldap_connection, $ldap_context, $filter, $justthese);
            $info = ldap_get_entries($ldap_connection, $sr);
            $no_accounts = ! $info['count'];
        } else {
            GlobalService::get('phpgw_setup')->db->query("SELECT count(*) FROM phpgw_accounts WHERE account_type='u' AND account_lid!='anonymous'", __LINE__, __FILE__);
            $no_accounts = ! GlobalService::get('phpgw_setup')->db->next_record() || ! GlobalService::get('phpgw_setup')->db->f(0);
        }
        $setup_tpl->set_var('admin_status_img', $no_accounts ? $incomplete : $completed);
        $setup_tpl->set_var('admin_status_alt', $no_accounts ? lang('not completed') : lang('completed'));
        $setup_tpl->set_var('admin_table_data', GlobalService::get('phpgw_setup')->html->make_frm_btn_simple($no_accounts ? 'Nenhuma conta <font color=red>expresso-admin</font> existe no ldap.' : 'A conta expresso-admin j� existe.', 'POST', 'setup_demo.php', 'submit', lang('Create admin account'), ''));
        break;
    default:
        $setup_tpl->set_var('admin_status_img', $incomplete);
        $setup_tpl->set_var('admin_status_alt', lang('not completed'));
        $setup_tpl->set_var('admin_table_data', lang('Not ready for this stage yet'));
        break;
}

// Lang Section
$setup_tpl->set_var('lang_step_text', lang('Step %1 - Language Management', 4));
GlobalService::get('phpgw_info')['setup']['stage']['lang'] = GlobalService::get('phpgw_setup')->detection->check_lang();

// begin DEBUG code
// GlobalService::get('phpgw_info')['setup']['stage']['lang'] = 0;
// end DEBUG code

switch (GlobalService::get('phpgw_info')['setup']['stage']['lang']) {
    case 1:
        $setup_tpl->set_var('lang_status_img', $incomplete);
        $setup_tpl->set_var('lang_status_alt', 'not completed');
        $btn_install_lang = GlobalService::get('phpgw_setup')->html->make_frm_btn_simple(lang('You do not have any languages installed. Please install one now <br>'), 'POST', 'lang.php', 'submit', lang('Install Language'), '');
        $setup_tpl->set_var('lang_table_data', $btn_install_lang);
        break;
    case 10:
        $langs_list = array();
        $languages = get_langs();
        foreach (GlobalService::get('phpgw_info')['setup']['installed_langs'] as $key => $value) {
            $langs_list[] = isset($languages[$key]) ? $languages[$key]['descr'] : $value;
        }
        $setup_tpl->set_var('lang_status_img', $completed);
        $setup_tpl->set_var('lang_status_alt', 'completed');
        $btn_manage_lang = GlobalService::get('phpgw_setup')->html->make_frm_btn_simple(lang('This stage is completed<br>') . lang('Currently installed languages: %1 <br>', implode(', ', $langs_list)), 'POST', 'lang.php', 'submit', lang('Manage Languages'), '');
        // show system-charset and offer conversation
        include_once (PHPGW_API_INC . '/class.translation_sql.inc.php');
        $translation = new translation();
        $btn_manage_lang .= lang('Current system-charset is %1, click %2here%3 to change it.', $translation->system_charset ? "'$translation->system_charset'" : lang('not set'), '<a href="system_charset.php">', '</a>');
        $setup_tpl->set_var('lang_table_data', $btn_manage_lang);
        break;
    default:
        $setup_tpl->set_var('lang_status_img', $incomplete);
        $setup_tpl->set_var('lang_status_alt', lang('not completed'));
        $setup_tpl->set_var('lang_table_data', lang('Not ready for this stage yet'));
        break;
}

$setup_tpl->set_var('apps_step_text', lang('Step %1 - Advanced Application Management', 5));
// GlobalService::get('phpgw_info')['setup']['stage']['apps'] = GlobalService::get('phpgw_setup')->check_apps();
switch (GlobalService::get('phpgw_info')['setup']['stage']['db']) {
    case 10:
        $setup_tpl->set_var('apps_status_img', $completed);
        $setup_tpl->set_var('apps_status_alt', lang('completed'));
        // check if we have apps to upgrade
        $to_upgrade = array();
        foreach ($setup_info as $app => $data) {
            if ($data['currentver'] && $data['version'] && $data['version'] != $data['currentver']) {
                $to_upgrade[] = $app;
            }
        }
        $btn_manage_apps = GlobalService::get('phpgw_setup')->html->make_frm_btn_simple(count($to_upgrade) ? '<b>' . lang('The following applications need to be upgraded:') . '</b> ' . implode(', ', $to_upgrade) : lang('This stage is completed<br>'), '', 'applications.php', 'submit', lang('Manage Applications'), '');
        $setup_tpl->set_var('apps_table_data', $btn_manage_apps);
        break;
    default:
        $setup_tpl->set_var('apps_status_img', $incomplete);
        $setup_tpl->set_var('apps_status_alt', lang('not completed'));
        $setup_tpl->set_var('apps_table_data', lang('Not ready for this stage yet'));
        break;
}

GlobalService::get('phpgw_setup')->html->show_header(GlobalService::get('phpgw_info')['setup']['header_msg'], False, 'config', GlobalService::get('phpgw_setup')->ConfigDomain . '(' . GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_setup')->ConfigDomain]['db_type'] . ')');
$setup_tpl->pparse('out', 'T_setup_main');
GlobalService::get('phpgw_setup')->html->show_footer();
?>
