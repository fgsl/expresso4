<?php

class ContactDeleteResource extends CatalogAdapter {

	public function setDocumentation() {

		$this->setResource("Catalog","Catalog/ContactDelete","Exclui um contato do cat�logo pessoal.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);

		$this->addResourceParam("contactID","string",false,"ID do contato que ser� exclu�do.");

	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
		{
			//New Contact
			$contactID	= trim( $this->getParam('contactID') );
			$contactID	= trim( preg_replace("/[^0-9]/", "", $contactID) );
			
			// Field Validation
		    if( $contactID === "" )
		    {
				Errors::runException( "CATALOG_ID_EMPTY" );
		    }
		    else
		    {	
				$result = unserialize($this->deleteContact($contactID));

				$this->setResult( $result );
		    }
		}

		//to Send Response (JSON RPC format)
		return $this->getResponse();		
	}
}