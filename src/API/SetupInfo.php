<?php
namespace Expresso\API;

/**
 * @package API
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright Copyright (c) 2019 FGSL (http://www.fgsl.eti.br)
 *           
 */
final class SetupInfo
{
    public static $setup_info = [];

    public static function start()
    {

        /* Basic information about this app */
        self::$setup_info['phpgwapi']['name'] = 'phpgwapi';
        self::$setup_info['phpgwapi']['title'] = 'API';
        self::$setup_info['phpgwapi']['version'] = '2.2.6';
        self::$setup_info['phpgwapi']['versions']['current_header'] = '2.3';
        self::$setup_info['phpgwapi']['enable'] = 3;
        self::$setup_info['phpgwapi']['app_order'] = 1;

        self::$setup_info['phpgwapi']['license'] = 'GPL';
        self::$setup_info['phpgwapi']['description'] = 'Core Library';

        /* The tables this app creates */
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_config';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_applications';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_acl';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_accounts';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_preferences';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_sessions';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_app_sessions';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_access_log';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_hooks';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_languages';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_lang';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_nextid';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_categories';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_addressbook';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_addressbook_extra';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_log';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_log_msg';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_interserv';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_vfs';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_history_log';
        self::$setup_info['phpgwapi']['tables'][] = 'phpgw_async';
    }
}