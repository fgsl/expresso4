<?php

namespace App\Services\Base\Modules\mail;

use App\Services\Base\Adapters\MailAdapter;
use App\Services\Base\Commons\Errors;

class AttachmentResource extends MailAdapter {	
	
	public function setDocumentation() {
		$this->setResource("Mail","Mail/Attachment","Retorna para download o conte�do de um Anexo.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);
		$this->addResourceParam("folderID","string",true,"ID da pasta da mensagem.");
		$this->addResourceParam("msgID","string",true,"ID da mensagem que cont�m o Anexo.");
		$this->addResourceParam("attachmentID","string",true,"ID do Anexo.");
		if($this->getExpressoVersion() == "2.2"){
			$this->addResourceParam("attachmentIndex","string",true,"�ndice do Anexo, parametro somente necess�rio para a vers�o 2.2 do Expresso.");
			$this->addResourceParam("attachmentName","string",true,"Nome do arquivo anexado, parametro somente necess�rio para a vers�o 2.2 do Expresso.");
			$this->addResourceParam("attachmentEncoding","string",true,"Codifica��o do Anexo, parametro somente necess�rio para a vers�o 2.2 do Expresso.");
		}

	}

	public function post($request){		
		/**
		* @todo what's happening here? Choose POST or GET. Not both.
		*/
		// to Receive POST Params (use $this->params)		
 		/*parent::post($request); 		
 		$folderID 		= $this->getParam('folderID');
 		$msgID 			= $this->getParam('msgID');
 		$attachmentID 	= $this->getParam('attachmentID');
 		
		if($this-> isLoggedIn()) {
								
			if( $folderID && $msgID && $attachmentID) {				
				$dir = PHPGW_INCLUDE_ROOT."/expressoMail1_2/inc";
				
				if($this->getExpressoVersion() != "2.2"){
					$_GET['msgFolder'] = $folderID;
					$_GET['msgNumber'] = $msgID;
					$_GET['indexPart'] = $attachmentID;
					include("$dir/get_archive.php");
					
				}else{
					$_GET['msg_folder'] = $folderID;
					$_GET['msg_number'] = $msgID;
					$_GET['msg_part'] = $attachmentID;
					$_GET['idx_file']	= $this->getParam('attachmentIndex');
					$_GET['newfilename']= $this->getParam('attachmentName');
					$_GET['encoding']	= $this->getParam('attachmentEncoding');
					include("$dir/gotodownload.php");
				}
				// Dont modify header of Response Method to 'application/json'
				$this->setCannotModifyHeader(true);
				return $this->getResponse();
			}
			else{
				Errors::runException("MAIL_ATTACHMENT_NOT_FOUND");
			}
		}*/
	}
}
