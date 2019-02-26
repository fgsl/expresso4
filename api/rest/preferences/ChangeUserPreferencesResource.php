<?php

class ChangeUserPreferencesResource extends PreferencesAdapter
{
	
	public function setDocumentation() {

		$this->setResource("Preferences","Preferences/ChangeUserPreferences","Altera as prefer�ncias do usu�rio.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);

		$this->addResourceParam("module","string",true,"M�dulo da Prefer�ncia.");
		$this->addResourceParam("preference","string",true,"ID da prefer�ncia.");
		$this->addResourceParam("value","string",true,"Novo valor da prefer�ncia.");


	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
		{

			$appName 	= $this->getParam('module');
			$preference = $this->getParam('preference');
			$value 		= $this->getParam('value');

			if ($preference == "") { $preference = ""; }
			if (($appName == "mail") || ($appName == "")) { $appName = "expressoMail"; }

			GlobalService::get('phpgw')->preferences->user[$appName][$preference] = $value;

			GlobalService::get('phpgw')->preferences->save_repository(True,"user");

			$this->setResult(true);
			
			return $this->getResponse();
		}	
	}
}

?>