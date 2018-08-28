<?php

namespace App\Services\Base\Modules\services;

use App\Services\Base\Adapters\ServicesAdapter;
use App\Services\Base\Commons\Errors;

class ChatResource extends ServicesAdapter
{
	public function setDocumentation() {
		$this->setResource("Services","Services/Chat","Retorna as informa��es de conex�o com o chat (ejabberd + xmpp-bosh).",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);
	}

	public function post($request)
	{
 		$this->setParams($request);

		$result = $this->authChat();

 		if( $result ){
			$this->setResult( $result );
 		} else {
			return Errors::runException( "ACCESS_NOT_PERMITTED" );
		}
		
		return $this->getResponse();
 	}	
}
