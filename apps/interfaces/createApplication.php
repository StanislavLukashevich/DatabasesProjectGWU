<?php	
	require_once('../includes/utils.php');

	$dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	try {
		$query = "INSERT INTO application_form (userID) VALUES (" . $_SESSION["id"] . ")";
		$_SESSION["appID"] = try_insert($dbc, $query, 'Created new application for userID ' . $_SESSION["id"]);
	}
	catch (Exception $e) {
		// fetch the application with the current user ID
		$query = "SELECT applicationID FROM application_form WHERE userID = " . $_SESSION["id"];
		$data = try_query($dbc, $query, NULL);
		$row = mysqli_fetch_array($data);
		$_SESSION["appID"] = $row["applicationID"];
		echo 'Set application ID by query to: ' . $_SESSION["appID"];
	}
?>