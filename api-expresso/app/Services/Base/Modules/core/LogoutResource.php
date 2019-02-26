<?php

namespace App\Services\Base\Modules\core;

use App\Services\Base\Adapters\ExpressoAdapter;
use App\Services\Base\Commons\Errors;

class LogoutResource extends ExpressoAdapter {

	public function setDocumentation() {
		$this->setResource("Expresso","Logout","Desloga o usu�rio, invalidando a chave de autentica��o.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);
	}

	public function post($request){

		$this->setParams( $request );
		
		if ($_SESSION['phpgw_session']['session_id'] && file_exists(GlobalService::get('phpgw_info')['server']['temp_dir'].SEP.$_SESSION['phpgw_session']['session_id']))	
		{
			$dh = opendir(GlobalService::get('phpgw_info')['server']['temp_dir']. SEP . $_SESSION['phpgw_session']['session_id']);
			
			while ($file = readdir($dh)) 
			{
				if ($file != '.' && $file != '..') {
					unlink(GlobalService::get('phpgw_info')['server']['temp_dir'].SEP.$_SESSION['phpgw_session']['session_id'].SEP.$file);
				}
			}
			
			rmdir(GlobalService::get('phpgw_info')['server']['temp_dir'].SEP.$_SESSION['phpgw_session']['session_id']);
		}

		GlobalService::get('phpgw')->hooks->process('logout');

		GlobalService::get('phpgw')->session->destroy($_SESSION['phpgw_session']['session_id'], GlobalService::get('kp3']);

		$this->setResult(true);

		return $this->getResponse();
	}	
}
