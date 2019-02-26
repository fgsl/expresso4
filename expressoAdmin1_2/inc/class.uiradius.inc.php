<?php
use Expresso\Core\GlobalService;

class uiradius
{
	var $public_functions = array(
		'save' => true,
		'config' => true,
		'edit' => true,
	);
	
	var $_so;
	var $_radius_schema;
	
	function uiradius()
	{
		$this->_so = CreateObject('expressoAdmin1_2.soradius');
		$this->_radius_schema = $this->_so->getRadiusSchema();
		if ( !@is_object(GlobalService::get('phpgw')->js) ) GlobalService::get('phpgw')->js = CreateObject('phpgwapi.javascript');
	}
	
	function save()
	{
		if ( !( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ) ) {
			GlobalService::get('phpgw')->redirect(GlobalService::get('phpgw')->link('/admin/index.php'));
			return false;
		}
		
		if ( !GlobalService::get('phpgw')->acl->check('run',1,'admin') ) $this->_setResponse( null, 401 );
		
		$result = $this->_so->setRadiusConf( $_POST );
		if ( $result === true ) $this->_setResponse( utf8_encode(lang('Configuration saved successfully')) );
		else $this->_setResponse( $result, 400 );
	}
	
	function config()
	{
		if ( !( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ) ) {
			GlobalService::get('phpgw')->redirect(GlobalService::get('phpgw')->link('/admin/index.php'));
			return false;
		}
		
		if ( !GlobalService::get('phpgw')->acl->check('run',1,'admin') ) $this->_setResponse( null, 401 );
		
		$result = $this->_so->getRadiusConf();
		$result->schema = $this->_so->getRadiusSchema();
		$result->schema['adm_groups'] = $this->_so->getTopGroupsLdap();
		
		$this->_setResponse( $result );
	}
	
	function _setResponse( $data, $code = null )
	{
		if ( !is_null($code) ) header( ':', true, (int)$code );
		header( 'Content-Type: application/json' );
		echo json_encode( (array)$data );
		exit;
	}
	
	function edit()
	{
		if ( !GlobalService::get('phpgw')->acl->check('run',1,'admin') ) GlobalService::get('phpgw')->redirect(GlobalService::get('phpgw')->link('/admin/index.php'));
		
		GlobalService::get('phpgw')->js->add('src','./prototype/plugins/jquery/jquery-latest.min.js');
		GlobalService::get('phpgw')->js->add('src','./prototype/plugins/jquery/jquery-ui-latest.min.js');
		GlobalService::get('phpgw')->js->validate_file('jscode','connector','expressoAdmin1_2');
		GlobalService::get('phpgw')->js->validate_file('jscode','lang','expressoAdmin1_2');
		GlobalService::get('phpgw')->js->validate_file('jscode','radius_config','expressoAdmin1_2');
		GlobalService::get('phpgw')->css->validate_file('./prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css');
		
		unset(GlobalService::get('phpgw_info')['flags']['nonavbar']);
		GlobalService::get('phpgw_info')['flags']['app_header'] = GlobalService::get('phpgw_info')['apps']['expressoAdmin1_2']['title'].' - '.lang('Radius Config');
		GlobalService::get('phpgw')->common->phpgw_header();
		
		$conf = $this->_so->getRadiusConf();
		
		$p = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
		$p->set_file(array('radius' => 'radius.tpl'));
		$p->set_block('radius','body','body');
		$p->set_var(array(
			'lang_title'					=> lang('Radius Config'),
			'lang_save'						=> lang('Save'),
			'lang_cancel'					=> lang('Cancel'),
			'lang_add'						=> lang('Add Profile'),
			'lang_rem'						=> lang('Remove Profile'),
			'lang_profile_name'				=> lang('Profile Name'),
			'lang_description'				=> lang('Description'),
			'lang_yes'						=> lang('Yes'),
			'lang_no'						=> lang('No'),
			'lang_radius_enabled'			=> lang('Enable Radius'),
			'lang_add_field'				=> lang('Add Field'),
			'lang_groupname_attribute'		=> lang('Group Name Field'),
			'lang_radius_profiles'			=> lang('Radius Profiles'),
			'value_radius_enabled_true'		=> $conf->enabled? ' selected="selected"' : '',
			'value_radius_enabled_false'	=> $conf->enabled? '' : ' selected="selected"',
			'select_grpname_attr_opts'		=> $this->_mkOptions($this->_radius_schema['may'], array($conf->groupname_attribute)),
		));
		$p->pparse('out','body');
	}
	
	function _mkOptions( $arr, $selected = array() )
	{
		$buf = '';
		sort($arr);
		foreach ( $arr as $value )
			$buf .= '<option value="'.$value.'"'.(in_array($value, $selected)?' selected="selected"':'').'>'.$value.'</option>';
		return $buf;
	}
}
?>
