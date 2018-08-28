<?php

namespace App\Services\Base\Modules\calendar;

use App\Services\Base\Adapters\CalendarAdapter;
use App\Services\Base\Commons\Errors;

class DelEventResource extends CalendarAdapter {

	public function setDocumentation() {
		$this->setResource("Calendar","Calendar/DelEvent","Exclui um evento do usu�rio.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);
		$this->addResourceParam("eventID","integer",false,"ID do evento que ser� exclu�do.");
	}

	public function post($request){

 		$this->setParams( $request );

		$eventID  = $this->getParam('eventID');

		$retCode = $this->delEvent($eventID);

		if( $retCode == 16 ){
			$this->setResult(true);
		} else {
			return Errors::runException("CALENDAR_EVENT_DELETE_ERROR");
		}

		 
		return $this->getResponse();
	}
}
