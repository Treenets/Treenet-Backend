<?php

require('vendor/autoload.php');

header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');
header('Content-Type: application/json');

// the following constant will help ensure all other PHP files will only work as part of this API.
if (!defined('CONST_INCLUDE_KEY')) {define('CONST_INCLUDE_KEY', 'd4e2ad09-b1c3-4d70-9a9a-0e6149302486');}

// run the class autoloader
require_once ('./services/app_autoloader.php');
require_once ('./config/conn.php');
require_once ('./services/request_handler.php');
require_once ('./services/db_classes/db_service.php');

//--------------------------------------------------------------------------------------------------------------------
// if this API must be used with a GET, POST, PUT, DELETE or OPTIONS request
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestPayload = json_decode(file_get_contents('php://input'), true);

// retrieve the inbound parameters based on request type.
if (in_array($requestMethod, ["GET", "POST", "PUT", "DELETE", "OPTIONS"])) {
	// Move the request array into a new variable and then unset the apiFunctionName 
	// so that we don't accidentally snag included interfaces after this.
	$requestMethodArray = array();
	$requestMethodArray = $_REQUEST;
	
	if (isset($requestPayload))								{$requestMethodArray['requestPayload'] = $requestPayload;}
	if (isset($requestMethodArray['apiKey']))				{$apiKey = $requestMethodArray['apiKey'];}
	if (isset($requestMethodArray['api_key']))				{$api_key = $requestMethodArray['api_key'];}
	if (isset($requestMethodArray['token']))				{$token = $requestMethodArray['token'];}
	if (isset($requestMethodArray['apiToken']))				{$apiToken = $requestMethodArray['apiToken'];}
	if (isset($requestMethodArray['function']))				{$functionName = $requestMethodArray['function'];}
	if (isset($requestMethodArray['functionParams']))		{$functionParams = $requestMethodArray['functionParams'];}

	// decode the function parameters array.
	if (isset($functionParams) && $functionParams != '') {
		$functionParams = json_decode($functionParams, true);
	}

	// instantiate this class and validate the API request
	$cApiHandler = new API_Handler();
	$res = App_Response::getResponse('200');
	// Requests should always include the API Key and JSON Web Token
	// Validate Users
	if (isset($api_key) && isset($token)) {
		// $res = validateUserRequestV1($api_key, $token, $db);
		if ($res['response'] !== '200') {
			// if request is not valid, then raise an error message.
			$res = json_encode($res);
		} else {
			$data = $requestMethodArray;
			if ($requestMethod == 'GET') {
				$res = get($data, $res, $db);
			} else if ($requestMethod == 'POST') {
				$res = post($data, $res, $db);
			} else if ($requestMethod == 'PUT') {
				$res = put($data, $res, $db);
			} else if ($requestMethod == 'DELETE') {
				$res = delete($data, $res, $db);
			}
			// encode and return
			$res = json_encode($res, JSON_PRETTY_PRINT);
		}
		echo($res);
	} else {
		$res= App_Response::getResponse('403');
		$res['responseDescription'] .= " Missing API key or token.";
		$res = json_encode($res);
		echo($res);
	}

	if (isset($cApiHandler)) {unset($cApiHandler);}

} else {
	$returnArray = App_Response::getResponse('405');
	echo(json_encode($returnArray));
}

function get($data, $res, $db) {
	$res["Request Method"] = "Get";
	if (isset($data['creator'])) {
		$code = 200;
		$creator = $data['creator'];
		$response = get_trees_by_creator($creator, $db);
		if ($response != 'No trees found.') {
			$res = App_Response::getResponse($code);
			$res['responseData'] = $response;
		} else {
			$res = App_Response::getResponse('404');
			$res['responseDescription'] = 'No trees found.';
		}
	} else {
		$code = 200;
		$response = get_trees($db);
		$res = App_Response::getResponse($code);
		$res['responseData'] = $response;
	}
	return $res;
}

function post($data, $res, $db) {
	$res["Request Method"] = "Post";
	$payload = $data['requestPayload'];
	if (isset($payload['user1']) && isset($payload['user2']) && isset($payload['channel_id']) && isset($payload['message'])) {
		$code = 200;
        $user1 = $payload['user1'];
        $user2 = $payload['user2'];
		$channel_id = $payload['channel_id'];
		$message = $payload['message'] == "" ? 'You have been added to this chat by ' . $user1 . '.' : $payload['message'];
		
		// $serverClient = new GetStream\StreamChat\Client("yu2kq86xntwk", "7reu6kbmng6ffx6rvdb98hqjvkcg7xvsp3q75xwe8nx33xjcup2mjtue6ufz4sct");
		
		$serverClient = new GetStream\StreamChat\Client("uvp4wwg3xz7m", "x23rhudce9ghwy9a6n2su3e7khq7ctukbaxpgyysjvhgey6sw3pqx9pac3sgw6u4");
		$channel = $serverClient->Channel("messaging", null, ["members" => [$user1, $user2]]);
		$result = $channel->create($channel_id);		
		$message = $channel->sendMessage(['text' => $message,],$user1);
		
		if ($result) {
			$res['response'] = '200';
			$res['responseDescription'] = $result;
		} else {
			$res = App_Response::getResponse('405');
			$res['responseDescription'] = 'Failed to add user to channel.';
		}
	} else if (isset($payload['name']) && isset($payload['channel_id'])) {
		$name = $payload['name'];
		$channel_id = $payload['channel_id'];
		
		// $serverClient = new GetStream\StreamChat\Client("yu2kq86xntwk", "7reu6kbmng6ffx6rvdb98hqjvkcg7xvsp3q75xwe8nx33xjcup2mjtue6ufz4sct");
		
		$serverClient = new GetStream\StreamChat\Client("uvp4wwg3xz7m", "x23rhudce9ghwy9a6n2su3e7khq7ctukbaxpgyysjvhgey6sw3pqx9pac3sgw6u4");
		$channel = $serverClient->Channel("messaging", $channel_id);
		$result = $channel->addMembers([$name], ["hide_history" => true]);

		if ($result) {
			$res['response'] = '200';
			$res['responseDescription'] = $result;
		} else {
			$res = App_Response::getResponse('405');
			$res['responseDescription'] = 'Failed to add user to channel.';
		}
	}
	return $res;
}

function put($data, $res, $db) {
	$res["Request Method"] = "Put";
	$data = $data['requestPayload'];
    if (
		isset($data['name']) && isset($data['description']) && isset($data['creator']) && isset($data['breed']) &&
		isset($data['location']) && isset($data['image_url']) && isset($data['start_date'])
    ) {
		$code = 200;
		$request = implode(" | ", $data);

		$name = $data['name'];
		$description = $data['description'];
		$creator = $data['creator'];
		$breed = $data['breed'];
		$location = $data['location'];
		$image_url = $data['image_url'];
		$start_date = $data['start_date'];

		$response = create_tree_token($name, $description, $creator, $breed, $location, $image_url, $start_date, $db);

		if($response == 'Tree Created!') {
			$res = App_Response::getResponse($code);
			$res['message'] = 'Tree Token Created!';
			$res['request'] = $request;
		} else {
			$res = App_Response::getResponse('405');
			$res['message'] = $response['responseDescription'];
			$res['request'] = $request;
		}
	} else {
		$res = App_Response::getResponse('405');
	}
	return $res;
}

function delete($data, $res, $db) {
	$res["Request Method"] = "Delete";
	$data = $data['requestPayload'];
    if (
		isset($data['collection_id']) && 
		isset($data['token_id']) && 
		isset($data['creator_id'])
	) {
		$code = 200;
		$request = implode(" | ", $data);
		$collection_id = $data['collection_id'];
		$token_id = $data['token_id'];
		$creator_id = $data['creator_id'];

		$response = delete_message_request($collection_id, $token_id, $creator_id, $db);
		if($response == 'Message Request Deleted!') {
			$res = App_Response::getResponse($code);
			$res['message'] = 'Message Request Deleted!';
			$res['request'] = $request;
		} else {
			$res = App_Response::getResponse('405');
			$res['message'] = $response['responseDescription'];
			$res['request'] = $request;
		}	
	} else {	
		$res = App_Response::getResponse('405');
	}	
	return $res;
}