<?php
namespace Expresso\Setup;

/**
 *
 * @package Setup
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright Copyright (c) 2019 FGSL (http://www.fgsl.eti.br)
 *           
 */
if (! defined('MAX_MESSAGE_ID_LENGTH')) {
    define('MAX_MESSAGE_ID_LENGTH', 230);
}

class Translation
{

    private $langarray = array();

    /*
     * !
     * @function setup_lang
     * @abstract constructor for the class, loads all phrases into langarray
     * @param $lang user lang variable (defaults to en)
     */
    function setup_translation()
    {
        $ConfigLang = get_var('ConfigLang', Array(
            'POST',
            'COOKIE'
        ));

        if (! $ConfigLang) {
            $lang = 'en';
        } else {
            $lang = $ConfigLang;
        }

        $fn = '.' . SEP . 'lang' . SEP . 'phpgw_' . $lang . '.lang';
        if (! file_exists($fn)) {
            $fn = '.' . SEP . 'lang' . SEP . 'phpgw_en.lang';
        }
        if (file_exists($fn)) {
            $fp = fopen($fn, 'r');
            while ($data = fgets($fp, 8000)) {
                // explode with "\t" and removing "\n" with str_replace, needed to work with mbstring.overload=7
                list ($message_id, , , $content) = explode("\t", $data);
                $this->langarray[strtolower(trim($message_id))] = str_replace("\n", '', $content);
            }
            fclose($fp);
        }
    }

    /*
     * !
     * @function translate
     * @abstract Translate phrase to user selected lang
     * @param $key phrase to translate
     * @param $vars vars sent to lang function, passed to us
     */
    function translate($key, $vars = False)
    {
        $ret = $key . '*';
        $key = strtolower(trim($key));
        if (isset($this->langarray[$key])) {
            $ret = $this->langarray[$key];
        }
        if (is_array($vars)) {
            foreach ($vars as $n => $var) {
                $ret = preg_replace('/%' . ($n + 1) . '/', $var, $ret);
            }
        }
        return $ret;
    }

    // the following functions have been moved to phpgwapi/tanslation_sql
    function setup_translation_sql()
    {
        if (! is_object($this->sql)) {
            $this->sql = new TranslationSql();
        }
    }

    function get_langs($DEBUG = False)
    {
        $this->setup_translation_sql();
        return $this->sql->get_langs($DEBUG);
    }

    function drop_langs($appname, $DEBUG = False)
    {
        $this->setup_translation_sql();
        return $this->sql->drop_langs($appname, $DEBUG);
    }

    function add_langs($appname, $DEBUG = False, $force_langs = False)
    {
        $this->setup_translation_sql();
        return $this->sql->add_langs($appname, $DEBUG, $force_langs);
    }
}