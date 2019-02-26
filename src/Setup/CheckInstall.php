<?php
namespace Expresso\Setup;

use Expresso\Core\Functions;
use Expresso\Core\GlobalService;

/**
 *
 * @package Setup
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright Copyright (c) 2019 FGSL (http://www.fgsl.eti.br)
 *           
 */
class CheckInstall
{

    private $intro = null;

    public function __construct($intro = null)
    {
        $this->intro = $intro;
        $run_by_webserver = ! ! $_SERVER['PHP_SELF'];
        $is_windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';

        if ($run_by_webserver) {
            $phpgw_info = array();
            GlobalService::get('phpgw_info')['flags'] = array(
                'noheader' => True,
                'nonavbar' => True,
                'currentapp' => 'home',
                'noapi' => True
            );
            $safe_er = error_reporting();
            error_reporting($safe_er);

            GlobalService::get('phpgw_info')['setup']['stage']['header'] = GlobalService::get('phpgw_setup')->detection->check_header();
            if (GlobalService::get('phpgw_info')['setup']['stage']['header'] == '10') {
                // Check header and authentication
                if (! GlobalService::get('phpgw_setup')->auth('Config') && ! GlobalService::get('phpgw_setup')->auth('Header')) {
                    Header('Location: index.php');
                    exit();
                }
            }
            $passed_icon = '<img src="templates/default/images/completed.png" title="Passed" align="middle"> ';
            $error_icon = '<img src="templates/default/images/incomplete.png" title="Error" align="middle"><span id="setup_error">';
            $warning_icon = '<img src="templates/default/images/dep.png" title="Warning" align="middle"><span id="setup_warning"> ';
        } else {
            $passed_icon = '>>> Passed ';
            $error_icon = '*** Error: ';
            $warning_icon = '!!! Warning: ';
        }
        $checks = array(
            'safe_mode' => array(
                'func' => 'php_ini_check',
                'value' => 0,
                'verbose_value' => 'Off',
                'warning' => $this->lang('safe_mode is turned on, which is generaly a good thing as it makes your install more secure.') . "\n" . $this->lang('If safe_mode is turned on, eGW is not able to change certain settings on runtime, nor can we load any not yet loaded module.') . "\n" . $this->lang('*** You have to do the changes manualy in your php.ini (usualy in /etc on linux) in order to get eGW fully working !!!') . "\n" . $this->lang('*** Do NOT update your database via setup, as the update might be interrupted by the max_execution_time, which leaves your DB in an unrecoverable state (your data is lost) !!!')
            ),
            'magic_quotes_runtime' => array(
                'func' => 'php_ini_check',
                'value' => 0,
                'verbose_value' => 'Off',
                'safe_mode' => 'magic_quotes_runtime = Off'
            ),
            'register_globals' => array(
                'func' => 'php_ini_check',
                'value' => 0,
                'verbose_value' => 'Off',
                'warning' => $this->lang('register_globals is turned On, expresso4 does NOT require it and it\'s generaly more secure to have it turned Off')
            ),
            'memory_limit' => array(
                'func' => 'php_ini_check',
                'value' => '16M',
                'check' => '>=',
                'error' => $this->lang('memory_limit is set to less than 16M: some applications of expresso4 need more than the recommend 8M, expect occasional failures'),
                'change' => 'memory_limit = 16M'
            ),
            'max_execution_time' => array(
                'func' => 'php_ini_check',
                'value' => 30,
                'check' => '>=',
                'error' => $this->lang('max_execution_time is set to less than 30 (seconds): eGroupWare sometimes needs a higher execution_time, expect occasional failures'),
                'safe_mode' => 'max_execution_time = 30'
            ),
            'include_path' => array(
                'func' => 'php_ini_check',
                'value' => '.',
                'check' => 'contain',
                'error' => $this->lang('include_path need to contain "." - the current directory'),
                'save_mode' => 'max_execution_time = 30'
            ),
            'ldap' => array(
                'func' => 'extension_check',
                'warning' => "<div id='setup_info'>" . $this->lang('The %1 extension is needed, if you plan to use a %2 database.', 'ldap', 'LDAP') . '</div>'
            ),
            'pgsql' => array(
                'func' => 'extension_check',
                'warning' => '<div id="setup_info">' . $this->lang('The %1 extension is needed, if you plan to use a %2 database.', 'pgsql', 'pgSQL') . '</div>'
            ),
            'mssql' => array(
                'func' => 'extension_check',
                'warning' => '<div id="setup_info">' . $this->lang('The %1 extension is needed, if you plan to use a %2 database.', 'mssql', 'MsSQL') . '</div>',
                'win_only' => True
            ),
            'mbstring' => array(
                'func' => 'extension_check',
                'warning' => '<div id="setup_info">' . $this->lang('The mbstring extension is needed to fully support unicode (utf-8) or other multibyte-charsets.') . "</div>"
            ),
            'mbstring.func_overload' => array(
                'func' => 'php_ini_check',
                'value' => 0,
                'warning' => '<div id="setup_info">' . $this->lang('The mbstring.func_overload = 0 is needed to fully support unicode (utf-8) or other multibyte-charsets.') . "</div>",
                'change' => extension_loaded('mbstring') || function_exists('dl') && (version_compare(PHP_VERSION, '5.3.0', '<') && @dl(PHP_SHLIB_PREFIX . 'mbstring.' . PHP_SHLIB_SUFFIX)) ? 'mbstring.func_overload = 0' : ''
            ),
            'imap' => array(
                'func' => 'extension_check',
                'warning' => '<div id="setup_info">' . $this->lang('The imap extension is needed by the two email apps (even if you use email with pop3 as protocoll).') . '</div>'
            ),
            'session' => array(
                'func' => 'extension_check',
                'warning' => '<div id="setup_info">' . $this->lang('The session extension is needed to use php4 session (db-sessions work without).') . "</div>"
            ),
            '.' => array(
                'func' => 'permission_check',
                'is_world_writable' => False,
                'recursiv' => True
            ),
            'header.inc.php' => array(
                'func' => 'permission_check',
                'is_world_readable' => False,
                'only_if_exists' => @GlobalService::get('phpgw_info')['setup']['stage']['header'] != 10
            ),
            'phpgwapi/images' => array(
                'func' => 'permission_check',
                'is_writable' => True
            )
        );

        // some constants for pre php4.3
        if (! defined('PHP_SHLIB_SUFFIX')) {
            define('PHP_SHLIB_SUFFIX', $is_windows ? 'dll' : 'so');
        }
        if (! defined('PHP_SHLIB_PREFIX')) {
            define('PHP_SHLIB_PREFIX', PHP_SHLIB_SUFFIX == 'dll' ? 'php_' : '');
        }

        if ($run_by_webserver) {
            $tpl_root = GlobalService::get('phpgw_setup')->html->setup_tpl_dir('setup');
            $setup_tpl = CreateObject('setup.Template', $tpl_root);
            $setup_tpl->set_file(array(
                'T_head' => 'head.tpl',
                'T_footer' => 'footer.tpl'
            ));
            $ConfigDomain = get_var('ConfigDomain', Array(
                'POST',
                'COOKIE'
            ));
            if (! empty($this->intro)) {
                if ($ConfigLang = get_var('ConfigLang', array(
                    'POST',
                    'COOKIE'
                ))) {
                    GlobalService::get('phpgw_setup')->set_cookie('ConfigLang', $ConfigLang, (int) (time() + (1200 * 9)), '/');
                }
                GlobalService::get('phpgw_setup')->html->show_header($this->lang('Welcome to the Expresso Livre (eGroupWare) Installation'), False, 'config');
                echo '<h1>' . $this->lang('Welcome to the Expresso Livre (eGroupWare) Installation') . "</h1>\n";
                if (! $ConfigLang) {
                    echo '<p><form action="check_install.php?intro=1" method="Post">Please select your language ' . Functions::lang_select(True, 'en') . "</form></p>\n";
                }
                echo '<p>' . $this->lang('The first step in installing Expresso Livre (eGroupWare) is to ensure your environment has the necessary settings to correctly run the application.');
                echo '<br /><br />' . $this->lang('We will now run a series of tests, which may take a few minutes.  Click the link below to proceed.');
                echo '<h3><a href="check_install.php">' . $this->lang('Run installation tests') . '</a></h3>';
                $setup_tpl->pparse('out', 'T_footer');
                exit();
            } else {
                GlobalService::get('phpgw_setup')->html->show_header($this->lang('Checking the Expresso Livre (eGroupWare) Installation'), False, 'config', $ConfigDomain ? $ConfigDomain . '(' . @$phpgw_domain[$ConfigDomain]['db_type'] . ')' : '');
                echo '<h1>' . $this->lang('Checking the Expresso Livre (eGroupWare) Installation') . "</h1>\n";
            }
        } else {
            echo "Checking the Expresso Livre (eGroupWare) Installation\n";
            echo "====================================\n\n";
        }

        $Ok = True;
        foreach ($checks as $name => $args) {
            $check_ok = $args['func']($name, $args);
            $Ok = $Ok && $check_ok;
        }

        if ($run_by_webserver) {

            if (GlobalService::get('phpgw_info')['setup']['stage']['header'] != 10) {
                if (! $Ok) {
                    echo '<h2>' . $this->lang('For correct instalation of Expresso Livre, the LDAP MUST be installed !!') . "</h2>\n";
                    echo '<h3>' . $this->lang('Please fix the above errors (%1) and warnings(%2)', $error_icon, $warning_icon) . "</h3>\n";
                    echo '<h3><a href="check_install.php">' . $this->lang('Click here to re-run the installation tests') . "</a></h3>\n";
                    echo '<h3>' . $this->lang('or %1Continue to the Header Admin%2', '<a href="manageheader.php">', '</a>') . "</h3>\n";
                } else {
                    echo '<h2>' . $this->lang('For correct instalation of Expresso Livre, the LDAP MUST be installed !!') . "</h2>\n";
                    echo '<h3><a href="manageheader.php">' . $this->lang('Continue to the Header Admin') . "</a></h3>\n";
                }
            } else {
                echo '<h3>';
                if (! $Ok) {
                    echo $this->lang('Please fix the above errors (%1) and warnings(%2)', $error_icon, $warning_icon) . '. ';
                }
                echo '<br><a href="' . str_replace('check_install.php', '', $_SERVER['HTTP_REFERER']) . '">' . $this->lang('Return to Setup') . "</a></h3>\n";
            }
            $setup_tpl->pparse('out', 'T_footer');
        }
    }

    function extension_check($name, $args)
    {
        global $passed_icon, $error_icon, $warning_icon, $is_windows;

        if (isset($args['win_only']) && $args['win_only'] && ! $is_windows) {
            return True; // check only under windows
        }
        // we check for the existens of 'dl', as multithreaded webservers dont have it !!!
        $availible = extension_loaded($name) || function_exists('dl') && (version_compare(PHP_VERSION, '5.3.0', '<') && @dl(PHP_SHLIB_PREFIX . $name . '.' . PHP_SHLIB_SUFFIX));

        echo ($availible ? $passed_icon : $warning_icon) . ' ' . $this->lang('Checking extension %1 is loaded or loadable', $name) . ': ' . ($availible ? $this->lang('True') : $this->lang('False')) . "</span><br>\n";

        if (! $availible) {
            echo $args['warning'];
        }
        echo "\n";

        return $availible;
    }

    function verbosePerms($in_Perms)
    {
        if ($in_Perms & 0x1000) // FIFO pipe
        {
            $sP = 'p';
        } elseif ($in_Perms & 0x2000) // Character special
        {
            $sP = 'c';
        } elseif ($in_Perms & 0x4000) // Directory
        {
            $sP = 'd';
        } elseif ($in_Perms & 0x6000) // Block special
        {
            $sP = 'b';
        } elseif ($in_Perms & 0x8000) // Regular
        {
            $sP = '-';
        } elseif ($in_Perms & 0xA000) // Symbolic Link
        {
            $sP = 'l';
        } elseif ($in_Perms & 0xC000) // Socket
        {
            $sP = 's';
        } else // UNKNOWN
        {
            $sP = 'u';
        }

        // owner
        $sP .= (($in_Perms & 0x0100) ? 'r' : '-') . (($in_Perms & 0x0080) ? 'w' : '-') . (($in_Perms & 0x0040) ? (($in_Perms & 0x0800) ? 's' : 'x') : (($in_Perms & 0x0800) ? 'S' : '-'));

        // group
        $sP .= (($in_Perms & 0x0020) ? 'r' : '-') . (($in_Perms & 0x0010) ? 'w' : '-') . (($in_Perms & 0x0008) ? (($in_Perms & 0x0400) ? 's' : 'x') : (($in_Perms & 0x0400) ? 'S' : '-'));

        // world
        $sP .= (($in_Perms & 0x0004) ? 'r' : '-') . (($in_Perms & 0x0002) ? 'w' : '-') . (($in_Perms & 0x0001) ? (($in_Perms & 0x0200) ? 't' : 'x') : (($in_Perms & 0x0200) ? 'T' : '-'));
        return $sP;
    }

    function permission_check($name, $args, $verbose = True)
    {
        global $passed_icon, $error_icon, $warning_icon, $is_windows;
        // echo "<p>permision_check('$name',".print_r($args,True).",'$verbose')</p>\n";

        if (substr($name, 0, 3) != '../') {
            $name = '../' . $name;
        }
        $rel_name = substr($name, 3);

        if (! file_exists($name) && isset($args['only_if_exists']) && $args['only_if_exists']) {
            return True;
        }

        $perms = $checks = '';
        if (file_exists($name)) {
            $owner = function_exists('posix_getpwuid') ? posix_getpwuid(@fileowner($name)) : array(
                'name' => 'nn'
            );
            $group = function_exists('posix_getgrgid') ? posix_getgrgid(@filegroup($name)) : array(
                'name' => 'nn'
            );
            $perms = "$owner[name]/$group[name] " . verbosePerms(@fileperms($name));
        }

        $checks = array();
        if (isset($args['is_readable']))
            $checks[] = (! $args['is_readable'] ? 'not ' : '') . $this->lang('readable by the webserver');
        if (isset($args['is_writable']))
            $checks[] = (! $args['is_writable'] ? 'not ' : '') . $this->lang('writable by the webserver');
        if (isset($args['is_world_readable']))
            $checks[] = (! $args['is_world_readable'] ? $this->lang('not') . ' ' : '') . $this->lang('world readable');
        if (isset($args['is_world_writable']))
            $checks[] = (! $args['is_world_writable'] ? $this->lang('not') . ' ' : '') . $this->lang('world writable');
        $checks = implode(', ', $checks);

        $icon = $passed_icon;
        $msg = $this->lang('Checking file-permissions of %1 for %2: %3', $rel_name, $checks, $perms) . "<br>\n";

        if (! file_exists($name)) {
            echo $error_icon . $msg . $this->lang('%1 does not exist !!!', $rel_name) . "</span><br/>\n";
            return False;
        }
        $warning = False;
        if (! GlobalService::get('run_by_webserver') && (@$args['is_readable'] || @$args['is_writable'])) {
            echo $warning_icon . ' ' . $msg . $this->lang('Check can only be performed, if called via a webserver, as the user-id/-name of the webserver is not known.') . "</span><br/>\n";
            unset($args['is_readable']);
            unset($args['is_writable']);
            $warning = True;
        }
        $Ok = True;
        if (isset($args['is_writable']) && is_writable($name) != $args['is_writable']) {
            echo "$error_icon $msg " . $this->lang('%1 is %2%3 !!!', $rel_name, $args['is_writable'] ? $this->lang('not') . ' ' : '', $this->lang('writable by the webserver')) . "</span><br/>\n";
            $Ok = False;
        }
        if (isset($args['is_readable']) && is_readable($name) != $args['is_readable']) {
            echo "$error_icon $msg " . $this->lang('%1 is %2%3 !!!', $rel_name, $args['is_readable'] ? $this->lang('not') . ' ' : '', $this->lang('readable by the webserver')) . "</span><br/>\n";
            $Ok = False;
        }
        if (! $is_windows && isset($args['is_world_readable']) && ! (fileperms($name) & 04) == $args['is_world_readable']) {
            echo "$error_icon $msg" . $this->lang('%1 is %2%3 !!!', $rel_name, $args['is_world_readable'] ? $this->lang('not') . ' ' : '', $this->lang('world readable')) . "</span><br/>\n";
            $Ok = False;
        }
        if (! $is_windows && isset($args['is_world_writable']) && ! (fileperms($name) & 02) == $args['is_world_writable']) {
            echo "$error_icon $msg " . $this->lang('%1 is %2%3 !!!', $rel_name, $args['is_world_writable'] ? $this->lang('not') . ' ' : '', $this->lang('world writable')) . "</span><br/>\n";
            $Ok = False;
        }
        if ($Ok && ! $warning && $verbose) {
            echo $passed_icon . ' ' . $msg;
        }
        if ($Ok && @$args['recursiv'] && is_dir($name)) {
            if ($verbose) {
                echo "<div id='setup_info'>" . $this->lang('This might take a while, please wait ...') . "</div>\n";
                flush();
            }
            @set_time_limit(0);
            $handle = @opendir($name);
            while ($handle && ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    $Ok = $Ok && permission_check(($name != '.' ? $name . '/' : '') . $file, $args, False);
                }
            }
            if ($handle)
                closedir($handle);
        }
        if ($verbose)
            echo "\n";

        return $Ok;
    }

    function mk_value($value)
    {
        if (! preg_match('/^([0-9]+)([mk]+)$/i', $value, $matches))
            return $value;

        return (strtolower($matches[2]) == 'm' ? 1024 * 1024 : 1024) * (int) $matches[1];
    }

    function php_ini_check($name, $args)
    {
        global $passed_icon, $error_icon, $warning_icon, $is_windows;

        $safe_mode = ini_get('safe_mode');

        $ini_value = ini_get($name);
        $check = isset($args['check']) ? $args['check'] : '=';
        $verbose_value = isset($args['verbose_value']) ? $args['verbose_value'] : $args['value'];
        $ini_value_verbose = '';
        if ($verbose_value == 'On' || $verbose_value == 'Off') {
            $ini_value_verbose = ' = ' . ($ini_value ? 'On' : 'Off');
        }
        switch ($check) {
            case 'not set':
                $check = $this->lang('not set');
                $result = ! ($ini_value & $args['value']);
                break;
            case 'set':
                $check = $this->lang('set');
                $result = ! ! ($ini_value & $args['value']);
                break;
            case '>=':
                $result = ! $ini_value || // value not used, eg. no memory limit
                (int) mk_value($ini_value) >= (int) mk_value($args['value']);
                break;
            case 'contain':
                $check = $this->lang('contain');
                $sep = $is_windows ? '[; ]+' : '[: ]+';
                $result = in_array($args['value'], preg_split('/' . $sep . '/', $ini_value));
                break;
            case '=':
            default:
                $result = $ini_value == $args['value'];
                break;
        }
        $msg = ' ' . $this->lang('Checking php.ini') . ": $name $check $verbose_value: <div id='setup_info'>ini_get('$name')='$ini_value'$ini_value_verbose</div>\n";

        if ($result) {
            echo $passed_icon . $msg;
        }
        if (! $result) {
            if (isset($args['warning'])) {
                echo $warning_icon . $msg . $args['warning'] . "<br/></span>\n";
            }
            if (isset($args['error'])) {
                echo $error_icon . $msg . $args['error'] . "</br></span>\n";
            }
            if (isset($args['safe_mode']) && $safe_mode || @$args['change']) {
                if (! isset($args['warning']) && ! isset($args['error'])) {
                    echo $error_icon . $msg . "<br/></span>";
                }
                echo "<div id='setup_error'>\n";
                echo '*** ' . $this->lang('Please make the following change in your php.ini') . ' (' . get_php_ini() . '): ' . (@$args['safe_mode'] ? $args['safe_mode'] : $args['change']) . "<br>\n";
                echo '*** ' . $this->lang('AND reload your webserver, so the above changes take effect !!!') . "</div>\n";
            }
        }
        return $result;
    }

    function get_php_ini()
    {
        ob_start();
        phpinfo(INFO_GENERAL);
        $phpinfo = ob_get_contents();
        ob_end_clean();

        return preg_match('/\(php.ini\).*<\/td><td[^>]*>([^ <]+)/', $phpinfo, $found) ? $found[1] : False;
    }

    function gd_check()
    {
        global $passed_icon, $warning_icon;
        $available = (function_exists('imagecopyresampled') || function_exists('imagecopyresized'));

        echo ($available ? $passed_icon : $warning_icon) . ' ' . $this->lang('Checking for GD support...') . ': ' . ($available ? $this->lang('True') : $this->lang('False')) . "<br/></span>\n";

        if (! $available) {
            echo $this->lang('Your PHP installation does not have appropriate GD support. You need gd library version 1.8 or newer to see Gantt charts in projects.') . "\n";
            return false;
        }
        return true;
    }

    public function lang($msg, $arg1 = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL)
    {
        return is_null($arg1) ? $msg : str_replace(array(
            '%1',
            '%2',
            '%3',
            '%4'
        ), array(
            $arg1,
            $arg2,
            $arg3,
            $arg4
        ), $msg);
    }
}