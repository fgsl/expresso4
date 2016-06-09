<?php

class CleanTrashResource extends MailAdapter {

	public function setDocumentation() {

		$this->setResource("Mail","Mail/CleanTrash","Limpa a Lixeira do Usu�rio.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if($this-> isLoggedIn())
		{
			$params['clean_folder'] = 'imapDefaultTrashFolder';
			if(!$this -> getImap() -> empty_folder($params))
				Errors::runException("MAIL_TRASH_NOT_CLEANED");
		}

		$this->setResult(true);

		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}

}
