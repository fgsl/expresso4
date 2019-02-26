<?php
use Expresso\Core\GlobalService;

/**
 * @package     Expresso
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright   Copyright (c) 2019 FGSL (http://www.fgsl.eti.br)
 *
 */
require __DIR__ . '/vendor/autoload.php';

if (file_exists('mobile.php')) {
    include 'mobile.php';
}

$current_url = substr($_SERVER["SCRIPT_NAME"], 0, strpos($_SERVER["SCRIPT_NAME"], 'index.php'));

$phpgw_info = array();
if (! file_exists('header.inc.php')) {
    Header('Location: ' . $current_url . 'setup/index.php');
    exit();
}

GlobalService::set('sessionid',isset($_GET['sessionid']) ? $_GET['sessionid'] : @$_COOKIE['sessionid']);
if (! GlobalService::get('sessionid')) {
    Header('Location: ' . $current_url . 'login.php' . (isset($_SERVER['QUERY_STRING']) && ! empty($_SERVER['QUERY_STRING']) ? '?phpgw_forward=' . urlencode('/index.php?' . $_SERVER['QUERY_STRING']) : ''));
    exit();
}

/*
 * This is the menuaction driver for the multi-layered design
 */
if (isset($_GET['menuaction'])) {
    list ($app, $class, $method) = explode('.', @$_GET['menuaction']);
    if (! $app || ! $class || ! $method) {
        $invalid_data = True;
    }
} else {
    // $phpgw->log->message('W-BadmenuactionVariable, menuaction missing or corrupt: %1',$menuaction);
    // $phpgw->log->commit();

    $app = 'home';
    $invalid_data = True;
}

if ($app == 'phpgwapi') {
    $app = 'home';
    $api_requested = True;
}

GlobalService::get('phpgw_info')['flags'] = array(
    'noheader' => True,
    'nonavbar' => True,
    'currentapp' => $app
);
include ('./header.inc.php');

if ((GlobalService::get('phpgw_info')['server']['use_https'] == 2) && ($_SERVER['HTTPS'] != 'on')) {
    Header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

if ($app == 'home' && ! $api_requested) {
    if ($_GET['dont_redirect_if_moble'] == 1)
        Header('Location: ' . GlobalService::get('phpgw')->link('/home.php?dont_redirect_if_moble=1'));
    else
        Header('Location: ' . GlobalService::get('phpgw')->link('/home.php'));
}

if ($api_requested) {
    $app = 'phpgwapi';
}
$_SESSION['phpgw_info']['server'] = GlobalService::get('phpgw_info')['server'];

GlobalService::set($class, CreateObject(sprintf('%s.%s', $app, $class)));
if ((is_array(GlobalService::get($class)->public_functions) && GlobalService::get($class)->public_functions[$method]) && ! $invalid_data) {
    $result = execmethod($_GET['menuaction']);

    if (getBestSupportedMimeType('application/json')) {
        header('Content-Type: application/json');
        echo json_encode(utf8_encode_recursive($result));
        exit();
    }

    unset($app);
    unset($class);
    unset($method);
    unset($invalid_data);
    unset($api_requested);
} else {
    if (! $app || ! $class || ! $method) {
        if (@is_object(GlobalService::get('phpgw')->log)) {
            if ($menuaction) {
                GlobalService::get('phpgw')->log->message(array(
                    'text' => "W-BadmenuactionVariable, menuaction missing or corrupt: $menuaction",
                    'p1' => $menuaction,
                    'line' => __LINE__,
                    'file' => __FILE__
                ));
            }
        }
    }

    if (! is_array(GlobalService::get($class)->public_functions) || ! $GlobalService::get($class)->public_functions[$method] && $method) {
        if (@is_object(GlobalService::get('phpgw')->log)) {
            if ($menuaction) {
                GlobalService::get('phpgw')->log->message(array(
                    'text' => "W-BadmenuactionVariable, attempted to access private method: $method",
                    'p1' => $method,
                    'line' => __LINE__,
                    'file' => __FILE__
                ));
            }
        }
    }
    if (@is_object(GlobalService::get('phpgw')->log)) {
        GlobalService::get('phpgw')->log->commit();
    }

    Header('Location: ' . GlobalService::get('phpgw')->link('/home.php'));
}
?>
