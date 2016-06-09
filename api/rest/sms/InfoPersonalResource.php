<?php
/**
 * InfoPersonalResource - Get informations of user
 *
 * @access		public
 * @author		Alexandre Rocha Wendling <alexandrerw@celepar.pr.gov.br>
 * @category	SMSAdapter
 * @package		Resource
 * @property	apps *
 * @property	rest SMS/InfoPersonalResource
 * @version		1.0
 */
include_once dirname(__FILE__).'/../../adapters/SMSAdapter.php';
class InfoPersonalResource extends SMSAdapter {

	public function setDocumentation() {

		$this->setResource("SMS","SMS/InfoPersonal","Retorna as informa��es de SMS do Usu�rio, �ltimo n�mero para qual foi enviado o c�digo de confirma��o, os telefones j� confirmados pelo usu�rio, tamb�m retorna se o usu�rio autorizou o envio de SMS para seu celular (SMSAuth) e se o usu�rio tem permiss�o de envio de SMS (SendAuth).",array("POST"));
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);

	}
	
	/**
	 * POST method request
	 *
	 * @property	method post
	 * @return		array - user info
	 */
	public function post($request){
		
		// to Receive POST Params (use $this->params)
		parent::post($request);
		
		$this->setResult(array(
			'LastPhoneNumberWasSendCode' => $this->getLastPhoneNumberWasSendCode(),
			'CheckedListPhoneNumbers' => $this->getCheckedListPhoneNumbers(),
			'SMSAuth' => $this->getAuth(),
			'SendAuth' => $this->hasSendAuth(),
		));
		
		return $this->getResponse();
	}
}