<?php

    function generateToken($varAPIKey = NULL, $db) {
        // api key is required
        if (!isset($varAPIKey) || $varAPIKey === '') {
            $responseArray = App_Response::getResponse('403');
            return $responseArray;
        }

        // $varAPIKey = 'rerewrer123';
        $query = "SELECT * FROM app_api_key WHERE (api_key = '" . $varAPIKey . "') AND (status_flag = 1);";
        $result = pg_query($db, $query);

        
        // if nothing comes back, then return a failure
        if (pg_num_rows($result) && $result) {
            $api_key_entry = pg_fetch_all($result);
            $res = $api_key_entry[0];
            $apiSecretKey = $res['api_secret_key'];
            $payloadArray = array();
            $payloadArray['apiKey'] = $varAPIKey;
            $token = JWT::encode($payloadArray, $apiSecretKey);
            $returnArray = App_Response::getResponse('200');
            $returnArray['token'] = $token;
        } else {
            $returnArray = App_Response::getResponse('403');
        }

        return $returnArray;
    }

    function generateUserToken($user = NULL, $db) {
        // api key is required
        if (!isset($user)) {
            $responseArray = App_Response::getResponse('403');
			$res['message'] = "Missing Or Invalid Credentials";
            return $responseArray;
        }

		// $apiSecretKey = $res['user_id'];
		// $payloadArray = array();
		// $payloadArray['apiKey'] = $varAPIKey;

		$secret_key = $user['password'];
		$issuer_claim = "equitytable"; // this can be the servername
		$audience_claim = "equitytable_front_end";
		$issuedat_claim = time(); // issued at
		$notbefore_claim = $issuedat_claim + 10; //not before in seconds
		$expire_claim = $issuedat_claim + 86400; // expire time in seconds
		$tokenPayload = array(
			"iss" => $issuer_claim,
			"aud" => $audience_claim,
			"iat" => $issuedat_claim,
			"nbf" => $notbefore_claim,
			"exp" => $expire_claim,
			"apiKey" => $user['user_api_key'],
			"data" => array(
				"id" => $user['user_id'],
				"user" => $user['username'],
				"email" => $email
		));

		$token = JWT::encode($tokenPayload, $secret_key);
		// $token = JWT::encode($payloadArray, $apiSecretKey);
		
		$returnArray = App_Response::getResponse('200');
		$returnArray['api_key'] = $user['user_api_key'];
		$returnArray['token'] = $token;
		$returnArray['expireAt'] = $expire_claim;

        return $returnArray;
    }

    function generateUserTokenV1($varAPIKey = NULL, $email = NULL, $db) {
        // api key is required
        if (!isset($varAPIKey) || $varAPIKey === '' && !isset($email) || $email === '') {
            $responseArray = App_Response::getResponse('403');
			$res['message'] = "Missing Or Invalid Credentials";
            return $responseArray;
        }

        // $varAPIKey = 'rerewrer123';
        $query = "SELECT * FROM users WHERE (user_api_key = '" . $varAPIKey . "') AND (email = '" . $email . "');";
        $result = pg_query($db, $query);

        // if nothing comes back, then return a failure
        if (pg_num_rows($result) && $result) {
            $api_key_entry = pg_fetch_all($result);
            $res = $api_key_entry[0];
            
			// $apiSecretKey = $res['user_id'];
            // $payloadArray = array();
            // $payloadArray['apiKey'] = $varAPIKey;

			$secret_key = $res['password'];;
			$issuer_claim = "equitytable"; // this can be the servername
			$audience_claim = "equitytable_front_end";
			$issuedat_claim = time(); // issued at
			$notbefore_claim = $issuedat_claim + 10; //not before in seconds
			$expire_claim = $issuedat_claim + 86400; // expire time in seconds
			$tokenPayload = array(
				"iss" => $issuer_claim,
				"aud" => $audience_claim,
				"iat" => $issuedat_claim,
				"nbf" => $notbefore_claim,
				"exp" => $expire_claim,
				"apiKey" => $varAPIKey,
				"data" => array(
					"id" => $res['user_id'],
					"user" => $res['username'],
					"email" => $email
			));

			$token = JWT::encode($tokenPayload, $secret_key);
            // $token = JWT::encode($payloadArray, $apiSecretKey);
            
			$returnArray = App_Response::getResponse('200');
            $returnArray['token'] = $token;
			$returnArray['expireAt'] = $expire_claim;
        } else {
            $returnArray = App_Response::getResponse('403');
			$res['message'] = "Invalid User";
        }

        return $returnArray;
    }

    function validateRequest($varAPIKey = NULL, $varToken = NULL) {

		// this function requires and API key and token parameters
		if (!$varAPIKey || !$varToken) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Missing API key or token.";
			return $returnArray;
		}

		// get the api key object
		$cApp_API_Key = new App_API_Key;
		$res = $cApp_API_Key->getRecordByAPIKey($varAPIKey);
		// $res = $cApp_API_Key->getRecordByAPIKeyPostgres($apiKey, $db);
		unset($cApp_API_Key);

		// if anything looks sketchy, bail.
		if ($res['response'] !== '200') {
			return $res;
		}

		// get the client API secret key.
		$apiSecretKey = $res['dataArray'][0]['api_secret_key'];

		// decode the token
		try {
			$payload = JWT::decode($varToken, $apiSecretKey, array('HS256'));
		}
		catch(Exception $e) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " ".$e->getMessage();
			return $returnArray;
		}

		// get items out of the payload
		$apiKey = $payload->apiKey;
		if (isset($payload->exp)) {$expire = $payload->exp;} else {$expire = 0;}

		// if api keys don't match, kick'em out
		if ($apiKey !== $varAPIKey) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Invalid API Key.";
			return $returnArray;
		}

		// if token is expired, kick'em out
		$currentTime = time();
		if (($expire !== 0) && ($expire < $currentTime)) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Token has expired.";
			return $returnArray;
		}

		$returnArray = App_Response::getResponse('200');
		return $returnArray;

	}

    function validateRequestPostgres($varAPIKey = NULL, $varToken = NULL, $db) {

		// this function requires and API key and token parameters
		if (!$varAPIKey || !$varToken) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Missing API key or token.";
			return $returnArray;
		}

		// get the api key object
		$cApp_API_Key = new App_API_Key;
		$res = $cApp_API_Key->getRecordByAPIKeyPostgres($apiKey, $db);
		unset($cApp_API_Key);

		// if anything looks sketchy, bail.
		if ($res['response'] !== '200') {
			return $res;
		}

		// get the client API secret key.
		$apiSecretKey = $res['dataArray'][0]['api_secret_key'];

		// decode the token
		try {
			$payload = JWT::decode($varToken, $apiSecretKey, array('HS256'));
		}
		catch(Exception $e) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " ".$e->getMessage();
			return $returnArray;
		}

		// get items out of the payload
		$apiKey = $payload->apiKey;
		if (isset($payload->exp)) {$expire = $payload->exp;} else {$expire = 0;}

		// if api keys don't match, kick'em out
		if ($apiKey !== $varAPIKey) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Invalid API Key.";
			return $returnArray;
		}

		// if token is expired, kick'em out
		$currentTime = time();
		if (($expire !== 0) && ($expire < $currentTime)) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Token has expired.";
			return $returnArray;
		}

		$returnArray = App_Response::getResponse('200');
		return $returnArray;

	}

    function validateUserRequestV2($varToken = NULL, $user_or_email = NULL, $db) {

		// this function requires and API key and token parameters
		if ((!$varToken || $varToken = '') && (!$user_or_email || $user_or_email = '')) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Missing User or User Token.";
			return $returnArray;
		}

		// get the api key object
		$cApp_API_Key = new App_API_Key;
		$res = $cApp_API_Key->getRecordByUserPostgres($user_or_email, $db);
		unset($cApp_API_Key);

		// if anything looks sketchy, bail.
		if ($res['response'] !== '200') {
			return $res;
		}

		// get the client API secret key.
		$apiSecretKey = $res['dataArray'][0]['password'];
		$varAPIKey = $res['dataArray'][0]['user_api_key'];

		// decode the token
		try {
			$payload = JWT::decode($varToken, $apiSecretKey, array('HS256'));
		}
		catch(Exception $e) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " ".$e->getMessage();
			$returnArray['user'] = $varAPIKey;
			return $returnArray;
		}

		// get items out of the payload
		$apiKey = $payload->apiKey;
		if (isset($payload->exp)) {$expire = $payload->exp;} else {$expire = 0;}

		// if api keys don't match, kick'em out
		if ($apiKey !== $varAPIKey) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Invalid API Key.";
			return $returnArray;
		}

		// if token is expired, kick'em out
		$currentTime = time();
		if (($expire !== 0) && ($expire < $currentTime)) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Token has expired.";
			return $returnArray;
		}

		$returnArray = App_Response::getResponse('200');
		return $returnArray;

	}

    function validateUserRequestV1($varAPIKey = NULL, $varToken = NULL, $db) {
		// this function requires and API key and token parameters
		if (!$varAPIKey || !$varToken) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Missing API key or Token.";
			return $returnArray;
		}

		// get the api key object
		$cApp_API_Key = new App_API_Key;
		$res = $cApp_API_Key->getRecordsByUserAPIKey($varAPIKey, $db);
		unset($cApp_API_Key);

		// if anything looks sketchy, bail.
		if ($res['response'] !== '200') {
			return $res;
		}

		// get the client API secret key.
		$apiSecretKey = $res['dataArray'][0]['password'];

		// decode the token
		try {
			$payload = JWT::decode($varToken, $apiSecretKey, array('HS256'));
		}
		catch(Exception $e) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " ".$e->getMessage();
			return $returnArray;
		}

		// get items out of the payload
		$apiKey = $payload->apiKey;
		if (isset($payload->exp)) {$expire = $payload->exp;} else {$expire = 0;}

		// if api keys don't match, kick'em out
		if ($apiKey !== $varAPIKey) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Invalid API Key.";
			return $returnArray;
		}

		// if token is expired, kick'em out
		$currentTime = time();
		if (($expire !== 0) && ($expire < $currentTime)) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Token has expired.";
			return $returnArray;
		}

		$returnArray = App_Response::getResponse('200');
		return $returnArray;

	}

    function validateUserRequest($varAPIKey = NULL, $varToken = NULL, $email = NULL, $db) {

		// this function requires and API key and token parameters
		if (!$varAPIKey || !$varToken || !$email) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Missing API key or token.";
			return $returnArray;
		}

		// get the api key object
		$cApp_API_Key = new App_API_Key;
		$res = $cApp_API_Key->getRecordByUserAPIKeyPostgres($varAPIKey, $email, $db);
		unset($cApp_API_Key);

		// if anything looks sketchy, bail.
		if ($res['response'] !== '200') {
			return $res;
		}

		// get the client API secret key.
		$apiSecretKey = $res['dataArray'][0]['password'];

		// decode the token
		try {
			$payload = JWT::decode($varToken, $apiSecretKey, array('HS256'));
		}
		catch(Exception $e) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " ".$e->getMessage();
			return $returnArray;
		}

		// get items out of the payload
		$apiKey = $payload->apiKey;
		if (isset($payload->exp)) {$expire = $payload->exp;} else {$expire = 0;}

		// if api keys don't match, kick'em out
		if ($apiKey !== $varAPIKey) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Invalid API Key.";
			return $returnArray;
		}

		// if token is expired, kick'em out
		$currentTime = time();
		if (($expire !== 0) && ($expire < $currentTime)) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Token has expired.";
			return $returnArray;
		}

		$returnArray = App_Response::getResponse('200');
		return $returnArray;

	}

?>