<?php
use Expresso\Core\GlobalService;

/**
 *
 * @package Core
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright Copyright (c) 2019 FGSL (http://www.fgsl.eti.br)
 *           
 */
class SetupDetection
{
    function get_versions()
    {
        $d = dir(PHPGW_SERVER_ROOT);
        while ($entry = $d->read()) {
            if ($entry != ".." && ! preg_match('/setup/', $entry) && is_dir(PHPGW_SERVER_ROOT . '/' . $entry)) {
                $f = PHPGW_SERVER_ROOT . '/' . $entry . '/setup/setup.inc.php';
                if (@file_exists($f)) {
                    include ($f);
                    $setup_info[$entry]['filename'] = $f;
                }
            }
        }
        $d->close();

        // _debug_array($setup_info);
        @ksort($setup_info);
        return $setup_info;
    }

    function get_db_versions($setup_info = '')
    {
        $tname = Array();
        GlobalService::get('phpgw_setup')->db->Halt_On_Error = 'no';
        $tables = GlobalService::get('phpgw_setup')->db->table_names();
        foreach ($tables as $key => $val) {
            $tname[] = $val['table_name'];
        }
        $newapps = in_array('phpgw_applications', $tname);
        $oldapps = in_array('applications', $tname);

        if ((count($tables) > 0) && (is_array($tables)) && ($newapps || $oldapps)) {
            /* one of these tables exists. checking for post/pre beta version */
            if ($newapps) {
                GlobalService::get('phpgw_setup')->db->query('SELECT * FROM phpgw_applications', __LINE__, __FILE__);
                while (@GlobalService::get('phpgw_setup')->db->next_record()) {
                    $setup_info[GlobalService::get('phpgw_setup')->db->f('app_name')]['currentver'] = GlobalService::get('phpgw_setup')->db->f('app_version');
                    $setup_info[GlobalService::get('phpgw_setup')->db->f('app_name')]['enabled'] = GlobalService::get('phpgw_setup')->db->f('app_enabled');
                }
                /* This is to catch old setup installs that did not have phpgwapi listed as an app */
                $tmp = @$setup_info['phpgwapi']['version']; /* save the file version */
                if (! @$setup_info['phpgwapi']['currentver']) {
                    $setup_info['phpgwapi']['currentver'] = $setup_info['admin']['currentver'];
                    $setup_info['phpgwapi']['version'] = $setup_info['admin']['currentver'];
                    $setup_info['phpgwapi']['enabled'] = $setup_info['admin']['enabled'];
                    // _debug_array($setup_info['phpgwapi']);exit;
                    // There seems to be a problem here. If ['phpgwapi']['currentver'] is set,
                    // The GLOBALS never gets set.
                    GlobalService::set('setup_info', $setup_info);
                    GlobalService::get('phpgw_setup')->register_app('phpgwapi');
                } else {
                    GlobalService::set('setup_info', $setup_info);
                }
                $setup_info['phpgwapi']['version'] = $tmp; /* restore the file version */
            } elseif ($oldapps) {
                GlobalService::get('phpgw_setup')->db->query('select * from applications');
                while (@GlobalService::get('phpgw_setup')->db->next_record()) {
                    if (GlobalService::get('phpgw_setup')->db->f('app_name') == 'admin') {
                        $setup_info['phpgwapi']['currentver'] = GlobalService::get('phpgw_setup')->db->f('app_version');
                    }
                    $setup_info[GlobalService::get('phpgw_setup')->db->f('app_name')]['currentver'] = GlobalService::get('phpgw_setup')->db->f('app_version');
                }
            }
        }
        // _debug_array($setup_info);
        return $setup_info;
    }

    /*
     * app status values:
     * U Upgrade required/available
     * R upgrade in pRogress
     * C upgrade Completed successfully
     * D Dependency failure
     * P Post-install dependency failure
     * F upgrade Failed
     * V Version mismatch at end of upgrade (Not used, proposed only)
     * M Missing files at start of upgrade (Not used, proposed only)
     */
    function compare_versions($setup_info)
    {
        foreach ($setup_info as $key => $value) {
            // echo '<br>'.$value['name'].'STATUS: '.$value['status'];
            /* Only set this if it has not already failed to upgrade - Milosch */
            if (! ((@$value['status'] == 'F') || (@$value['status'] == 'C'))) {
                // if ($setup_info[$key]['currentver'] > $setup_info[$key]['version'])
                if (GlobalService::get('phpgw_setup')->amorethanb($value['currentver'], @$value['version'])) {
                    $setup_info[$key]['status'] = 'V';
                } elseif (@$value['currentver'] == @$value['version']) {
                    $setup_info[$key]['status'] = 'C';
                } elseif (GlobalService::get('phpgw_setup')->alessthanb(@$value['currentver'], @$value['version'])) {
                    $setup_info[$key]['status'] = 'U';
                } else {
                    $setup_info[$key]['status'] = 'U';
                }
            }
        }
        // _debug_array($setup_info);
        return $setup_info;
    }

    function check_depends($setup_info)
    {
        /* Run the list of apps */
        foreach ($setup_info as $key => $value) {
            /* Does this app have any depends */
            if (isset($value['depends'])) {
                /* If so find out which apps it depends on */
                foreach ($value['depends'] as $depkey => $depvalue) {
                    /* I set this to False until we find a compatible version of this app */
                    $setup_info['depends'][$depkey]['status'] = False;
                    /* Now we loop thru the versions looking for a compatible version */

                    foreach ($depvalue['versions'] as $depskey => $depsvalue) {
                        $currentver = $setup_info[$depvalue['appname']]['currentver'];
                        if ($depvalue['appname'] == 'phpgwapi' && substr($currentver, 0, 6) == '0.9.99') {
                            $currentver = '0.9.14.508';
                        }
                        $major = GlobalService::get('phpgw_setup')->get_major($currentver);
                        if ($major == $depsvalue) {
                            $setup_info['depends'][$depkey]['status'] = True;
                        } else // check if majors are equal and minors greater or equal
                        {
                            $major_depsvalue = GlobalService::get('phpgw_setup')->get_major($depsvalue);
                            list (, , , $minor_depsvalue) = explode('.', $depsvalue);
                            list (, , , $minor) = explode('.', $currentver);
                            if ($major == $major_depsvalue && $minor <= $minor_depsvalue) {
                                $setup_info['depends'][$depkey]['status'] = True;
                            }
                        }
                    }
                }
                /*
                 * Finally, we loop through the dependencies again to look for apps that still have a failure status
                 * If we find one, we set the apps overall status as a dependency failure.
                 */
                foreach ($value['depends'] as $depkey => $depvalue) {
                    if ($setup_info['depends'][$depkey]['status'] == False) {
                        /* Only set this if it has not already failed to upgrade - Milosch */
                        if ($setup_info[$key]['status'] != 'F') // && $setup_info[$key]['status'] != 'C')
                        {
                            if ($setup_info[$key]['status'] == 'C') {
                                $setup_info[$key]['status'] = 'D';
                            } else {
                                $setup_info[$key]['status'] = 'P';
                            }
                        }
                    }
                }
            }
        }
        return $setup_info;
    }

    /*
     * Called during the mass upgrade routine (Stage 1) to check for apps
     * that wish to be excluded from this process.
     */
    function upgrade_exclude($setup_info)
    {
        foreach ($setup_info as $key => $value) {
            if (isset($value['no_mass_update'])) {
                unset($setup_info[$key]);
            }
        }
        return $setup_info;
    }

    function check_header()
    {
        if (! file_exists('../header.inc.php')) {
            GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage One';
            return '1';
        } else {
            if (! @isset(GlobalService::get('phpgw_info')['server']['header_admin_password'])) {
                GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage One (No header admin password set)';
                return '2';
            } elseif (! @isset(GlobalService::get('phpgw_domain'))) {
                GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage One (Add domains to your header.inc.php)';
                return '3';
            } elseif (@GlobalService::get('phpgw_info')['server']['versions']['header'] != @GlobalService::get('phpgw_info')['server']['versions']['current_header']) {
                GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage One (Upgrade your header.inc.php)';
                return '4';
            }
        }
        /* header.inc.php part settled. Moving to authentication */
        GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage One (Completed)';
        return '10';
    }

    function check_db($setup_info = '')
    {
        $setup_info = $setup_info ? $setup_info : GlobalService::get('setup_info');

        GlobalService::get('phpgw_setup')->db->Halt_On_Error = 'no';
        // _debug_array($setup_info);

        if (! GlobalService::get('phpgw_setup')->db->Link_ID) {
            $old = error_reporting();
            error_reporting($old & ~ E_WARNING); // no warnings
            GlobalService::get('phpgw_setup')->db->connect();
            error_reporting($old);
        }
        if (! GlobalService::get('phpgw_setup')->db->Link_ID) {
            GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage 1 (Create Database)';
            return 1;
        }
        if (! isset($setup_info['phpgwapi']['currentver'])) {
            $setup_info = $this->get_db_versions($setup_info);
        }
        // _debug_array($setup_info);
        if (isset($setup_info['phpgwapi']['currentver'])) {
            if (@$setup_info['phpgwapi']['currentver'] == @$setup_info['phpgwapi']['version']) {
                GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage 1 (Tables Complete)';
                return 10;
            } else {
                GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage 1 (Tables need upgrading)';
                return 4;
            }
        } else {
            /* no tables, so checking if we can create them */
            GlobalService::get('phpgw_setup')->db->query('CREATE TABLE phpgw_testrights ( testfield varchar(5) NOT NULL )');
            if (! GlobalService::get('phpgw_setup')->db->Errno) {
                GlobalService::get('phpgw_setup')->db->query('DROP TABLE phpgw_testrights');
                GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage 3 (Install Applications)';
                return 3;
            } else {
                GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage 1 (Create Database)';
                return 1;
            }
        }
    }

    function check_config()
    {
        GlobalService::get('phpgw_setup')->db->Halt_On_Error = 'no';
        if (@GlobalService::get('phpgw_info')['setup']['stage']['db'] != 10) {
            return '';
        }

        /* Since 0.9.10pre6 config table is named as phpgw_config */
        $ver = explode('.', @GlobalService::get('phpgw_info')['server']['versions']['phpgwapi']);
        $config_table = $ver[0] > 0 || (int) $ver[2] > 10 ? 'phpgw_config' : 'config';

        if (preg_match("/([0-9]+)(pre)([0-9]+)/", $ver[2], $regs)) {
            if (($regs[1] == '10') && ($regs[3] >= '6')) {
                $config_table = 'phpgw_config';
            }
        }

        @GlobalService::get('phpgw_setup')->db->query("select config_value from $config_table where config_name='freshinstall'");
        $configured = GlobalService::get('phpgw_setup')->db->next_record() ? GlobalService::get('phpgw_setup')->db->f('config_value') : False;
        if ($configed) {
            GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage 2 (Needs Configuration)';
            return 1;
        } else {
            GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage 2 (Configuration OK)';
            return 10;
        }
    }

    function check_lang($check = True)
    {
        GlobalService::get('phpgw_setup')->db->Halt_On_Error = 'no';
        if ($check && GlobalService::get('phpgw_info')['setup']['stage']['db'] != 10) {
            return '';
        }
        if (! $check) {
            GlobalService::get('setup_info') = GlobalService::get('phpgw_setup')->detection->get_db_versions(GlobalService::get('setup_info'));
        }
        if (GlobalService::get('phpgw_setup')->alessthanb(GlobalService::get('setup_info')['phpgwapi']['currentver'], '0.9.14.501') || preg_match('/0\.9\.15\.00[01]{1,1}/', GlobalService::get('setup_info')['phpgwapi']['currentver'])) {
            $langtbl = 'lang';
            $languagestbl = 'languages';
        } else {
            $langtbl = 'phpgw_lang';
            $languagestbl = 'phpgw_languages';
        }
        GlobalService::get('phpgw_setup')->db->query($q = "SELECT DISTINCT lang FROM $langtbl", __LINE__, __FILE__);
        if (GlobalService::get('phpgw_setup')->db->num_rows() == 0) {
            GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage 3 (No languages installed)';
            return 1;
        } else {
            while (@GlobalService::get('phpgw_setup')->db->next_record()) {
                GlobalService::get('phpgw_info')['setup']['installed_langs'][GlobalService::get('phpgw_setup')->db->f('lang')] = GlobalService::get('phpgw_setup')->db->f('lang');
            }
            foreach (GlobalService::get('phpgw_info')['setup']['installed_langs'] as $key => $value) {
                $sql = "SELECT lang_name FROM $languagestbl WHERE lang_id = '" . $value . "'";
                GlobalService::get('phpgw_setup')->db->query($sql);
                if (GlobalService::get('phpgw_setup')->db->next_record()) {
                    GlobalService::get('phpgw_info')['setup']['installed_langs'][$value] = GlobalService::get('phpgw_setup')->db->f('lang_name');
                }
            }
            GlobalService::get('phpgw_info')['setup']['header_msg'] = 'Stage 3 (Completed)';
            return 10;
        }
    }

    /*
     * @function check_app_tables
     * @abstract Verify that all of an app's tables exist in the db
     * @param $appname
     * @param $any optional, set to True to see if any of the apps tables are installed
     */
    function check_app_tables($appname, $any = False)
    {
        $none = 0;
        $setup_info = GlobalService::get('setup_info');

        if (@$setup_info[$appname]['tables']) {
            /* Make a copy, else we send some callers into an infinite loop */
            $copy = $setup_info;
            GlobalService::get('phpgw_setup')->db->Halt_On_Error = 'no';
            $table_names = GlobalService::get('phpgw_setup')->db->table_names();
            $tables = Array();
            foreach ($table_names as $key => $val) {
                $tables[] = $val['table_name'];
            }
            foreach ($copy[$appname]['tables'] as $key => $val) {
                if (GlobalService::get('DEBUG')) {
                    echo '<br>check_app_tables(): Checking: ' . $appname . ',table: ' . $val;
                }
                if (! in_array($val, $tables)) {
                    if (GlobalService::get('DEBUG')) {
                        echo '<br>check_app_tables(): ' . $val . ' missing!';
                    }
                    if (! $any) {
                        return False;
                    } else {
                        $none ++;
                    }
                } else {
                    if ($any) {
                        if (GlobalService::get('DEBUG')) {
                            echo '<br>check_app_tables(): Some tables installed';
                        }
                        return True;
                    }
                }
            }
        }
        if ($none && $any) {
            if (GlobalService::get('DEBUG')) {
                echo '<br>check_app_tables(): No tables installed';
            }
            return False;
        } else {
            if (GlobalService::get('DEBUG')) {
                echo '<br>check_app_tables(): All tables installed';
            }
            return True;
        }
    }
}
?>
