<?php

namespace App\Services\Base\Modules\mail;

use App\Services\Base\Adapters\MailAdapter;
use App\Services\Base\Commons\Errors;

class SendSupportFeedbackResource extends MailAdapter {

	public function setDocumentation() {
		$this->setResource("Mail","Mail/SendSupportFeedback","Envia uma mensagem de Sugest�o para o administrador do Expresso.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);
		$this->addResourceParam("message","string",true,"Mensagem de sugest�o a ser enviada para o suporte do Expresso.");
	}

	public function post($request){

 		$this->setParams( $request );

		$msgBody = $this->getParam("message");

		$params['input_to'] = GlobalService::get('phpgw_info')['server']['sugestoes_email_to'];
		$params['input_cc'] = GlobalService::get('phpgw_info')['server']['sugestoes_email_cc'];
		$params['input_cc'] = GlobalService::get('phpgw_info')['server']['sugestoes_email_bcc'];
		$params['input_subject'] = lang("Suggestions");
		$params['body'] = $msgBody;
		$params['type'] = 'textplain';

		GlobalService::get('phpgw')->preferences->read_repository();
		$_SESSION['phpgw_info']['expressomail']['user'] = GlobalService::get('phpgw_info')['user'];
		$boemailadmin   = CreateObject('emailadmin.bo');
		$emailadmin_profile = $boemailadmin->getProfileList();
		$_SESSION['phpgw_info']['expressomail']['email_server'] = $boemailadmin->getProfile($emailadmin_profile[0]['profileID']);
		$_SESSION['phpgw_info']['expressomail']['server'] = GlobalService::get('phpgw_info')['server'];
		$_SESSION['phpgw_info']['expressomail']['user']['email'] = GlobalService::get('phpgw')->preferences->values['email'];

		$expressoMail = CreateObject('expressoMail1_2.imap_functions');
		
		$returncode   = $expressoMail->send_mail($params);
		
		if (!$returncode || !(is_array($returncode) && $returncode['success'] == true)){
			return Errors::runException("MAIL_NOT_SENT");
		}

		$this->setResult(true);

		return $this->getResponse();
	}
}
