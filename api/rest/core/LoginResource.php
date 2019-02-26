<?php

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
			$_SESSION['wallet']['user']['uidNumber'] = GlobalService::get('phpgw_info')['user']['account_id'];
		}
	
		return array(
				'contactID'				=> GlobalService::get('phpgw_info')['user']['account_dn'],
				'contactMails' 			=> array(GlobalService::get('phpgw_info')['user']['email']),
				'contactPhones' 		=> array(GlobalService::get('phpgw_info')['user']['telephonenumber']),
				'contactFullName'		=> GlobalService::get('phpgw_info')['user']['fullname'],
				'contactLID'			=> GlobalService::get('phpgw_info')['user']['account_lid'],
				'contactUIDNumber'		=> GlobalService::get('phpgw_info')['user']['account_id'],
				'contactApps'			=> $this->getUserApps(),
				'contactServices'		=> $this->getServices()

		);
	}
	
	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);
		if($sessionid = GlobalService::get('phpgw')->session->create($this->getParam('user'), $this->getParam('password')))
		{
			$result = array(
				'auth' 			=> $sessionid.":".GlobalService::get('phpgw')->session->kp3,
				'profile' 		=> array($this->getUserProfile())
			);
			$this->setResult($result);
		}
		else
		{
			Errors::runException(GlobalService::get('phpgw')->session->cd_reason);
		}
		return $this->getResponse();
	}	

}