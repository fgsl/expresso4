<?php
namespace Expresso\Factory;
use Expresso\Setup\Template;
use Expresso\Core\GlobalService;

/**
 *
 * @package Setup
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright Copyright (c) 2019 FGSL (http://www.fgsl.eti.br)
 *
 */
class SetupTplFactory
{
    private static $instance = null;
    
    public static function getInstance()
    {
        if (self::$instance == null){
            $tpl_root = GlobalService::get('phpgw_setup')->html->setup_tpl_dir('setup');
            $setup_tpl = new Template($tpl_root);
            $setup_tpl->set_file(array(
                'T_head' => 'head.tpl',
                'T_footer' => 'footer.tpl',
                'T_alert_msg' => 'msg_alert_msg.tpl',
                'T_login_main' => 'login_main.tpl',
                'T_login_stage_header' => 'login_stage_header.tpl',
                'T_setup_manage' => 'manageheader.tpl'
            ));
            $setup_tpl->set_block('T_login_stage_header', 'B_multi_domain', 'V_multi_domain');
            $setup_tpl->set_block('T_login_stage_header', 'B_single_domain', 'V_single_domain');
            $setup_tpl->set_block('T_setup_manage', 'manageheader', 'manageheader');
            $setup_tpl->set_block('T_setup_manage', 'domain', 'domain');
            self::$instance = $setup_tpl;
        }
        return self::$instance;
    }
}
