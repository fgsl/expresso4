<?php
namespace Expresso\App;

use Expresso\Core\GlobalService;
use Expresso\Core\Common;
use Expresso\API\Setup;

/**
 *
 * @package Setup
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright Copyright (c) 2019 FGSL (http://www.fgsl.eti.br)
 *           
 */
final class Registry
{
    public static function start()
    {
        /* ######## Start security check ########## */
        $d1 = strtolower(substr(@GlobalService::get('phpgw_info')['server']['api_inc'],0,3));
        $d2 = strtolower(substr(@GlobalService::get('phpgw_info')['server']['server_root'],0,3));
        $d3 = strtolower(substr(@GlobalService::get('phpgw_info')['server']['app_inc'],0,3));
        if($d1 == 'htt' || $d1 == 'ftp' || $d2 == 'htt' || $d2 == 'ftp' || $d3 == 'htt' || $d3 == 'ftp')
        {
            echo 'Failed attempt to break in via an old Security Hole!<br>';
            exit;
        }
        unset($d1);unset($d2);unset($d3);
        /* ######## End security check ########## */
        
        error_reporting(error_reporting() & ~E_NOTICE);
        
        if(file_exists('../header.inc.php'))
        {
            include('../header.inc.php');
        }
        
        if (!function_exists('version_compare'))//version_compare() is only available in PHP4.1+
        {
            echo 'eGroupWare now requires PHP 4.1 or greater.<br>';
            echo 'Please contact your System Administrator';
            exit;
        }
        
        /*  If we included the header.inc.php, but it is somehow broken, cover ourselves... */
        if(!defined('PHPGW_SERVER_ROOT') && !defined('PHPGW_INCLUDE_ROOT'))
        {
            define('PHPGW_SERVER_ROOT','..');
            define('PHPGW_INCLUDE_ROOT','..');
        }
        
        require_once(PHPGW_INCLUDE_ROOT . '/phpgwapi/inc/common_functions.inc.php');
        
        define('SEP',filesystem_separator());
        
        if(file_exists(PHPGW_SERVER_ROOT.'/phpgwapi/setup/setup.inc.php'))
        {
            include(PHPGW_SERVER_ROOT.'/phpgwapi/setup/setup.inc.php'); /* To set the current core version */
            /* This will change to just use setup_info */
            GlobalService::get('phpgw_info')['server']['versions']['current_header'] = $setup_info['phpgwapi']['versions']['current_header'];
        }
        else
        {
            GlobalService::get('phpgw_info')['server']['versions']['phpgwapi'] = 'Undetected';
        }
        
        GlobalService::get('phpgw_info')['server']['app_images'] = 'templates/default/images';
        
        GlobalService::set('phpgw_setup',new Setup(false,false));
    }
    
    /*!
     @function lang
     @abstract function to handle multilanguage support
     */
    public static function lang($key,$m1='',$m2='',$m3='',$m4='',$m5='',$m6='',$m7='',$m8='',$m9='',$m10='')
    {
        if(is_array($m1))
        {
            $vars = $m1;
        }
        else
        {
            $vars = array($m1,$m2,$m3,$m4,$m5,$m6,$m7,$m8,$m9,$m10);
        }
        $value = GlobalService::get('phpgw_setup')->translation->translate("$key", $vars );
        return $value;
    }
    
    /*!
     @function get_langs
     @abstract	returns array of languages we support, with enabled set
     to True if the lang file exists
     */
    public static function get_langs()
    {
        $f = fopen('./lang/languages','rb');
        while($line = fgets($f,200))
        {
            list($x,$y) = explode("\t",$line);
            $languages[$x]['lang']  = trim($x);
            $languages[$x]['descr'] = trim($y);
            $languages[$x]['available'] = False;
        }
        fclose($f);
        
        $d = dir('./lang');
        while($file=$d->read())
        {
            if(preg_match('/^phpgw_([-a-z]+).lang$/i',$file,$matches))
            {
                $languages[$matches[1]]['available'] = True;
            }
        }
        $d->close();
        
        //print_r($languages);
        return $languages;
    }
    
    public static function lang_select($onChange=False,$ConfigLang='')
    {
        if (!$ConfigLang)
        {
            $ConfigLang = get_var('ConfigLang',Array('POST','COOKIE'));
        }
        $select = '<select name="ConfigLang"'.($onChange ? ' onChange="this.form.submit();"' : '').'>' . "\n";
        $languages = get_langs();
        usort($languages,create_function('$a,$b','return strcmp(@$a[\'descr\'],@$b[\'descr\']);'));
        foreach($languages as $data)
        {
            if($data['available'] && !empty($data['lang']))
            {
                $selected = '';
                $short = substr($data['lang'],0,2);
                if ($short == $ConfigLang || empty($ConfigLang) && $short == substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2))
                {
                    $selected = ' selected';
                }
                $select .= '<option value="' . $data['lang'] . '"' . $selected . '>' . $data['descr'] . '</option>' . "\n";
            }
        }
        $select .= '</select>' . "\n";
        
        return $select;
    }
}