<?php
namespace Expresso\Core;

use Expresso\Setup\ManageHeader;

/**
 *
 * @package Setup
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright Copyright (c) 2019 FGSL (http://www.fgsl.eti.br)
 *           
 */
error_reporting(error_reporting() & ~ E_NOTICE);

class Functions
{

    public function __construct()
    {
        new CommonFunctions();
        /* Make sure the header.inc.php is current. */
        if (GlobalService::get('phpgw_info')['server']['versions']['header'] < GlobalService::get('phpgw_info')['server']['versions']['current_header']) {
            new ManageHeader();
            return;
        }
        /* Make sure the developer is following the rules. */
        if (! isset(GlobalService::get('phpgw_info')['flags']['currentapp'])) {
            /* This object does not exist yet. */
            /* GlobalService::get('phpgw')->log->write(array('text'=>'W-MissingFlags, currentapp flag not set')); */

            echo '<b>!!! YOU DO NOT HAVE YOUR GlobalService::get(\'phpgw_info\'][\'flags\'][\'currentapp\'] SET !!!';
            echo '<br>!!! PLEASE CORRECT THIS SITUATION !!!</b>';
        }

        if (get_magic_quotes_runtime())
            @set_magic_quotes_runtime(false);
        print_debug('sane environment', 'messageonly', 'api');

        /**
         * **************************************************************************\
         * Multi-Domain support *
         * \***************************************************************************
         */

        /* make them fix their header */
        if (! GlobalService::isset('phpgw_domain')) {
            echo '<center><b>The administrator must upgrade the header.inc.php file before you can continue.</b></center>';
            exit();
        }
        if (! isset(GlobalService::get('phpgw_info')['server']['default_domain']) || // allow to overwrite the default domain
        ! isset(GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['server']['default_domain']])) {
            reset(GlobalService::get('phpgw_domain'));
            list (GlobalService::get('phpgw_info')['server']['default_domain']) = each(GlobalService::get('phpgw_domain'));
        }
        if (isset($_POST['login'])) // on login
        {
            GlobalService::set('login', $_POST['login']);
            if (strstr(GlobalService::get('login'), '@') === False || count(GlobalService::get('phpgw_domain')) == 1) {
                GlobalService::set('login', GlobalService::get('login') . '@' . get_var('logindomain', array(
                    'POST'
                ), GlobalService::get('phpgw_info')['server']['default_domain']));
            }
            $parts = explode('@', GlobalService::get('login'));
            GlobalService::get('phpgw_info')['user']['domain'] = array_pop($parts);
        } else // on "normal" pageview
        {
            GlobalService::get('phpgw_info')['user']['domain'] = get_var('domain', array(
                'GET',
                'COOKIE'
            ), FALSE);
        }

        if (@isset(GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['user']['domain']])) {
            GlobalService::get('phpgw_info')['server']['db_host'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['user']['domain']]['db_host'];
            GlobalService::get('phpgw_info')['server']['db_port'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['user']['domain']]['db_port'];
            GlobalService::get('phpgw_info')['server']['db_name'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['user']['domain']]['db_name'];
            GlobalService::get('phpgw_info')['server']['db_user'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['user']['domain']]['db_user'];
            GlobalService::get('phpgw_info')['server']['db_pass'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['user']['domain']]['db_pass'];
            GlobalService::get('phpgw_info')['server']['db_type'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['user']['domain']]['db_type'];
        } else {
            GlobalService::get('phpgw_info')['server']['db_host'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['server']['default_domain']]['db_host'];
            GlobalService::get('phpgw_info')['server']['db_port'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['server']['default_domain']]['db_port'];
            GlobalService::get('phpgw_info')['server']['db_name'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['server']['default_domain']]['db_name'];
            GlobalService::get('phpgw_info')['server']['db_user'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['server']['default_domain']]['db_user'];
            GlobalService::get('phpgw_info')['server']['db_pass'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['server']['default_domain']]['db_pass'];
            GlobalService::get('phpgw_info')['server']['db_type'] = GlobalService::get('phpgw_domain')[GlobalService::get('phpgw_info')['server']['default_domain']]['db_type'];
        }

        if (GlobalService::get('phpgw_info')['flags']['currentapp'] != 'login') {
            GlobalService::unset('phpgw_domain'); // we kill this for security reasons
        }

        print_debug('domain', @GlobalService::get('phpgw_info')['user']['domain'], 'api');

        /**
         * **************************************************************************\
         * These lines load up the API, fill up the $phpgw_info array, etc *
         * \***************************************************************************
         */
        /* Load main class */
        GlobalService::set('phpgw', CreateObject('phpgwapi.phpgw'));
        /**
         * **********************************************************************\
         * Load up the main instance of the db class.
         * *
         * \***********************************************************************
         */
        GlobalService::get('phpgw')->db = CreateObject('phpgwapi.db_egw');
        if (GlobalService::get('phpgw')->debug) {
            GlobalService::get('phpgw')->db->Debug = 1;
        }
        GlobalService::get('phpgw')->db->Halt_On_Error = 'no';
        /* jakjr: ExpressoLivre: We do not count the config table. */
        if (! GlobalService::get('phpgw')->db->connect(GlobalService::get('phpgw_info')['server']['db_name'], GlobalService::get('phpgw_info')['server']['db_host'], GlobalService::get('phpgw_info')['server']['db_port'], GlobalService::get('phpgw_info')['server']['db_user'], GlobalService::get('phpgw_info')['server']['db_pass'], GlobalService::get('phpgw_info')['server']['db_type'])) 
        // @GlobalService::get('phpgw')->db->query("SELECT COUNT(config_name) FROM phpgw_config");
        // if(!@GlobalService::get('phpgw')->db->next_record())
        {

            /* BEGIN - CELEPAR - jakjr - 05/06/2006 */
            /* $setup_dir = str_replace($_SERVER['PHP_SELF'],'index.php','setup/'); */
            /*
             * echo '<center><b>Fatal Error:</b> It appears that you have not created the database tables for '
             * .'eGroupWare. Click <a href="' . $setup_dir . '">here</a> to run setup.</center>';
             */
            echo '<center><b>' . lang("ExpressoLivre is unavailable at this moment. Code %1<br>Please, try later.", "001") . '</b></center>';
            /* END - CELEPAR - jakjr - 05/06/2006 */
            exit();
        }
        GlobalService::get('phpgw')->db->Halt_On_Error = 'yes';

        /* Fill phpgw_info["server"] array */
        // An Attempt to speed things up using cache premise
        /* jakjr: ExpressoLivre does not use cache. */
        /*
         * GlobalService::get('phpgw')->db->query("select config_value from phpgw_config WHERE config_app='phpgwapi' and config_name='cache_phpgw_info'",__LINE__,__FILE__);
         * if (GlobalService::get('phpgw')->db->num_rows())
         * {
         * GlobalService::get('phpgw')->db->next_record();
         * GlobalService::get('phpgw_info')['server']['cache_phpgw_info'] = stripslashes(GlobalService::get('phpgw')->db->f('config_value'));
         * }
         */

        /* jakjr: ExpressoLivre does not use cache. */
        /*
         * $cache_query = "select content from phpgw_app_sessions where"
         * ." sessionid = '0' and loginid = '0' and app = 'phpgwapi' and location = 'config'";
         *
         * GlobalService::get('phpgw')->db->query($cache_query,__LINE__,__FILE__);
         * $server_info_cache = GlobalService::get('phpgw')->db->num_rows();
         */
        /*
         * if(@GlobalService::get('phpgw_info')['server']['cache_phpgw_info'] && $server_info_cache)
         * {
         * GlobalService::get('phpgw')->db->next_record();
         * GlobalService::get('phpgw_info')['server'] = unserialize(stripslashes(GlobalService::get('phpgw')->db->f('content')));
         * }
         * else
         * {
         */
        GlobalService::get('phpgw')->db->query("SELECT * from phpgw_config WHERE config_app='phpgwapi'", __LINE__, __FILE__);
        while (GlobalService::get('phpgw')->db->next_record()) {
            GlobalService::get('phpgw_info')['server'][GlobalService::get('phpgw')->db->f('config_name')] = stripslashes(GlobalService::get('phpgw')->db->f('config_value'));
        }

        /*
         * if(@isset(GlobalService::get('phpgw_info')['server']['cache_phpgw_info']))
         * {
         * if($server_info_cache)
         * {
         * $cache_query = "DELETE FROM phpgw_app_sessions WHERE sessionid='0' and loginid='0' and app='phpgwapi' and location='config'";
         * GlobalService::get('phpgw')->db->query($cache_query,__LINE__,__FILE__);
         * }
         * $cache_query = 'INSERT INTO phpgw_app_sessions(sessionid,loginid,app,location,content) VALUES('
         * . "'0','0','phpgwapi','config','".addslashes(serialize(GlobalService::get('phpgw_info')['server']))."')";
         * GlobalService::get('phpgw')->db->query($cache_query,__LINE__,__FILE__);
         * }
         */
        // }
        unset($cache_query);
        unset($server_info_cache);
        if (@isset(GlobalService::get('phpgw_info')['server']['enforce_ssl']) && ! $_SERVER['HTTPS']) {
            Header('Location: https://' . GlobalService::get('phpgw_info')['server']['hostname'] . GlobalService::get('phpgw_info')['server']['webserver_url'] . $_SERVER['REQUEST_URI']);
            exit();
        }

        /**
         * **************************************************************************\
         * This is a global constant that should be used *
         * instead of / or \ in file paths *
         * \***************************************************************************
         */
        define('SEP', filesystem_separator());

        /**
         * **********************************************************************\
         * Required classes *
         * \***********************************************************************
         */
        GlobalService::get('phpgw')->log = CreateObject('phpgwapi.errorlog');
        GlobalService::get('phpgw')->translation = CreateObject('phpgwapi.translation');
        GlobalService::get('phpgw')->common = CreateObject('phpgwapi.common');
        GlobalService::get('phpgw')->hooks = CreateObject('phpgwapi.hooks');
        GlobalService::get('phpgw')->auth = CreateObject('phpgwapi.auth_egw');
        GlobalService::get('phpgw')->accounts = CreateObject('phpgwapi.accounts');
        GlobalService::get('phpgw')->acl = CreateObject('phpgwapi.acl');
        GlobalService::get('phpgw')->session = CreateObject('phpgwapi.sessions');
        GlobalService::get('phpgw')->preferences = CreateObject('phpgwapi.preferences');
        GlobalService::get('phpgw')->applications = CreateObject('phpgwapi.applications');
        GlobalService::get('phpgw')->css = CreateObject('phpgwapi.css');
        print_debug('main class loaded', 'messageonly', 'api');
        if (! isset(GlobalService::get('phpgw_info')['flags']['included_classes']['error']) || ! GlobalService::get('phpgw_info')['flags']['included_classes']['error']) {
            include_once (PHPGW_INCLUDE_ROOT . '/phpgwapi/inc/class.error_sys.inc.php');
            GlobalService::get('phpgw_info')['flags']['included_classes']['error'] = True;
        }

        /**
         * ***************************************************************************\
         * ACL defines - moved here to work for xml-rpc/soap, also *
         * \****************************************************************************
         */
        define('PHPGW_ACL_READ', 1);
        define('PHPGW_ACL_ADD', 2);
        define('PHPGW_ACL_EDIT', 4);
        define('PHPGW_ACL_DELETE', 8);
        define('PHPGW_ACL_PRIVATE', 16);
        define('PHPGW_ACL_GROUP_MANAGERS', 32);
        define('PHPGW_ACL_CUSTOM_1', 64);
        define('PHPGW_ACL_CUSTOM_2', 128);
        define('PHPGW_ACL_CUSTOM_3', 256);

        /**
         * **************************************************************************\
         * Forcing the footer to run when the rest of the script is done.
         * *
         * \***************************************************************************
         */
        register_shutdown_function(array(
            GlobalService::get('phpgw')->common,
            'phpgw_final'
        ));

        /**
         * **************************************************************************\
         * Stuff to use if logging in or logging out *
         * \***************************************************************************
         */
        if (GlobalService::get('phpgw_info')['flags']['currentapp'] == 'login' || GlobalService::get('phpgw_info')['flags']['currentapp'] == 'logout') {
            if (GlobalService::get('phpgw_info')['flags']['currentapp'] == 'login') {
                if (@$_POST['login'] != '') {
                    if (count(GlobalService::get('phpgw_domain')) > 1) {
                        list ($login) = explode('@', $_POST['login']);
                    } else {
                        $login = $_POST['login'];
                    }
                    print_debug('LID', $login, 'app');
                    $login_id = GlobalService::get('phpgw')->accounts->name2id($login);
                    print_debug('User ID', $login_id, 'app');
                    GlobalService::get('phpgw')->accounts->accounts($login_id);
                    GlobalService::get('phpgw')->preferences->preferences($login_id);
                    GlobalService::get('phpgw')->datetime = CreateObject('phpgwapi.date_time');
                }
            }
        /**
         * ************************************************************************\
         * Everything from this point on will ONLY happen if *
         * the currentapp is not login or logout *
         * \*************************************************************************
         */
        } else {
            if (! GlobalService::get('phpgw')->session->verify()) {
                // we forward to the same place after the re-login
                if (GlobalService::get('phpgw_info')['server']['webserver_url'] && GlobalService::get('phpgw_info')['server']['webserver_url'] != '/') {
                    list (, $relpath) = explode(GlobalService::get('phpgw_info')['server']['webserver_url'], $_SERVER['PHP_SELF'], 2);
                } else // the webserver-url is empty or just a slash '/' (eGW is installed in the docroot and no domain given)
                {
                    if (preg_match('/^https?:\/\/[^\/]*\/(.*)$/', $relpath = $_SERVER['PHP_SELF'], $matches)) {
                        $relpath = $matches[1];
                    }
                }

                // this removes the sessiondata if its saved in the URL
                $query = preg_replace('/[&]?sessionid(=|%3D)[^&]+&kp3(=|%3D)[^&]+&domain=.*$/', '', $_SERVER['QUERY_STRING']);
                Header('Location: ' . GlobalService::get('phpgw_info')['server']['webserver_url'] . '/login.php?cd=10&phpgw_forward=' . urlencode($relpath . (! empty($query) ? '?' . $query : '')));
                exit();
            }

            GlobalService::get('phpgw')->datetime = CreateObject('phpgwapi.date_time');

            /* A few hacker resistant constants that will be used throught the program */
            define('PHPGW_TEMPLATE_DIR', ExecMethod('phpgwapi.phpgw.common.get_tpl_dir', 'phpgwapi'));
            define('PHPGW_IMAGES_DIR', ExecMethod('phpgwapi.phpgw.common.get_image_path', 'phpgwapi'));
            define('PHPGW_IMAGES_FILEDIR', ExecMethod('phpgwapi.phpgw.common.get_image_dir', 'phpgwapi'));
            define('PHPGW_APP_ROOT', ExecMethod('phpgwapi.phpgw.common.get_app_dir'));
            define('PHPGW_APP_INC', ExecMethod('phpgwapi.phpgw.common.get_inc_dir'));
            define('PHPGW_APP_TPL', ExecMethod('phpgwapi.phpgw.common.get_tpl_dir'));
            define('PHPGW_IMAGES', ExecMethod('phpgwapi.phpgw.common.get_image_path'));
            define('PHPGW_APP_IMAGES_DIR', ExecMethod('phpgwapi.phpgw.common.get_image_dir'));

            /**
             * ******* This sets the user variables ********
             */
            GlobalService::get('phpgw_info')['user']['private_dir'] = GlobalService::get('phpgw_info')['server']['files_dir'] . '/users/' . GlobalService::get('phpgw_info')['user']['userid'];

            /* This will make sure that a user has the basic default prefs. If not it will add them */
            GlobalService::get('phpgw')->preferences->verify_basic_settings();

            /**
             * ******* Optional classes, which can be disabled for performance increases ********
             */
            while ($phpgw_class_name = each(GlobalService::get('phpgw_info')['flags'])) {
                if (preg_match('/enable_/', $phpgw_class_name[0])) {
                    $enable_class = str_replace('enable_', '', $phpgw_class_name[0]);
                    $enable_class = str_replace('_class', '', $enable_class);
                    eval('GlobalService::get("phpgw"]->' . $enable_class . ' = createobject(\'phpgwapi.' . $enable_class . '\');');
                }
            }
            unset($enable_class);
            reset(GlobalService::get('phpgw_info')['flags']);

            if (! include (PHPGW_SERVER_ROOT . '/phpgwapi/themes/' . get_theme() . '.theme')) {
                if (! include (PHPGW_SERVER_ROOT . '/phpgwapi/themes/default.theme')) {
                    /* Hope we don't get to this point. Better then the user seeing a */
                    /* complety back screen and not know whats going on */
                    echo '<body bgcolor="FFFFFF">';
                    GlobalService::get('phpgw')->log->write(array(
                        'text' => 'F-Abort, No themes found'
                    ));

                    exit();
                }
            }

            /**
             * ***********************************************************************\
             * These lines load up the templates class *
             * \************************************************************************
             */
            if (! @GlobalService::get('phpgw_info')['flags']['disable_Template_class']) {
                GlobalService::get('phpgw')->template = CreateObject('phpgwapi.Template', PHPGW_APP_TPL);
                preg_match('/(.*)\/(.*)/', PHPGW_APP_TPL, $matches);

                $_SESSION['phpgw_info'][GlobalService::get('phpgw_info')['flags']['currentapp']]['user']['preferences']['common']['template_set'] = $matches[2];
            }

            /**
             * ***********************************************************************\
             * If they are using frames, we need to set some variables *
             * \************************************************************************
             */
            if (((isset(GlobalService::get('phpgw_info')['user']['preferences']['common']['useframes']) && GlobalService::get('phpgw_info')['user']['preferences']['common']['useframes']) && GlobalService::get('phpgw_info')['server']['useframes'] == 'allowed') || (GlobalService::get('phpgw_info')['server']['useframes'] == 'always')) {
                GlobalService::get('phpgw_info')['flags']['navbar_target'] = 'phpgw_body';
            }

            /**
             * ***********************************************************************\
             * Verify that the users session is still active otherwise kick them out *
             * \************************************************************************
             */
            if (GlobalService::get('phpgw_info')['flags']['currentapp'] != 'home' && GlobalService::get('phpgw_info')['flags']['currentapp'] != 'about' && GlobalService::get('phpgw_info')['flags']['currentapp'] != 'mobile') {
                // This will need to use ACL in the future
                if (! GlobalService::get('phpgw_info')['user']['apps'][GlobalService::get('phpgw_info')['flags']['currentapp']] || (@GlobalService::get('phpgw_info')['flags']['admin_only'] && ! GlobalService::get('phpgw_info')['user']['apps']['admin'])) {
                    GlobalService::get('phpgw')->common->phpgw_header();
                    if (GlobalService::get('phpgw_info')['flags']['noheader']) {
                        echo parse_navbar();
                    }

                    GlobalService::get('phpgw')->log->write(array(
                        'text' => 'W-Permissions, Attempted to access %1',
                        'p1' => GlobalService::get('phpgw_info')['flags']['currentapp']
                    ));

                    echo '<p><center><b>' . lang('Access not permitted') . '</b></center>';
                    GlobalService::get('phpgw')->common->phpgw_exit(True);
                }
            }

            if (! is_object(GlobalService::get('phpgw')->datetime)) {
                GlobalService::get('phpgw')->datetime = CreateObject('phpgwapi.date_time');
            }
            GlobalService::get('phpgw')->applications->read_installed_apps(); // to get translated app-titles

            /**
             * ***********************************************************************\
             * Load the header unless the developer turns it off *
             * \************************************************************************
             */
            if (! @GlobalService::get('phpgw_info')['flags']['noheader']) {
                GlobalService::get('phpgw')->common->phpgw_header();
            }
        }
    }

    /*
     * !
     * @function lang
     * @abstract function to handle multilanguage support
     */
    public static function lang($key, $m1 = '', $m2 = '', $m3 = '', $m4 = '', $m5 = '', $m6 = '', $m7 = '', $m8 = '', $m9 = '', $m10 = '')
    {
        if (is_array($m1)) {
            $vars = $m1;
        } else {
            $vars = array(
                $m1,
                $m2,
                $m3,
                $m4,
                $m5,
                $m6,
                $m7,
                $m8,
                $m9,
                $m10
            );
        }
        // Get the translation from Lang File, if the database is down.
        if (! GlobalService::get('phpgw')->translation) {
            $fn = PHPGW_SERVER_ROOT . '/phpgwapi/setup/phpgw_' . GlobalService::get('_SERVER')['HTTP_ACCEPT_LANGUAGE'] . '.lang';
            if (file_exists($fn)) {
                $fp = fopen($fn, 'r');
                while ($data = fgets($fp, 16000)) {
                    list ($message_id, $app_name, $null, $content) = explode("\t", substr($data, 0, - 1));
                    GlobalService::get('phpgw_info')['phpgwapi']['lang'][$message_id] = $content;
                }
                fclose($fp);
            }
            $return = str_replace('%1', $vars[0], GlobalService::get('phpgw_info')['phpgwapi']['lang'][$key]);
            return $return;
        }
        $value = GlobalService::get('phpgw')->translation->translate("$key", $vars);
        return $value;
    }

    public function get_theme()
    {
        $test_cookie = get_var('THEME', 'COOKIE');

        // se o cookie foi definido coloca tema na sessão
        if (! empty($test_cookie)) {
            $_SESSION['THEME'] = $test_cookie;
        }

        // se tema não estiver definido na sessão retorna GlobalService::get('phpgw_info')['user']['preferences']['common']['theme']
        if (! (isset($_SESSION['THEME']) && $_SESSION['THEME'])) {
            return GlobalService::get('phpgw_info')['user']['preferences']['common']['theme'];
        }

        // senão retorna o tema definido na sessão
        return $_SESSION['THEME'];
    }

    public static function lang_select($onChange = False, $ConfigLang = '')
    {
        if (! $ConfigLang) {
            $ConfigLang = get_var('ConfigLang', Array(
                'POST',
                'COOKIE'
            ));
        }
        $select = '<select name="ConfigLang"' . ($onChange ? ' onChange="this.form.submit();"' : '') . '>' . "\n";
        $languages = self::get_langs();
        usort($languages, create_function('$a,$b', 'return strcmp(@$a[\'descr\'],@$b[\'descr\']);'));
        foreach ($languages as $data) {
            if ($data['available'] && ! empty($data['lang'])) {
                $selected = '';
                $short = substr($data['lang'], 0, 2);
                if ($short == $ConfigLang || empty($ConfigLang) && $short == substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)) {
                    $selected = ' selected';
                }
                $select .= '<option value="' . $data['lang'] . '"' . $selected . '>' . $data['descr'] . '</option>' . "\n";
            }
        }
        $select .= '</select>' . "\n";

        return $select;
    }

    public static function get_langs()
    {
        $f = fopen('./lang/languages', 'rb');
        while ($line = fgets($f, 200)) {
            list ($x, $y) = explode("\t", $line);
            $languages[$x]['lang'] = trim($x);
            $languages[$x]['descr'] = trim($y);
            $languages[$x]['available'] = False;
        }
        fclose($f);

        $d = dir('./lang');
        while ($file = $d->read()) {
            if (preg_match('/^phpgw_([-a-z]+).lang$/i', $file, $matches)) {
                $languages[$matches[1]]['available'] = True;
            }
        }
        $d->close();

        // print_r($languages);
        return $languages;
    }
}