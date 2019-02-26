<?php

class ContactPictureResource extends CatalogAdapter {	

	public function setDocumentation() {

		$this->setResource("Catalog","Catalog/ContactPicture","Retorna a foto do contato em BASE64.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);

		$this->addResourceParam("contactType","string",true,"(1 = Cat�logo Pessoal, 2 = Cat�logo Geral)",false,"1");
		$this->addResourceParam("contactID","string",false,"ID do contato que ser� retornado.");

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);	
		
		if($this-> isLoggedIn()) 
		{								
			$contact = array();
			$contactID = $this->getParam('contactID');

			// User Contact
			if($this->getParam('contactType') == 1 && $contactID != null){

				$query = "select A.id_contact, A.photo from phpgw_cc_contact A where A.id_contact= ? and A.id_owner = ? ";

				$result = $this->getDb()->Link_ID->query($query, array($contactID,$this->getUserId()));

				if ($result) {

					while($row = $result->fetchRow()) {
						if($row['photo'] != null) {
							$contact[] = array(
								'contactID'     => $row['id_contact'],
								'contactImagePicture'   => ($row['photo'] != null ? base64_encode($row['photo']) : "")
							);
						}
					}

				}
			}
			// Global Catalog
			elseif($this->getParam('contactType') == 2){
				if(!$contactID){
					$contactID = GlobalService::get('phpgw_info')['user']['account_dn'];
				}
				$photo = $this->getUserLdapPhoto(urldecode($contactID));
				$contact[] = array(
						'contactID'		=> $contactID,
						'contactImagePicture'	=> ($photo != null ? base64_encode($photo[0]) : "")
				);
	
			}
			$result = array ('contacts' => $contact);
			$this->setResult($result);			
		}
		//to Send Response (JSON RPC format)
		return $this->getResponse();		
	}	

}
