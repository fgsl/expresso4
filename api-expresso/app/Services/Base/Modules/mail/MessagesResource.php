<?php

namespace App\Services\Base\Modules\mail;

use App\Services\Base\Adapters\MailAdapter;
use App\Services\Base\Commons\Errors;

class MessagesResource extends MailAdapter {	

	public function setDocumentation() {
		$this->setResource("Mail","Mail/Messages","Retorna as mensagens do usu�rio.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);
		$this->addResourceParam("folderID","string",true,"Pasta base retornar as mensagens.",true,"INBOX");
		$this->addResourceParam("msgID","integer",false,"ID da mensagem, quando informado retorna uma �nica mensagem.");
		$this->addResourceParam("search","string",false,"Buscar mensagens com o conteudo.");
		$this->addResourceParam("resultsPerPage","integer",true,"Resultados por p�gina.",true,"10");
		$this->addResourceParam("page","integer",true,"P�gina Atual.",true,"1");
	}

	public function post($request){

 		$this->setParams( $request );	
 		$imap_msgs = null;
 		$all_msgs = array();
		
		if($this->getParam('folderID') && $this->getParam('msgID') > 0) {
			$msg = $this->getMessage();
			if(!$msg){
				return Errors::runException("MAIL_MESSAGE_NOT_FOUND", $this->getParam('folderID'));
			}
			else{
				$result = array ('messages' => array($msg));
				$this->setResult($result) ;
				return $this->getResponse();
			}
		}

		elseif($this->getParam('search') != "") {
			$imap = $this->getImap();
			$condition = array();
			$imap_folders =  $imap->get_folders_list();

			if($this->getExpressoVersion() == "2.2") {

				foreach ($imap_folders as $i => $imap_folder) {
					if(is_int($i)) {
						$folder = mb_convert_encoding($imap_folder['folder_id'],'UTF-8','ISO-8859-1');
						$folder = mb_convert_encoding($folder, 'UTF7-IMAP','UTF-8');
						$condition[] = "$folder##ALL <=>".$this->getParam('search')."##";
					}
				}

				$params = array(
						'condition' => implode(",",$condition),							
						'page' 		=> (intval($this->getParam('page') ? $this->getParam('page') : "1"))-1,
						'sort_type' => "SORTDATE"
					);

				$this->getImap()->prefs['preview_msg_subject'] = "1";

				$imap_msgs = $this->getImap()->search_msg($params);

				if($imap_msgs['num_msgs'] > 0) {
					foreach($imap_msgs['data'] as $imap_msg) {
						$msg = array();
						$msg['msgID'] = $imap_msg['uid'];
						$msg['folderID'] = mb_convert_encoding($imap_msg['boxname'],'UTF-8','ISO-8859-1');
						$msg['msgDate']	= $imap_msg['udate']." 00:00";
						$msg['msgSubject'] = mb_convert_encoding($imap_msg['subject'],"UTF-8", "ISO-8859-1");
						$msg['msgSize'] = $imap_msg['size'];
						$msg['msgFrom']	= array('fullName' => mb_convert_encoding($imap_msg['from'],"UTF-8", "ISO-8859-1"), 'mailAddress' => "");							
						$msg['msgFlagged']	= strpos($imap_msg['flag'],"F") !== false ? "1" : "0";
						$msg['msgSeen']		= strpos($imap_msg['flag'],"U") !== false ? "0" : "1";
						$msg['msgHasAttachments'] = strpos($imap_msg['flag'],"T") !== false ? "1" : "0";							
						$msg['msgForwarded'] = (strpos($imap_msg['flag'],"A") !== false && strpos($imap_msg['flag'],"X") !== false) ? "1" : "0";
						$msg['msgAnswered'] = $msg['msgForwarded'] != "1" && strpos($imap_msg['flag'],"A") !== false  ? "1" : "0";
						$msg['msgDraft'] 	= $msg['msgForwarded'] != "1" && strpos($imap_msg['flag'],"X") !== false ? "1" : "0";
						$all_msgs[] = $msg;
					}
				}
			}
		} else {
			$max_email_per_page = intval($this->getParam('resultsPerPage') ? $this->getParam('resultsPerPage') :
			$this->getImap()->prefs['max_email_per_page']);

			$current_page = intval($this->getParam('page') ? $this->getParam('page') : 1);

			$msg_range_begin = ($max_email_per_page * ($current_page - 1)) + 1;
			$msg_range_end = $msg_range_begin + ($max_email_per_page  - 1);

			$this->getImap()->prefs['preview_msg_subject'] = "1";

			$imap_msgs = $this->getImap()->get_range_msgs2(
				array(	"folder"			=> $this->getParam('folderID'),
						"msg_range_begin" 	=> $msg_range_begin,
						"msg_range_end"	 	=> $msg_range_end,
						"search_box_type"	=> "ALL",
						"sort_box_reverse"	=> "1",
						"sort_box_type"		=> "SORTARRIVAL"
				)
			);
			
			if(!$imap_msgs){ return $this->getResponse(); }

			$folderID = mb_convert_encoding($this->getParam('folderID'), 'UTF-8','ISO-8859-1');

			foreach($imap_msgs as $i => $imap_msg) {
				if(!is_int($i)) {
					continue;
				}
				
				$msg = array();
				$msg['msgID'] = $imap_msg['msg_number'];
				$msg['folderID'] = $folderID;

				$msg['msgDate']	= gmdate('d/m/Y H:i', $imap_msg['timestamp']);
				$msg['msgFrom']['fullName'] = mb_convert_encoding($imap_msg['from']['name'],"UTF-8", "ISO-8859-1");
				$msg['msgFrom']['mailAddress'] = $imap_msg['from']['email'];
				$msg['msgTo'] = array();
				if($this->getExpressoVersion() != "2.2") {
					foreach($imap_msg['to'] as $to){
						$msg['msgTo'][] = array('fullName' => mb_convert_encoding($to['name'],"UTF-8", "ISO-8859-1"), 'mailAddress' => $to['email']);
					}
				}else{
					$msg['msgTo'][] = array('fullName' => mb_convert_encoding($to['name'],"UTF-8", "ISO-8859-1"), 'mailAddress' => $imap_msg['to']['email']);
				}
				$msg['msgReplyTo'][0] = $this->formatMailObject($imap_msg['reply_toaddress']);
				$msg['msgSubject']  = mb_convert_encoding($imap_msg['subject'],"UTF-8", "ISO-8859-1");

				if($this->getExpressoVersion() != "2.2") {
					$msg['msgHasAttachments'] = $imap_msg['attachment'] ? "1" : "0";
				}
				else{
					$msg['msgHasAttachments'] = $imap_msg['attachment']['number_attachments'] ? "1" : "0";
				}

				$msg['msgFlagged'] 	= $imap_msg['Flagged'] == "F" ? "1" : "0";
				$msg['msgForwarded']= $imap_msg['Forwarded'] == "F" ? "1" : "0";
				$msg['msgAnswered'] = $imap_msg['Answered'] == "A" ? "1" : "0";
				$msg['msgDraft']	= $imap_msg['Draft'] == "X" ? "1" : "0";
				$msg['msgSeen'] 	= $imap_msg['Unseen'] == "U" ? "0" : "1";

				$msg['ContentType']	= $imap_msg['ContentType'];
				$msg['msgSize'] 	= $imap_msg['Size'];

				$msg['msgBodyResume'] = $imap_msg['msg_sample']['body'];

				if($this->getExpressoVersion() != "2.2") {
					$msg['msgBodyResume'] =  base64_decode($msg['msgBodyResume']);
				}

				$msg['msgBodyResume'] = substr($msg['msgBodyResume'], 2);
				$msg['msgBodyResume'] = str_replace("\r\n", "", $msg['msgBodyResume']);
				$msg['msgBodyResume'] = str_replace(chr(160)," ", $msg['msgBodyResume']);
				$msg['msgBodyResume'] = preg_replace('/\s\s+/', '', $msg['msgBodyResume']);
				$msg['msgBodyResume'] = mb_convert_encoding($msg['msgBodyResume'],"UTF-8", "ISO-8859-1");

				$all_msgs[] = $msg;
			}
		}
		
		$result = array (
				'messages' 	  => $all_msgs,
				'timeZone'	  => $imap_msgs['offsetToGMT'] ? $imap_msgs['offsetToGMT'] : "",
				'totalUnseen' => $imap_msgs['tot_unseen'] ? $imap_msgs['tot_unseen'] : ""
		);
			
		$this->setResult($result);	
		
		//	to Send Response (JSON RPC format)
		return $this->getResponse();		
	}
}
