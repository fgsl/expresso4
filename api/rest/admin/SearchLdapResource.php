<?php

class SearchLdapResource extends AdminAdapter
{

	public function setDocumentation() {

		$this->setResource("Admin","Admin/SearchLdap","Faz a busca do usu�rio dentro do cat�log ldap.",array("POST"));
		$this->addResourceParam("auth","string",true,"Chave de autentica��o do Usu�rio.",false);

		$this->addResourceParam("accountSearchUID","string",true,"Faz a busca pelo uid do usu�rio no cat�logo ldap");
		$this->addResourceParam("accountSearchCPF","string",true,"Faz a busca pelo CPF do usu�rio no cat�logo ldap");
		$this->addResourceParam("accountSearchRG","string",true,"Faz a busca pelo RG do usu�rio no cat�logo ldap");
		$this->addResourceParam("accountSearchMail","string",true,"Faz a busca pelo email do usu�rio no cat�logo ldap");
	}

	public function post($request)
	{
		// to Receive POST Params (use $this->params)
 		parent::post($request);

 		if( $this->isLoggedIn() )
 		{
			// Permission
			$permission = array();
			$permission['action'] 	= 'list_users';
			$permission['apps'] 	= $this->getUserApps();

			//Load Conf Admin
			$this->loadConfAdmin();
			
			if( $this->validatePermission($permission) )
			{
				$accountSearchUID 	= ( $this->getParam('accountSearchUID') ) ? trim($this->getParam('accountSearchUID')) : null;
				$accountSearchCPF 	= ( $this->getParam('accountSearchCPF') ) ? trim($this->getParam('accountSearchCPF')) : null;
				$accountSearchRG 	= ( $this->getParam('accountSearchRG') ) ? trim($this->getParam('accountSearchRG')) : null;
				$accountSearchMail 	= ( $this->getParam('accountSearchMail') ) ? trim($this->getParam('accountSearchMail')) : null;

				if( !is_null($accountSearchUID) || !is_null($accountSearchCPF) || !is_null($accountSearchRG) || !is_null($accountSearchMail))	
				{
					if( $accountSearchUID != "")
					{
						$accountSearchUID = trim(preg_replace("/[^a-z_0-9_-_.\\s]/", "", strtolower($accountSearchUID)));
						
						$accountSearchUID = trim($this->getParam('accountSearchUID'));

						$this->setResult( array( "result" => $this->getUserSearchLdap(serialize(array("uid",$accountSearchUID)))) );
					}
					else if( $accountSearchCPF != "" )
					{
						$accountSearchCPF = trim(preg_replace("/[^0-9]/", "", $accountSearchCPF));	

						if( strlen($accountSearchCPF) == 11 )
						{
							$this->setResult( array( "result" => $this->getUserSearchLdap(serialize(array("cpf",$accountSearchCPF)))) );
						}
						else
						{
							Errors::runException( "ADMIN_CPF_IS_NOT_VALID" );
						}
					}
					else if( $accountSearchRG != "" )
					{
						$accountSearchRG = trim(preg_replace("/[^0-9]/", "", $accountSearchRG));

						if( !empty($accountSearchRG) )
						{
							$this->setResult( array( "result" => $this->getUserSearchLdap(serialize(array("rg",$accountSearchRG)))) );	
						}
						else
						{
							Errors::runException( "ADMIN_RG_UF_EMPTY");
						}
					}
					else if( $accountSearchMail != "" )
					{
						if( !empty($accountSearchMail) )
						{
							$this->setResult( array( "result" => $this->getUserSearchLdap(serialize(array("mail",$accountSearchMail)))) );	
						}
						else
						{
							Errors::runException( "ADMIN_MAIL_EMPTY" );
						}
					}
					else
					{
						Errors::runException( "ADMIN_SEARCH_LDAP_CHARACTERS_NOT_ALLOWED" );
					}
				}
				else
				{
					Errors::runException( "ADMIN_SEARCH_LDAP_VAR_IS_NULL" );
				}
			}
			else
			{
				Errors::runException( "ACCESS_NOT_PERMITTED" );
			}
 		}

 		return $this->getResponse();
	}
}
