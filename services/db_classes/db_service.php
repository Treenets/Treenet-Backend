<?php

function create_tree_token(
	$name, $description, $creator, $breed, $location, $image_url, $start_date, $db
) {
	$sign_up_timestamp = date('Y-m-d H:i:s', time());
	$query = "INSERT INTO trees (
		name, description, creator, breed, location, image_url, start_date, log_timestamp
	) VALUES (
		'$name', '$description', '$creator', '$breed', '$location', '$image_url', '$start_date', '$sign_up_timestamp'
	);";

	// $query = "INSERT INTO trees (
	// 	id, name, description, creator, breed, location, image_url, start_date, log_timestamp
	// ) VALUES (
	// 	DEFAULT, '" . $name . "', '" . $description . "', '" . $creator . "', '" . $breed . "', '" . 
	// 	$location . "',  '" . $image_url . "', " . $start_date . ", " . $sign_up_timestamp . 
	// ");";
	$result = pg_query($db, $query);
	$response = "Tree Created!"; 
	return $response;
}

function get_trees($db) {
	$query = "SELECT * FROM trees";
	$result = pg_query($db, $query);
	$response = "";
	if (pg_num_rows($result) > 0) {
		$response = pg_fetch_all($result);
	} else {
		$response = "No trees found.";
	}
	return $response;
}

function get_trees_by_creator($creator, $db) {
	$query = "SELECT * FROM trees WHERE creator = '$creator'";
	$result = pg_query($db, $query);
	$requests = pg_fetch_all($result);
	return $requests;
}

function get_requests_by_id($event_id, $db) {
	// Get event by event_id
	$query = "SELECT * FROM message_requests WHERE event_id = '" . $event_id . "';";
	$result = pg_query($db, $query);
	$event = pg_fetch_all($result);
	return $event;
}

function get_latest_requests($page_size, $db) {
	// Get latest events created 
	$query = "SELECT * FROM message_requests order by request_timestamp limit " . $page_size . ";";
	$result = pg_query($db, $query);
	$requests = pg_fetch_all($result);
	return $requests;
}

function delete_message_request($collection_id, $token_id, $creator_id, $db) {
	$query = "DELETE FROM message_requests WHERE collection_id = '" . $collection_id . "' AND token_id = '" . $token_id . "' AND creator_id = '" . $creator_id . "';";
	$result = pg_query($db, $query);
	$response = "Message Request Deleted!";
	return $response;
}

function create_api_request_log($request, $user_id, $response, $description, $type, $db) {
	$log_timestamp = date('Y-m-d H:i:s', time());
	$query = "INSERT INTO api_request_log
		(id, request, user_id, response, log_timestamp, description, type)
		VALUES 
		(DEFAULT, '" . $request . "','" . $user_id . "','" . $response . "','" . $log_timestamp . "', '" . $description . "', '" . $type . "')";
	$result = pg_query($db, $query);
}

function generateId() {
	return md5(uniqid(time()));
}

?>