<?php 
/*
 * This file is part of the "Another" suite of products.
 *
 * (c) 2020 Another, LLC
 *
 */

if ((!defined('CONST_INCLUDE_KEY')) || (CONST_INCLUDE_KEY !== 'd4e2ad09-b1c3-4d70-9a9a-0e6149302486')) {
	// if accessing this class directly through URL, send 404 and exit
	// this section of code will only work if you have a 404.html file in your root document folder.
	header("Location: /404.html", TRUE, 404);
	echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/404.html');
	die;
}

//----------------------------------------------------------------------------------------------------------------------
class App_API_Key extends Data_Access {

	protected $object_name = 'app_api_key';
	protected $object_view_name = 'vw_app_api_key';

	//----------------------------------------------------------------------------------------------------
	// public function __construct() {
    //     // attempt database connection
    //     $res = $this->dbConnect();
        
    //     // if we get anything but a good response ...
    //     if ($res['response'] != '200') {
    //         echo "Houston? We have a problem.";
    //         die;
    //     }
	// }

	//----------------------------------------------------------------------------------------------------
	public function getRecordByAPIKey($varAPIKey = NULL) {

		// job category is required
		if (!isset($varAPIKey) || $varAPIKey === '') {
			$responseArray = App_Response::getResponse('403');
			return $responseArray;
		}

		// build the query
		$query = "SELECT * FROM " . CONST_DB_SCHEMA . "." . $this->object_view_name;
		$query .= " WHERE (api_key = '" . $varAPIKey . "') AND (status_flag = 1);";
		
		$res = $this->getResultSetArray($query);
		
		// if nothing comes back, then return a failure
		if ($res['response'] !== '200') {
			$responseArray = App_Response::getResponse('403');
		} else {
			$responseArray = $res;
		}

		// send back what we got
		return $responseArray;

	}

	//----------------------------------------------------------------------------------------------------
	public function getRecordByAPIKeyPostgres($varAPIKey = NULL, $db) {

		// job category is required
		if (!isset($varAPIKey) || $varAPIKey === '') {
			$responseArray = App_Response::getResponse('403');
			$responseArray['responseDescription'] .= " Missing API key!";
			return $responseArray;
		}

		$query = "SELECT * FROM app_api_key WHERE (api_key = '" . $varAPIKey . "') AND (status_flag = 1);";
		$res = $this->getResultSetArrayPostgres($query, $db);

		// if nothing comes back, then return a failure
		if ($res['response'] !== '200') {
			$responseArray = App_Response::getResponse('403');
		} else {
			$responseArray = $res;
		}

		// send back what we got
		return $responseArray;
	}

	//----------------------------------------------------------------------------------------------------
	public function getRecordByUserPostgres($user_or_email = NULL, $db) {

		// job category is required
		if (!isset($user_or_email) || $user_or_email === '') {
			$responseArray = App_Response::getResponse('403');
			return $responseArray;
		}

		// build the query
		$query = "SELECT * FROM users WHERE (user = '" . $user_or_email . "') OR (email = '" . $user_or_email . "');";
		$res = $this->getResultSetArrayPostgres($query, $db);
		
		// if nothing comes back, then return a failure
		if ($res['response'] !== '200') {
			$responseArray = App_Response::getResponse('403');
			$responseArray['responseDescription'] .= " Missing!";
		} else {
			$responseArray = $res;
		}

		// send back what we got
		return $responseArray;
	}

	//----------------------------------------------------------------------------------------------------
	public function getRecordsByUserAPIKey($varAPIKey = NULL, $db) {

		// job category is required
		if (!isset($varAPIKey) || $varAPIKey === '') {
			$responseArray = App_Response::getResponse('403');
			return $responseArray;
		}

		// build the query
		$query = "SELECT * FROM users WHERE user_api_key = '" . $varAPIKey . "';";
		$res = $this->getResultSetArrayPostgres($query, $db);
		
		// if nothing comes back, then return a failure
		if ($res['response'] !== '200') {
			$responseArray = App_Response::getResponse('403');
			$responseArray['responseDescription'] .= " Invalid Credentials!";
		} else {
			$responseArray = $res;
		}

		// send back what we got
		return $responseArray;
	}


	//----------------------------------------------------------------------------------------------------
	public function getRecordByUserAPIKeyPostgres($varAPIKey = NULL, $email = NULL, $db) {

		// job category is required
		if (!isset($varAPIKey) || $varAPIKey === '' && !isset($email) || $email === '') {
			$responseArray = App_Response::getResponse('403');
			return $responseArray;
		}

		// build the query
		$query = "SELECT * FROM users WHERE (user_api_key = '" . $varAPIKey . "') AND (email = '" . $email . "');";
		$res = $this->getResultSetArrayPostgres($query, $db);
		
		// if nothing comes back, then return a failure
		if ($res['response'] !== '200') {
			$responseArray = App_Response::getResponse('403');
			$responseArray['responseDescription'] .= " Missing!";
		} else {
			$responseArray = $res;
		}

		// send back what we got
		return $responseArray;
	}

} // end class