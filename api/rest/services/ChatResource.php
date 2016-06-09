<?php

class ChatResource extends ServicesAdapter
{

	public function setDocumentation() {

		$this->setResource("Services","Services/Chat","Retorna as informa��es de conex�o com o chat (ejabberd + xmpp-bosh).",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);

	}


	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
 		{
			$this->setResult( $this->authChat() );
 		}
		else
		{
			Errors::runException( "ACCESS_NOT_PERMITTED" );
		}

 		return $this->getResponse();
 	}	

}

?>