<?php

namespace App\Services\Base\adapters;

use App\Services\Base\Adapters\ExpressoAdapter;
use App\Services\Base\Commons\Errors;

class CatalogAdapter extends ExpressoAdapter {	

	private $minArgumentSearch = "4";
	private $userId;
	private $ldapCatalog;

	protected function addContact($contact)
	{
		$newContact = CreateObject('contactcenter.ui_data');

		return $newContact->quick_add_mobile(serialize($contact));
	}

	protected function deleteContact($contactID)
	{
		$contactDelete = CreateObject('contactcenter.ui_data');
		return $contactDelete->remove_entry( (int)$contactID , true );
	}
	
	protected function getMinArgumentSearch(){
		return $this->minArgumentSearch;
	}
	
	protected function getUserId(){
		return $GLOBALS['phpgw_info']['user']['account_id'];
	}		

	protected function getLdapCatalog(){
		if(!$this->ldapCatalog)
		{
			$_SESSION['phpgw_info']['expressomail']['server'] = $GLOBALS['phpgw_info']['server'];
			
			$this->ldapCatalog = CreateObject("expressoMail1_2.ldap_functions");
		}
	
		return $this->ldapCatalog;
	}
	
	protected function getDb(){
		return $GLOBALS['phpgw']->db;
	}	
	
	protected function getUserLdapAttrs($mail)
	{
		$filter="(&(phpgwAccountType=u)(mail=".$mail."))";
		$ldap_context = $GLOBALS['phpgw_info']['server']['ldap_context'];
		$justthese = array("dn", 'jpegPhoto','givenName', 'sn', 'uidNumber','telephonenumber'); 
		$ds = $this->getLdapCatalog()->ds;
		if ($ds){
			$sr = @ldap_search($ds, $ldap_context, $filter, $justthese);	
			if ($sr) {
				$entry = ldap_first_entry($ds, $sr);
				if($entry) {									
					$givenName = @ldap_get_values_len($ds, $entry, "givenname");
					$sn = @ldap_get_values_len($ds, $entry, "sn");
					$uidNumber = @ldap_get_values_len($ds, $entry, "uidnumber");
					$contactHasImagePicture = (@ldap_get_values_len($ds, $entry, "jpegphoto") ? 1 : 0);
					$phone = @ldap_get_values_len($ds, $entry, "telephonenumber");
					$dn = ldap_get_dn($ds, $entry);
					return array(
						"contactID" => urlencode($dn),
						"contactUIDNumber" => $uidNumber[0],
						"contactFirstName" => $givenName[0],
						"contactLastName" 	=> $sn[0],
						"contactHasImagePicture" => $contactHasImagePicture,
						"contactPhones" => array($phone[0])
					);
				}
			}
		}
		return false;
	}

	
	protected function getUserLdapPhoto($contactID) {		
		$ldap_context = $_SESSION['phpgw_info']['expressomail']['ldap_server']['dn'];
		$justthese = array("dn", 'jpegPhoto','givenName', 'sn');
		$this->getLdapCatalog()->ldapConnect(true);
		$ds = $this->getLdapCatalog()->ds;		
		
		if ($ds){
			$resource = @ldap_read($ds, $contactID, "phpgwaccounttype=u");
			$n_entries = @ldap_count_entries($ds, $resource);

			if ( $n_entries == 1) {			
				$first_entry = ldap_first_entry($ds, $resource);
				$obj = ldap_get_attributes($ds, $first_entry);
				
				if($obj['jpegPhoto']){
					return ldap_get_values_len( $ds, $first_entry, "jpegPhoto");
				}
			}								
		}
		return false;
	}	
	
	protected function getGlobalContacts($search, $uidNumber){
		$contacts = array();

		if (empty($uidNumber))
		{
			$params = array ("search_for" => $search);
	 		$result = $this->getLdapCatalog()->quickSearch($params);
		}
		else
	 		$result = $this->getLdapCatalog()->uidNumber2cn($uidNumber);

 		
 		if ( array_key_exists('error', $result))
 		{
 			Errors::runException("CATALOG_MANY_RESULTS");
 		}
 		else
 		{
	 		// Reconnect for searching other attributes.
	 		$this->getLdapCatalog()->ldapConnect(true);
			foreach($result as $i => $row) {
				if(is_int($i)) {
					$contacts[$i] = array(
						'contactMails'	=> array($result[$i]['mail']),
						'contactAlias' => "",					
						'contactFullName' 	=> ($result[$i]['cn'] != null ? mb_convert_encoding($row['cn'],"UTF8", "ISO_8859-1") : ""),
						'contactBirthDate'	=> "",
						'contactNotes' 		=> ""
					);
					// Buscar atributos faltantes. 
					$otherAttrs = $this->getUserLdapAttrs($result[$i]['mail']);
					if(is_array($otherAttrs))
						$contacts[$i] = array_merge($otherAttrs, $contacts[$i]);				
				}
			}
			// Force ldap close
			ldap_close($this->getLdapCatalog()->ds);		
			if( count($contacts) )
			{
				$result = array ('contacts' => $contacts);
				$this->setResult($result);
				return $this->getResponse();
			}
			else
			{
				Errors::runException("CATALOG_NO_RESULTS");
			}
 		}
	}
}
