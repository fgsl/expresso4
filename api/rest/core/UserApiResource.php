<?php

class UserApiResource extends ExpressoAdapter {

	public function setDocumentation() {

		$this->setResource("Expresso","UserApi","Retorna a API do expresso que o usuario podera se logar.",array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("user","string",true,"Login do Usuario");
		$this->addResourceParam("modules","string",false,"Modulos esperados que o usuario tenha (separados por virgula).");

	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		if( !file_exists( dirname( __FILE__ ) . '/../../config/profileHomeServer.ini') ){
			Errors::runException(2201);
		} else {

			$profiles	= parse_ini_file( dirname( __FILE__ ) . '/../../config/profileHomeServer.ini', true);
			$profileValues = array();

			foreach( $profiles['home.server'] as $key => $value ) {
				$value =  preg_match('/\/$/', trim($value)) ? trim($value) : trim($value) . "/";
				$profileValues[] = $value;
			}

			$apis = array_unique($profileValues);

	 		$resource = "UserApps";
	 		$user_id = $this->getParam('user');
	 		$modules = $this->getParam('modules');
	 		$modulesArray	= array();

			if( strrpos( $modules, ",") !== FALSE ) {
				$modulesArray = explode(",", $modules );
			} else {
				if ($modules != "") {
					$modulesArray[0] = $modules;
				}
			}

			$response = array();
			$params = array();
			$params['user'] = $user_id;

			$i = 0;

			$predictedAPI = "";

			foreach( $apis as $api ){
				$result = $this->callBase($api . $resource, $params);
				$arr_res = json_decode($result);
				$apps = $arr_res->result->apps;

				if (!is_array($apps)) {
					$apps = array();
				}
				$qtdFound = 0;
				foreach( $modulesArray as $moduleName )
				{
					foreach ($apps as $appName) {
						if ($moduleName == $appName) { $qtdFound = $qtdFound + 1; }
					}
				}

				if (count($modulesArray) != 0) {
					if ($qtdFound == count($modulesArray)) { $predictedAPI = $api; }
				} else {
					if (count($apps) != 0) { $predictedAPI = $api; }
				}

				$response['apis'][$i]["api"] = $api;
				$response['apis'][$i]['apps'] = $apps;
				$i++;
			}

			if ($predictedAPI != "") {
				$response['userAPI'] = $predictedAPI;
				$this->setResult($response);
			} else {
				Errors::runException(2200);
			}
			return $this->getResponse();
		}
	}

	public function callBase($url,$params) {
		$ch = curl_init();

		$str_data  = json_encode($params);
		$newPost['id'] = $_POST['id'];
		$newPost['params'] = $str_data;

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $newPost);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Keep-Alive','Expect:'));

		$result = curl_exec($ch);

		return $result;
	}

}
