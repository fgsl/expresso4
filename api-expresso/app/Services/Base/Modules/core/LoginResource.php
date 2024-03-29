<?php

namespace App\Services\Base\Modules\core;

use App\Services\Base\Adapters\ExpressoAdapter;
use App\Services\Base\Commons\Errors;

class LoginResource extends ExpressoAdapter {

	public function setDocumentation() {
		$this->setResource("Expresso","Login","Realiza a autentica��o do usu�rio.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);
		$this->addResourceParam("user","string",true,"Login do Usu�rio");
		$this->addResourceParam("password","string",true,"Senha do Usu�rio",true,"","password");
	}

	private function getUserProfile(){
		if($this->getExpressoVersion() != "2.2") {
			$_SESSION['wallet']['user']['uidNumber'] = $GLOBALS['phpgw_info']['user']['account_id'];
		}
	
		return array(
			'contactID'				=> $GLOBALS['phpgw_info']['user']['account_dn'],
			'contactMails' 			=> array($GLOBALS['phpgw_info']['user']['email']),
			'contactPhones' 		=> array($GLOBALS['phpgw_info']['user']['telephonenumber']),
			'contactFullName'		=> $GLOBALS['phpgw_info']['user']['fullname'],
			'contactLID'			=> $GLOBALS['phpgw_info']['user']['account_lid'],
			'contactUIDNumber'		=> $GLOBALS['phpgw_info']['user']['account_id'],
			'contactApps'			=> $this->getUserApps(),
			'contactServices'		=> $this->getServices()
		);
	}
	
	public function post($request){

		$this->setParams( $request );

		if($sessionid = $GLOBALS['phpgw']->session->create($this->getParam('user'), $this->getParam('password')))
		{
			$result = array(
				'auth' 			=> $sessionid.":".$GLOBALS['phpgw']->session->kp3,
				'profile' 		=> array($this->getUserProfile())
			);
			$this->setResult($result);
		}
		else
		{
			return Errors::runException($GLOBALS['phpgw']->session->cd_reason);
		}
		return $this->getResponse();
	}	

}
