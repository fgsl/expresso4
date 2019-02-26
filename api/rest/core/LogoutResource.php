<?php
class LogoutResource extends ExpressoAdapter {

	public function setDocumentation() {

		$this->setResource("Expresso","Logout","Desloga o usu�rio, invalidando a chave de autentica��o.",array("POST"));
		$this->setIsMobile(true);

		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
		parent::post($request);
		
		if($this-> isLoggedIn())
		{	if ($_SESSION['phpgw_session']['session_id'] && file_exists(GlobalService::get('phpgw_info')['server']['temp_dir'].SEP.$_SESSION['phpgw_session']['session_id']))	
			{
				$dh = opendir(GlobalService::get('phpgw_info')['server']['temp_dir']. SEP . $_SESSION['phpgw_session']['session_id']);
				while ($file = readdir($dh)) 
				{
					if ($file != '.' && $file != '..') 
					{
						unlink(GlobalService::get('phpgw_info')['server']['temp_dir'].SEP.$_SESSION['phpgw_session']['session_id'].SEP.$file);
					}
				}
				rmdir(GlobalService::get('phpgw_info')['server']['temp_dir'].SEP.$_SESSION['phpgw_session']['session_id']);
			}
			GlobalService::get('phpgw')->hooks->process('logout');
			GlobalService::get('phpgw')->session->destroy($_SESSION['phpgw_session']['session_id'], GlobalService::get('kp3']);
			$this->setResult(true);
		}
		
		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}	
}