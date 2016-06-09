<?php

class EventResource extends CalendarAdapter {

	public function setDocumentation() {

		$this->setResource("Calendar","Calendar/Event","Retorna o evento da agenda pessoal do usu�rio.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);

		$this->addResourceParam("eventID","integer",true,"ID do evento que ser� retornado.");

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( $this->isLoggedIn() )
		{
			$eventID  = $this->getParam('eventID');

			//VALIDA��ES DE CAMPOS 
			$this->validateInteger($eventID,false,"CALENDAR_INVALID_EVENTID");

			if ($eventID != "") {
				$event = $this->getEventByID($eventID);
				if ($event['eventID'] != $eventID) 
				{
					Errors::runException("CALENDAR_INVALID_EVENTID");
				}

				$result = array( 'events' => array( $event ) );

				$this->setResult($result);
			}
			
		}

		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}
}
