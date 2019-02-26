<?php
/**************************************************************************\
* eGroupWare                                                               *
* http://www.egroupware.org                                                *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

/**
* Prova métodos que acessam informa��es relacionadas �s inst�ncias.
* @author Sidnei Augusto Drovetto Junior - drovetto@gmail.com
* @version 1.0
* @license http://www.gnu.org/copyleft/gpl.html GPL
* @package Workflow
* @subpackage local
*/
class wf_instance
{
	/**
	* @var object $db objeto do banco de dados
	* @access private
	*/
	private $db;

	/**
	* @var int $processID o ID do processo onde a classe est� sendo utilizada
	* @access private
	*/
	private $processID;

	/**
	* Verifica se um dado processo equivale �quele que est� sendo executado.
	* @param int $processID O ID do processo.
	* @return bool true caso sejam o mesmo processo ou, false caso contr�rio.
	* @access public
	*/
	private function checkProcessAccess($processID)
	{
		$processID = (int) $processID;
		return ($processID === $this->processID);
	}

	/**
	* Verifica se uma inst�ncia pertence ao processo que est� sendo executado.
	* @param int instanceID O ID da inst�ncia.
	* @return bool true caso a inst�ncia perten�a ao processo que est� sendo executado ou, false caso contr�rio.
	* @access public
	*/
	private function checkInstanceAccess($instanceID, $activityID = null)
	{
		$instance = $this->getInstanceObject($instanceID);
		if ($instance === false)
			return false;
		if (!is_null($activityID))
		{
			$activityFound = false;
			foreach ($instance->activities as $activity)
				if (($activityFound = ($activityID == $activity['wf_activity_id'])))
					break;

			if ($activityFound == false)
				return false;
		}
		return $this->checkProcessAccess($instance->pId);
	}

	/**
	* Pega o objeto de uma inst�ncia.
	* @param int instanceID O ID da inst�ncia.
	* @return mixed object caso a inst�ncia seja encontrada ou false caso contr�rio.
	* @access public
	*/
	private function getInstanceObject($instanceID)
	{
		$instanceID = (int) $instanceID;
		$instance = Factory::newInstance('workflow_instance');
		if (!$instance->getInstance($instanceID))
			return false;
		else
			return $instance;
	}

	/**
	* Construtor do wf_instances.
	* @return object
	* @access public
	*/
	public function wf_instance()
	{
		/* load the DB */
		$this->db = &Factory::getInstance('WorkflowObjects')->getDBGalaxia()->Link_ID;

		/* load the process ID from the runtime */
		if (!is_null($GLOBALS['workflow']['wf_runtime']->activity))
			$this->processID = (int) $GLOBALS['workflow']['wf_runtime']->activity->getProcessId();
		/* if a job is running the process, then load the processID specified by the job */
		if (isset($GLOBALS['workflow']['job']))
			$this->processID = (int) $GLOBALS['workflow']['job']['processID'];
	}

	/**
	* D� seq��ncia no fluxo de uma inst�ncia (simula a��o do usu�rio).
	* @param int $activityID O ID da atividade da inst�ncia.
	* @param int $instanceID O ID da inst�ncia.
	* @return bool true caso a inst�ncia tenha sido continuada e false caso contr�rio.
	* @access public
	*/
	public function continueInstance($activityID, $instanceID)
	{
		/* check instanceID */
		if (!$this->checkInstanceAccess($instanceID, $activityID))
			return false;

		/* load the instance object */
		$instance = $this->getInstanceObject($instanceID);

		$runActivity = Factory::newInstance('run_activity');

		ob_start();
		$output = $runActivity->go($activityID, $instanceID, true);
		ob_end_clean();

		return ($output !== false);
	}

	/**
	* Aborta uma inst�ncia
	* @param int $instanceID O ID da inst�ncia.
	* @return boolean true se foi poss�vel abortar a inst�ncia e false caso contr�rio.
	* @access public
	*/
	public function abort($instanceID)
	{
		/* check instanceID */
		if (!$this->checkInstanceAccess($instanceID))
			return false;

		/* load the instance object */
		$instance = $this->getInstanceObject($instanceID);

		/* abort the instance */
		return $instance->abort();
	}

	/**
	* Define o nome (identificador) de uma inst�ncia
	* @param int $instanceID O ID da inst�ncia.
	* @param string $name O novo nome da inst�ncia.
	* @return boolean true se foi poss�vel mudar o nome da inst�ncia e false caso contr�rio.
	* @access public
	*/
	public function setName($instanceID, $name)
	{
		/* check instanceID */
		if (!$this->checkInstanceAccess($instanceID))
			return false;

		/* load the instance object */
		$instance = $this->getInstanceObject($instanceID);

		/* set the name */
		$output = $instance->setName($name);
		$output = $output && $instance->sync();

		return $output;
	}

	/**
	* Define a prioridade de uma inst�ncia
	* @param int $instanceID O ID da inst�ncia.
	* @param int $priority A nova prioridade da inst�ncia
	* @return boolean true se foi poss�vel mudar a prioridade da inst�ncia e false caso contr�rio.
	* @access public
	*/
	public function setPriority($instanceID, $priority)
	{
		/* check instanceID */
		if (!$this->checkInstanceAccess($instanceID))
			return false;

		/* load the instance object */
		$instance = $this->getInstanceObject($instanceID);

		/* ensure the instance priority range */
		$priority = max(min((int) $priority, 4), 0);

		/* set the new priority */
		$output = $instance->setPriority($priority);
		$output = $output && $instance->sync();
		return $output;
	}

	/**
	* Busca inst�ncias ativas que est�o "abandonadas".
	* @param int $numberOfDays O tempo (em dias) em que a inst�ncia est� abandonada.
	* @param array $activities Uma lista de atividades das quais se quer as inst�ncias abandonadas (tamb�m pode ser um valor inteiro).
	* @return array As inst�ncias que satisfazem o crit�rio de sele��o.
	* @access public
	*/
	public function getIdle($numberOfDays, $activities = null)
	{
		/* prepare some variables */
		$output = array();
		$restrictToActivities = !is_null($activities);
		if (is_numeric($activities))
			$activities = array((int) $activities);

		if (is_numeric($numberOfDays))
			$numberOfDays = (int) $numberOfDays;
		else
			return $output;

		/* restrict the range and get the threshold date (in UNIX ERA format) */
		$numberOfDays = max(0, $numberOfDays);
		$threshold = time() - ($numberOfDays * 24 * 60 * 60);

		/* build the SQL query */
		$query = 'SELECT ia.wf_instance_id AS wf_instance_id, ia.wf_activity_id AS wf_activity_id, ia.wf_started AS wf_started, i.wf_name AS wf_name, i.wf_status AS wf_status, ia.wf_user AS wf_user, i.wf_priority AS wf_priority ';
		$query .= 'FROM egw_wf_instance_activities ia, egw_wf_instances i ';
		$query .= 'WHERE (ia.wf_instance_id = i.wf_instance_id) AND (i.wf_p_id = ?) AND (ia.wf_started < ?)';
		$resultSet = $this->db->query($query, array($this->processID, $threshold));

		/* fetch the results */
		while ($row = $resultSet->fetchRow())
		{
			/* if required, restrict to specific activities */
			if ($restrictToActivities)
				if (!in_array($row['wf_activity_id'], $activities))
					continue;

			$output[] = $row;
		}

		return $output;
	}

	/**
	* Busca todas as inst�ncias ativas.
	* @param array $activities Uma lista de atividades das quais se quer as inst�ncias (tamb�m pode ser um valor inteiro).
	* @return array As inst�ncias que satisfazem o crit�rio de sele��o.
	* @access public
	* @deprecated 2.2.000
	*/
	public function getAll($activities = null)
	{
		wf_warn_deprecated_method('wf_instances', 'getAllActive');
		return $this->getIdle(0, $activities);
	}

	/**
	* Search and return all active instances.
	* @param array $activities A list of activities codes to restrict instances from (may also be a single integer code).
	* @return array The instaces which match the search criteria.
	* @access public
	*/
	public function getAllActive($activities = null)
	{
		return $this->getIdle(0, $activities);
	}

	/**
	* Retrieve all completed instances.
	* @return array All completed instances from current process. Be careful this may be a long array.
	* @access public
	*/
	public function getAllCompleted()
	{
		$output = array();

		// Build the SQL query
		// Select all instances from the process that has a final date
		$query = 'SELECT i.wf_instance_id, i.wf_started, i.wf_ended, i.wf_name, i.wf_status, i.wf_priority ';
		$query .= 'FROM egw_wf_instances i ';
		$query .= 'WHERE (i.wf_p_id = ?) AND (i.wf_ended > 0)';
		$resultSet = $this->db->query($query, array($this->processID));

		/* fetch the results */
		while ($row = $resultSet->fetchRow())
		{
			$output[] = $row;
		}

		return $output;
	}

	/**
	* This method gets all children instances of the given instance.
	* If there is no parameter, it gets the children instances of the current instance
	* @param int $instanceID Dad instance's identification.
	* @return array Array with the children instances, or false
	* @access public
	*/
	public function getChildren($instanceID = null)
	{
		$output = array();
		if (is_null($instanceID))
			$instanceID = $GLOBALS['workflow']['wf_runtime']->instance_id;

		/* check instanceID */
		if (!$this->checkInstanceAccess($instanceID))
			return $output;

		/* build the SQL query */
		$query = "
			SELECT
				i.wf_instance_id AS wf_instance_id,
				ia.wf_activity_id AS wf_activity_id,
				i.wf_started AS wf_started,
				i.wf_name AS wf_name,
				i.wf_status AS wf_status,
				ia.wf_user AS wf_user,
				ir.wf_parent_lock AS wf_parent_lock
			FROM
			    egw_wf_interinstance_relations as ir
			LEFT JOIN
			    egw_wf_instances as i
			ON
			    i.wf_instance_id = ir.wf_child_instance_id
			LEFT JOIN
			    egw_wf_instance_activities as ia
			ON
			    i.wf_instance_id = ia.wf_instance_id
			WHERE
			    ir.wf_parent_instance_id = ?";

		$result = $this->db->query($query, array($instanceID));
		$output = $result->GetArray(-1);

		return $output;
	}

	/**
	* Busca as propriedades de uma inst�ncia (do mesmo processo).
	* @param int $instanceID O ID da inst�ncia.
	* @return mixed Uma array contento as propriedades da inst�ncia (no formato "nome_da_propriedade" => "valor"). Ou false em caso de erro.
	* @access public
	*/
	public function getProperties($instanceID)
	{
		/* check instanceID */
		if (!$this->checkInstanceAccess($instanceID))
			return false;

		/* load the properties of the instance object */
		return $this->getInstanceObject($instanceID)->properties;
	}

	/**
	* Define uma propriedade de uma inst�ncia.
	* @param int $instanceID O ID da inst�ncia.
	* @return bool true caso a propriedade tenha sido alterada com sucesso
	* @access public
	*/
	public function setProperty($instanceID, $propertyName, $propertyValue)
	{
		/* check instanceID */
		if (!$this->checkInstanceAccess($instanceID))
			return false;

		/* load the instance object */
		$instance = $this->getInstanceObject($instanceID);

		/* set the property */
		$output = $instance->set($propertyName, $propertyValue);
		$output = $output && $instance->sync();

		return $output;
	}

	/**
	* Busca as inst�ncia de usu�rios de acordo com alguns crit�rios
	* @param mixed $users Um array com IDs de usu�rios ou perfis (no caso de perfis, deve-se prefixar seu ID com o caractere 'p'). Tamb�m pode possuir um �nico ID (seja de usu�rio ou de perfil)
	* @param mixed $activities Um array com IDs de atividades das se quer as inst�ncias. Tamb�m pode ser um inteiro, representando um �nico ID. Caso possua valor null, o resultado n�o � filtrado de acordo com as atividades (par�metro opcional)
	* @param mixed $status Um array com os status requeridos (para filtrar as inst�ncias). Tamb�m pode ser uma string, representando um �nico status. Caso possua valor null, o resultado n�o � filtrado de acordo com o status. Os status podem ser: completed, active, aborted e exception (par�metro opcional)
	* @return array As inst�ncias que satisfazem o crit�rio de sele��o.
	* @access public
	*/
	public function getByUser($users, $activities = null, $status = null)
	{
		/* check for the supplied users/roles */
		if (empty($users))
			return array();
		if (!is_array($users))
			$users = array($users);
		foreach ($users as &$user)
		{
			if (!preg_match('/^[p]{0,1}[0-9]+$/i', "$user"))
				trigger_error('wf_engine::getUserInstances: O usu�rio/perfil "' . $user . '" � inv�lido', E_USER_ERROR);
			$user = "'{$user}'";
		}

		/* check for activity restriction */
		$restrictToActivities = !is_null($activities);
		if (!is_array($activities))
			$activities = array((int) $activities);
		array_walk($activities, create_function('&$a', '$a = (int) $a;'));

		/* check for status restriction */
		$statusPossibleValues = array('completed', 'active', 'aborted', 'exception');
		$restrictToStatus = !is_null($status);
		if (is_string($status))
			$status = array($status);
		/* check if the supplied status are valid */
		if ($restrictToStatus)
		{
			array_walk($status, create_function('&$a', '$a = strtolower($a);'));
			foreach ($status as $currentStatus)
				if (!in_array($currentStatus, $statusPossibleValues))
					trigger_error('wf_engine::getUserInstances: O status "' . $currentStatus . '" � inv�lido', E_USER_ERROR);
		}

		/* build the SQL query */
		$query = "SELECT ia.wf_instance_id AS wf_instance_id, ia.wf_activity_id AS wf_activity_id, ia.wf_started AS wf_started, i.wf_name AS wf_name, i.wf_status AS wf_status, ia.wf_user AS wf_user, i.wf_priority AS wf_priority ";
		$query .= "FROM egw_wf_instance_activities ia, egw_wf_instances i ";
		$query .= "WHERE (ia.wf_instance_id = i.wf_instance_id) AND (i.wf_p_id = ?) AND (ia.wf_user IN (" . implode(', ', $users) . "))";
		$values = array($this->processID);

		if ($restrictToActivities)
		{
			$query .= ' AND (ia.wf_activity_id = ANY (?))';
			$values[] = '{' . implode(', ', $activities) . '}';
		}

		if ($restrictToStatus)
		{
			$aux = ' AND (i.wf_status IN (';
			foreach($status as $id){
				$values[] = $id;
				$query .= $aux.' ?';
				$aux = ', ';
			}
			$query .= ' ))';
		}

		$resultSet = $this->db->query($query, $values);

		/* fetch and return the results */
		return $resultSet->GetArray(-1);
	}

	/**
	* Busca uma inst�ncia pelo id
	* @param int $wf_instance_id O id da inst�ncia
	* @return array As inst�ncias que satisfazem o crit�rio de sele��o.
	* @access public
	*/
	public function getById($wf_instance_id)
	{
		/* build the SQL query */
		$query = 'SELECT i.wf_instance_id AS wf_instance_id, ia.wf_activity_id AS wf_activity_id, ia.wf_started AS wf_started, i.wf_name AS wf_name, i.wf_status AS wf_status, ia.wf_user AS wf_user, i.wf_priority AS wf_priority ';
		$query .= 'FROM egw_wf_instances i LEFT JOIN egw_wf_instance_activities ia ON (ia.wf_instance_id = i.wf_instance_id)';
		$query .= 'WHERE (i.wf_p_id = ?) AND i.wf_instance_id = ?';

		$resultSet = $this->db->query($query, array($this->processID, intval($wf_instance_id)));
		
		return $resultSet->GetArray();
	}

	/**
	* Busca todas as inst�ncias que possuem esse nome (identificador).
	* @param string $name O nome da inst�ncia que se quer encontrar.
	* @return array As inst�ncias que satisfazem o crit�rio de sele��o.
	* @access public
	*/
	public function getByName($name)
	{
		/* build the SQL query */
		$query = 'SELECT i.wf_instance_id AS wf_instance_id, ia.wf_activity_id AS wf_activity_id, ia.wf_started AS wf_started, i.wf_name AS wf_name, i.wf_status AS wf_status, ia.wf_user AS wf_user, i.wf_priority AS wf_priority ';
		$query .= 'FROM egw_wf_instances i LEFT JOIN egw_wf_instance_activities ia ON (ia.wf_instance_id = i.wf_instance_id)';
		$query .= 'WHERE (i.wf_p_id = ?) AND (UPPER(i.wf_name) = UPPER(?))';

		$resultSet = $this->db->query($query, array($this->processID, $name));
		return $resultSet->GetArray();
	}

	/**
	* Busca todas as inst�ncias que possuem um trecho do nome (identificador).
	* @param string $name O trecho do nome da inst�ncia que se quer encontrar.
	* @return array As inst�ncias que satisfazem o crit�rio de sele��o.
	* @access public
	*/
	public function getLikeName($name)
	{
		/* build the SQL query */
		$query = 'SELECT i.wf_instance_id AS wf_instance_id, ia.wf_activity_id AS wf_activity_id, ia.wf_started AS wf_started, i.wf_name AS wf_name, i.wf_status AS wf_status, ia.wf_user AS wf_user, i.wf_priority AS wf_priority ';
		$query .= 'FROM egw_wf_instances i LEFT JOIN egw_wf_instance_activities ia ON (ia.wf_instance_id = i.wf_instance_id)';
		$query .= "WHERE (i.wf_p_id = ?) AND i.wf_name ILIKE '%$name%'";

		$resultSet = $this->db->query($query, array($this->processID));

		return $resultSet->GetArray();
	}

	/**
	* Verifica se um dado usu�rio tem acesso a uma inst�ncia
	* @param int $userID O ID do usu�rio que se quer verificar
	* @param int $instanceID O ID da inst�ncia
	* @param int $activityID O ID da atividade onde a inst�ncia est�
	* @param bool $writeAccess Se true, indica que � necess�rio que o usu�rio tenha acesso para modificar a inst�ncia (dar seq��ncia ao fluxo). Se false, n�o ser� verificado se o usu�rio tem permiss�o de escrita na inst�ncia
	* @return bool true se o usu�rio tiver acesso � inst�ncia (levando em considera��o $writeAccess) ou false caso contr�rio
	* @access public
	*/
	public function checkUserAccess($userID, $instanceID, $activityID, $writeAccess = true)
	{
		/* only integers are allowed */
		$userID = (int) $userID;
		$instanceID = (int) $instanceID;
		$activityID = (int) $activityID;

		/* load the required instance (for the required user) */
		$GUI = &Factory::newInstance('GUI');
		$userInstance = $GUI->gui_list_user_instances($userID, 0, -1, '', '', "(ga.wf_is_interactive = 'y') AND (gia.wf_activity_id = {$activityID}) AND (gia.wf_instance_id = {$instanceID})", false, $this->processID, true, false, true, false, false, false);
		$userInstance = $userInstance['data'];

		/* if no instance is found, the user does not have access to it */
		if (empty($userInstance))
			return false;

		/* if no write access is required, then the user have access to the instance */
		if (!$writeAccess)
			return true;

		/* write access is required, check for it */
		return ($userInstance['wf_readonly'] == 0);
	}

	/**
	* Define o usu�rio de uma inst�ncia (em uma atividade)
	* @param int $instanceID O ID da inst�ncia.
	* @param int $activityID O ID da atividade.
	* @param int $userID O ID do usu�rio.
	* @return boolean true se foi poss�vel definir o usu�rio da inst�ncia ou false caso contr�rio.
	* @access public
	*/
	public function setUser($instanceID, $activityID, $userID)
	{
		/* check instanceID and activityID */
		if (!$this->checkInstanceAccess($instanceID, $activityID))
			return false;

		if ($userID !== '*')
		{
			$wfRole = Factory::getInstance('wf_role');
			$engine = Factory::getInstance('wf_engine');
			/* get information about the activity */
			if (($activityInfo = $engine->getActivityInformationByID($activityID)) === false)
				return false;

			/* load the possible roles of the activity */
			$possibleRoles = $wfRole->getActivityRoles($activityInfo['name']);
			if (substr($userID, 0, 1) == 'p')
			{
				/* the instance is being set to a role */
				/* check if the role is valid */
				$roleID = (int) substr($userID, 1);
				$userID = 'p' . $roleID;
				$validRole = false;
				foreach ($possibleRoles as $possibleRole)
				{
					if ($roleID == $possibleRole['id'])
					{
						$validRole = true;
						break;
					}
				}
				if (!$validRole)
					return false;
			}
			else
			{
				/* the instance is being set to a user */
				/* check if the $userID is a number */
				if (!is_numeric($userID))
					return false;

				/* check if the user is valid */
				$userID = (int) $userID;
				$validUser = false;
				foreach ($possibleRoles as $possibleRole)
				{
					if ($wfRole->checkUserInRole($userID, $possibleRole['name']))
					{
						$validUser = true;
						break;
					}
				}
				if (!$validUser)
					return false;
			}
		}

		$query = 'UPDATE egw_wf_instance_activities SET wf_user = ? WHERE (wf_instance_id = ?) AND (wf_activity_id = ?)';
		$this->db->execute($query, array($userID, $instanceID, $activityID));

		return true;
	}

	/**
	* Define o perfil que poder� acessar uma inst�ncia (em uma atividade)
	* @param int $instanceID O ID da inst�ncia.
	* @param int $activityID O ID da atividade.
	* @param string $roleName O nome do perfil.
	* @return boolean true se foi poss�vel definir o perfil da inst�ncia ou false caso contr�rio.
	* @access public
	*/
	public function setRole($instanceID, $activityID, $roleName)
	{
		/* check instanceID and activityID */
		if (!$this->checkInstanceAccess($instanceID, $activityID))
			return false;

		/* try to get the role id */
		$wfRole = Factory::getInstance('wf_role');
		if (($roleID = $wfRole->getRoleIdByName($roleName)) === false)
			return false;

		return $this->setUser($instanceID, $activityID, 'p' . $roleID);
	}

	/**
	* Altera o wf_name das instâncias, fazendo o replace no nome da categoria de serviço. 
	* Método chamado quando é alterada a categoria de um serviço e existem ocorrências atrelados a esse servi�o.
	* @param int | array $instanceID se for um array, concatena os instances ids
	* @param string @currentServiceName Nome da categoria de serviço atual
	* @param string @newServiceName Nome da nova categoria de serviço
	* @return object resultSet em caso de sucesso ou false
	* @access public 
	*/
	public function updateReplaceName($instanceID, $currentServiceName, $newServiceName)
	{
		$success = true;

		// Se for array, faz implode concatenando os ids
		if (is_array($instanceID))
		{
			$instanceID = implode(',', $instanceID);
		}

		$query = "UPDATE egw_wf_instances SET wf_name = REPLACE(wf_name, '" . $currentServiceName . "', '" . $newServiceName . "') WHERE wf_instance_id IN (" . $instanceID . ")";
		$success = $this->db->execute($query);

		return $success;
	}
}
?>
