<?php

class UserPreferencesResource extends PreferencesAdapter
{

	public function setDocumentation() {

		$this->setResource("Preferences","Preferences/UserPreferences","Retorna as prefer�ncias do usu�rio.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);

		$this->addResourceParam("module","string",false,"M�dulo da Prefer�ncia (Default = mail).",false,"mail");
		$this->addResourceParam("preference","string",false,"ID da prefer�ncia.");

	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);
 		if( $this->isLoggedIn() )
 		{
			$appName = $this->getParam('module');
			$preference = $this->getParam('preference');

			if( trim($appName) === "" ) { $appName = "mail"; }

			$apps = $this->readUserApp();
			$module = $appName;
			if( array_key_exists( $appName, $apps ) )
			{
				$appName = $apps[$appName];
				$prefs_forced = (isset(GlobalService::get('phpgw')->preferences->forced[$appName]) ? GlobalService::get('phpgw')->preferences->forced[$appName] : array());
				$prefs_default = (isset(GlobalService::get('phpgw')->preferences->default[$appName]) ? GlobalService::get('phpgw')->preferences->default[$appName] : array());
				$prefs_user = (isset(GlobalService::get('phpgw')->preferences->user[$appName]) ? GlobalService::get('phpgw')->preferences->user[$appName] : array());

				$prefs = array_merge( $prefs_default, $prefs_user);

				foreach( $prefs as $k => $pref) {
					$prefs[$k] = is_string( $pref )? mb_convert_encoding($pref, "UTF8","ISO_8859-1") : $pref;
				}

				if ($preference == "") {
					$result = array( $module => $prefs );
				} else {
					if (isset($prefs[$preference])) {
						$result = array( $module => array( "" . $preference => $prefs[$preference]) );
					} else {
						$result = array( $module => array( "" . $preference => "") );
					}
					
				}
			} else {
				$result = array( $module => array( "" . $preference => "") );
			}
			
			$this->setResult($result);
			
			return $this->getResponse();
		}	
	}
}

?>
